<?php

namespace App\Jobs;

use Throwable;
use App\Models\Notification;
use App\Models\PushSubscriptionHead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected int $notificationId;
    public int $timeout = 3600;
    public int $tries   = 3;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        $notification = Notification::with('domains')->findOrFail($this->notificationId);
        $domainNames  = $notification->domains->pluck('name')->toArray();

        $message = [
            'notification' => [
                'title' => $notification->title,
                'body'  => $notification->description,
                'icon'  => $notification->banner_icon,
                'image' => $notification->banner_image,
            ],
            'data' => [
                'click_action'  => $notification->target_url,
                'campaign_name' => $notification->campaign_name,
            ],
        ];

        $serverKey = config('services.fcm.server_key');
        if (!$serverKey) {
            throw new \Exception('FCM server key not configured');
        }

        $init  = $succ = $fail = 0;
        $nid   = $this->notificationId;

        PushSubscriptionHead::whereIn('domain', $domainNames)
            ->where('status', 1)
            ->whereNotExists(function($q) use ($nid) {
                $q->select(DB::raw(1))
                  ->from('notification_sends')
                  ->whereColumn('notification_sends.subscription_head_id', 'push_subscriptions_head.id')
                  ->where('notification_sends.notification_id', $nid);
            })
            ->select(['id','token'])
            ->chunkById(500, function($subs) use ($message, $serverKey, &$init, &$succ, &$fail, $nid) {
                $ids    = $subs->pluck('id')->all();
                $tokens = $subs->pluck('token')->all();
                $init  += count($tokens);
                if (empty($tokens)) {
                    return;
                }

                $resp = Http::withHeaders([
                        'Authorization' => 'key=' . $serverKey,
                        'Content-Type'  => 'application/json',
                    ])
                    ->timeout(60)
                    ->post('https://fcm.googleapis.com/fcm/send', array_merge(
                        $message,
                        ['registration_ids' => $tokens]
                    ));

                $results = $resp->json('results', []);

                $rows      = [];
                $failedIds = [];
                foreach ($results as $i => $r) {
                    $ok = !isset($r['error']);
                    $rows[] = [
                        'notification_id'      => $nid,
                        'subscription_head_id' => $ids[$i] ?? null,
                        'status'               => $ok,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                    if ($ok) {
                        $succ++;
                    } else {
                        $fail++;
                        $failedIds[] = $ids[$i];
                    }
                }

                DB::table('notification_sends')->insertOrIgnore($rows);

                if (!empty($failedIds)) {
                    PushSubscriptionHead::whereIn('id', $failedIds)
                                        ->update(['status' => 0]);
                }
            });

        $notification->update([
            'active_count'   => $init,
            'success_count'  => $succ,
            'failed_count'   => $fail,
            'inactive_count' => PushSubscriptionHead::where('status', 0)->count(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendNotificationJob failed', [
            'notification_id' => $this->notificationId,
            'error'           => $exception->getMessage(),
        ]);
    }
}
