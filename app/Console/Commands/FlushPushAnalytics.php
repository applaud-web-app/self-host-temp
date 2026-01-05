<?php


// use Illuminate\Console\Command;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Redis;

// class FlushPushAnalytics extends Command
// {
//     protected $signature = 'analytics:flush';
//     protected $description = 'Flush push event counts from Redis to MySQL';

//     public function handle(): void
//     {
//         $key = 'buffer:push_events';
//         $batchSize = 10;
//         $maxBatches = 10;
//         $this->info("Starting Redis analytics flush...");

//         try {
//             // Safety check: Is Redis alive?
//             if (! Redis::ping()) {
//                 throw new \Exception('Push Analytics : Redis not available or connection refused.');
//             }

//             for ($i = 0; $i < $maxBatches; $i++) {
//                 // Step 1: Read batch with LPOP (avoids race condition)
//                 $batch = [];
//                 for ($j = 0; $j < $batchSize; $j++) {
//                     $item = Redis::lpop($key);
//                     if (!$item) break; // Stop if no more items are left
//                     $batch[] = $item;
//                 }

//                 if (empty($batch)) break;

//                 // Step 2: Aggregate by (event, message_id)
//                 $counts = [];

//                 foreach ($batch as $raw) {
//                     $data = json_decode($raw, true);

//                     if (!isset($data['message_id'], $data['event'])) {
//                         Log::warning('Malformed Redis entry in analytics buffer', ['raw' => $raw]);
//                         continue;
//                     }

//                     $event = $data['event'];
//                     $messageId = $data['message_id'];

//                     // Unique key to avoid duplicates for same message_id and event
//                     $uniqueKey = "{$event}|{$messageId}";
//                     $counts[$uniqueKey] = ($counts[$uniqueKey] ?? 0) + 1;
//                 }

//                 // Step 3: Bulk update to DB
//                 foreach ($counts as $ukey => $count) {
//                     [$event, $messageId] = explode('|', $ukey, 2);

//                     // Update DB and mark as processed
//                     DB::table('push_event_counts')->updateOrInsert(
//                         ['message_id' => $messageId, 'event' => $event],
//                         ['count' => DB::raw("count + {$count}")]
//                     );

//                     Redis::sadd('processed:push_analytics', $ukey);
//                     Redis::expire('processed:push_analytics', 3600);
//                 }

//                 $this->info("Processed batch of " . count($batch) . " entries.");
//             }
//         } catch (\Throwable $e) {
//             Log::error('analytics:flush crashed', [
//                 'message' => $e->getMessage()
//             ]);
//             $this->error("Flush terminated due to error: " . $e->getMessage());
//         }

//         $this->info("Redis analytics flush complete.");
//     }
// }

// use Illuminate\Console\Command;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Redis;

// class FlushPushAnalytics extends Command
// {
//     protected $signature = 'analytics:flush';
//     protected $description = 'Flush push analytics from Redis to DB';

//     public function handle(): void
//     {
//         $batchSize = 1000;

//         $batch = [];
//         for ($i = 0; $i < $batchSize; $i++) {
//             $item = Redis::lpop('buffer:push_events');
//             if (!$item) break;
//             $batch[] = json_decode($item, true);
//         }

//         if (!$batch) return;

//         $agg = [];
//         foreach ($batch as $e) {
//             $key = "{$e['message_id']}|{$e['event']}";
//             $agg[$key]['message_id'] = $e['message_id'];
//             $agg[$key]['event'] = $e['event'];
//             $agg[$key]['count'] = ($agg[$key]['count'] ?? 0) + 1;
//         }

//         DB::table('push_event_counts')->upsert(
//             array_values($agg),
//             ['message_id', 'event'],
//             ['count' => DB::raw('count + VALUES(count)')]
//         );
//     }
// }

// namespace App\Console\Commands;

// use Illuminate\Console\Command;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Redis;

// class FlushPushAnalytics extends Command
// {
//     protected $signature = 'analytics:flush 
//         {--rounds=5 : How many batches to process per run}
//         {--limit=1000 : Max events to pop per batch}
//         {--chunk=200 : DB upsert chunk size}';

//     protected $description = 'Flush push analytics events from Redis buffer to MySQL (aggregated)';

//     public function handle(): int
//     {
//         $redisKey = 'buffer:push_events';
//         $rounds   = max(1, (int) $this->option('rounds'));
//         $limit    = max(100, (int) $this->option('limit'));   // safe minimum
//         $chunkSz  = max(50, (int) $this->option('chunk'));    // safe minimum

//         try {
//             Redis::ping();
//         } catch (\Throwable $e) {
//             Log::error('analytics:flush Redis unavailable', ['error' => $e->getMessage()]);
//             $this->error('Redis unavailable');
//             return self::FAILURE;
//         }

//         $totalPopped = 0;
//         $totalPairs  = 0;

//         for ($r = 0; $r < $rounds; $r++) {
//             $rawBatch = [];

//             // Pop up to $limit events (LPOP loop; simple & safe)
//             for ($i = 0; $i < $limit; $i++) {
//                 $item = Redis::lpop($redisKey);
//                 if (!$item) break;
//                 $rawBatch[] = $item;
//             }

//             if (empty($rawBatch)) {
//                 break; // nothing left
//             }

//             $totalPopped += count($rawBatch);

//             // Aggregate: (message_id,event) => count
//             $agg = [];

//             foreach ($rawBatch as $raw) {
//                 $e = json_decode($raw, true);

//                 if (!is_array($e) || empty($e['message_id']) || empty($e['event'])) {
//                     continue;
//                 }

//                 $mid = (string) $e['message_id'];
//                 $evt = (string) $e['event'];

//                 // Only allow known events (hard safety)
//                 if (!in_array($evt, ['received', 'click', 'close'], true)) {
//                     continue;
//                 }

//                 $key = $mid . '|' . $evt;

//                 if (!isset($agg[$key])) {
//                     $agg[$key] = [
//                         'message_id' => $mid,
//                         'event'      => $evt,
//                         'count'      => 0,
//                     ];
//                 }

//                 $agg[$key]['count']++;
//             }

//             if (empty($agg)) {
//                 continue;
//             }

//             $rows = array_values($agg);
//             $totalPairs += count($rows);

//             // Atomic upsert increment (requires UNIQUE(message_id,event))
//             foreach (array_chunk($rows, $chunkSz) as $chunk) {
//                 DB::table('push_event_counts')->upsert(
//                     $chunk,
//                     ['message_id', 'event'],
//                     ['count' => DB::raw('count + VALUES(count)')]
//                 );
//             }
//         }

//         $this->info("analytics:flush done. popped={$totalPopped}, unique_pairs={$totalPairs}");

//         return self::SUCCESS;
//     }
// }


namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class FlushPushAnalytics extends Command
{
    protected $signature = 'analytics:flush
        {--rounds=5 : How many batches to process per run}
        {--limit=1000 : Max events to pop per batch}
        {--chunk=200 : DB upsert chunk size}';

    protected $description = 'Flush push analytics events from Redis buffer to MySQL (aggregated)';

    public function handle(): int
    {
        $redisKey = 'buffer:push_events';
        $rounds   = max(1, (int) $this->option('rounds'));
        $limit    = max(100, (int) $this->option('limit'));
        $chunkSz  = max(50, (int) $this->option('chunk'));

        // ---------- Redis health ----------
        try {
            Redis::ping();
        } catch (\Throwable $e) {
            Log::error('analytics:flush Redis unavailable', [
                'error' => $e->getMessage()
            ]);
            $this->error('Redis unavailable');
            return self::FAILURE;
        }

        // ---------- Lua script for atomic batch pop ----------
        $lua = <<<'LUA'
local key = KEYS[1]
local limit = tonumber(ARGV[1])
local items = redis.call('LRANGE', key, 0, limit - 1)
if #items > 0 then
    redis.call('LTRIM', key, limit, -1)
end
return items
LUA;

        $totalPopped = 0;
        $totalPairs  = 0;

        for ($r = 0; $r < $rounds; $r++) {

            // ---------- FAST atomic pop ----------
            $rawBatch = Redis::eval($lua, 1, $redisKey, $limit);

            if (empty($rawBatch)) {
                break; // nothing left
            }

            $totalPopped += count($rawBatch);

            // ---------- Aggregate (message_id + event) ----------
            $agg = [];

            foreach ($rawBatch as $raw) {
                $e = json_decode($raw, true);

                if (
                    !is_array($e) ||
                    empty($e['message_id']) ||
                    empty($e['event'])
                ) {
                    continue;
                }

                $evt = $e['event'];

                // Hard allow-list (safety)
                if (!in_array($evt, ['received', 'click', 'close'], true)) {
                    continue;
                }

                $mid = (string) $e['message_id'];
                $key = $mid . '|' . $evt;

                if (!isset($agg[$key])) {
                    $agg[$key] = [
                        'message_id' => $mid,
                        'event'      => $evt,
                        'count'      => 0,
                    ];
                }

                $agg[$key]['count']++;
            }

            if (empty($agg)) {
                continue;
            }

            $rows = array_values($agg);
            $totalPairs += count($rows);

            // ---------- Atomic DB upsert ----------
            foreach (array_chunk($rows, $chunkSz) as $chunk) {
                DB::table('push_event_counts')->upsert(
                    $chunk,
                    ['message_id', 'event'],
                    ['count' => DB::raw('count + VALUES(count)')]
                );
            }
        }

        $this->info(
            "analytics:flush done. popped={$totalPopped}, unique_pairs={$totalPairs}"
        );

        return self::SUCCESS;
    }
}