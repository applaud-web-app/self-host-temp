<?php
// IS ACTIVE JOB

namespace App\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Models\PushSubscriptionHead;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\PushConfig;
use App\Models\Setting;

class SendNotificationByNeedTest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;
    public int $timeout = 7200;
    public int $backoff = 60;

    private const MAX_FCM_BATCH = 500;

    protected array $tokens;
    protected array $message;
    protected string $domainName;
    protected ?int $notificationId;

    public function __construct(array $tokens, array $message, string $domainName, ?int $notificationId = null)
    {
        $this->tokens = $tokens;
        $this->message = $message;
        $this->domainName = $domainName;
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        try {
            if (empty($this->tokens)) {
                Log::warning("No tokens provided for domain: {$this->domainName}");
                $this->updateNotificationStatus(0, 0, 0);
                return;
            }

            // Get Firebase credentials for this domain
            $firebaseCredentials = $this->getFirebaseCredentials();
            if (!$firebaseCredentials) {
                throw new \Exception("Firebase credentials not found for domain: {$this->domainName}");
            }

            // Get settings from cache or database
            $settings = Cache::remember('settings_batch_size', 3600, function () {
                return Setting::firstOrCreate(['id' => 1], [
                    'gap_size' => self::MAX_FCM_BATCH,
                    'time_gap' => 1000,
                    'daily_cleanup' => false,
                    'sending_speed' => 'fast',
                ]);
            });

            $batchSize = max(1, (int)($settings->gap_size ?? self::MAX_FCM_BATCH));
            $timeGapMs = max(0, (int)($settings->time_gap ?? 1000));

            // Process tokens in batches
            $batches = array_chunk($this->tokens, $batchSize);

            $totalSuccess = 0;
            $totalFailure = 0;
            $allFailedTokens = [];

            Log::info("Processing ".count($this->tokens)." tokens in ".count($batches)." batches for domain: {$this->domainName}", [
                'batch_size' => $batchSize,
                'time_gap_ms' => $timeGapMs,
                'sending_speed' => $settings->sending_speed,
            ]);

            $hasConnectionError = false;

            foreach ($batches as $batchIndex => $batch) {
                Log::info("Processing batch " . ($batchIndex + 1) . " of " . count($batches) . " with " . count($batch) . " tokens");

                $response = $this->sendBatchToNodeService($batch, $firebaseCredentials);

                if ($response !== null) {
                    // Successful API call - process the response
                    $successCount = $response['successCount'] ?? 0;
                    $failureCount = $response['failureCount'] ?? 0;
                    $batchFailedTokens = $response['failedTokens'] ?? [];

                    $totalSuccess += $successCount;
                    $totalFailure += $failureCount;
                    
                    // Only add tokens that actually failed from Firebase, not connection errors
                    $allFailedTokens = array_merge($allFailedTokens, $batchFailedTokens);

                    Log::info("Batch " . ($batchIndex + 1) . " completed - Success: {$successCount}, Failed: {$failureCount}, Invalid tokens: " . count($batchFailedTokens));
                } else {
                    // Connection/API error - don't mark tokens as inactive
                    $hasConnectionError = true;
                    $totalFailure += count($batch);
                    Log::error("Batch " . ($batchIndex + 1) . " failed due to connection/API error - tokens will not be marked inactive");
                }

                if ($timeGapMs > 0) {
                    usleep($timeGapMs * 1000);
                }
            }

            // Determine the final notification status
            $finalStatus = 'sent';
            if ($hasConnectionError && $totalSuccess === 0) {
                // If there were connection errors and no success, mark as failed for retry
                $finalStatus = 'failed';
                Log::warning("Notification marked as failed due to connection errors - will be retried");
            } elseif ($hasConnectionError && $totalSuccess > 0) {
                // Partial success with connection errors - mark as sent but log warning
                Log::warning("Notification partially sent - some batches failed due to connection errors");
            }

            // Update notification status in database
            $this->updateNotificationStatus($totalSuccess, $totalFailure, count($this->tokens), $finalStatus);

            // Mark failed tokens as inactive ONLY if they came from Firebase response
            if (!empty($allFailedTokens)) {
                $this->deactivateFailedTokens($allFailedTokens);
                Log::info("Marked " . count($allFailedTokens) . " tokens as inactive (Firebase failures only)");
            }

            Log::info("SendNotificationByNode completed for domain: {$this->domainName} - Success: {$totalSuccess}, Failed: {$totalFailure}, Inactive: " . count($allFailedTokens) . ", Status: {$finalStatus}");

        } catch (Throwable $e) {
            Log::error("SendNotificationByNode failed for domain: {$this->domainName}: {$e->getMessage()}", [
                'domain' => $this->domainName,
                'notification_id' => $this->notificationId,
                'exception' => $e->getTraceAsString()
            ]);

            // Mark notification as failed only for exceptions, not connection errors
            $this->updateNotificationStatus(0, count($this->tokens), count($this->tokens), 'failed');
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    private function getFirebaseCredentials(): ?array
    {
        try {
            $cacheKey = "firebase_credentials_{$this->domainName}";
            
            return Cache::remember($cacheKey, 1800, function () {
                $config = PushConfig::first();
                if (!$config || !$config->service_account_json) {
                    return null;
                }
                return $config->credentials; 
            });
            
        } catch (Throwable $e) {
            Log::error("Failed to get Firebase credentials for domain {$this->domainName}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Send a batch of tokens to the Node.js service
     */
    private function sendBatchToNodeService(array $batch): ?array
    {
        try {
            $payload = [
                'tokens' => $batch,
                'message' => $this->message,
            ];

            $nodeServiceUrl = env('SERVER_URL').'/push/send-notification';

            $response = Http::timeout(300) // 5 minutes timeout
                ->retry(3, 1000) // Retry 3 times with 1 second delay
                ->post($nodeServiceUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::debug("Node service response", [
                    'success_count' => $data['successCount'] ?? 0,
                    'failure_count' => $data['failureCount'] ?? 0,
                    'failed_tokens_count' => count($data['failedTokens'] ?? [])
                ]);
                return $data;
            } else {
                Log::error("Node service returned error", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (Throwable $e) {
            Log::error("Error communicating with Node service: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Update notification status in database
     */
    private function updateNotificationStatus(int $successCount, int $failureCount, int $totalCount, string $status = 'sent'): void
    {
        if (!$this->notificationId) {
            // Fallback to message_id if notification_id is not provided
            DB::table('notifications')
                ->where('message_id', $this->message['data']['message_id'] ?? '')
                ->update([
                    'active_count' => $totalCount,
                    'success_count' => $successCount,
                    'failed_count' => $failureCount,
                    'status' => $status,
                    'sent_at' => now(),
                ]);
        } else {
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update([
                    'active_count' => $totalCount,
                    'success_count' => $successCount,
                    'failed_count' => $failureCount,
                    'status' => $status,
                    'sent_at' => now(),
                ]);
        }
    }

    /**
     * Deactivate failed tokens to prevent future sending
     */
    private function deactivateFailedTokens(array $failedTokens): void
    {
        try {
            $uniqueFailedTokens = array_unique($failedTokens);
            
            $updatedCount = PushSubscriptionHead::whereIn('token', $uniqueFailedTokens)
                ->where('parent_origin', $this->domainName)
                ->update([
                    'status' => 0,
                    'updated_at' => now()
                ]);

            Log::info("Marked {$updatedCount} tokens as inactive for domain: {$this->domainName}");
        } catch (Throwable $e) {
            Log::error("Failed to deactivate tokens: {$e->getMessage()}");
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error("SendNotificationByNode job failed permanently", [
            'domain' => $this->domainName,
            'notification_id' => $this->notificationId,
            'exception' => $exception->getMessage(),
            'tokens_count' => count($this->tokens)
        ]);

        // Mark notification as failed
        $this->updateNotificationStatus(0, count($this->tokens), count($this->tokens), 'failed');
    }
}