<?php
// app/Jobs/SendSegmentNotificationJob.php

namespace App\Jobs;

use App\Models\PushSubscriptionHead;
use App\Models\SegmentDeviceRule;
use App\Models\SegmentGeoRule;
use Modules\AdvanceSegmentation\Models\SegmentUrlRule;
use Modules\AdvanceSegmentation\Models\SegmentTimeRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\Expression;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;
use App\Jobs\SendNotificationByNode;

class SendSegmentNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 1;
    public int $timeout = 3600;

    protected int $notificationId;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        try {
            $n = DB::table('notifications as n')
                ->join('domains as d', 'n.domain_id', '=', 'd.id')
                ->leftJoin('segments as s', 's.id', '=', 'n.segment_id')
                ->where('n.id', $this->notificationId)
                ->where('n.status', 'pending')
                ->where('n.segment_type', 'particular')
                ->first([
                    'n.id', 'n.domain_id', 'n.title', 'n.description', 'n.banner_icon', 'n.banner_image',
                    'n.target_url', 'n.message_id', 'n.btn_1_title', 'n.btn_1_url',
                    'n.btn_title_2', 'n.btn_url_2', 'n.status',
                    'n.segment_id', 'n.segment_type',
                    'd.name as domain_name',
                    's.id as s_id', 's.type as s_type', 's.name as s_name',
                ]);

            if (!$n) {
                Log::warning("Notif {$this->notificationId}: not found / not pending / not particular.");
                return;
            }

            $segmentId   = (int)($n->segment_id ?? 0);
            $domainName  = $n->domain_name;
            $segmentType = $n->s_type ?? null;

            if ($segmentId <= 0) {
                DB::table('notifications')->where('id', $this->notificationId)->update(['status' => 'failed']);
                Log::error("Notif {$this->notificationId}: missing valid segment_id for 'particular'.");
                return;
            }

            $webPushPayload = $this->buildWebPush($n);

            $query = PushSubscriptionHead::query()
                ->where('status', 1)
                ->where('parent_origin', $domainName)
                ->whereNotNull('token');

            $appliedAnyRule = false;

            // --- DEVICE ------------------------------------------------------
            if ($segmentType === 'device' && Schema::hasTable('segment_device_rules') && class_exists(SegmentDeviceRule::class)) {
                $deviceTypes = SegmentDeviceRule::where('segment_id', $segmentId)
                    ->pluck('device_type')->filter()->values()->all();
                if ($deviceTypes) {
                    $query->whereHas('meta', fn($q) => $q->whereIn('device', $deviceTypes));
                    $appliedAnyRule = true;
                }
            }

            // --- GEO ---------------------------------------------------------
            if ($segmentType === 'geo' && Schema::hasTable('segment_geo_rules') && class_exists(SegmentGeoRule::class)) {
                $geoRules = SegmentGeoRule::where('segment_id', $segmentId)->get();
                foreach ($geoRules as $rule) {
                    if ($rule->operator === 'equals') {
                        $query->whereHas('meta', function ($q) use ($rule) {
                            $q->where('country', $rule->country);
                            if (!empty($rule->state)) { $q->where('state', $rule->state); }
                        });
                    } else { // not_equals
                        $query->whereHas('meta', function ($q) use ($rule) {
                            $q->where('country', '!=', $rule->country);
                            if (!empty($rule->state)) { $q->where('state', '!=', $rule->state); }
                        });
                    }
                }
                $appliedAnyRule = $appliedAnyRule || ($geoRules->count() > 0);
            }

            // --- TIME -----------------------------------------------
            if ($segmentType === 'time') {
                if (!Schema::hasTable('segment_time_rules') || !class_exists(SegmentTimeRule::class)) {
                    Log::warning("Notif {$this->notificationId}: time segment selected but addon tables/models missing.");
                    $this->markNoRecipients();
                    return;
                }

                $timeRule = SegmentTimeRule::where('segment_id', $segmentId)->first();
                if (!$timeRule || !$timeRule->start_at || !$timeRule->end_at || $timeRule->end_at <= $timeRule->start_at) {
                    Log::warning("Notif {$this->notificationId}: invalid time rule for segment {$segmentId}.");
                    $this->markNoRecipients();
                    return;
                }

                $query->whereBetween('created_at', [$timeRule->start_at, $timeRule->end_at]);
                $appliedAnyRule = true;
            }

            // --- URL -------------------------------------------------
            if ($segmentType === 'url') {
                if (!Schema::hasTable('segment_url_rules') || !class_exists(SegmentUrlRule::class)) {
                    Log::warning("Notif {$this->notificationId}: url segment selected but addon tables/models missing.");
                    $this->markNoRecipients();
                    return;
                }

                $urls = SegmentUrlRule::where('segment_id', $segmentId)->pluck('url')->filter()->unique()->values()->all();

                if (!$urls) {
                    Log::warning("Notif {$this->notificationId}: no URL rules for segment {$segmentId}.");
                    $this->markNoRecipients();
                    return;
                }

                // Normalize expression to match how rules are stored
                $normExpr = DB::raw("LOWER(TRIM(BOTH '/' FROM push_subscriptions_meta.subscribed_url))");

                // NOTE: do NOT alias the relation; use 'meta' (your hasOne) directly
                $query->whereHas('meta', function ($q) use ($urls, $normExpr) {
                    $q->whereIn($normExpr, $urls);
                });

                $appliedAnyRule = true;
            }

            if (!$appliedAnyRule) {
                Log::warning("Notif {$this->notificationId}: no rules applied for segment {$segmentId} (type={$segmentType}).");
                $this->markNoRecipients();
                return;
            }

            // One-shot: collect all tokens and dispatch once
            $tokens = (clone $query)
                ->select('token')
                ->whereNotNull('token')
                ->distinct()   
                ->pluck('token')
                ->filter()
                ->values()
                ->all();

            if (empty($tokens)) {
                $this->markNoRecipients();
                Log::info("Notif {$this->notificationId}: 0 recipients after token load for domain {$domainName}, segment {$segmentId}.");
                return;
            }

            // Update queued + active count (we already computed audienceCount above; keep it or recompute)
            DB::table('notifications')->where('id', $this->notificationId)->update([
                'status'       => 'queued',
                'active_count' => count($tokens),
            ]);

            // Single job to Node with all tokens
            SendNotificationByNode::dispatch(
                $tokens,
                $webPushPayload,
                $n->domain_name,
                $this->notificationId
            )->onQueue('notifications');

            Log::info("Notif {$this->notificationId}: dispatched ONE job with ".count($tokens)." tokens (no batching) for domain {$domainName}, segment {$segmentId}.");

        } catch (Throwable $e) {
            DB::table('notifications')->where('id', $this->notificationId)->update(['status' => 'failed']);
            Log::error("SendSegmentNotificationJob failed [{$this->notificationId}]: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function markNoRecipients(): void
    {
        DB::table('notifications')
            ->where('id', $this->notificationId)
            ->update([
                'status'        => 'sent',
                'sent_at'       => now(),
                'active_count'  => 0,
                'success_count' => 0,
                'failed_count'  => 0,
            ]);
    }

    protected function buildWebPush(object $row): array
    {
        $base = [
            'title'        => $row->title ?? '',
            'body'         => $row->description ?? '',
            'icon'         => $row->banner_icon ?? '',
            'image'        => $row->banner_image ?? '',
            'click_action' => $row->target_url ?? '',
            'message_id'   => (string) ($row->message_id ?? ''),
        ];

        $actions = [];
        if (!empty($row->btn_1_title) && !empty($row->btn_1_url)) {
            $actions[] = ['action' => 'btn1', 'title' => $row->btn_1_title, 'url' => $row->btn_1_url];
        }
        if (!empty($row->btn_title_2) && !empty($row->btn_url_2)) {
            $actions[] = ['action' => 'btn2', 'title' => $row->btn_title_2, 'url' => $row->btn_url_2];
        }
        if (count($actions) < 2) {
            $actions[] = ['action' => 'close', 'title' => 'Close'];
        }

        return [
            'data'    => array_merge($base, ['actions' => json_encode($actions)]),
            'headers' => ['Urgency' => 'high'],
        ];
    }
}