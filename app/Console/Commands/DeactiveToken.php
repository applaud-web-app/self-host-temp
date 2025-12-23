<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\Setting;
use Carbon\Carbon;

class DeactiveToken extends Command
{
    protected $signature = 'app:deactive-token';
    protected $description = 'Delete previous-day inactive subscribers (and related data via cascade) in chunks.';

    public function handle(): int
    {
        $startedAt = microtime(true);
        $this->info('Starting daily inactive-subscriber cleanup...');

        try {
            // 1) Feature flag
            if (!Setting::dailyCleanupEnabled()) {
                $this->info('Daily cleanup is OFF. Exiting.');
                return self::SUCCESS;
            }

            $cutoff = Carbon::yesterday()->endOfDay();
            $chunkSize = $this->chunkSize();

            $totalDeleted = 0;

            // 4) Stream IDs and delete in batches (child rows removed via ON DELETE CASCADE)
            DB::table('push_subscriptions_head')
                ->select('id')
                ->where('status', 0)
                ->where('updated_at', '<=', $cutoff)
                ->chunkById($chunkSize, function ($rows) use (&$totalDeleted) {
                    $ids = $rows->pluck('id')->all();
                    if (empty($ids)) {
                        return;
                    }
                    try {
                        // Delete heads only; payload/meta will cascade
                        $deleted = DB::table('push_subscriptions_head')
                            ->whereIn('id', $ids)
                            ->delete();

                        $totalDeleted += $deleted;
                    } catch (Throwable $e) {
                        Log::error('Cleanup chunk failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }, 'id');

            $this->info("Cleanup complete. Deleted rows: {$totalDeleted}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('Daily cleanup failed fatally', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Cleanup failed. Check logs.');
            return self::FAILURE;
        }
    }

    private function chunkSize(): int
    {
        return 1000;
    }
}