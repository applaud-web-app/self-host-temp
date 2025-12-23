<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SegmentFilterHelper
{
    /**
     * Apply segment rules to base subscription query.
     * Query MUST already have alias `h` for push_subscriptions_head.
     */
    public static function apply($query, object $segment): void
    {
        if ($segment->type === 'device') {
            self::applyDeviceRules($query, $segment);
            return;
        }

        if ($segment->type === 'geo') {
            self::applyGeoRules($query, $segment);
            return;
        }

        if ($segment->type === 'time') {
            self::applyTimeRules($query, $segment);
            return;
        }

        if ($segment->type === 'url') {
            self::applyUrlRules($query, $segment);
            return;
        }

        // Unknown segment → no recipients
        $query->whereRaw('1=0');
    }

    private static function applyDeviceRules($query, object $segment): void
    {
        $devices = DB::table('segment_device_rules')
            ->where('segment_id', $segment->id)
            ->pluck('device_type')
            ->filter()
            ->values()
            ->all();

        if (!$devices) {
            $query->whereRaw('1=0');
            return;
        }

        $query->join('push_subscriptions_meta as m', 'h.id', '=', 'm.head_id')
              ->whereIn('m.device', $devices);
    }

    // private static function applyGeoRules($query, object $segment): void
    // {
    //     $rules = DB::table('segment_geo_rules')
    //         ->where('segment_id', $segment->id)
    //         ->get();

    //     if ($rules->isEmpty()) {
    //         $query->whereRaw('1=0');
    //         return;
    //     }

    //     $query->join('push_subscriptions_meta as m', 'h.id', '=', 'm.head_id');

    //     $query->where(function ($q) use ($rules) {
    //         foreach ($rules as $rule) {
    //             if ($rule->operator === 'equals') {
    //                 $q->orWhere(function ($qq) use ($rule) {
    //                     $qq->where('m.country', $rule->country);
    //                     if ($rule->state) {
    //                         $qq->where('m.state', $rule->state);
    //                     }
    //                 });
                    
    //             } else {
    //                 $q->orWhere(function ($qq) use ($rule) {
    //                     $qq->where('m.country', '!=', $rule->country);
    //                     if ($rule->state) {
    //                         $qq->where('m.state', '!=', $rule->state);
    //                     }
    //                 });
    //             }
    //         }
    //     });
    // }

    private static function applyGeoRules($query, object $segment): void
    {
        $rules = DB::table('segment_geo_rules')
            ->where('segment_id', $segment->id)
            ->get();

        if ($rules->isEmpty()) {
            $query->whereRaw('1=0');
            return;
        }

        $query->join('push_subscriptions_meta as m', 'h.id', '=', 'm.head_id');

        $query->where(function ($q) use ($rules) {
            foreach ($rules as $rule) {
                if ($rule->operator === 'equals') {
                    $q->orWhere(function ($qq) use ($rule) {
                        $qq->where('m.country', $rule->country);
                        if ($rule->state) {
                            $qq->where('m.state', $rule->state);
                        }
                    });
                } else {
                    // ✅ FIXED NOT-EQUALS LOGIC
                    $q->orWhere(function ($qq) use ($rule) {
                        $qq->where(function ($x) use ($rule) {
                            $x->where('m.country', '!=', $rule->country);
                            if ($rule->state) {
                                $x->orWhere('m.state', '!=', $rule->state);
                            }
                        });
                    });
                }
            }
        });
    }

    private static function applyTimeRules($query, object $segment): void
    {
        $rule = DB::table('segment_time_rules')
            ->where('segment_id', $segment->id)
            ->first();

        if (!$rule || !$rule->start_at || !$rule->end_at) {
            $query->whereRaw('1=0');
            return;
        }

        $query->whereBetween('h.created_at', [$rule->start_at, $rule->end_at]);
    }

    private static function applyUrlRules($query, object $segment): void
    {
        $urls = DB::table('segment_url_rules')
            ->where('segment_id', $segment->id)
            ->pluck('url')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!$urls) {
            $query->whereRaw('1=0');
            return;
        }

        // ✅ ADD THIS BLOCK RIGHT HERE
        $urls = array_map(
            fn ($u) => strtolower(trim($u, "/ \t\n\r\0\x0B")),
            $urls
        );

        // existing code stays the same
        $normExpr = DB::raw("LOWER(TRIM(BOTH '/' FROM m.subscribed_url))");

        $query->join('push_subscriptions_meta as m', 'h.id', '=', 'm.head_id')
            ->whereIn($normExpr, $urls);
    }

}