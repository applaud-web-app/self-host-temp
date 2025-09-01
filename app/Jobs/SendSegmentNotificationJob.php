<?php
// IS ACTIVE JOB

namespace App\Jobs;

use App\Models\PushSubscriptionHead;
use App\Jobs\SendNotificationByNode;
use App\Models\SegmentDeviceRule;
use App\Models\SegmentGeoRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            // Load the notification and domain (must be pending + particular)
            $pendingNotification = DB::table('notifications as n')
                ->join('domains as d', 'n.domain_id', '=', 'd.id')
                ->where('n.id', $this->notificationId)
                ->where('n.status', 'pending')
                ->where('n.segment_type', 'particular')
                ->first([
                    'n.domain_id', 'n.title', 'n.description', 'n.banner_icon', 'n.banner_image',
                    'n.target_url', 'n.message_id', 'n.btn_1_title', 'n.btn_1_url',
                    'n.btn_title_2', 'n.btn_url_2', 'n.status',
                    'n.segment_id', 'n.segment_type',
                    'd.name as domain_name',
                ]);

            if (!$pendingNotification) {
                Log::warning("Notification {$this->notificationId} not found, not 'pending', or not 'particular'.");
                return;
            }

            // Guard: segment_id required for 'particular'
            $segmentId  = (int) ($pendingNotification->segment_id ?? 0);
            $domainName = $pendingNotification->domain_name;
            if ($segmentId <= 0) {
                DB::table('notifications')
                    ->where('id', $this->notificationId)
                    ->update(['status' => 'failed']);
                Log::error("Notification {$this->notificationId}: missing valid segment_id for 'particular'.");
                return;
            }

            // Build payload
            $webPushPayload = $this->buildWebPush($pendingNotification);

            // ---------- SEGMENTED AUDIENCE QUERY ----------
            $query = PushSubscriptionHead::query()
                ->where('status', 1)
                ->where('parent_origin', $domainName)
                ->whereNotNull('token');

            // Device rules
            $deviceTypes = SegmentDeviceRule::where('segment_id', $segmentId)
                ->pluck('device_type')
                ->filter()
                ->values()
                ->all();

            if (!empty($deviceTypes)) {
                $query->whereHas('meta', fn ($q) =>
                    $q->whereIn('device', $deviceTypes)
                );
            }

            // Geo rules (AND semantics across rules, as in your snippet)
            $geoRules = SegmentGeoRule::where('segment_id', $segmentId)->get();
            foreach ($geoRules as $rule) {
                if ($rule->operator === 'equals') {
                    $query->whereHas('meta', function ($q) use ($rule) {
                        $q->where('country', $rule->country);
                        if (!empty($rule->state)) {
                            $q->where('state', $rule->state);
                        }
                    });
                } else { // not equals
                    $query->whereHas('meta', function ($q) use ($rule) {
                        $q->where('country', '!=', $rule->country);
                        if (!empty($rule->state)) {
                            $q->where('state', '!=', $rule->state);
                        }
                    });
                }
            }

            // Count audience without loading rows
            $audienceCount = (clone $query)->count('id');

            if ($audienceCount === 0) {
                // Mark as sent to avoid reprocessing (no recipients)
                DB::table('notifications')
                    ->where('id', $this->notificationId)
                    ->update([
                        'status'        => 'sent',
                        'sent_at'       => now(),
                        'active_count'  => 0,
                        'success_count' => 0,
                        'failed_count'  => 0,
                    ]);

                Log::warning("No matching tokens for domain {$domainName}, notif {$this->notificationId}, segment {$segmentId}");
                return;
            }

            // Mark queued with audience size
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update([
                    'status'       => 'queued',
                    'active_count' => $audienceCount,
                ]);

            // Stream tokens in CHUNKS to avoid memory blowups
            $batchSize = 5000; // tune as needed
            $totalChunks = 0;
            $totalDispatched = 0;

            $query->select('id', 'token')
                ->orderBy('id')    
                ->chunkById($batchSize, function ($rows) use (
                    &$totalChunks, &$totalDispatched, $webPushPayload, $domainName
                ) {
                    // Per-chunk: filter and dedupe
                    $tokens = $rows->pluck('token')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    if (count($tokens) === 0) {
                        return;
                    }

                    // Dispatch this chunk to Node
                    SendNotificationByNode::dispatch($tokens, $webPushPayload, $domainName, $this->notificationId);

                    $totalChunks++;
                    $totalDispatched += count($tokens);
                    Log::info("Notification {$this->notificationId}: dispatched chunk #{$totalChunks} with ".count($tokens)." tokens.");
                });

            Log::info("Notification {$this->notificationId} queued to {$totalDispatched} tokens across {$totalChunks} chunks for domain {$domainName} (segment {$segmentId})");
        } catch (Throwable $e) {
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update(['status' => 'failed']);

            Log::error("SendSegmentNotificationJob failed [{$this->notificationId}]: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function buildWebPush(object $row): array
    {
        $base = [
            'title'       => $row->title ?? '',
            'body'        => $row->description ?? '',
            'icon'        => $row->banner_icon ?? '',
            'image'       => $row->banner_image ?? '',
            'click_action'=> $row->target_url ?? '',
            'message_id'  => (string) $row->message_id,
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