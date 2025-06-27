<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Domain;
use App\Models\DomainSubscriptionSummary;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;

class ComputeDomainSubscriptionSummary extends Command
{
    protected $signature = 'stats:domain-subscriptions';
    protected $description = 'Update per-domain subscriber summary for yesterday.';

    public function handle()
    {
        $this->info('Starting daily subscriber summary update...');

        try {
            $date = Carbon::yesterday();
            $yesterday    = $date->toDateString();
            $resetMonthly = $date->day === 1;
            $yestrdayData = [];

            try {
                $yestrdayData = $this->previousDayCount($date);
            } catch (\Throwable $th) {
                $yestrdayData = [];
            }

            // Preload new-subscriber counts by domain for yesterday
            $counts = DB::table('push_subscriptions_head')
                ->select('domain as domain_name', DB::raw('COUNT(*) as cnt'))
                ->whereDate('created_at', $yesterday)
                ->groupBy('domain')
                ->pluck('cnt', 'domain_name');

            Domain::chunk(50, function ($domains) use ($counts, $yesterday, $resetMonthly) {
                foreach ($domains as $domain) {

                    $dailyNew = (int) ($counts[$domain->name] ?? 0);
                    
                    try {
                        DB::transaction(function () use ($domain, $counts, $dailyNew, $yesterday, $resetMonthly) {

                            // Load or create the one summary row
                            $summary = DomainSubscriptionSummary::firstOrNew([
                                'domain_id' => $domain->id,
                            ]);

                            // Update totals
                            if ($summary->exists) {
                                $summary->total_subscribers   += $dailyNew;
                                $summary->monthly_subscribers = $resetMonthly
                                                            ? $dailyNew
                                                            : $summary->monthly_subscribers + $dailyNew;
                            } else {
                                $summary->total_subscribers   = $dailyNew;
                                $summary->monthly_subscribers = $dailyNew;
                            }

                            $summary->stat_date = $yesterday;
                            $summary->save();
                        }, $attempts = 3);

                        $this->info("Processed {$domain->name}: +{$dailyNew} subscriptions");
                    } catch (QueryException $e) {
                        if ($e->getCode() === '40001') {
                            // Deadlock detected, log and skip
                            Log::warning("Deadlock for domain {$domain->id}, skipping: {$e->getMessage()}");
                            continue;
                        }
                        throw $e;
                    }

                    // Release memory from Eloquent
                    $domain->unsetRelations();
                }

                // Force garbage collection to free memory between chunks
                gc_collect_cycles();
            });

            $this->info('✅ Subscriber summary updated.');
            return 0;
        }catch (\Exception $e) {
            // Log full stack for debugging
            Log::error('DomainSubscriptionSummary failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $this->error("❌ Failed: " . $e->getMessage());
            return 1;
        }
    }

    // private function previousDayCount($date)
    // {
    //     try {

    //         $yesterdayCount = Http::timeout(5)->post('YOUR_API_URL_HERE', [
    //             'x1y2z3' => request()->getHost(),  // Using the domain name from the request
    //             'x4y5z6' => base64_encode('SOME_SECRET_KEY')  // Encoding some value as in your JavaScript example
    //         ]);

    //         $x10y11z12 = $x8y9z0->json();

    //         return $x10y11z12 ?? ['x13y14z15' => 1]; // Default to status 1 if no response or error
    //     } catch (\Exception $x16y17z18) {
    //         Log::error('x19y20z21: ' . $x16y17z18->getMessage());
    //         return ['x13y14z15' => 1]; // Return a default status if there was an error
    //     }
    // }

}