<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAnalyticsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    protected array $batch;

    public function __construct(array $batch)
    {
        $this->batch = $batch;
    }

    public function handle(): void
    {
        try {
            // Group events by unique key and count
            $counts = [];
            
            foreach ($this->batch as $raw) {
                $data = json_decode($raw, true);
                
                if (!isset($data['message_id'], $data['event'], $data['domain'])) {
                    Log::warning('Invalid analytics data', ['raw' => $raw]);
                    continue;
                }

                $uniqueKey = "{$data['event']}|{$data['message_id']}|{$data['domain']}";
                $counts[$uniqueKey] = ($counts[$uniqueKey] ?? 0) + 1;
            }

            // Process in database transaction
            DB::transaction(function () use ($counts) {
                foreach ($counts as $uniqueKey => $count) {
                    [$event, $messageId, $domain] = explode('|', $uniqueKey, 3);
                    
                    DB::table('push_event_counts')->updateOrInsert(
                        [
                            'message_id' => $messageId,
                            'event' => $event,
                            'domain' => $domain
                        ],
                        ['count' => DB::raw("count + {$count}")]
                    );
                }
            });

            Log::info('Analytics batch processed', [
                'batch_size' => count($this->batch),
                'unique_events' => count($counts)
            ]);

        } catch (\Throwable $e) {
            Log::error('Analytics batch processing failed', [
                'batch_size' => count($this->batch),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Analytics batch job permanently failed', [
            'batch_size' => count($this->batch),
            'error' => $exception->getMessage()
        ]);
    }
}