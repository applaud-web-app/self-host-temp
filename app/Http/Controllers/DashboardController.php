<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Notification;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard(){
       $initialDomain = Domain::value('name');
       return view('dashboard',compact('initialDomain'));
    }

    // public function getDomainStats(Request $request)
    // {
    //     $user       = $request->user();
    //     $domainName = $request->query('domain_name');
    //     $refresh    = $request->boolean('refresh', false);

    //     $dq = Domain::orderBy('id');
    //     if ($domainName) {
    //         $dq->where('name', $domainName);
    //     }
    //     $domain = $dq->first();

    //     if (! $domain) {
    //         return response()->json([
    //             'status' => true,
    //             'data'   => [
    //                 'total'     => 0,
    //                 'active'    => 0,
    //                 'monthly'   => 0,
    //                 'today'     => 0,
    //             ],
    //         ]);
    //     }

    //     $cacheKey = "domain_stats:{$domain->name}";
    //     if ($refresh) {
    //         Cache::forget($cacheKey);
    //     }

    //     $stats = Cache::remember($cacheKey, now()->addMinutes(5), function() use($domain) {
    //         return DB::table('push_subscriptions_head')
    //             ->where('parent_origin', $domain->name)
    //             ->selectRaw('COUNT(*) AS total')
    //             ->selectRaw("SUM(CASE WHEN status='1'   THEN 1 ELSE 0 END) AS active")
    //             ->selectRaw("SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS monthly", [ now()->startOfMonth() ])
    //             ->selectRaw("SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS today",   [ now()->startOfDay()   ])
    //             ->first();
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'data'   => array_map('intval', (array)$stats),
    //     ]);
    // }

    public function getDomainStats(Request $request)
    {
        $user       = $request->user();
        $domainName = $request->query('domain_name');
        $refresh    = $request->boolean('refresh', false);

        $dq = Domain::orderBy('id');
        if ($domainName) {
            $dq->where('name', $domainName);
        }
        $domain = $dq->first();

        if (! $domain) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'total'   => 0,
                    'active'  => 0,
                    'monthly' => 0,
                    'today'   => 0,
                ],
            ]);
        }

        $cacheKey = "domain_stats:{$domain->name}";
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($domain) {
            $now          = Carbon::now();
            $todayStart   = $now->copy()->startOfDay();
            $monthStart   = $now->copy()->startOfMonth();
            $yesterdayStr = $now->copy()->subDay()->toDateString();

            // 1) Get snapshot row (prefer yesterday; otherwise latest available)
            $snapshot = DB::table('domain_subscription_summaries')
                ->where('domain_id', $domain->id)
                ->where('stat_date', $yesterdayStr)
                ->first();

            if (! $snapshot) {
                $snapshot = DB::table('domain_subscription_summaries')
                    ->where('domain_id', $domain->id)
                    ->orderByDesc('stat_date')
                    ->first();
            }

            // If no snapshot exists yet, fallback to direct (one-time) full count
            if (! $snapshot) {
                return DB::table('push_subscriptions_head')
                    ->where('parent_origin', $domain->name)
                    ->selectRaw('COUNT(*) AS total')
                    ->selectRaw("SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS active")
                    ->selectRaw("SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS monthly", [$monthStart])
                    ->selectRaw("SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS today",   [$todayStart])
                    ->first();
            }

            // 2) Base values from snapshot
            $snapDate        = Carbon::parse($snapshot->stat_date);
            $baseTotal       = (int) $snapshot->total_subscribers;
            $baseActive      = (int) $snapshot->active_subscribers;
            $baseMonthly     = $snapDate->isSameMonth($now) ? (int) $snapshot->monthly_subscribers : 0;
            $deltaStart      = $snapDate->copy()->addDay()->startOfDay();
            $monthlyIncStart = $deltaStart->greaterThan($monthStart) ? $deltaStart : $monthStart;

            // 3) Incremental counts since snapshot
            $delta = DB::table('push_subscriptions_head')
                ->where('parent_origin', $domain->name)
                ->where('created_at', '>=', $deltaStart)
                ->selectRaw('COUNT(*) AS total_inc')
                ->selectRaw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS active_inc')
                ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS monthly_inc', [$monthlyIncStart])
                ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS today',       [$todayStart])
                ->first();

            // 4) Combine snapshot + increments
            return (object) [
                'total'   => $baseTotal  + (int) ($delta->total_inc   ?? 0),
                'active'  => $baseActive + (int) ($delta->active_inc  ?? 0),
                'monthly' => $baseMonthly+ (int) ($delta->monthly_inc ?? 0),
                'today'   => (int) ($delta->today ?? 0),
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => array_map('intval', (array) $stats),
        ]);
    }

    public function getNotificationStats(Request $request)
    {
        $domainName = $request->get('domain_name');
        $refresh    = $request->get('refresh', 0);

        $domain = Domain::where('name', $domainName)->first();

        if (! $domain) {
            return response()->json([
               'status' => true,
                'data'   => [
                    'total'     => 0,
                    'broadcast' => 0,
                    'segment'   => 0,
                    'plugin'    => 0,
                ],
            ]);
        }

        $stats = Notification::query()
            ->where('domain_id', $domain->id)
            ->selectRaw('
                count(*) as total,
                sum(case when segment_type = "all" then 1 else 0 end) as broadcast,
                sum(case when segment_type = "particular" then 1 else 0 end) as segment,
                sum(case when segment_type = "api" then 1 else 0 end) as plugin
            ')
            ->first();

        return response()->json([
            'status' => true,
            'data'   => [
                'total'     => (int)$stats->total,
                'broadcast' => (int)$stats->broadcast,
                'segment'   => (int)$stats->segment,
                'plugin'    => (int)$stats->plugin,
            ],
        ]);
    }

    public function getWeeklyStats(Request $request)
    {
        $user       = $request->user();
        $domainName = $request->query('domain_name');
        $refresh    = $request->boolean('refresh', false);
        $metric     = $request->query('metric');

        $dq = Domain::orderBy('created_at');
        if ($domainName) {
            $dq->where('name', $domainName);
        }
        $domain = $dq->first();

        // Window: previous 7 full days (yesterday-6 ... yesterday), excludes today
        $end   = Carbon::yesterday()->endOfDay();
        $start = $end->copy()->subDays(6)->startOfDay();

        // Build ordered day list for labels/series
        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->toDateString(); // 'Y-m-d'
            $cursor->addDay();
        }
        $labels = array_map(fn($d) => Carbon::parse($d)->format('D'), $days);
        $zeros  = array_fill(0, 7, 0);

        if (!$domain) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'labels'        => $labels,
                    'subscribers'   => $zeros,
                    'notifications' => $zeros,
                ],
            ]);
        }

        $subKey = "weekly_sub:{$domain->id}";
        $notKey = "weekly_notif:{$domain->id}";

        // selective cache bust
        if ($refresh) {
            if ($metric === 'subscribers') {
                Cache::forget($subKey);
            } elseif ($metric === 'notifications') {
                Cache::forget($notKey);
            } else {
                Cache::forget($subKey);
                Cache::forget($notKey);
            }
        }

        // ---- Subscribers from precomputed snapshots (daily_subscribers) ----
        $subsSeries = Cache::remember($subKey, now()->addMinutes(5), function () use ($domain, $start, $end, $days) {
            $map = DB::table('domain_subscription_summaries')
                ->where('domain_id', $domain->id)
                ->whereBetween('stat_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('stat_date')
                ->pluck('daily_subscribers', 'stat_date')   // requires the new column
                ->toArray();

            // Align to ordered $days; default 0 if a snapshot is missing
            return array_map(fn($d) => (int)($map[$d] ?? 0), $days);
        });

        // ---- Notifications (previous 7 days, exclude today) ----
        $notSeries = Cache::remember($notKey, now()->addMinutes(5), function () use ($domain, $start, $end, $days) {
            $map = Notification::query()
                ->where('domain_id', $domain->id)
                ->whereBetween('sent_at', [$start, $end])
                ->selectRaw('DATE(sent_at) AS day, COUNT(*) AS cnt')
                ->groupBy('day')
                ->pluck('cnt', 'day')
                ->toArray();

            return array_map(fn($d) => (int)($map[$d] ?? 0), $days);
        });

        return response()->json([
            'status' => true,
            'data'   => [
                'labels'        => $labels,
                'subscribers'   => $subsSeries,
                'notifications' => $notSeries,
            ],
        ]);
    }

    
}