<?php

namespace App\Console\Commands;

use App\Jobs\SubscribePushSubscriptionJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

// class FlushPushSubscriptions extends Command
// {
//     protected $signature = 'subscriptions:flush';
//     protected $description = 'Flush push subscriptions from Redis to the queue for background processing';

//     public function handle(): void
//     {
//         $key = 'buffer:push_subscriptions';
//         $processedKey = 'processed:push_subscriptions';
//         $batchSize = 2; // 200
//         $maxBatches = 10; // 5

//         $this->info("Starting Redis subscription flush...");

//         try {

//             // Safety check: Is Redis alive?
//             if (! Redis::ping()) {
//                 throw new \Exception('Push Subscriber : Redis not available or connection refused.');
//             }

//             for ($i = 0; $i < $maxBatches; $i++) {
//                 // Step 1: Read batch
//                 $batch = Redis::lrange($key, 0, $batchSize - 1);

//                 if (empty($batch)) {
//                     $this->info("No more subscriptions to process.");
//                     break;
//                 }

//                 // Step 2: Trim processed entries
//                 Redis::ltrim($key, count($batch), -1);

//                 // Step 3: Dispatch jobs
//                 foreach ($batch as $raw) {
//                     $data = json_decode($raw, true);

//                     if (!is_array($data) || !isset($data['token'], $data['domain'], $data['endpoint'])) {
//                         Log::warning('Malformed Redis subscription entry', ['raw' => $raw]);
//                         continue;
//                     }

//                     $hash = $data['subscription_hash'] ?? md5($data['token'] . $data['domain'] . $data['endpoint']);

//                     // ✅ Check if already processed
//                     if (Redis::sismember($processedKey, $hash)) {
//                         Log::info('Skipping already processed subscription', ['hash' => $hash]);
//                         continue;
//                     }

//                     SubscribePushSubscriptionJob::dispatch($data);
//                     Redis::sadd($processedKey, $hash);
//                     Redis::expire($processedKey, 86400);
//                 }

//                 $this->info("Dispatched batch of " . count($batch) . " subscriptions.");
//             }
//         } catch (\Throwable $e) {
//             Log::error('subscriptions:flush crashed', [
//                 'message' => $e->getMessage()
//             ]);
//             $this->error("Flush terminated due to error: " . $e->getMessage());
//         }

//         $this->info("Redis subscription flush complete.");
//     }
// }

class FlushPushSubscriptions extends Command
{
    protected $signature = 'subscriptions:flush';
    protected $description = 'Flush push subscriptions from Redis buffer to queue (atomic + crash-safe)';

    public function handle(): int
    {
        $bufferKey     = 'buffer:push_subscriptions';
        $processingKey = 'processing:push_subscriptions';
        $queuedKey     = 'queued:push_subscriptions';
        $processedKey  = 'processed:push_subscriptions';

        $batchSize = 100; // per loop
        $maxLoops  = 20;  // max 2000 items per run

        $this->info('Starting push subscription flush...');

        try {
            Redis::ping();

            $totalDispatched = 0;
            $totalSkipped    = 0;
            $totalMalformed  = 0;

            for ($loop = 0; $loop < $maxLoops; $loop++) {
                $loopDispatched = 0;

                for ($i = 0; $i < $batchSize; $i++) {
                    // Atomic: buffer -> processing
                    $raw = Redis::rpoplpush($bufferKey, $processingKey);
                    if (!$raw) break;

                    $data = json_decode($raw, true);

                    // Strict validation
                    if (
                        !is_array($data) ||
                        !isset($data['subscription_hash'], $data['token'], $data['domain'], $data['endpoint'])
                    ) {
                        $totalMalformed++;
                        Log::warning('Malformed subscription entry removed from processing', [
                            'raw' => substr($raw, 0, 200)
                        ]);
                        Redis::lrem($processingKey, 1, $raw);
                        continue;
                    }

                    $hash = $data['subscription_hash'];

                    // Best-effort skip if already processed
                    try {
                        if (Redis::sismember($processedKey, $hash)) {
                            $totalSkipped++;
                            Redis::lrem($processingKey, 1, $raw);
                            Redis::srem($queuedKey, $hash);
                            continue;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Redis processed check failed in flush; proceeding', [
                            'error' => $e->getMessage()
                        ]);
                    }

                    // Dispatch safely (never lose item)
                    try {
                        SubscribePushSubscriptionJob::dispatch($data);
                        $loopDispatched++;
                        $totalDispatched++;

                        Redis::lrem($processingKey, 1, $raw);

                    } catch (\Throwable $e) {
                        Log::error('Job dispatch failed; returning item to buffer', [
                            'hash'  => $hash,
                            'error' => $e->getMessage()
                        ]);

                        try {
                            Redis::lrem($processingKey, 1, $raw);
                            Redis::lpush($bufferKey, $raw);
                        } catch (\Throwable $recoveryError) {
                            Log::critical('Cannot recover failed dispatch item', [
                                'hash'  => $hash,
                                'error' => $recoveryError->getMessage()
                            ]);
                        }
                    }
                }

                if ($loopDispatched === 0) {
                    break;
                }
            }

            // Crash-safety: move stuck processing back to buffer
            $stuckCount = 0;
            while (true) {
                $stuck = Redis::rpoplpush($processingKey, $bufferKey);
                if (!$stuck) break;
                $stuckCount++;
            }

            $this->info("✓ Flush complete: dispatched={$totalDispatched}, skipped={$totalSkipped}, malformed={$totalMalformed}, recovered_stuck={$stuckCount}");
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            Log::error('subscriptions:flush crashed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            // Emergency recovery
            try {
                while (Redis::rpoplpush($processingKey, $bufferKey)) {}
            } catch (\Throwable $ignore) {}

            $this->error("✗ Flush failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}