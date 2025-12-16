<?php

namespace App\Jobs;

use App\Models\PushSubscriptionHead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeactivateFailedTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    protected array $failedTokens;
    protected string $domainName;

    public function __construct(array $failedTokens, string $domainName)
    {
        $this->failedTokens = array_unique(array_filter($failedTokens));
        $this->domainName = $domainName;
    }

    public function handle(): void
    {
        try {
            if (empty($this->failedTokens)) {
                return;
            }

            $startTime = microtime(true);

            // Batch update for better performance
            $updatedCount = DB::table('push_subscription_head')
                ->whereIn('token', $this->failedTokens)
                ->where('parent_origin', $this->domainName)
                ->where('status', 1) // Only update active tokens
                ->update([
                    'status' => 0,
                    'updated_at' => now(),
                    // 'deactivated_at' => now(),
                    // 'deactivation_reason' => 'firebase_invalid_token',
                ]);

            $duration = round((microtime(true) - $startTime) * 1000);

            if ($updatedCount > 0) {
                Log::info("Deactivated invalid tokens", [
                    'domain' => $this->domainName,
                    'attempted' => count($this->failedTokens),
                    'deactivated' => $updatedCount,
                    'duration_ms' => $duration,
                ]);
            }

        } catch (Throwable $e) {
            Log::error("Failed to deactivate tokens", [
                'domain' => $this->domainName,
                'tokens_count' => count($this->failedTokens),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("DeactivateFailedTokensJob failed permanently", [
            'domain' => $this->domainName,
            'tokens_count' => count($this->failedTokens),
            'exception' => $exception->getMessage(),
        ]);
    }
}