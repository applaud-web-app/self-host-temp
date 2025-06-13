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
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;

class SendNotificationBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 1;
    public int $timeout = 600;

    protected int     $notificationId;
    protected Factory $factory;
    protected array   $webPush;
    protected array   $subscriptionIds;
    protected array   $tokens;

     public function __construct(
        int     $notificationId,
        Factory $factory,
        array   $webPush,
        array   $subscriptionIds,
        array   $tokens
    ) {
        $this->notificationId  = $notificationId;
        $this->factory         = $factory;
        $this->webPush         = $webPush;
        $this->subscriptionIds = $subscriptionIds;
        $this->tokens          = $tokens;
    }

    public function handle(): void
    {
        if (empty($this->tokens)) {
            return;
        }

        $messaging = $this->factory->createMessaging();
        $config    = WebPushConfig::fromArray($this->webPush);
        $message   = CloudMessage::new()->withWebPushConfig($config);
        $now       = now();

        try {
            $report       = $messaging->sendMulticast($message, $this->tokens);
            $successCount = $report->successes()->count();
            $failures     = $report->failures();
            $failureItems = $failures->getItems();

            // log each failure reason
            foreach ($failureItems as $index => $failure) {
                $error = method_exists($failure, 'error')
                    ? $failure->error()->getMessage()
                    : (string)$failure;
                Log::error('Push send failed', [
                    'notification_id'      => $this->notificationId,
                    'subscription_id'      => $this->subscriptionIds[$index] ?? null,
                    'token'                => $this->tokens[$index] ?? null,
                    'error'                => $error,
                ]);
            }

            $rows = [];
            foreach ($this->subscriptionIds as $i => $subId) {
                $ok = ! array_key_exists($i, $failureItems);
                $rows[] = [
                    'notification_id'      => $this->notificationId,
                    'subscription_head_id' => $subId,
                    'status'               => $ok ? 1 : 0,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
                if (! $ok) {
                    PushSubscriptionHead::where('id', $subId)
                        ->update(['status' => 0]);
                }
            }

            DB::table('notification_sends')->insertOrIgnore($rows);
            Notification::where('id', $this->notificationId)
                        ->increment('active_count', count($this->tokens));
            Notification::where('id', $this->notificationId)
                        ->increment('success_count', $successCount);
            Notification::where('id', $this->notificationId)
                        ->increment('failed_count', count($failureItems));

        } catch (\Throwable $e) {
            Log::error("Batch job permanently failed [notif={$this->notificationId}]: " . $e->getMessage());
            // Fallback: mark all as failed
            $now = now();
            $rows = array_map(fn($subId) => [
                'notification_id'      => $this->notificationId,
                'subscription_head_id' => $subId,
                'status'               => 0,
                'created_at'           => $now,
                'updated_at'           => $now,
            ], $this->subscriptionIds);
            DB::table('notification_sends')->insertOrIgnore($rows);
            PushSubscriptionHead::whereIn('id', $this->subscriptionIds)
                                 ->update(['status' => 0]);
            Notification::where('id', $this->notificationId)
                        ->increment('failed_count', count($this->subscriptionIds));
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("Batch job permanently failed [notif={$this->notificationId}]: " . $e->getMessage());
    }
}
