<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\PushSubscriptionHead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Firebase\Factory;
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

            $success = $failed = 0;

            // chunk only id+token for this domain
            PushSubscriptionHead::where('status',1)
                ->where('domain',$this->domainName)
                ->select('id','token')
                ->orderBy('id')
                ->chunkById(500, function($subs) use (
                    $messaging, $message, &$success, &$failed, $now
                ) {
                    $list   = $subs->values()->all();
                    $tokens = array_column($list, 'token');
                    if (!$tokens) return;

                    $report = $messaging->sendMulticast($message, $tokens);
                    $s      = $report->successes()->count();
                    $f      = $report->failures()->count();
                    $success += $s;
                    $failed  += $f;

                    // deactivate bad tokens
                    $idxs = array_keys($report->failures()->getItems());
                    if ($idxs) {
                        $bad = array_map(fn($i) => $list[$i]->id, $idxs);
                        DB::table('push_subscriptions_head')
                          ->whereIn('id',$bad)
                          ->update(['status'=>0]);
                    }

                    // record each send in bulk
                    $rows = [];
                    foreach ($list as $i => $sub) {
                        $rows[] = [
                            'notification_id'      => $this->notificationId,
                            'subscription_head_id' => $sub->id,
                            'status'               => in_array($i,$idxs) ? 0 : 1,
                            'created_at'           => $now,
                            'updated_at'           => $now,
                        ];
                    }
                    foreach (array_chunk($rows,500) as $chunk) {
                        DB::table('notification_sends')->insertOrIgnore($chunk);
                    }
                });

            // bump global counters
            DB::table('notifications')
                ->where('id',$this->notificationId)
                ->update([
                    'active_count'  => DB::raw("active_count + ".($success+$failed)),
                    'success_count' => DB::raw("success_count + {$success}"),
                    'failed_count'  => DB::raw("failed_count + {$failed}"),
                ]);

            // mark this domain pivot
            DB::table('domain_notification')
                ->where('notification_id',$this->notificationId)
                ->where('domain_id',$this->domainId)
                ->update([
                    'status'  => $success > 0 ? 'sent' : 'failed',
                    'sent_at' => $now,
                ]);

        } catch (Throwable $e) {
            Log::error("Domain job failed [notif={$this->notificationId},dom={$this->domainId}]: {$e->getMessage()}");
            DB::table('domain_notification')
              ->where('notification_id',$this->notificationId)
              ->where('domain_id',$this->domainId)
              ->update(['status'=>'failed','sent_at'=>now()]);
        }
    }
}