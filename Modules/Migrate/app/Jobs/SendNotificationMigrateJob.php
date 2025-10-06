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

        $payload = $this->buildWebPush($row);

        // Mark as queued early
        DB::table('notifications')->where('id', $this->notificationId)->update(['status' => 'queued']);

        $activeCount  = 0;
        $batchCount   = 0;

        // Global batch index to schedule delays in order across all key groups
        $globalBatchIndex = 0;

        DB::table('migrate_subs')
            ->select('id','endpoint','auth','p256dh','public_key','private_key')
            ->where('domain_id', $row->domain_id)
            ->where('status', 1)
            ->orderBy('id')
            ->chunkById(2000, function ($chunk) use ($row, $payload, $batchSize, $timeGapSec, &$activeCount, &$batchCount, &$globalBatchIndex) {
                $buckets = [];

                foreach ($chunk as $s) {
                    if (empty($s->endpoint) || empty($s->auth) || empty($s->p256dh)) {
                        continue;
                    }
                    $key = ($s->public_key ?? '') . '|' . ($s->private_key ?? '');
                    $buckets[$key] ??= [
                        'public_key'  => (string) $s->public_key,
                        'private_key' => (string) $s->private_key,
                        'subs'        => [],
                    ];
                    $buckets[$key]['subs'][] = [
                        'endpoint' => (string) $s->endpoint,
                        'auth'     => (string) $s->auth,
                        'p256dh'   => (string) $s->p256dh,
                    ];
                    $activeCount++;
                }

                foreach ($buckets as $bucket) {
                    $subs       = $bucket['subs'];
                    $publicKey  = $bucket['public_key'];
                    $privateKey = $bucket['private_key'];

                    if (!$publicKey || !$privateKey) {
                        continue;
                    }

                    // Respect configured batch size but never exceed 500
                    $chunks = array_chunk($subs, $batchSize);

                    foreach ($chunks as $block) {
                        $batchCount++;

                        // Schedule per-batch delay using gap (sec)
                        $delaySeconds = $timeGapSec > 0 ? $timeGapSec * $globalBatchIndex : 0;
                        $globalBatchIndex++;

                        SendMigrateNotificationByNode::dispatch(
                            notificationId: $this->notificationId,
                            vapidPublicKey: $publicKey,
                            vapidPrivateKey: $privateKey,
                            subscribers: $block,
                            payload: $payload,
                            domainName: $row->domain_name
                        )
                        // ->onQueue('migrate-notifications')
                        ->delay(now()->addSeconds($delaySeconds));
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

    // protected function buildWebPush(object $row): array
    // {
    //     // Define the base data for the push notification
    //     $base = [
    //         'title' => $row->title ?? '',
    //         'body' => $row->description ?? '',
    //         'icon' => $row->banner_icon ?? '',
    //         'image' => $row->banner_image ?? '',
    //         'click_action' => $row->target_url ?? '',
    //         'message_id' => (string)$row->message_id,
    //     ];

    //     // Define actions for buttons
    //     $actions = [];
    //     if (!empty($row->btn_1_title) && !empty($row->btn_1_url)) {
    //         $actions[] = [
    //             'action' => 'btn1', 
    //             'title' => $row->btn_1_title, 
    //             'url' => $row->btn_1_url
    //         ];
    //     }
    //     if (!empty($row->btn_title_2) && !empty($row->btn_url_2)) {
    //         $actions[] = [
    //             'action' => 'btn2', 
    //             'title' => $row->btn_title_2, 
    //             'url' => $row->btn_url_2
    //         ];
    //     }
    //     if (count($actions) < 2) {
    //         $actions[] = ['action' => 'close', 'title' => 'Close'];
    //     }

    //     return [
    //         'data' => array_merge($base, ['actions' => json_encode($actions)]),
    //         'headers' => ['Urgency' => 'high'],
    //     ];
    // }

    // protected function buildWebPush(object $row): array
    // {
    //     $base = [
    //         'title'        => $row->title,
    //         'body'         => $row->description,
    //         'icon'         => $row->banner_icon ?? '',
    //         'image'        => $row->banner_image ?? '',
    //         'click_action' => $row->target_url,
    //         'message_id'   => (string)$row->message_id,
    //     ];

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

    //     return [
    //         'data'    => array_merge(array_map('strval', $base), ['actions' => json_encode($actions)]),
    //         'headers' => ['Urgency' => 'high'],
    //     ];
    // }

    // APLU.IO
    protected function buildWebPushApluPush(object $row): array
    {
        // Notification data for the browser to display
        $notification = [
            'title'  => $row->title,
            'body'   => $row->description,
            'icon'   => $row->banner_icon ?? '',
            'image'  => $row->banner_image ?? '',
        ];

        // Define actions for buttons as a plain array
        $actions = [];
        if ($row->btn_1_title && $row->btn_1_url) {
            $actions[] = ['action' => 'btn1', 'title' => $row->btn_1_title, 'url' => $row->btn_1_url];
        }
        if ($row->btn_title_2 && $row->btn_url_2) {
            $actions[] = ['action' => 'btn2', 'title' => $row->btn_title_2, 'url' => $row->btn_url_2];
        }
        if (count($actions) < 2) {
            $actions[] = ['action' => 'close', 'title' => 'Close'];
        }
        $notification['actions'] = $actions;

        // Custom data for the service worker to process
        $data = [
            'click_action' => $row->target_url,
            'message_id'   => (string)$row->message_id,
            'source' => 'webpush',
        ];

        return [
            'notification' => $notification,
            'data'         => $data,
            'headers'      => ['Urgency' => 'high'], 
        ];
    }

    // LARAPUSH
    // protected function buildWebPushLaraPush(object $row): array
    // {
    //     // Build the object the SW will JSON.parse() from data.notification
    //     $notif = [
    //         'title'              => (string) ($row->title ?? ''),
    //         'body'               => (string) ($row->description ?? ''),
    //         'icon'               => (string) ($row->banner_icon ?? ''),
    //         'image'              => (string) ($row->banner_image ?? ''),
    //         // clicked URL + ping URL expected by your SW
    //         'url'                => (string) ($row->target_url ?? ''),
    //         'api_url'            => (string) (url('/api/notification/click?message_id=' . $row->message_id)), // adjust to your tracking endpoint
    //         // let SW decide final requireInteraction per OS
    //         'requireInteraction' => false,
    //         // actions AS A MAP (the SW reads actions[event.action])
    //         'actions' => array_filter([
    //             'btn1' => ($row->btn_1_title && $row->btn_1_url) ? [
    //                 'title'        => (string) $row->btn_1_title,
    //                 'click_action' => (string) $row->btn_1_url,
    //                 'api_url'      => (string) (url('/api/notification/action?btn=1&message_id=' . $row->message_id)),
    //             ] : null,
    //             'btn2' => ($row->btn_title_2 && $row->btn_url_2) ? [
    //                 'title'        => (string) $row->btn_title_2,
    //                 'click_action' => (string) $row->btn_url_2,
    //                 'api_url'      => (string) (url('/api/notification/action?btn=2&message_id=' . $row->message_id)),
    //             ] : null,
    //         ]),
    //         // include anything else you want available in event.notification.data
    //         'message_id' => (string) $row->message_id,
    //         'swVersion'  => '3.0.9',
    //     ];
    //     // IMPORTANT: FCM "data" values must be strings → stringify the notification object.
    //     $data = [
    //         'notification' => json_encode($notif, JSON_UNESCAPED_SLASHES),
    //         'swVersion'    => '3.0.9',
    //     ];
    //     return [
    //         'data'    => $data,
    //         'headers' => ['Urgency' => 'high'],
    //     ];
    // }
}