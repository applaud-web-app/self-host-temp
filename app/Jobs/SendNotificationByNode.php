<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;

class SendNotificationByNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;
    public int $timeout = 600;
    
    // Maximum tokens per HTTP request to avoid payload limits
    private const MAX_TOKENS_PER_REQUEST = 10000;
    private const NODE_SERVICE_TIMEOUT = 120;
    private const MAX_CONCURRENT_REQUESTS = 5; // Process 5 requests in parallel
    
    protected array $tokens;
    protected array $message;
    protected string $domainName;
    protected ?int $notificationId;

    public function __construct(
        array $tokens, 
        array $message, 
        string $domainName, 
        ?int $notificationId = null
    ) {
        $this->tokens = array_values(array_unique(array_filter($tokens)));
        $this->message = $message;
        $this->domainName = $domainName;
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            if (empty($this->tokens)) {
                Log::warning("No valid tokens for domain: {$this->domainName}");
                $this->finalizeNotification(0, 0, 0);
                return;
            }

            $totalTokens = count($this->tokens);
            Log::info("Starting SendNotificationByNode", [
                'notification_id' => $this->notificationId,
                'domain' => $this->domainName,
                'total_tokens' => $totalTokens,
            ]);

            // Update status to processing
            $this->updateNotificationStatus('processing', $totalTokens);

            // Split tokens into chunks to avoid payload size limits
            $tokenChunks = array_chunk($this->tokens, self::MAX_TOKENS_PER_REQUEST);
            $totalChunks = count($tokenChunks);
            
            Log::info("Split into chunks", [
                'notification_id' => $this->notificationId,
                'total_tokens' => $totalTokens,
                'chunks' => $totalChunks,
                'tokens_per_chunk' => self::MAX_TOKENS_PER_REQUEST,
            ]);

            // Process chunks with controlled parallelism using HTTP Pool
            $aggregatedResults = $this->processChunksInParallel($tokenChunks);

            $successCount = $aggregatedResults['successCount'];
            $failureCount = $aggregatedResults['failureCount'];
            $failedTokens = $aggregatedResults['failedTokens'];

            // Async cleanup of failed tokens
            if (!empty($failedTokens)) {
                dispatch(new DeactivateFailedTokensJob($failedTokens, $this->domainName))
                    ->onQueue('cleanup')
                    ->delay(now()->addSeconds(5));
            }

            // Finalize notification
            $this->finalizeNotification($successCount, $failureCount, $totalTokens);

            $duration = round((microtime(true) - $startTime) * 1000);
            $throughput = $totalTokens > 0 ? round($totalTokens / ($duration / 1000)) : 0;
            
            Log::info("SendNotificationByNode completed", [
                'notification_id' => $this->notificationId,
                'domain' => $this->domainName,
                'success' => $successCount,
                'failed' => $failureCount,
                'invalid_tokens' => count($failedTokens),
                'chunks_processed' => $totalChunks,
                'duration_ms' => $duration,
                'throughput_per_sec' => $throughput,
            ]);

        } catch (Throwable $e) {
            Log::error("SendNotificationByNode exception", [
                'notification_id' => $this->notificationId,
                'domain' => $this->domainName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->finalizeNotification(0, count($this->tokens), count($this->tokens), 'failed');
        }
    }

    /**
     * Process chunks in parallel using Laravel HTTP Pool
     */
    private function processChunksInParallel(array $tokenChunks): array
    {
        $results = [
            'successCount' => 0,
            'failureCount' => 0,
            'failedTokens' => [],
        ];

        $nodeServiceUrl = config('services.node_service.url') . '/send-notification';

        // Process chunks in batches for controlled concurrency
        $chunkBatches = array_chunk($tokenChunks, self::MAX_CONCURRENT_REQUESTS);

        foreach ($chunkBatches as $batchIndex => $batch) {
            try {
                // Create parallel HTTP requests using Pool
                $responses = Http::pool(function (Pool $pool) use ($batch, $nodeServiceUrl) {
                    $requests = [];
                    
                    foreach ($batch as $tokens) {
                        $requests[] = $pool->withOptions([
                                'connect_timeout' => 10,
                                'timeout' => self::NODE_SERVICE_TIMEOUT,
                                'verify' => false,
                            ])
                            ->post($nodeServiceUrl, [
                                'tokens' => $tokens,
                                'message' => $this->message,
                                'domain' => $this->domainName,
                                'notification_id' => $this->notificationId,
                            ]);
                    }
                    
                    return $requests;
                });

                // Process responses
                foreach ($responses as $index => $response) {
                    $tokens = $batch[$index];
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        
                        if (isset($data['successCount']) && isset($data['failureCount'])) {
                            $results['successCount'] += $data['successCount'];
                            $results['failureCount'] += $data['failureCount'];
                            
                            if (!empty($data['failedTokens'])) {
                                $results['failedTokens'] = array_merge(
                                    $results['failedTokens'],
                                    $data['failedTokens']
                                );
                            }
                        } else {
                            // Invalid response structure
                            Log::error("Invalid Node service response", [
                                'response' => $data,
                                'tokens_count' => count($tokens),
                            ]);
                            $results['failureCount'] += count($tokens);
                        }
                    } else {
                        // HTTP error
                        Log::error("Node service HTTP error", [
                            'status' => $response->status(),
                            'body' => substr($response->body(), 0, 500),
                            'tokens_count' => count($tokens),
                        ]);
                        $results['failureCount'] += count($tokens);
                    }
                }

                // Small delay between batches to avoid overwhelming server
                if ($batchIndex < count($chunkBatches) - 1) {
                    usleep(100000); // 100ms
                }

            } catch (Throwable $e) {
                Log::error("Chunk batch processing error", [
                    'batch_index' => $batchIndex,
                    'error' => $e->getMessage(),
                ]);
                
                // Count all tokens in this batch as failures
                foreach ($batch as $tokens) {
                    $results['failureCount'] += count($tokens);
                }
            }
        }

        // Remove duplicate failed tokens
        $results['failedTokens'] = array_unique($results['failedTokens']);

        return $results;
    }

    /**
     * Update notification status
     */
    private function updateNotificationStatus(string $status, ?int $activeCount = null): void
    {
        if (!$this->notificationId) return;

        $updateData = ['status' => $status];
        
        if ($activeCount !== null) {
            $updateData['active_count'] = $activeCount;
        }

        DB::table('notifications')
            ->where('id', $this->notificationId)
            ->update($updateData);
    }

    /**
     * Finalize notification with stats
     */
    private function finalizeNotification(
        int $successCount, 
        int $failureCount, 
        int $totalCount,
        string $status = 'sent'
    ): void {
        if (!$this->notificationId) return;

        try {
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update([
                    'active_count' => $totalCount,
                    'success_count' => $successCount,
                    'failed_count' => $failureCount,
                    'status' => $status,
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
        } catch (Throwable $e) {
            Log::error("Failed to finalize notification", [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle permanent job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error("SendNotificationByNode job failed permanently", [
            'notification_id' => $this->notificationId,
            'domain' => $this->domainName,
            'tokens_count' => count($this->tokens),
            'exception' => $exception->getMessage(),
        ]);

        $this->finalizeNotification(0, count($this->tokens), count($this->tokens), 'failed');
    }
}