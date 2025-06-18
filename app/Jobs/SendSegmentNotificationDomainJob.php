<?php
// app/Jobs/SendSegmentNotificationDomainJob.php

namespace App\Jobs;

use App\Models\PushConfig;
use App\Models\PushSubscriptionHead;
use App\Models\SegmentDeviceRule;
use App\Models\SegmentGeoRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;
use Throwable;

class SendSegmentNotificationDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 1;
    public int $timeout = 7200;

    public function __construct(
        protected int    $notificationId,
        protected int    $segmentId,
        protected int    $domainId,
        protected string $domainName,
        protected array  $webPush
    ) {}

    public function handle(): void
    {
        try {
            $cfg       = PushConfig::first();
            $factory   = (new Factory())->withServiceAccount($cfg->credentials);
            $messaging = $factory->createMessaging();
            $config    = WebPushConfig::fromArray($this->webPush);
            $message   = CloudMessage::new()->withWebPushConfig($config);
            $now       = now();

            $success = $failed = 0;

            $query = PushSubscriptionHead::where('status', 1)
                ->where('domain', $this->domainName);

            // apply device rules via meta.device
            $deviceTypes = SegmentDeviceRule::where('segment_id', $this->segmentId)
                ->pluck('device_type')
                ->all();
            if (! empty($deviceTypes)) {
                $query->whereHas('meta', fn($q) =>
                    $q->whereIn('device', $deviceTypes)
                );
            }

            // apply geo rules via meta.country/state
            $geoRules = SegmentGeoRule::where('segment_id', $this->segmentId)->get();
            foreach ($geoRules as $rule) {
                if ($rule->operator === 'equals') {
                    $query->whereHas('meta', fn($q) =>
                        $q->where('country', $rule->country)
                          ->when($rule->state, fn($q2) => $q2->where('state', $rule->state))
                    );
                } else {
                    $query->whereHas('meta', fn($q) =>
                        $q->where('country', '!=', $rule->country)
                          ->when($rule->state, fn($q2) => $q2->where('state', '!=', $rule->state))
                    );
                }
            }

            $query->select('id','token')
                  ->orderBy('id')
                  ->chunkById(500, function($subs) use ($messaging, $message, &$success, &$failed, $now) {
                      $list   = $subs->values()->all();
                      $tokens = array_column($list, 'token');
                      if (! empty($tokens)) {
                          $report = $messaging->sendMulticast($message, $tokens);
                          $s = $report->successes()->count();
                          $f = $report->failures()->count();
                          $success += $s;
                          $failed  += $f;

                          $idxs = array_keys($report->failures()->getItems());
                          if (! empty($idxs)) {
                              $bad = array_map(fn($i) => $list[$i]->id, $idxs);
                              DB::table('push_subscriptions_head')
                                ->whereIn('id', $bad)
                                ->update(['status'=>0]);
                          }

                          $rows = [];
                          foreach ($list as $i => $sub) {
                              $rows[] = [
                                  'notification_id'      => $this->notificationId,
                                  'subscription_head_id' => $sub->id,
                                  'status'               => in_array($i, $idxs) ? 0 : 1,
                                  'created_at'           => $now,
                                  'updated_at'           => $now,
                              ];
                          }
                          foreach (array_chunk($rows,500) as $chunk) {
                              DB::table('notification_sends')->insertOrIgnore($chunk);
                          }
                      }
                  });

            DB::table('notifications')->where('id', $this->notificationId)
              ->update([
                'active_count'  => DB::raw("active_count + ".($success+$failed)),
                'success_count' => DB::raw("success_count + {$success}"),
                'failed_count'  => DB::raw("failed_count + {$failed}"),
              ]);

            DB::table('domain_notification')
              ->where('notification_id', $this->notificationId)
              ->where('domain_id', $this->domainId)
              ->update(['status'=>'sent','sent_at'=>$now]);

        } catch (Throwable $e) {
            Log::error("Segment domain job failed [notif={$this->notificationId},seg={$this->segmentId},dom={$this->domainId}]: {$e->getMessage()}");
            DB::table('domain_notification')
              ->where('notification_id', $this->notificationId)
              ->where('domain_id', $this->domainId)
              ->update(['status'=>'failed','sent_at'=>now()]);
        }
    }
}