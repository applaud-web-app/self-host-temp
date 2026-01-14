<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use Throwable;

class ComputeDomainSubscriptionSummary extends Command
{
    protected $signature = 'stats:domain-subscriptions';
    protected $description = 'Upsert per-domain subscriber snapshot as of yesterday.';

    public function handle(): int
    {
        $this->info('Starting daily subscriber summary update.');
        $started = microtime(true);

        try {
            // Use immutable dates for safety
            $day        = CarbonImmutable::yesterday();        // date we are snapshotting (e.g., 2026-01-13)
            $nextDay    = $day->addDay()->startOfDay();       // 2026-01-14 00:00:00
            $monthStart = $day->startOfMonth();               // 2026-01-01 00:00:00
            $now        = now();

            DB::table('domains')
                ->select('id', 'name')
                ->orderBy('id')
                ->chunkById(500, function ($domains) use ($day, $nextDay, $monthStart, $now) {
                    if ($domains->isEmpty()) return;

                    $namesById = $domains->pluck('name', 'id');
                    $names = $namesById->values()->all();

                    // Single query per chunk with bindings
                    // Get all subscribers created before today (< nextDay start)
                    $stats = DB::table('push_subscriptions_head')->useWritePdo()
                        ->whereIn('parent_origin', $names)
                        ->where('created_at', '<', $nextDay)  // All records up to yesterday
                        ->groupBy('parent_origin')
                        ->selectRaw(
                            'parent_origin,
                            COUNT(*) AS total_c,
                            SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS monthly_c,
                            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS active_c,
                            SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) AS daily_c',
                            [$monthStart, $day->toDateString()]
                        )
                        ->get()
                        ->keyBy('parent_origin');

                    $rows = [];
                    foreach ($namesById as $domainId => $domainName) {
                        $s = $stats->get($domainName);
                        $rows[] = [
                            'domain_id' => $domainId,
                            'stat_date' => $day->toDateString(),
                            'total_subscribers' => $s ? (int) $s->total_c : 0,
                            'monthly_subscribers' => $s ? (int) $s->monthly_c : 0,
                            'daily_subscribers'    => $s ? (int) $s->daily_c : 0, 
                            'active_subscribers' => $s ? (int) $s->active_c : 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    // Upsert once per chunk (unique on [domain_id, stat_date])
                    DB::table('domain_subscription_summaries')->upsert(
                        $rows,
                        ['domain_id', 'stat_date'],
                        ['total_subscribers', 'monthly_subscribers', 'daily_subscribers', 'active_subscribers', 'updated_at']
                    );

                    $this->info("Processed domain ids {$domains->first()->id}–{$domains->last()->id}");
                });

            // Keep only last 30 days of snapshots
            $keepFrom = $day->subDays(29)->toDateString();
            $deleted  = DB::table('domain_subscription_summaries')
                ->where('stat_date', '<', $keepFrom)
                ->delete();

            $this->info("✅ Subscriber summary updated. Cleaned up {$deleted} old records.");
            Log::info('stats:domain-subscriptions OK', [
                'duration_s' => round(microtime(true) - $started, 3),
                'deleted_old_records' => $deleted
            ]);
            return self::SUCCESS;

        } catch (Throwable $e) {
            Log::error('stats:domain-subscriptions failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('❌ Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}