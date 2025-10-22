<?php
// IS ACTIVE JOB

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
    public int $timeout = 3600;

    protected int $notificationId;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        try {
            // Load the notification data and related domain name
            $pendingNotification = DB::table('notifications as n')
                ->join('domains as d', 'n.domain_id', '=', 'd.id')
                ->where('n.id', $this->notificationId)
                ->where('n.status', 'pending')
                ->first([
                    'n.domain_id', 'n.title', 'n.description', 'n.banner_icon', 'n.banner_image',
                    'n.target_url', 'n.message_id', 'n.btn_1_title', 'n.btn_1_url', 
                    'n.btn_title_2', 'n.btn_url_2', 'n.status', 'd.name as domain_name'
                ]);

            if (!$pendingNotification) {
                Log::warning("Notification {$this->notificationId} not found or already processed");
                return;
            }

            // Build the web push notification payload
            $webPushPayload = $this->buildWebPush($pendingNotification);

            // Fetch only active tokens for the domain
            $tokens = PushSubscriptionHead::where('status', 1)
                ->where('parent_origin', $pendingNotification->domain_name)
                ->pluck('token')
                ->filter() // Remove any empty tokens
                ->unique() // Remove duplicates
                ->values()
                ->toArray();

            if (empty($tokens)) {
                // Mark as sent even with no tokens to avoid reprocessing
                DB::table('notifications')
                    ->where('id', $this->notificationId)
                    ->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'active_count' => 0,
                        'success_count' => 0,
                        'failed_count' => 0
                    ]);
                
                Log::warning("No active tokens found for domain {$pendingNotification->domain_name} for notification {$this->notificationId}");
                return;
            }

            // Update notification status to 'queued' immediately to prevent duplicate processing
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update([
                    'status' => 'queued',
                    'active_count' => count($tokens)
                ]);

            // Dispatch the job to send notifications via Node.js
            SendNotificationByNode::dispatch($tokens, $webPushPayload, $pendingNotification->domain_name, $this->notificationId)->onQueue('notifications');

            // Log::info("Notification {$this->notificationId} queued for delivery to " . count($tokens) . " tokens for domain {$pendingNotification->domain_name}");

        } catch (Throwable $e) {
            // Update notification status to failed
            DB::table('notifications')
                ->where('id', $this->notificationId)
                ->update(['status' => 'failed']);

            Log::error("SendNotificationJob failed for Notification {$this->notificationId}: {$e->getMessage()}", [
                'notification_id' => $this->notificationId,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Build the payload for the web push notification.
     */
    protected function buildWebPush(object $row): array
    {
        // Define the base data for the push notification
        $base = [
            'title' => $row->title ?? '',
            'body' => $row->description ?? '',
            'icon' => $row->banner_icon ?? '',
            'image' => $row->banner_image ?? '',
            'click_action' => $row->target_url ?? '',
            'message_id' => (string)$row->message_id,
        ];

        // Define actions for buttons
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
}
