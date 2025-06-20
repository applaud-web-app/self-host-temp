<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\DomainNotification;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard(){
       $initialDomain = Domain::value('name');
       return view('dashboard',compact('initialDomain'));
    }

    public function getDomainStats(Request $request)
    {
        $user       = $request->user();
        $domainName = $request->query('domain_name');
        $refresh    = $request->boolean('refresh', false);

        // Pick selected or first-added domain
        $dq = Domain::orderBy('created_at');
        if ($domainName) {
            $dq->where('name', $domainName);
        }
        $domain = $dq->first();

        if (! $domain) {
            // no domains → zero everything
            return response()->json([
                'status' => true,
                'data'   => [
                    'total'     => 0,
                    'active'    => 0,
                    'monthly'   => 0,
                    'today'     => 0,
                ],
            ]);
        }

        $cacheKey = "domain_stats:{$domain->name}";
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function() use($domain) {
            return DB::table('push_subscriptions_head')
                ->where('domain', $domain->name)
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw("SUM(CASE WHEN status='1'   THEN 1 ELSE 0 END) AS active")
                ->selectRaw("SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS monthly", [ now()->startOfMonth() ])
                ->selectRaw("SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS today",   [ now()->startOfDay()   ])
                ->first();
        });

        return response()->json([
            'status' => true,
            'data'   => array_map('intval', (array)$stats),
        ]);
    }

    public function getNotificationStats(Request $request)
    {
        $domainName = $request->get('domain_name');
        $refresh    = $request->get('refresh', 0);

        // 1) Find the domain
        $domain = Domain::where('name', $domainName)->firstOrFail();

        if (! $domain) {
            return response()->json([
               'status' => true,
                'data'   => [
                    'total'     => 0,
                    'broadcast' => 0,
                    'segment'   => 0,
                    'plugin'    => 0,
                ],
            ], 404);
        }

        // 2) Join DomainNotification → Notification to get segment_type
        $stats = DomainNotification::query()
            ->join('notifications as n', 'domain_notification.notification_id', '=', 'n.id')
            ->where('domain_notification.domain_id', $domain->id)
            ->selectRaw('
                count(*) as total,
                sum(case when n.segment_type = "all" then 1 else 0 end) as broadcast,
                sum(case when n.segment_type = "particular" then 1 else 0 end) as segment,
                sum(case when n.segment_type = "api" then 1 else 0 end) as plugin
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
        $metric     = $request->query('metric'); // 'subscribers', 'notifications', or null

        // pick domain
        $dq = Domain::orderBy('created_at');
        if ($domainName) {
            $dq->where('name', $domainName);
        }
        $domain = $dq->first();

        // prepare 7-day labels
        $startOfWeek = Carbon::now()->startOfWeek(); // Monday
        $days = [];
        for ($i=0; $i<7; $i++) {
            $days[] = $startOfWeek->copy()->addDays($i)->format('Y-m-d');
        }
        $labels = array_map(fn($d)=> Carbon::parse($d)->format('D'), $days);
        $zeros  = array_fill(0,7,0);

        if (! $domain) {
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
            if ($metric==='subscribers') {
                Cache::forget($subKey);
            } elseif ($metric==='notifications') {
                Cache::forget($notKey);
            } else {
                Cache::forget($subKey);
                Cache::forget($notKey);
            }
        }

        // fetch subscriber counts
        $subsData = Cache::remember($subKey, now()->addMinutes(5), function() use($domain, $startOfWeek) {
            return DB::table('push_subscriptions_head')
                ->where('domain', $domain->name)
                ->where('created_at','>=', $startOfWeek)
                ->selectRaw("DATE(created_at) AS day, COUNT(*) AS cnt")
                ->groupBy('day')
                ->pluck('cnt','day')
                ->toArray();
        });

        // fetch notification counts
        $notData = Cache::remember($notKey, now()->addMinutes(5), function() use($domain, $startOfWeek) {
            return DomainNotification::query()
                ->where('domain_id', $domain->id)
                ->where('sent_at', '>=', $startOfWeek)
                ->selectRaw('DATE(sent_at) AS day, COUNT(*) AS cnt')
                ->groupBy('day')
                ->pluck('cnt','day')
                ->toArray();
        });

        // build series aligned to $days
        $subsSeries = array_map(fn($d)=> $subsData[$d] ?? 0, $days);
        $notSeries  = array_map(fn($d)=> $notData[$d]  ?? 0, $days);

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
