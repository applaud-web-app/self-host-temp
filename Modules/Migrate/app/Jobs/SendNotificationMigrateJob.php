<?php

namespace Modules\Migrate\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;
use Modules\Migrate\Jobs\SendMigrateNotificationByNode;

class SendNotificationMigrateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;

    protected int $notificationId = 0;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        if ($this->notificationId <= 0) {
            Log::error('SendNotificationMigrateJob: notificationId missing/invalid.');
            return;
        }

        // 0) Load settings from cache (same pattern you used)
        $settings = Cache::remember('settings_batch_size', 3600, function () {
            // default gap_size: 500, time_gap: 1000ms
            return Setting::firstOrCreate(['id' => 1], [
                'gap_size'      => 500,
                'time_gap'      => 1000,     // milliseconds
                'daily_cleanup' => false,
                'sending_speed' => 'fast',
            ]);
        });

        // Hard ceiling of 500 per your spec; never go above 500 per Node call
        $configuredBatch = (int) ($settings->gap_size ?? 500);
        $batchSize       = max(1, min(500, $configuredBatch));   // <= 500
        $timeGapMs       = max(0, (int) ($settings->time_gap ?? 0));
        $timeGapSec      = (int) ceil($timeGapMs / 1000);

        // 1) Load the notification + its domain name
        $row = DB::table('notifications as n')
            ->join('domains as d', 'n.domain_id', '=', 'd.id')
            ->where('n.id', $this->notificationId)
            ->whereIn('n.status', ['pending','queued'])
            ->first([
                'n.id','n.domain_id','n.title','n.description','n.banner_icon','n.banner_image',
                'n.target_url','n.message_id','n.btn_1_title','n.btn_1_url',
                'n.btn_title_2','n.btn_url_2','n.status',
                'd.name as domain_name',
            ]);

        if (!$row) {
            Log::warning("SendNotificationMigrateJob: notification {$this->notificationId} missing or already processed.");
            return;
        }

        // $payload = $this->buildWebPush($row);
        $subscriberCount = DB::table('migrate_subs')
            ->where('domain_id', $row->domain_id)
            ->where('migration_status', 'pending')
            ->where('status', 1)
            ->count();

        if ($subscriberCount == 0) {
            // If no subscribers, mark the notification as "sent" and exit
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update([
                    'status' => 'sent', // Mark as sent
                    'sent_at' => now(), // Set sent timestamp
                ]);
            Log::info("Notification {$this->notificationId} marked as sent because no subscribers were found.");
            return; // Exit early, don't process further
        }

        // Mark as queued early
        DB::table('notifications')->where('id', $this->notificationId)->update(['status' => 'queued']);

        $activeCount  = 0;
        $batchCount   = 0;

        // Global batch index to schedule delays in order across all key groups
        $globalBatchIndex = 0;

        DB::table('migrate_subs')
            ->select('id','endpoint','auth','p256dh','public_key','private_key','migrate_from')
            ->where('domain_id', $row->domain_id)
            ->where('migration_status','pending')
            ->where('status', 1)
            ->orderBy('migrate_from')
            ->orderBy('id')
            ->chunkById(2000, function ($chunk) use ($row, $batchSize, $timeGapSec, &$activeCount, &$batchCount, &$globalBatchIndex) {

            // 1) Bucket by source first, then by VAPID keypair
            $sourceBuckets = []; // [source => [ "{pub}|{priv}" => ['pub'=>..,'priv'=>..,'subs'=>[]] ]]

            foreach ($chunk as $s) {
                if (empty($s->endpoint) || empty($s->auth) || empty($s->p256dh)) {
                    continue;
                }

                $source = $s->migrate_from ?? '';
                $pub    = (string) ($s->public_key ?? '');
                $priv   = (string) ($s->private_key ?? '');
                if ($pub === '' || $priv === '') {
                    continue; // can’t send without a VAPID pair
                }

                $sourceBuckets[$source] ??= [];
                $pairKey = "{$pub}|{$priv}";
                if (!isset($sourceBuckets[$source][$pairKey])) {
                    $sourceBuckets[$source][$pairKey] = [
                        'public_key'  => $pub,
                        'private_key' => $priv,
                        'subs'        => [],
                    ];
                }

                $sourceBuckets[$source][$pairKey]['subs'][] = [
                    'endpoint' => (string) $s->endpoint,
                    'auth'     => (string) $s->auth,
                    'p256dh'   => (string) $s->p256dh,
                ];

                $activeCount++;
            }

            // 2) Dispatch in the order of sources: aplu → lara_push → default
            foreach (['aplu','lara_push','default','feedify','izooto'] as $srcOrder) {
                if (empty($sourceBuckets[$srcOrder])) {
                    continue;
                }

                // build payload once per source
                switch ($srcOrder) {
                    case 'aplu':
                        $payload = $this->buildWebPushApluPush($row);
                        break;
                    case 'lara_push':
                        $payload = $this->buildWebPushLaraPush($row);
                        break;
                    case 'feedify':
                        $payload = $this->buildWebPushFeedify($row);
                        break;
                    case 'izooto':
                        $payload = $this->buildWebPushIzooto($row);
                        break;
                    default:
                        $payload = $this->buildWebPush($row);
                        break;
                }

                foreach ($sourceBuckets[$srcOrder] as $pair) {
                    $subs = $pair['subs'];

                    // respect batch size cap (<= 500)
                    $blocks = array_chunk($subs, $batchSize);
                    foreach ($blocks as $block) {
                        $batchCount++;

                        // schedule gap (sec) across ALL batches
                        $delaySeconds = $timeGapSec > 0 ? $timeGapSec * $globalBatchIndex : 0;
                        $globalBatchIndex++;

                        SendMigrateNotificationByNode::dispatch(
                            notificationId: $this->notificationId,
                            vapidPublicKey: $pair['public_key'],
                            vapidPrivateKey: $pair['private_key'],
                            subscribers: $block,
                            payload: $payload,
                            domainName: $row->domain_name
                        )
                        // ->onQueue('migrate-notifications')
                        ->delay(now()->addSeconds($delaySeconds));
                    }
                }
            }
        });

        // Save attempted recipients
        DB::table('notifications')
            ->where('id', $this->notificationId)
            ->update([
                'active_count' => $activeCount,
            ]);

        Log::info("Notification {$this->notificationId}: queued {$batchCount} batches (size={$batchSize}, gap={$timeGapSec}s) for {$activeCount} recipients.");
    }

    // DEFAULT
    protected function buildWebPush(object $row): array
    {
        // Define the base data for the push notification
        $base = [
            'title' => $row->title ?? '',
            'body' => $row->description ?? '',
            'icon' => $row->banner_icon ?? '',
            'image' => $row->banner_image ?? '',
            'click_action' => $row->target_url ?? '',
            'message_id' => (string)$row->message_id,
        ];

        // Define actions for buttons
        $actions = [];
        if (!empty($row->btn_1_title) && !empty($row->btn_1_url)) {
            $actions[] = [
                'action' => 'btn1', 
                'title' => $row->btn_1_title, 
                'url' => $row->btn_1_url
            ];
        }
        if (!empty($row->btn_title_2) && !empty($row->btn_url_2)) {
            $actions[] = [
                'action' => 'btn2', 
                'title' => $row->btn_title_2, 
                'url' => $row->btn_url_2
            ];
        }
        if (count($actions) < 2) {
            $actions[] = ['action' => 'close', 'title' => 'Close'];
        }

        return [
            'data' => array_merge($base, ['actions' => json_encode($actions)]),
            'headers' => ['Urgency' => 'high'],
        ];
    }

    // APLU
    // protected function buildWebPushApluPush(object $row): array
    // {
    //     // Notification data for the browser to display
    //     $notification = [
    //         'title'  => $row->title,
    //         'body'   => $row->description,
    //         'icon'   => $row->banner_icon ?? '',
    //         'image'  => $row->banner_image ?? '',
    //     ];

    //     // Define actions for buttons as a plain array
    //     $actions = [];
    //     if ($row->btn_1_title && $row->btn_1_url) {
    //         $actions[] = ['action' => 'btn1', 'title' => $row->btn_1_title, 'url' => $row->btn_1_url];
    //     }
    //     if ($row->btn_title_2 && $row->btn_url_2) {
    //         $actions[] = ['action' => 'btn2', 'title' => $row->btn_title_2, 'url' => $row->btn_url_2];
    //     }
    //     if (count($actions) < 2) {
    //         $actions[] = ['action' => 'close', 'title' => 'Close'];
    //     }
    //     $notification['actions'] = $actions;

    //     // Custom data for the service worker to process
    //     $data = [
    //         'click_action' => $row->target_url,
    //         'message_id'   => (string)$row->message_id,
    //         'source' => 'webpush',
    //     ];

    //     return [
    //         'notification' => $notification,
    //         'data'         => $data,
    //         'headers'      => ['Urgency' => 'high'], 
    //     ];
    // }

    // APLU
    protected function buildWebPushApluPush(object $row): array
    {
        // Prepare URLs array
        $urls = [$row->target_url ?? ''];
        
        // Define actions for buttons
        $actions = [];
        
        if ($row->btn_1_title && $row->btn_1_url) {
            $actions[] = [
                'action' => 'open_url',
                'title'  => $row->btn_1_title,
            ];
            // Use button 1 URL as primary
            $urls[0] = $row->btn_1_url;
        }
        
        if ($row->btn_title_2 && $row->btn_url_2) {
            $actions[] = [
                'action' => 'open_url_2',
                'title'  => $row->btn_title_2,
            ];
            // Add second URL
            $urls[1] = $row->btn_url_2;
        }
        
        // Add close button if less than 2 actions
        if (count($actions) < 2) {
            $actions[] = [
                'action' => 'close',
                'title'  => 'Close'
            ];
        }

        // Notification data matching your SW's expected structure
        $notification = [
            'title'  => $row->title ?? '',
            'body'   => $row->description ?? '',
            'icon'   => $row->banner_icon ?? '',
            'image'  => $row->banner_image ?? '',
            'actions' => $actions,
        ];

        // Build the FCM_MSG structure that your SW expects
        // Your SW accesses: event.notification.data.FCM_MSG.notification.data.url
        $data = [
            'FCM_MSG' => json_encode([
                'notification' => [
                    'title' => $row->title ?? '',
                    'body'  => $row->description ?? '',
                    'icon'  => $row->banner_icon ?? '',
                    'image' => $row->banner_image ?? '',
                    'data'  => [
                        'url' => count($urls) === 1 ? $urls[0] : $urls,
                        'notification_id' => (string)$row->message_id,
                    ]
                ]
            ], JSON_UNESCAPED_SLASHES),
        ];

        return [
            'notification' => $notification,
            'data'         => $data,
            'headers'      => ['Urgency' => 'high'],
        ];
    }

    // LARAPUSH
    protected function buildWebPushLaraPush(object $row): array
    {
        // Build the object the SW will JSON.parse() from data.notification
        $notif = [
            'title'              => (string) ($row->title ?? ''),
            'body'               => (string) ($row->description ?? ''),
            'icon'               => (string) ($row->banner_icon ?? ''),
            'image'              => (string) ($row->banner_image ?? ''),
            // clicked URL + ping URL expected by your SW
            'url'                => (string) ($row->target_url ?? ''),
            'api_url'            => (string) (url('/api/notification/click?message_id=' . $row->message_id)), // adjust to your tracking endpoint
            // let SW decide final requireInteraction per OS
            'requireInteraction' => false,
            // actions AS A MAP (the SW reads actions[event.action])
            'actions' => array_filter([
                'btn1' => ($row->btn_1_title && $row->btn_1_url) ? [
                    'title'        => (string) $row->btn_1_title,
                    'click_action' => (string) $row->btn_1_url,
                    'api_url'      => (string) (url('/api/notification/action?btn=1&message_id=' . $row->message_id)),
                ] : null,
                'btn2' => ($row->btn_title_2 && $row->btn_url_2) ? [
                    'title'        => (string) $row->btn_title_2,
                    'click_action' => (string) $row->btn_url_2,
                    'api_url'      => (string) (url('/api/notification/action?btn=2&message_id=' . $row->message_id)),
                ] : null,
            ]),
            // include anything else you want available in event.notification.data
            'message_id' => (string) $row->message_id,
            'swVersion'  => '3.0.9',
        ];
        // IMPORTANT: FCM "data" values must be strings → stringify the notification object.
        $data = [
            'notification' => json_encode($notif, JSON_UNESCAPED_SLASHES),
            'swVersion'    => '3.0.9',
        ];
        return [
            'data'    => $data,
            'headers' => ['Urgency' => 'high'],
        ];
    }

    // FEEDIFY
    protected function buildWebPushFeedify(object $row): array
    {
        $notif = [
            'title'              => (string) ($row->title ?? ''),
            'body'               => (string) ($row->description ?? ''),
            'icon'               => (string) ($row->banner_icon ?? ''),
            'image'              => (string) ($row->banner_image ?? ''),
            'url'                => (string) ($row->target_url ?? ''),
            'api_url'            => (string) (url('/api/notification/click?message_id=' . $row->message_id)),
            'requireInteraction' => false,
            'actions'            => array_filter([
                'btn1' => ($row->btn_1_title && $row->btn_1_url) ? [
                    'title'        => (string) $row->btn_1_title,
                    'click_action' => (string) $row->btn_1_url,
                    'api_url'      => (string) (url('/api/notification/action?btn=1&message_id=' . $row->message_id)),
                ] : null,
                'btn2' => ($row->btn_title_2 && $row->btn_url_2) ? [
                    'title'        => (string) $row->btn_title_2,
                    'click_action' => (string) $row->btn_url_2,
                    'api_url'      => (string) (url('/api/notification/action?btn=2&message_id=' . $row->message_id)),
                ] : null,
            ]),
            'message_id' => (string) $row->message_id,
            'swVersion'  => '3.0.9',
        ];

        $data = [
            'notification' => json_encode($notif, JSON_UNESCAPED_SLASHES),
            'swVersion'    => '3.0.9',
        ];

        return [
            'data'    => $data,
            'headers' => ['Urgency' => 'high'],
        ];
    }

    // IZOOTO
    protected function buildWebPushIzooto(object $row): array
    {
        $notif = [
            'title'              => (string) ($row->title ?? ''),
            'message'            => (string) ($row->description ?? ''),
            'icon'               => (string) ($row->banner_icon ?? ''),
            'banner'             => (string) ($row->banner_image ?? ''),
            'link'               => (string) ($row->target_url ?? ''),
            'tag'                => (string) ($row->message_id ?? ''),  // Use message_id as tag for uniqueness
            'requireInteraction' => false,  // You can change this if you want the notification to stay until interacted
            'actions'            => [
                [
                    'action'       => 'action1',
                    'title'        => (string) ($row->btn_1_title ?? 'Action 1'),  // Button 1 title
                    'url'          => (string) ($row->btn_1_url ?? '#'),  // Button 1 link
                    'icon'         => (string) ($row->btn_1_icon ?? ''),  // Optional Button 1 icon
                ],
                [
                    'action'       => 'action2',
                    'title'        => (string) ($row->btn_2_title ?? 'Action 2'),  // Button 2 title
                    'url'          => (string) ($row->btn_2_url ?? '#'),  // Button 2 link
                    'icon'         => (string) ($row->btn_2_icon ?? ''),  // Optional Button 2 icon
                ]
            ],
            'message_id'         => (string) ($row->message_id ?? ''),
            'swVersion'          => '3.0.9',  // Can be set dynamically if needed
        ];

        // Additional Data (optional, you can add any extra attributes needed)
        $data = [
            'notification' => json_encode($notif, JSON_UNESCAPED_SLASHES),
            'swVersion'    => '3.0.9',
            'priority'     => 'high',  // Notification priority (could be 'high' or 'normal')
            'urgency'      => 'high',  // Can be set based on your needs
        ];

        return [
            'data'    => $data,
            'headers' => [
                'Urgency' => 'high',  // Setting header for urgency if needed
            ]
        ];
    }

}