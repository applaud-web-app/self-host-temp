<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class FlushPushAnalytics extends Command
{
    protected $signature = 'analytics:flush';
    protected $description = 'Flush push event counts from Redis to MySQL';

    public function handle(): void
    {
        $key = 'buffer:push_events';
        $batchSize = 200;
        $maxBatches = 10;

        $this->info("Starting Redis analytics flush...");

        try {
            for ($i = 0; $i < $maxBatches; $i++) {
                // Step 1: Read batch
                $batch = Redis::lrange($key, 0, $batchSize - 1);

                if (empty($batch)) break;

                // Step 2: Trim entries (atomic delete from front)
                Redis::ltrim($key, count($batch), -1);

                // Step 3: Aggregate by (event, message_id)
                $counts = [];

                foreach ($batch as $raw) {
                    $data = json_decode($raw, true);

                    if (!isset($data['message_id'], $data['event'])) {
                        Log::warning('Malformed Redis entry in analytics buffer', ['raw' => $raw]);
                        continue;
                    }

                    $event = $data['event'];
                    $messageId = $data['message_id'];

                    $uniqueKey = "{$event}|{$messageId}";
                    $counts[$uniqueKey] = ($counts[$uniqueKey] ?? 0) + 1;
                }

                // Step 4: Bulk update to DB
                foreach ($counts as $key => $count) {
                    [$event, $messageId] = explode('|', $key, 2);

                    DB::table('push_event_counts')->updateOrInsert(
                        ['message_id' => $messageId, 'event' => $event],
                        ['count' => DB::raw("count + {$count}")]
                    );
                }

                $this->info("Processed batch of " . count($batch) . " entries.");
            }
        } catch (\Throwable $e) {
            Log::error('analytics:flush crashed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error("Flush terminated due to error: " . $e->getMessage());
        }

        $this->info("Redis analytics flush complete.");
    }
}