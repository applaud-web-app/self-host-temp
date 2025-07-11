<?php

namespace App\Console\Commands;

use App\Jobs\SubscribePushSubscriptionJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class FlushPushSubscriptions extends Command
{
    protected $signature = 'subscriptions:flush';
    protected $description = 'Flush push subscriptions from Redis to the queue for background processing';

    public function handle(): void
    {
        $key = 'buffer:push_subscriptions';
        $processedKey = 'processed:push_subscriptions';
        $batchSize = 2; // 200
        $maxBatches = 10; // 5

        $this->info("Starting Redis subscription flush...");

        try {

            // Safety check: Is Redis alive?
            if (! Redis::ping()) {
                throw new \Exception('Push Subscriber : Redis not available or connection refused.');
            }

            for ($i = 0; $i < $maxBatches; $i++) {
                // Step 1: Read batch
                $batch = Redis::lrange($key, 0, $batchSize - 1);

                if (empty($batch)) {
                    $this->info("No more subscriptions to process.");
                    break;
                }

                // Step 2: Trim processed entries
                Redis::ltrim($key, count($batch), -1);

                // Step 3: Dispatch jobs
                foreach ($batch as $raw) {
                    $data = json_decode($raw, true);

                    if (!is_array($data) || !isset($data['token'], $data['domain'], $data['endpoint'])) {
                        Log::warning('Malformed Redis subscription entry', ['raw' => $raw]);
                        continue;
                    }

                    $hash = $data['subscription_hash'] ?? md5($data['token'] . $data['domain'] . $data['endpoint']);

                    // ✅ Check if already processed
                    if (Redis::sismember($processedKey, $hash)) {
                        Log::info('Skipping already processed subscription', ['hash' => $hash]);
                        continue;
                    }

                    SubscribePushSubscriptionJob::dispatch($data);
                    Redis::sadd($processedKey, $hash);
                    Redis::expire($processedKey, 3600);
                }

                $this->info("Dispatched batch of " . count($batch) . " subscriptions.");
            }
        } catch (\Throwable $e) {
            Log::error('subscriptions:flush crashed', [
                'message' => $e->getMessage()
            ]);
            $this->error("Flush terminated due to error: " . $e->getMessage());
        }

        $this->info("Redis subscription flush complete.");
    }
}
