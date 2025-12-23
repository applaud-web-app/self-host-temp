<?php

namespace App\Jobs;

use App\Support\SegmentFilterHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendNotificationChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;
    public int $backoff = 10;

    public function __construct(
        public int $notificationId,
        public int $chunkIndex,
        public int $cursorStartId,
        public int $cursorEndId,
        public string $domainName,
        public string $segmentType,
        public ?int $segmentId,
        public string $messageId
    ) {}

    public function handle(): void
    {
        $startTime = microtime(true);

        // Idempotency / dedupe
        $dedupeKey = $this->dedupeKey();
        if (Cache::has($dedupeKey)) {
            Log::info("Chunk already processed (dedupe)", [
                'notification_id' => $this->notificationId,
                'chunk_index' => $this->chunkIndex,
            ]);
            return;
        }

        // Circuit breaker
        // if ($this->isCircuitBreakerOpen()) {
        //     Log::warning("Circuit breaker open, retrying chunk later", [
        //         'notification_id' => $this->notificationId,
        //         'chunk_index' => $this->chunkIndex,
        //     ]);
        //     $this->release(30);
        //     return;
        // }
        if ($this->isCircuitBreakerOpen()) {
            Log::warning("Circuit breaker open, failing chunk", [
                'notification_id' => $this->notificationId,
                'chunk_index' => $this->chunkIndex,
            ]);
            
            // ✅ Don't use release() - mark chunk as failed
            $this->markChunkDone(0, count($this->fetchTokensByCursorRange()));
            Cache::put($this->dedupeKey(), true, now()->addHours(24));
            return; // Exit gracefully without throwing
        }

        try {
            // Ensure notification exists and still processable
            $notification = DB::table('notifications')
                ->where('id', $this->notificationId)
                ->whereIn('status', ['queued', 'processing'])
                ->first(['id', 'status', 'chunks_total', 'chunks_done']);

            if (!$notification) {
                Log::warning("Notification not found or not processable", [
                    'notification_id' => $this->notificationId,
                ]);
                return;
            }

            // Fetch tokens for this chunk
            $tokens = $this->fetchTokensByCursorRange();

            if (empty($tokens)) {
                Log::info("No tokens in chunk", [
                    'notification_id' => $this->notificationId,
                    'chunk_index' => $this->chunkIndex,
                ]);

                $this->markChunkDone(0, 0);
                Cache::put($dedupeKey, true, now()->addHours(24));
                return;
            }

            Log::info("Processing chunk", [
                'notification_id' => $this->notificationId,
                'chunk_index' => $this->chunkIndex,
                'token_count' => count($tokens),
                'cursor_range' => [$this->cursorStartId, $this->cursorEndId],
            ]);

            $payload = $this->buildPayload();

            $result = $this->callNodeService($tokens, $payload);

            $this->markChunkDone(
                $result['successCount'],
                $result['failureCount']
            );

            // Optional cleanup job (keep your existing job if you have it)
            if (!empty($result['failedTokens'])) {
                DeactivateFailedTokensJob::dispatch(
                    $result['failedTokens'],
                    $this->domainName
                )->onQueue('cleanup')->delay(now()->addSeconds(5));
            }

            Cache::put($dedupeKey, true, now()->addHours(24));

            $this->resetCircuitBreaker();

            $duration = (int) round((microtime(true) - $startTime) * 1000);
            Log::info("Chunk completed", [
                'notification_id' => $this->notificationId,
                'chunk_index' => $this->chunkIndex,
                'success' => $result['successCount'],
                'failed' => $result['failureCount'],
                'duration_ms' => $duration,
            ]);
        } catch (Throwable $e) {
            $this->recordCircuitBreakerFailure();

            Log::error("SendNotificationChunkJob failed", [
                'notification_id' => $this->notificationId,
                'chunk_index' => $this->chunkIndex,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Let the queue retry
            throw $e;
        }
    }

    private function dedupeKey(): string
    {
        // Include domain to avoid collisions across domains if messageId overlaps
        return "notif_chunk:{$this->domainName}:{$this->messageId}:{$this->chunkIndex}";
    }

    /**
     * Fetch tokens using cursor range with segment filters.
     */
    private function fetchTokensByCursorRange(): array
    {
        $q = DB::table('push_subscriptions_head as h')
            ->where('h.parent_origin', $this->domainName)
            ->where('h.status', 1)
            ->whereNotNull('h.token')
            ->where('h.token', '!=', '')
            ->where('h.id', '>', $this->cursorStartId)
            ->where('h.id', '<=', $this->cursorEndId);

        if ($this->segmentType === 'particular' && $this->segmentId) {
            $segment = DB::table('segments')->where('id', $this->segmentId)->first();
            if (!$segment) {
                Log::warning("Segment not found", ['segment_id' => $this->segmentId]);
                return [];
            }
            SegmentFilterHelper::apply($q, $segment);
        }

        return $q->orderBy('h.id')
            ->pluck('h.token')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build FCM payload from notification.
     */
    private function buildPayload(): array
    {
        $n = DB::table('notifications')
            ->where('id', $this->notificationId)
            ->first([
                'title', 'description', 'banner_icon', 'banner_image',
                'target_url', 'message_id', 'btn_1_title', 'btn_1_url',
                'btn_title_2', 'btn_url_2'
            ]);

        if (!$n) {
            throw new RuntimeException("Notification not found: {$this->notificationId}");
        }

        $actions = [];

        if ($n->btn_1_title && $n->btn_1_url) {
            $actions[] = ['action' => 'btn1', 'title' => $n->btn_1_title, 'url' => $n->btn_1_url];
        }

        if ($n->btn_title_2 && $n->btn_url_2) {
            $actions[] = ['action' => 'btn2', 'title' => $n->btn_title_2, 'url' => $n->btn_url_2];
        }

        if (count($actions) < 2) {
            $actions[] = ['action' => 'close', 'title' => 'Close'];
        }

        return [
            'data' => [
                'title' => (string) $n->title,
                'body' => (string) $n->description,
                'icon' => (string) ($n->banner_icon ?? ''),
                'image' => (string) ($n->banner_image ?? ''),
                'click_action' => (string) $n->target_url,
                'message_id' => (string) $n->message_id,
                'delivery' => 'fcm',
                'actions' => json_encode($actions),
            ],
            'headers' => [
                'Urgency' => 'high',
            ],
        ];
    }

    /**
     * Call Node.js service with retry and timeout.
     */
    private function callNodeService(array $tokens, array $payload): array
    {
        $base = (string) config('services.node_service.url');
        if ($base === '') {
            throw new RuntimeException("services.node_service.url is not configured");
        }

        $url = rtrim($base, '/') . '/send-notification';

        // $response = Http::timeout(20)
        //     ->connectTimeout(5)
        //     ->retry(0)           
        //     ->post($url, [
        //         'tokens' => $tokens,
        //         'message' => $payload,
        //         'domain' => $this->domainName,
        //         'notification_id' => $this->notificationId,
        //         'chunk_index' => $this->chunkIndex,
        //     ]);

        $response = Http::withHeaders([
            'Host' => 'push.awmtab.in',
        ])
        ->timeout(20)
        ->connectTimeout(5)
        ->retry(0)
        ->post($url, [
            'tokens' => $tokens,
            'message' => $payload,
            'domain' => $this->domainName,
            'notification_id' => $this->notificationId,
            'chunk_index' => $this->chunkIndex,
        ]);

        if ($response->successful()) {
            $data = $response->json() ?: [];
            return [
                'successCount' => (int) ($data['successCount'] ?? 0),
                'failureCount' => (int) ($data['failureCount'] ?? 0),
                'failedTokens' => is_array($data['failedTokens'] ?? null) ? $data['failedTokens'] : [],
            ];
        }

        // 5xx => throw so queue retry happens
        if ($response->status() >= 500) {
            throw new RuntimeException("Node.js 5xx error: {$response->status()}");
        }

        // 4xx => don't retry, count as failure but DO NOT deactivate
        Log::error("Node.js 4xx error", [
            'status' => $response->status(),
            'body' => substr($response->body(), 0, 800),
        ]);

        return [
            'successCount' => 0,
            'failureCount' => count($tokens),
            'failedTokens' => [],
        ];
    }

    /**
     * Mark chunk as done and finalize notification if complete.
     * - Uses SQL-safe binding for increments
     * - Finalization is race-safe (only one worker will win the 'sent' update)
     */
    private function markChunkDone(int $success, int $fail): void
    {
        $success = max(0, (int) $success);
        $fail = max(0, (int) $fail);

        DB::transaction(function () use ($success, $fail) {
            // Lock the row to avoid races in chunks_done / chunks_total logic
            $n = DB::table('notifications')
                ->where('id', $this->notificationId)
                ->whereIn('status', ['queued', 'processing'])
                // ->lockForUpdate()
                ->first(['id', 'status', 'chunks_total', 'chunks_done']);

            if (!$n) {
                Log::warning("Could not update notification stats (not processable)", [
                    'notification_id' => $this->notificationId,
                ]);
                return;
            }

            // Atomic increments (SQL-safe)
            DB::update(
                "UPDATE notifications
                 SET success_count = success_count + ?,
                     failed_count  = failed_count + ?,
                     chunks_done   = chunks_done + 1,
                     updated_at    = ?
                 WHERE id = ? AND status IN ('queued','processing')",
                [$success, $fail, now(), $this->notificationId]
            );

            // Re-fetch (still under transaction lock semantics)
            $n2 = DB::table('notifications')
                ->where('id', $this->notificationId)
                ->first(['chunks_total', 'chunks_done', 'status']);

            if ($n2 && in_array($n2->status, ['queued', 'processing'], true) && (int)$n2->chunks_done >= (int)$n2->chunks_total) {
                // Race-safe finalize: only the first update will flip status
                $updated = DB::table('notifications')
                    ->where('id', $this->notificationId)
                    ->whereIn('status', ['queued', 'processing'])
                    ->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($updated) {
                    Log::info("Notification completed", [
                        'notification_id' => $this->notificationId,
                        'total_chunks' => (int) $n2->chunks_total,
                    ]);
                }
            }
        });
    }

    /* ---------- Circuit Breaker ---------- */

    private function circuitBreakerKey(): string
    {
        // Scope breaker to domain (prevents one domain outage blocking all domains)
        return "circuit_breaker:node_service:{$this->domainName}";
    }

    private function isCircuitBreakerOpen(): bool
    {
        return (int) Cache::get($this->circuitBreakerKey(), 0) >= 10;
    }

    private function recordCircuitBreakerFailure(): void
    {
        $key = $this->circuitBreakerKey();
        Cache::add($key, 0, now()->addMinutes(2));
        Cache::increment($key);
    }

    private function resetCircuitBreaker(): void
    {
        Cache::forget($this->circuitBreakerKey());
    }

    /**
     * Permanent failure handler.
     * NOTE: Do NOT add arbitrary +100. Track chunk failure sanely.
     */
    // public function failed(Throwable $exception): void
    // {
    //     Log::error("SendNotificationChunkJob failed permanently", [
    //         'notification_id' => $this->notificationId,
    //         'chunk_index' => $this->chunkIndex,
    //         'error' => $exception->getMessage(),
    //     ]);

    //     // Optional: mark notification as failed if you want.
    //     // (Or keep it processing and rely on manual retry tools.)
    //     DB::table('notifications')
    //         ->where('id', $this->notificationId)
    //         ->whereIn('status', ['queued', 'processing'])
    //         ->update([
    //             'status' => 'failed',
    //             'updated_at' => now(),
    //         ]);
    // }

    public function failed(Throwable $exception): void
    {
        Log::error("SendNotificationChunkJob failed permanently", [
            'notification_id' => $this->notificationId,
            'chunk_index' => $this->chunkIndex,
            'cursor_range' => [$this->cursorStartId, $this->cursorEndId],
            'error' => $exception->getMessage(),
        ]);

        // ✅ Mark this chunk as failed WITHOUT stopping other chunks
        try {
            $tokens = $this->fetchTokensByCursorRange();
            $failCount = count($tokens);

            DB::transaction(function () use ($failCount) {
                // Increment failure count for this chunk only
                DB::update(
                    "UPDATE notifications
                    SET failed_count = failed_count + ?,
                        chunks_done  = chunks_done + 1,
                        updated_at   = ?
                    WHERE id = ?",
                    [$failCount, now(), $this->notificationId]
                );

                // Check if all chunks are done
                $n = DB::table('notifications')
                    ->where('id', $this->notificationId)
                    ->first(['chunks_total', 'chunks_done']);

                if ($n && (int)$n->chunks_done >= (int)$n->chunks_total) {
                    // All chunks processed - mark as sent (even with failures)
                    DB::table('notifications')
                        ->where('id', $this->notificationId)
                        ->whereIn('status', ['queued', 'processing'])
                        ->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                            'updated_at' => now(),
                        ]);

                    Log::info("Notification completed with failures", [
                        'notification_id' => $this->notificationId,
                    ]);
                }
            });
        } catch (Throwable $e) {
            Log::error("Failed to update chunk stats", [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
            ]);
        }

        // ✅ DON'T mark entire notification as failed
    }
}