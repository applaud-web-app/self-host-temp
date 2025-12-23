<?php

namespace App\Jobs;

use App\Models\PushSubscriptionHead;
use App\Jobs\SendNotificationByNode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;
    public int $timeout = 600;

    protected int $notificationId;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            // Load notification with domain in single optimized query
            $pendingNotification = DB::table('notifications as n')
                ->join('domains as d', 'n.domain_id', '=', 'd.id')
                ->where('n.id', $this->notificationId)
                ->where('n.status', 'pending')
                ->lockForUpdate() // Prevent duplicate processing
                ->first([
                    'n.id', 'n.domain_id', 'n.title', 'n.description', 
                    'n.banner_icon', 'n.banner_image', 'n.target_url', 
                    'n.message_id', 'n.btn_1_title', 'n.btn_1_url', 
                    'n.btn_title_2', 'n.btn_url_2', 'd.name as domain_name'
                ]);

            if (!$pendingNotification) {
                Log::warning("Notification {$this->notificationId} not found or already processed");
                return;
            }

            Log::info("Processing notification {$this->notificationId} for domain: {$pendingNotification->domain_name}");

            // Build payload once
            $webPushPayload = $this->buildWebPush($pendingNotification);

            // Optimized token fetching with streaming
            $tokens = $this->fetchActiveTokens($pendingNotification->domain_name);

            if (empty($tokens)) {
                Log::info("No active tokens for domain: {$pendingNotification->domain_name}");
                
                DB::table('notifications')
                    ->where('id', $this->notificationId)
                    ->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'active_count' => 0,
                        'success_count' => 0,
                        'failed_count' => 0,
                    ]);
                
                return;
            }

            // Mark as queued immediately
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update([
                    'status' => 'queued',
                    'active_count' => count($tokens),
                    'updated_at' => now(),
                ]);

            // Dispatch to fast queue for immediate processing
            SendNotificationByNode::dispatch(
                $tokens, 
                $webPushPayload, 
                $pendingNotification->domain_name, 
                $this->notificationId
            )->onQueue('notifications');

            $duration = round((microtime(true) - $startTime) * 1000);
            Log::info("Notification {$this->notificationId} queued", [
                'tokens' => count($tokens),
                'duration_ms' => $duration,
            ]);

        } catch (Throwable $e) {
            Log::error("SendNotificationJob failed", [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update([
                    'status' => 'failed',
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }

    /**
     * Fetch active tokens efficiently using chunking
     */
    private function fetchActiveTokens(string $domainName): array
    {
        $tokens = [];
        
        // Use query builder with index hint for better performance
        DB::table('push_subscriptions_head')
            ->select('token')
            ->where('parent_origin', $domainName)
            ->where('status', 1)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->orderBy('id')
            ->chunk(10000, function ($chunk) use (&$tokens) {
                foreach ($chunk as $row) {
                    if (!empty($row->token)) {
                        $tokens[] = $row->token;
                    }
                }
            });

        // Remove duplicates and reindex
        return array_values(array_unique($tokens));
    }

    /**
     * Build web push payload
     */
    protected function buildWebPush(object $row): array
    {
        $base = [
            'title' => $row->title ?? '',
            'body' => $row->description ?? '',
            'icon' => $row->banner_icon ?? '',
            'image' => $row->banner_image ?? '',
            'click_action' => $row->target_url ?? '',
            'message_id' => (string)$row->message_id,
        ];

        $actions = [];
        
        if (!empty($row->btn_1_title) && !empty($row->btn_1_url)) {
            $actions[] = [
                'action' => 'btn1', 
                'title' => $row->btn_1_title, 
                'url' => $row->btn_1_url
            ];
        }
        
        if (!empty($row->btn_title_2) && !empty($row->btn_url_2)) {
            $actions[] = [
                'action' => 'btn2', 
                'title' => $row->btn_title_2, 
                'url' => $row->btn_url_2
            ];
        }
        
        if (count($actions) < 2) {
            $actions[] = ['action' => 'close', 'title' => 'Close'];
        }

        return [
            'data' => array_merge($base, ['actions' => json_encode($actions)]),
            'headers' => ['Urgency' => 'high'],
        ];
    }

    /**
     * Handle permanent job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error("SendNotificationJob failed permanently", [
            'notification_id' => $this->notificationId,
            'exception' => $exception->getMessage(),
        ]);

        DB::table('notifications')
            ->where('id', $this->notificationId)
            ->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);
    }
}