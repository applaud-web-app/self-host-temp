<?php

namespace App\Jobs;

use App\Models\PushConfig;
use App\Models\PushSubscriptionHead;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;
use Throwable;

class OptimizedSendNotificationDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;
    public int $timeout = 3600;

    // Optimal batch sizes for FCM
    private const FCM_BATCH_SIZE = 100; // FCM supports up to 500, but 100 is more reliable
    private const DB_INSERT_BATCH_SIZE = 1000;

    public function __construct(
        protected int $notificationId,
        protected array $webPush,
        protected int $domainId,
        protected string $domainName,
        protected int $batchSize = 500
    ) {}

    public function handle(): void
    {
        try {
            // 1) Get FCM instance once and reuse
            $messaging = $this->getFcmMessaging();
            if (!$messaging) {
                return;
            }

            // 2) Fetch subscribers in one optimized query
            $subscribers = DB::table('push_subscriptions_head')
                ->where('status', 1)
                ->where('parent_origin', $this->domainName)
                ->select('id', 'token')
                ->orderBy('id')
                ->get();

            if ($subscribers->isEmpty()) {
                $this->markDomainCompleted('sent');
                return;
            }

            $totalCount = $subscribers->count();
            $successCount = 0;
            $failureCount = 0;
            $sendRecords = [];

            // 3) Process in FCM-optimized batches
            $batches = $subscribers->chunk(self::FCM_BATCH_SIZE);
            
            foreach ($batches as $batch) {
                $tokens = $batch->pluck('token')->toArray();
                $idTokenMap = $batch->pluck('token', 'id')->toArray();

                try {
                    // Send to FCM
                    $config = WebPushConfig::fromArray($this->webPush);
                    $message = CloudMessage::new()->withWebPushConfig($config);
                    $report = $messaging->sendMulticast($message, $tokens);

                    $batchSuccessCount = $report->successes()->count();
                    $batchFailureCount = $report->failures()->count();

                    $successCount += $batchSuccessCount;
                    $failureCount += $batchFailureCount;

                    // 4) Prepare batch insert data
                    $now = now();
                    foreach ($idTokenMap as $id => $token) {
                        $sendRecords[] = [
                            'notification_id' => $this->notificationId,
                            'subscription_head_id' => $id,
                            'status' => 1, // We'll handle failures separately if needed
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }

                    // 5) Handle token cleanup for failures (optional optimization)
                    if ($batchFailureCount > 0) {
                        $this->handleFailedTokens($report->failures());
                    }

                } catch (Throwable $e) {
                    // Mark entire batch as failed
                    $failureCount += count($idTokenMap);
                    $now = now();
                    foreach ($idTokenMap as $id => $token) {
                        $sendRecords[] = [
                            'notification_id' => $this->notificationId,
                            'subscription_head_id' => $id,
                            'status' => 0,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }
                }
            }

            // 6) Bulk insert send records
            if (!empty($sendRecords)) {
                $this->bulkInsertSendRecords($sendRecords);
            }

            // 7) Update domain status
            $this->markDomainCompleted('sent');

            // 8) Minimal logging for performance
            if ($failureCount > 0) {
                Log::warning("Domain {$this->domainName}: {$successCount} success, {$failureCount} failed");
            }

        } catch (Throwable $e) {
            Log::error("Domain job failed [notif={$this->notificationId}, dom={$this->domainId}]: {$e->getMessage()}");
            $this->markDomainCompleted('failed');
        }
    }

    private function getFcmMessaging()
    {
        // Cache FCM config to avoid repeated DB queries
        $cfg = Cache::remember('fcm_config', 300, function () {
            return PushConfig::first();
        });

        if (!$cfg) {
            Log::error("FCM config missing for domain: {$this->domainName}");
            $this->markDomainCompleted('failed');
            return null;
        }

        $factory = (new Factory())->withServiceAccount($cfg->credentials);
        return $factory->createMessaging();
    }

    private function bulkInsertSendRecords(array $records): void
    {
        // Insert in chunks to avoid memory issues
        $chunks = array_chunk($records, self::DB_INSERT_BATCH_SIZE);
        
        foreach ($chunks as $chunk) {
            try {
                DB::table('notification_sends')->insertOrIgnore($chunk);
            } catch (Throwable $e) {
                Log::error("Failed to insert send records: {$e->getMessage()}");
            }
        }
    }

    private function handleFailedTokens($failures): void
    {
        // Optional: Clean up invalid tokens to improve future performance
        $invalidTokens = [];
        
        foreach ($failures as $failure) {
            $error = $failure->error();
            // Handle specific FCM errors that indicate invalid tokens
            if (strpos($error->getMessage(), 'registration-token-not-registered') !== false ||
                strpos($error->getMessage(), 'invalid-registration-token') !== false) {
                $invalidTokens[] = $failure->token();
            }
        }

        if (!empty($invalidTokens)) {
            // Disable invalid tokens in batches
            DB::table('push_subscriptions_head')
                ->whereIn('token', $invalidTokens)
                ->update(['status' => 0]);
        }
    }

    private function markDomainCompleted(string $status): void
    {
        DB::table('domain_notification')
            ->where('notification_id', $this->notificationId)
            ->where('domain_id', $this->domainId)
            ->update(['status' => $status, 'sent_at' => now()]);
    }
}