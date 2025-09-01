<?php
// IS ACTIVE JOB

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessAnalyticsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $batchId;
    public $tries   = 5;
    public $backoff = [1, 5, 15, 60, 120]; 

    public function __construct(string $batchId)
    {
        $this->batchId = $batchId;
    }

    public function handle(): void
    {
        $redisKey = "analytics_batch:{$this->batchId}";
        $raw = Redis::get($redisKey);

        if (!$raw) {
            Log::warning('Analytics batch missing in Redis', ['batch_id' => $this->batchId]);
            return;
        }

        $events = json_decode($raw, true);
        if (!is_array($events)) {
            Log::warning('Analytics batch invalid JSON', ['batch_id' => $this->batchId]);
            return;
        }

        $agg = [];
        foreach ($events as $e) {
            $mid   = (string)($e['message_id'] ?? '');
            $event = (string)($e['event'] ?? '');
            if ($mid === '' || $event === '') continue;

            $key = $mid.'|'.$event;
            if (!isset($agg[$key])) {
                $agg[$key] = ['message_id' => $mid, 'event' => $event, 'count' => 0];
            }
            $agg[$key]['count']++;
        }

        if (empty($agg)) {
            Redis::del($redisKey);
            return;
        }

        foreach (array_chunk(array_values($agg), 500) as $chunk) {
            $this->upsertIncrementCounts($chunk);
        }

        Redis::del($redisKey);
        Log::info('Analytics batch processed', [
            'batch_id'     => $this->batchId,
            'unique_pairs' => count($agg),
            'raw_events'   => count($events),
        ]);
    }

    protected function upsertIncrementCounts(array $rows): void
    {
        DB::table('push_event_counts')->upsert(
            $rows,
            ['message_id', 'event'], 
            ['count' => DB::raw('`count` + VALUES(`count`)')]
        );
    }
}