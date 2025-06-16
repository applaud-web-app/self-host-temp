<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Throwable;

class SendNotificationDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 1;
    public int $timeout = 7200;

    public function __construct(
        protected int     $notificationId,
        protected Factory $factory,
        protected array   $webPush,
        protected int     $domainId,
        protected string  $domainName
    ) {}

    public function handle(): void
    {
        try {
            $messaging = $this->factory->createMessaging();
            $config    = WebPushConfig::fromArray($this->webPush);
            $message   = CloudMessage::new()->withWebPushConfig($config);
            $now       = now();

            $success = 0;
            $failed  = 0;

            // 1) Chunk subscribers for this domain with only needed columns
            PushSubscriptionHead::where('status', 1)
                ->where('domain', $this->domainName)
                ->select('id', 'token')
                ->orderBy('id')
                ->chunkById(500, function ($subs) use (
                    $messaging,
                    $message,
                    &$success,
                    &$failed,
                    $now
                ) {
                    try {
                        // numeric index mapping
                        $list = $subs->values()->all();
                        $tokens = array_column($list, 'token');

                        if (empty($tokens)) {
                            return;
                        }

                        $report = $messaging->sendMulticast($message, $tokens);

                        $s = $report->successes()->count();
                        $f = $report->failures()->count();
                        $success += $s;
                        $failed  += $f;

                        // deactivate failed subscriptions
                        $failIndexes = array_keys($report->failures()->getItems());
                        if (!empty($failIndexes)) {
                            $badIds = [];
                            foreach ($failIndexes as $i) {
                                $badIds[] = $list[$i]->id;
                            }
                            DB::table('push_subscription_heads')
                                ->whereIn('id', $badIds)
                                ->update(['status' => 0]);
                        }

                        // record delivery results in bulk
                        $rows = [];
                        foreach ($list as $idx => $sub) {
                            $rows[] = [
                                'notification_id'      => $this->notificationId,
                                'subscription_head_id' => $sub->id,
                                'status'               => in_array($idx, $failIndexes) ? 0 : 1,
                                'created_at'           => $now,
                                'updated_at'           => $now,
                            ];
                        }
                        foreach (array_chunk($rows, 500) as $chunk) {
                            DB::table('notification_sends')->insertOrIgnore($chunk);
                        }

                    } catch (Throwable $e) {
                        Log::error("Chunk failed for domain {$this->domainName} [notif={$this->notificationId}]: {$e->getMessage()}");
                    }
                });

            // 2) Update notification counters atomically
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update([
                    'active_count'  => DB::raw("active_count + " . ($success + $failed)),
                    'success_count' => DB::raw("success_count + {$success}"),
                    'failed_count'  => DB::raw("failed_count + {$failed}"),
                ]);

            // 3) Mark domain pivot once
            DB::table('domain_notification')
                ->where('notification_id', $this->notificationId)
                ->where('domain_id', $this->domainId)
                ->update([
                    'status'  => $success > 0 ? 'sent' : 'failed',
                    'sent_at' => $now,
                ]);

        } catch (Throwable $e) {
            Log::error("SendNotificationDomainJob failed [notif={$this->notificationId}, domain={$this->domainId}]: {$e->getMessage()}");
            // mark domain as failed
            DB::table('domain_notification')
            ->where('notification_id', $this->notificationId)
            ->where('domain_id', $this->domainId)
            ->update(['status' => 'failed', 'sent_at' => now()]);
        }
    }
}