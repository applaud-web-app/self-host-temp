<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\FirebaseTopicService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PushSubscriptionHead;

class SendNotificationToTopicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $notificationId;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle(FirebaseTopicService $firebase): void
    {
        // 1️⃣ Load notification
        $notification = DB::table('notifications')
            ->where('id', $this->notificationId)
            ->where('status', 'pending')
            ->first([
                'title', 'description', 'banner_icon', 'banner_image',
                'target_url', 'message_id', 'btn_1_title', 'btn_1_url',
                'btn_title_2', 'btn_url_2', 'domain_id'
            ]);

        if (!$notification) {
            return; // already sent or deleted
        }

        // 2️⃣ Resolve domain (topic name)
        $domain = Domain::where('id', $notification->domain_id)
            ->where('status', 1)
            ->value('name');

        if (!$domain) {
            Log::warning('Domain not found for notification', [
                'notification_id' => $this->notificationId,
            ]);
            return;
        }

        // 3️⃣ Build FCM payload
        // $payload = [
        //     'topic' => $domain, // domain == topic
        //     'notification' => [
        //         'title' => $notification->title,
        //         'body'  => $notification->description,
        //         'image' => $notification->banner_image,
        //     ],
        //     'data' => [
        //         'target_url' => $notification->target_url,
        //         'notification_id' => (string) $notification->id,
        //     ],
        // ];

        $payload = $this->buildPayload($notification, $domain);

        // 4️⃣ Send to topic
        try {
            $firebase->sendToTopic($payload);

            // TOTAL ACTIVE COUNT 
            $activeUser = $this->getTotalActiveCount($domain);
            Log::info('Domain is : '.$domain.' with active user '. $activeUser ?? 0);

            // 5️⃣ Mark as sent
            DB::table('notifications')
            ->where('id', $this->notificationId)
            ->update([
                'active_count' => $activeUser,
                'success_count' => $activeUser,
                'status'  => 'sent',
                'sent_at' => now(),
                'updated_at' => now(),
            ]);

            Log::warning('Topic notification send', [
                'notification_id' => $this->notificationId,
            ]);

        } catch (\Throwable $e) {
            Log::error('Topic notification send failed', [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
            ]);

            // Let job retry
            throw $e;
        }
    }

    /**
     * Build FCM payload from notification.
    */
    private function buildPayload($n, string $topic): array
    {
        if (!$n) {
            throw new RuntimeException("Notification not found: {$this->notificationId}");
        }

        $actions = [];

        if ($n->btn_1_title && $n->btn_1_url) {
            $actions[] = ['action' => 'btn1', 'title' => $n->btn_1_title, 'url' => $n->btn_1_url];
        }

        if ($n->btn_title_2 && $n->btn_url_2) {
            $actions[] = ['action' => 'btn2', 'title' => $n->btn_title_2, 'url' => $n->btn_url_2];
        }

        if (count($actions) < 2) {
            $actions[] = ['action' => 'close', 'title' => 'Close'];
        }

        return [
            'topic' => $this->normalizeTopic($topic),
            'data' => [
                'title' => (string) $n->title,
                'body' => (string) $n->description,
                'icon' => (string) ($n->banner_icon ?? ''),
                'image' => (string) ($n->banner_image ?? ''),
                'click_action' => (string) $n->target_url,
                'message_id' => (string) $n->message_id,
                'delivery' => 'fcm',
                'actions' => json_encode($actions),
            ],
            'headers' => [
                'Urgency' => 'high',
            ],
        ];
    }

    private function normalizeTopic(string $topic): string
    {
        $topic = strtolower(trim($topic));
        $topic = preg_replace('#^https?://#', '', $topic);
        return rtrim($topic, '/');
    }

    private function getTotalActiveCount(string $topic): int
    {
        return PushSubscriptionHead::where('domain', $topic)->where('status', 1)->count();
    }
}
