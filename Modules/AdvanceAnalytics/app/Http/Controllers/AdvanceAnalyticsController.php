<?php

namespace Modules\AdvanceAnalytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Domain;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdvanceAnalyticsController extends Controller
{
    public function performance(){
        return view('advanceanalytics::performance');
    }

    public function fetch(Request $request)
    {
        // -------- 1) Validate --------
        $request->validate([
            'domain' => ['required', 'integer'],  // domain_id from select2
            'range'  => ['nullable', 'in:24h,7d,28d,3m,more'],
            'months' => ['nullable', 'integer', 'in:6,12,18,24'],
            'start'  => ['nullable', 'date'],
            'end'    => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $domainId = (int) $request->get('domain');
        $range    = (string) $request->get('range', '24h');

        // -------- 2) Compute window (based on NOTIFICATION created_at) --------
        [$startAt, $endAt, $granularity] = $this->computeWindow($range, $request);

        // Cache key: domain+exact window+granularity
        $cacheKey = sprintf(
            'adv_analytics:v3:domain:%d:start:%s:end:%s:gran:%s',
            $domainId,
            $startAt->timestamp,
            $endAt->timestamp,
            $granularity
        );

        // remember for 30 minutes
        $payload = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($domainId, $startAt, $endAt, $granularity) {

            // Early existence check to avoid heavy joins if no notifications in window
            $hasAny = DB::table('notifications AS n')
                ->where('n.domain_id', $domainId)
                ->whereBetween('n.created_at', [$startAt, $endAt])
                ->limit(1)
                ->exists();

            if (!$hasAny) {
                $empty = $this->emptyPayload($startAt, $endAt, $granularity);
                $empty['meta'] = [
                    'cached_at'   => now()->toIso8601String(),
                    'start_at'    => $startAt->toIso8601String(),
                    'end_at'      => $endAt->toIso8601String(),
                    'granularity' => $granularity,
                ];
                return $empty;
            }

            // -------- Pivot events once (one row per message_id) --------
            // Since push_event_counts has exactly 2 rows per message_id (click/received),
            // we can pivot with MAX(...) safely.
            $pcSub = DB::raw("
                (
                    SELECT
                        message_id,
                        MAX(CASE WHEN event='click' THEN `count` ELSE 0 END) AS clicks,
                        MAX(CASE WHEN event='received' THEN `count` ELSE 0 END) AS impressions
                    FROM push_event_counts
                    GROUP BY message_id
                ) AS pc
            ");

            // -------- Bucket SQL --------
            $bucketSql = $granularity === 'hour'
                ? "DATE_FORMAT(n.created_at, '%Y-%m-%d %H:00:00')"
                : "DATE_FORMAT(n.created_at, '%Y-%m-%d')";

            // -------- 4) KPI Totals (single pass on pivot) --------
            $totals = DB::table('notifications AS n')
                ->join($pcSub, 'pc.message_id', '=', 'n.message_id')
                ->where('n.domain_id', $domainId)
                ->where('n.status', 'sent')
                ->whereBetween('n.created_at', [$startAt, $endAt])
                ->selectRaw('SUM(pc.clicks) AS clicks, SUM(pc.impressions) AS impressions')
                ->first();

            $totalClicks = (int) ($totals->clicks ?? 0);
            $totalImps   = (int) ($totals->impressions ?? 0);
            $ctr         = $totalImps > 0 ? round(($totalClicks / $totalImps) * 100, 4) : 0.0;

            // -------- 5) Time Series (group by bucket on pivot) --------
            $seriesRows = DB::table('notifications AS n')
                ->join($pcSub, 'pc.message_id', '=', 'n.message_id')
                ->where('n.domain_id', $domainId)
                ->whereBetween('n.created_at', [$startAt, $endAt])
                ->selectRaw("$bucketSql AS bucket")
                ->selectRaw('SUM(pc.clicks) AS clicks, SUM(pc.impressions) AS impressions')
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get();

            [$labels, $seriesClicks, $seriesImps] = $this->buildSeries($seriesRows, $startAt, $endAt, $granularity);

            // -------- 6) Pages table (group by target_url on pivot) with pagination --------
            $pages = DB::table('notifications AS n')
                ->join($pcSub, 'pc.message_id', '=', 'n.message_id')
                ->where('n.domain_id', $domainId)
                ->whereBetween('n.created_at', [$startAt, $endAt])
                ->groupBy('n.target_url')
                ->selectRaw('n.target_url AS page')
                ->selectRaw('SUM(pc.clicks) AS clicks, SUM(pc.impressions) AS impressions')
                ->orderByRaw('(SUM(pc.clicks) + SUM(pc.impressions)) DESC')
                ->paginate(150);  // Pagination added for performance

            return [
                'kpis' => [
                    'clicks'      => $totalClicks,
                    'impressions' => $totalImps,
                    'ctr'         => $ctr,
                ],
                'series' => [
                    'labels'      => $labels,
                    'clicks'      => $seriesClicks,
                    'impressions' => $seriesImps,
                ],
                'tables' => [
                    'pages'       => $pages,
                ],
                'meta' => [
                    'cached_at'   => now()->toIso8601String(),
                    'start_at'    => $startAt->toIso8601String(),
                    'end_at'      => $endAt->toIso8601String(),
                    'granularity' => $granularity,
                ],
            ];
        });

        return response()->json($payload);
    }

    private function computeWindow(string $range, Request $request): array
    {
        $now = Carbon::now(); // server tz
        switch ($range) {
            case '24h':
                return [$now->copy()->subHours(24)->startOfHour(), $now->copy()->endOfHour(), 'hour'];
            case '7d':
                return [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay(), 'day'];
            case '28d':
                return [$now->copy()->subDays(28)->startOfDay(), $now->copy()->endOfDay(), 'day'];
            case '3m':
                return [$now->copy()->subMonths(3)->startOfDay(), $now->copy()->endOfDay(), 'day'];
            case 'more':
                $months = (int) $request->get('months', 0);
                if (in_array($months, [6, 12, 18, 24], true)) {
                    return [$now->copy()->subMonths($months)->startOfDay(), $now->copy()->endOfDay(), 'day'];
                }
                /** @var Carbon|null $start */
                $start = $request->date('start', null, null);
                /** @var Carbon|null $end */
                $end   = $request->date('end', null, null);
                if ($start && $end) {
                    $gran = $start->diffInDays($end) > 2 ? 'day' : 'hour';
                    return [
                        $gran === 'hour' ? $start->copy()->startOfHour() : $start->copy()->startOfDay(),
                        $gran === 'hour' ? $end->copy()->endOfHour()   : $end->copy()->endOfDay(),
                        $gran
                    ];
                }
                // Fallback
                return [$now->copy()->subDays(28)->startOfDay(), $now->copy()->endOfDay(), 'day'];
            default:
                return [$now->copy()->subHours(24)->startOfHour(), $now->copy()->endOfHour(), 'hour'];
        }
    }

    private function buildSeries($aggRows, Carbon $start, Carbon $end, string $granularity): array
    {
        // index by bucket string
        $index = collect($aggRows)->keyBy(function ($r) {
            return $r->bucket;
        });

        $labels = [];
        $clicks = [];
        $imps   = [];

        $cursor = $start->copy();
        while ($cursor <= $end) {
            if ($granularity === 'hour') {
                $bucketKey = $cursor->format('Y-m-d H:00:00');
                $labels[]  = $cursor->format('M d, H:i');
                $cursor->addHour();
            } else { // day
                $bucketKey = $cursor->format('Y-m-d');
                $labels[]  = $cursor->format('M d');
                $cursor->addDay();
            }

            $row = $index->get($bucketKey);
            $clicks[] = $row ? (int) $row->clicks : 0;
            $imps[]   = $row ? (int) $row->impressions : 0;
        }

        // If hourly and we overshoot by 1 bucket due to inclusive <=, trim last
        if ($granularity === 'hour' && count($labels)) {
            array_pop($labels);
            array_pop($clicks);
            array_pop($imps);
        }

        return [$labels, $clicks, $imps];
    }

    private function emptyPayload(Carbon $start, Carbon $end, string $granularity): array
    {
        $labels = [];
        $clicks = [];
        $imps   = [];

        $cursor = $start->copy();
        while ($cursor <= $end) {
            if ($granularity === 'hour') {
                $labels[] = $cursor->format('M d, H:i');
                $cursor->addHour();
            } else {
                $labels[] = $cursor->format('M d');
                $cursor->addDay();
            }
            $clicks[] = 0;
            $imps[]   = 0;
        }

        if ($granularity === 'hour' && count($labels)) {
            array_pop($labels);
            array_pop($clicks);
            array_pop($imps);
        }

        return [
            'kpis'   => ['clicks' => 0, 'impressions' => 0, 'ctr' => 0],
            'series' => ['labels' => $labels, 'clicks' => $clicks, 'impressions' => $imps],
            'tables' => ['pages'  => []],
        ];
    }

    // performance
    public function subscribers(Request $request)
    {
        return view('advanceanalytics::subscribers');
    }

    private function previousWindow(Carbon $startAt, Carbon $endAt): array
    {
        $diff = $startAt->diffAsCarbonInterval($endAt);
        $prevEnd = $startAt->copy();
        $prevStart = $startAt->copy()->sub($diff);
        return [$prevStart, $prevEnd];
    }

    public function subscribersFetch(Request $request)
    {
        $request->validate([
            'origin' => ['required', 'string'],
            'range'  => ['nullable', 'in:24h,7d,28d,3m,more'],
            'months' => ['nullable', 'integer', 'in:6,12,18,24'],
            'start'  => ['nullable', 'date'],
            'end'    => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $origin = (string) $request->get('origin');
        $range  = (string) $request->get('range', '24h');

        // 1) Adaptive window + granularity (hour|day|week|month)
        [$startAt, $endAt, $granularity] = $this->computeWindowSub($range, $request);

        // 2) Cache key
        $cacheKey = sprintf(
            'adv_subs:v2:origin:%s:start:%s:end:%s:gran:%s',
            md5($origin),
            $startAt->timestamp,
            $endAt->timestamp,
            $granularity
        );

        $payload = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($origin, $startAt, $endAt, $granularity) {

            // 3) Early existence check
            $hasAny = DB::table('push_subscriptions_head AS h')
                ->where('h.parent_origin', $origin)
                ->whereBetween('h.created_at', [$startAt, $endAt])
                ->limit(1)
                ->exists();

            if (!$hasAny) {
                $empty = $this->emptyPayloadSubs($startAt, $endAt, $granularity);
                $empty['meta'] = [
                    'cached_at'   => now()->toIso8601String(),
                    'start_at'    => $startAt->toIso8601String(),
                    'end_at'      => $endAt->toIso8601String(),
                    'granularity' => $granularity,
                    'origin'      => $origin,
                ];
                return $empty;
            }

            // 4) Bucket SQL (hour/day/week/month)
            switch ($granularity) {
                case 'hour':
                    $bucketSql = "DATE_FORMAT(h.created_at, '%Y-%m-%d %H:00:00')";
                    break;
                case 'day':
                    $bucketSql = "DATE_FORMAT(h.created_at, '%Y-%m-%d')";
                    break;
                case 'week':
                    // Monday-start ISO week; store as week start date
                    $bucketSql = "DATE_FORMAT(DATE_SUB(h.created_at, INTERVAL (WEEKDAY(h.created_at)) DAY), '%Y-%m-%d')";
                    break;
                default: // month
                    $bucketSql = "DATE_FORMAT(h.created_at, '%Y-%m-01')";
            }

            // 5) KPI totals for selected window (one scan)
            $totals = DB::table('push_subscriptions_head AS h')
                ->where('h.parent_origin', $origin)
                ->whereBetween('h.created_at', [$startAt, $endAt])
                ->selectRaw('COUNT(*) AS all_subs')
                ->selectRaw('SUM(h.status = 1) AS active')
                ->selectRaw('SUM(h.status <> 1) AS inactive')
                ->first();

            $allInWin   = (int) ($totals->all_subs ?? 0);
            $activeWin  = (int) ($totals->active ?? 0);
            $inactiveWin= (int) ($totals->inactive ?? 0);

            // 6) Overall active (lifetime for this origin) — optional; you didn’t need it anymore for KPIs,
            // but we’ll keep it in meta in case UI needs it elsewhere.
            $overallActive = (int) DB::table('push_subscriptions_head AS h')
                ->where('h.parent_origin', $origin)
                ->where('h.status', 1)
                ->count();

            // 7) Previous window for growth vs *all subscribers* curve (same width)
            [$prevStart, $prevEnd] = $this->previousWindow($startAt, $endAt);
            $prevAll = (int) DB::table('push_subscriptions_head AS h')
                ->where('h.parent_origin', $origin)
                ->whereBetween('h.created_at', [$prevStart, $prevEnd])
                ->count();

            $growthPct = $prevAll > 0 ? round((($allInWin - $prevAll) / $prevAll) * 100, 2) : null;

            // 8) Time series (one grouped query with 3 measures)
            $seriesRows = DB::table('push_subscriptions_head AS h')
                ->where('h.parent_origin', $origin)
                ->whereBetween('h.created_at', [$startAt, $endAt])
                ->selectRaw("$bucketSql AS bucket")
                ->selectRaw('COUNT(*) AS all_subs')
                ->selectRaw('SUM(h.status = 1) AS active')
                ->selectRaw('SUM(h.status <> 1) AS inactive')
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get();

            [$labels, $allSeries, $activeSeries, $inactiveSeries] =
                $this->buildSeriesSubs($seriesRows, $startAt, $endAt, $granularity);

            // 9) Base for breakdowns (kept, but can be toggled off if you don’t need them for speed)
            $base = DB::table('push_subscriptions_head AS h')
                ->leftJoin('push_subscriptions_meta AS m', 'm.head_id', '=', 'h.id')
                ->where('h.parent_origin', $origin)
                ->whereBetween('h.created_at', [$startAt, $endAt]);

            $byDevice = (clone $base)
                ->selectRaw("COALESCE(NULLIF(TRIM(m.device), ''), 'Unknown') AS device, COUNT(*) AS subs")
                ->groupBy('device')->orderByDesc('subs')->limit(50)->get();

            $byBrowser = (clone $base)
                ->selectRaw("COALESCE(NULLIF(TRIM(m.browser), ''), 'Unknown') AS browser, COUNT(*) AS subs")
                ->groupBy('browser')->orderByDesc('subs')->limit(50)->get();

            $byCountry = (clone $base)
                ->selectRaw("COALESCE(NULLIF(TRIM(m.country), ''), 'Unknown') AS country, COUNT(*) AS subs")
                ->groupBy('country')->orderByDesc('subs')->limit(200)->get();

            $byState = (clone $base)
                ->selectRaw("COALESCE(NULLIF(TRIM(m.country), ''), 'Unknown') AS country,
                            COALESCE(NULLIF(TRIM(m.state), ''), 'Unknown')   AS state,
                            COUNT(*) AS subs")
                ->groupBy('country','state')->orderByDesc('subs')->limit(200)->get();

            $byCity = (clone $base)
                ->selectRaw("COALESCE(NULLIF(TRIM(m.country), ''), 'Unknown') AS country,
                            COALESCE(NULLIF(TRIM(m.state), ''), 'Unknown')   AS state,
                            COALESCE(NULLIF(TRIM(m.city), ''), 'Unknown')     AS city,
                            COUNT(*) AS subs")
                ->groupBy('country','state','city')->orderByDesc('subs')->limit(200)->get();

            $byUrl = (clone $base)
                ->selectRaw("COALESCE(NULLIF(TRIM(m.subscribed_url), ''), 'Unknown') AS url, COUNT(*) AS subs")
                ->groupBy('url')->orderByDesc('subs')->limit(200)->get();

            return [
                'kpis' => [
                    // renamed to match the UI ask
                    'all_in_window'      => $allInWin,
                    'active_in_window'   => $activeWin,
                    'inactive_in_window' => $inactiveWin,
                    'growth_pct'         => $growthPct,
                ],
                'series' => [
                    'labels'  => $labels,
                    'all'     => $allSeries,
                    'active'  => $activeSeries,
                    'inactive'=> $inactiveSeries,
                ],
                'breakdowns' => [
                    'country'  => $byCountry,
                    'state'    => $byState,
                    'city'     => $byCity,
                    'device'   => $byDevice,
                    'browser'  => $byBrowser,
                    'platform' => [], // deprecated here; remove from blade if unused
                    'url'      => $byUrl,
                ],
                'meta' => [
                    'cached_at'   => now()->toIso8601String(),
                    'start_at'    => $startAt->toIso8601String(),
                    'end_at'      => $endAt->toIso8601String(),
                    'granularity' => $granularity,
                    'origin'      => $origin,
                    'prev_start'  => $prevStart->toIso8601String(),
                    'prev_end'    => $prevEnd->toIso8601String(),
                    'overall_active' => $overallActive,
                ],
            ];
        });

        return response()->json($payload);
    }

    private function computeWindowSub(string $range, Request $request): array
    {
        $now = Carbon::now();
        $start = null; $end = null;

        switch ($range) {
            case '24h': $start = $now->copy()->subHours(24)->startOfHour(); $end = $now->copy()->endOfHour(); break;
            case '7d' : $start = $now->copy()->subDays(7)->startOfDay();    $end = $now->copy()->endOfDay();   break;
            case '28d': $start = $now->copy()->subDays(28)->startOfDay();   $end = $now->copy()->endOfDay();   break;
            case '3m' : $start = $now->copy()->subMonths(3)->startOfDay();  $end = $now->copy()->endOfDay();   break;
            case 'more':
                $months = (int) $request->get('months', 0);
                if (in_array($months, [6,12,18,24], true)) {
                    $start = $now->copy()->subMonths($months)->startOfDay(); $end = $now->copy()->endOfDay();
                } else {
                    /** @var Carbon|null $s */ $s = $request->date('start', null, null);
                    /** @var Carbon|null $e */ $e = $request->date('end', null, null);
                    if ($s && $e) { $start = $s; $end = $e; }
                }
                if (!$start || !$end) { $start = $now->copy()->subDays(28)->startOfDay(); $end = $now->copy()->endOfDay(); }
                break;
            default:
                $start = $now->copy()->subHours(24)->startOfHour(); $end = $now->copy()->endOfHour();
        }

        $diffDays = max(1, $start->diffInDays($end));
        $gran = $diffDays <= 2 ? 'hour' : ($diffDays <= 30 ? 'day' : ($diffDays <= 180 ? 'week' : 'month'));

        // snap boundaries to bucket edges
        if ($gran === 'hour') { $start = $start->copy()->startOfHour(); $end = $end->copy()->endOfHour(); }
        elseif ($gran === 'day') { $start = $start->copy()->startOfDay(); $end = $end->copy()->endOfDay(); }
        elseif ($gran === 'week') {
            $start = $start->copy()->startOfWeek(); $end = $end->copy()->endOfWeek();
        } else { // month
            $start = $start->copy()->startOfMonth(); $end = $end->copy()->endOfMonth();
        }

        return [$start, $end, $gran];
    }

    private function buildSeriesSubs($rows, Carbon $start, Carbon $end, string $granularity): array
    {
        // Build index for quick lookup
        $index = collect($rows)->keyBy(fn($r) => $r->bucket);
        $labels = [];
        $all = [];
        $active = [];
        $inactive = [];

        $cursor = $start->copy();

        while ($cursor <= $end) {

            // Format the labels for each data point
            if ($granularity === 'hour') {
                $bucketKey = $cursor->format('Y-m-d H:00:00'); // Simplified to just day-based granularity
                $labels[] = $cursor->format('M d, H:i'); // Hour granularity (used for short periods)
            } else {
                $bucketKey = $cursor->format('Y-m-d'); // Simplified to just day-based granularity
                $labels[] = $cursor->format('M d, Y'); // Day-based granularity (will handle >30 days gracefully)
            }

            // Lookup the row for the current bucket
            /** @var object|null $row */
            $row = $index->get($bucketKey);
            $all[] = $row ? (int) $row->all_subs : 0;
            $active[] = $row ? (int) $row->active : 0;
            $inactive[] = $row ? (int) $row->inactive : 0;

            // Move cursor based on granularity
            if ($granularity === 'hour') {
                $cursor->addHour();
            } else {
                $cursor->addDay(); // Increase by one day
            }
        }

        // For hour-based granularity, adjust the last date (remove last hour if unnecessary)
        if ($granularity === 'hour' && count($labels)) {
            array_pop($labels);
            array_pop($all);
            array_pop($active);
            array_pop($inactive);
        }

        return [$labels, $all, $active, $inactive];
    }

    private function emptyPayloadSubs(Carbon $start, Carbon $end, string $granularity): array
    {
        $labels = []; $all=[]; $active=[]; $inactive=[];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            if ($granularity === 'hour') { $labels[]=$cursor->format('M d, H:i'); $cursor->addHour(); }
            elseif ($granularity === 'day') { $labels[]=$cursor->format('M d'); $cursor->addDay(); }
            elseif ($granularity === 'week') { $labels[]='Wk of '.$cursor->copy()->startOfWeek()->format('M d'); $cursor->addWeek(); }
            else { $labels[]=$cursor->format('M Y'); $cursor->addMonth(); }
            $all[] = $active[] = $inactive[] = 0;
        }

        if ($granularity === 'hour' && count($labels)) { array_pop($labels); array_pop($all); array_pop($active); array_pop($inactive); }

        return [
            'kpis' => [
                'all_in_window'      => 0,
                'active_in_window'   => 0,
                'inactive_in_window' => 0,
                'growth_pct'         => null,
            ],
            'series' => [
                'labels'  => $labels,
                'all'     => $all,
                'active'  => $active,
                'inactive'=> $inactive,
            ],
            'breakdowns' => [
                'country'=>[], 'state'=>[], 'city'=>[], 'device'=>[], 'browser'=>[], 'platform'=>[], 'url'=>[],
            ],
        ];
    }


}
