<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendNotificationChunkJob;
use Throwable;
use App\Support\SegmentFilterHelper;

class DispatchNotificationChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public const CHUNK_SIZE = 100;

    public function __construct(public int $notificationId) {}

    public function handle(): void
    {
        try {
            // ✅ No lockForUpdate (prevents row-lock contention)
            $notification = DB::table('notifications as n')
                ->join('domains as d', 'n.domain_id', '=', 'd.id')
                ->where('n.id', $this->notificationId)
                ->whereIn('n.status', ['pending', 'queued']) // allow safe resume
                ->first([
                    'n.id',
                    'n.domain_id',
                    'n.segment_type',
                    'n.segment_id',
                    'n.status',
                    'n.message_id',
                    'd.name as domain_name',
                ]);

            if (!$notification) {
                Log::warning("DispatchNotificationChunksJob: notification not found", [
                    'notification_id' => $this->notificationId,
                ]);
                return;
            }

            // ✅ If already queued and chunks_total already set, do nothing (avoid duplicate dispatch)
            if ($notification->status === 'queued') {
                $chunksTotal = (int) DB::table('notifications')
                    ->where('id', $this->notificationId)
                    ->value('chunks_total');

                if ($chunksTotal > 0) {
                    Log::info("DispatchNotificationChunksJob: already queued, skipping", [
                        'notification_id' => $this->notificationId,
                        'chunks_total' => $chunksTotal,
                    ]);
                    return;
                }
            }

            $totalCount = $this->countRecipients($notification);

            if ($totalCount === 0) {
                DB::table('notifications')->where('id', $this->notificationId)->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'active_count' => 0,
                    'success_count' => 0,
                    'failed_count' => 0,
                    'chunks_total' => 0,
                    'chunks_done' => 0,
                    'updated_at' => now(),
                ]);

                Log::info("DispatchNotificationChunksJob: no recipients", [
                    'notification_id' => $this->notificationId,
                ]);
                return;
            }

            $numChunks = (int) ceil($totalCount / self::CHUNK_SIZE);

            // ✅ Set queued + chunk tracking
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update([
                    'status' => 'queued',
                    'active_count' => $totalCount,
                    'chunks_total' => $numChunks,
                    'chunks_done' => 0,
                    'success_count' => 0,
                    'failed_count' => 0,
                    'updated_at' => now(),
                ]);

            // ✅ Cursor-range chunking: each chunk has (start_id, end_id)
            $lastId = 0;
            $dispatched = 0;

            for ($chunkIndex = 0; $chunkIndex < $numChunks; $chunkIndex++) {
                $chunkMaxId = $this->getChunkMaxId($notification, $lastId, self::CHUNK_SIZE);

                if (!$chunkMaxId) {
                    break; // no more rows
                }

                SendNotificationChunkJob::dispatch(
                    notificationId: (int) $this->notificationId,
                    chunkIndex: (int) $chunkIndex,
                    cursorStartId: (int) $lastId,
                    cursorEndId: (int) $chunkMaxId,
                    domainName: (string) $notification->domain_name,
                    segmentType: (string) $notification->segment_type,
                    segmentId: $notification->segment_id ? (int) $notification->segment_id : null,
                    messageId: (string) $notification->message_id
                )->onQueue('notifications');

                $lastId = (int) $chunkMaxId;
                $dispatched++;
            }

            Log::info("DispatchNotificationChunksJob: dispatched chunks", [
                'notification_id' => $this->notificationId,
                'domain' => $notification->domain_name,
                'segment_type' => $notification->segment_type,
                'segment_id' => $notification->segment_id,
                'total_recipients' => $totalCount,
                'chunks_expected' => $numChunks,
                'chunks_dispatched' => $dispatched,
            ]);

        } catch (Throwable $e) {
            Log::error("DispatchNotificationChunksJob failed", [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
            ]);

            DB::table('notifications')->where('id', $this->notificationId)->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Base query for eligible recipients for this domain.
     */
    private function baseRecipientQuery(object $notification)
    {
        return DB::table('push_subscriptions_head as h')
            ->where('h.parent_origin', $notification->domain_name)
            ->where('h.status', 1)
            ->whereNotNull('h.token')
            ->where('h.token', '!=', '');
    }

    /**
     * Cursor helper: fetch the last id in the next chunk.
     * We query ids > $afterId ordered by id, limited by chunk size,
     * and take the last id as the chunk's end cursor.
     */
    private function getChunkMaxId(object $notification, int $afterId, int $limit): ?int
    {
        $q = $this->baseRecipientQuery($notification)
            ->where('h.id', '>', $afterId);

        // Apply segment filters only for "particular"
        if ($notification->segment_type === 'particular' && $notification->segment_id) {
            $segment = DB::table('segments')->where('id', $notification->segment_id)->first();
            if (!$segment) return null;
            SegmentFilterHelper::apply($q, $segment);
        }

        $last = $q->orderBy('h.id')
            ->limit($limit)
            ->get(['h.id'])
            ->last();

        return $last ? (int) $last->id : null;
    }

    /**
     * Count recipients using same filters as chunking.
     */
    private function countRecipients(object $notification): int
    {
        $q = $this->baseRecipientQuery($notification);

        if ($notification->segment_type === 'particular') {
            if (!$notification->segment_id) return 0;

            $segment = DB::table('segments')->where('id', $notification->segment_id)->first();
            if (!$segment) return 0;

            SegmentFilterHelper::apply($q, $segment);
        }

        // For 'all', 'api', 'rss' -> same pool (domain + active tokens)
        return (int) $q->count();
    }
}