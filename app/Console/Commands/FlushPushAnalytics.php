<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class FlushPushAnalytics extends Command
{
    protected $signature = 'analytics:flush';
    protected $description = 'Flush push event counts from Redis to MySQL';

    public function handle(): void
    {
        $key = 'buffer:push_events';
        $batchSize = 10;
        $maxBatches = 10;
        $this->info("Starting Redis analytics flush...");

        try {
            // Safety check: Is Redis alive?
            if (! Redis::ping()) {
                throw new \Exception('Push Analytics : Redis not available or connection refused.');
            }

            for ($i = 0; $i < $maxBatches; $i++) {
                // Step 1: Read batch with LPOP (avoids race condition)
                $batch = [];
                for ($j = 0; $j < $batchSize; $j++) {
                    $item = Redis::lpop($key);
                    if (!$item) break; // Stop if no more items are left
                    $batch[] = $item;
                }

                if (empty($batch)) break;

                // Step 2: Aggregate by (event, message_id)
                $counts = [];

                foreach ($batch as $raw) {
                    $data = json_decode($raw, true);

                    if (!isset($data['message_id'], $data['event'])) {
                        Log::warning('Malformed Redis entry in analytics buffer', ['raw' => $raw]);
                        continue;
                    }

                    $event = $data['event'];
                    $messageId = $data['message_id'];

                    // Unique key to avoid duplicates for same message_id and event
                    $uniqueKey = "{$event}|{$messageId}";
                    $counts[$uniqueKey] = ($counts[$uniqueKey] ?? 0) + 1;
                }

                // Step 3: Bulk update to DB
                foreach ($counts as $ukey => $count) {
                    [$event, $messageId] = explode('|', $ukey, 2);

                    // Update DB and mark as processed
                    DB::table('push_event_counts')->updateOrInsert(
                        ['message_id' => $messageId, 'event' => $event],
                        ['count' => DB::raw("count + {$count}")]
                    );

                    Redis::sadd('processed:push_analytics', $ukey);
                    Redis::expire('processed:push_analytics', 3600);
                }

                $this->info("Processed batch of " . count($batch) . " entries.");
            }
        } catch (\Throwable $e) {
            Log::error('analytics:flush crashed', [
                'message' => $e->getMessage()
            ]);
            $this->error("Flush terminated due to error: " . $e->getMessage());
        }

        $this->info("Redis analytics flush complete.");
    }
}