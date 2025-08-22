<?php

namespace App\Jobs;

use App\Models\PushConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;
    public int $timeout = 7200;

    // Increased batch sizes for better throughput
    private const DOMAIN_BATCH_SIZE = 500; // tokens per domain job
    private const MAX_CONCURRENT_DOMAINS = 10; // max parallel domain jobs

    public function __construct(protected int $notificationId) {}

    public function handle(): void
    {
        try {
            // 1) Load notification data once
            $notification = DB::table('notifications')
                ->where('id', $this->notificationId)
                ->first([
                    'title', 'description', 'banner_icon', 'banner_image',
                    'target_url', 'message_id',
                    'btn_1_title', 'btn_1_url',
                    'btn_title_2', 'btn_url_2',
                ]);

            if (!$notification) {
                Log::error("Notification {$this->notificationId} not found");
                return;
            }

            // 2) Build payload once
            $webPush = $this->buildWebPush($notification);

            // 3) Get domains and process in optimized batches
            $domains = DB::transaction(function() {
                $domainList = DB::table('domain_notification as dn')
                    ->join('domains as d', 'dn.domain_id', '=', 'd.id')
                    ->where('dn.notification_id', $this->notificationId)
                    ->where('dn.status', 'pending')
                    ->lockForUpdate()
                    ->select('dn.domain_id', 'd.name as domain_name')
                    ->get();

                if ($domainList->isNotEmpty()) {
                    $domainIds = $domainList->pluck('domain_id')->all();
                    DB::table('domain_notification')
                        ->where('notification_id', $this->notificationId)
                        ->whereIn('domain_id', $domainIds)
                        ->update(['status' => 'queued', 'sent_at' => null]);
                }

                return $domainList;
            });

            if ($domains->isEmpty()) {
                return;
            }

            // 4) Process domains in controlled batches to prevent overwhelming
            $domainChunks = $domains->chunk(self::MAX_CONCURRENT_DOMAINS);
            
            foreach ($domainChunks as $domainChunk) {
                foreach ($domainChunk as $domain) {
                    // Dispatch optimized domain job with larger batches
                    OptimizedSendNotificationDomainJob::dispatch(
                        $this->notificationId,
                        $webPush,
                        $domain->domain_id,
                        $domain->domain_name,
                        self::DOMAIN_BATCH_SIZE
                    );
                }
            }

        } catch (Throwable $e) {
            Log::error("SendNotificationJob failed [{$this->notificationId}]: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function buildWebPush(object $row): array
    {
        $base = [
            'title' => $row->title,
            'body' => $row->description,
            'icon' => $row->banner_icon ?? '',
            'image' => $row->banner_image ?? '',
            'click_action' => $row->target_url,
            'message_id' => (string)$row->message_id,
        ];

        $actions = [];
        if ($row->btn_1_title && $row->btn_1_url) {
            $actions[] = ['action' => 'btn1', 'title' => $row->btn_1_title, 'url' => $row->btn_1_url];
        }
        if ($row->btn_title_2 && $row->btn_url_2) {
            $actions[] = ['action' => 'btn2', 'title' => $row->btn_title_2, 'url' => $row->btn_url_2];
        }
        if (count($actions) < 2) {
            $actions[] = ['action' => 'close', 'title' => 'Close'];
        }

        return [
            'data' => array_merge(array_map('strval', $base), ['actions' => json_encode($actions)]),
            'headers' => ['Urgency' => 'high'],
        ];
    }
}