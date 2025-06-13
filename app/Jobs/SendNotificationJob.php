<?php

// app/Jobs/SendNotificationJob.php

namespace App\Jobs;

use App\Models\PushConfig;
use App\Models\PushSubscriptionHead;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 1;      // only one attempt
    public int $timeout = 3600;   // plenty of time for chunking

    protected int $notificationId;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        // ONLY SEND TO SELECT DOMAIN SUBSCRIBER NOT TO THE ALL -- PLEASE ADD THIS LOGIC
        $notification = Notification::with('domains')->findOrFail($this->notificationId);

        if (! $cfg = PushConfig::first()) {
            Log::error('FCM config missing in DB');
            return;
        }

        $factory = (new Factory())->withServiceAccount($cfg->credentials);

        $payload = [
            'title'        => $notification->title,
            'body'         => $notification->description,
            'icon'         => $notification->banner_icon ?? '',
            'image'        => $notification->banner_image ?? '',
            'click_action' => $notification->target_url,
            'message_id'   => (string) $notification->message_id,
        ];

        $payload = array_map(fn($v) => (string) $v, $payload);

        $webPushData = [
            'data'    => $payload,
            'headers' => ['Urgency' => 'high'],
        ];

        $domainName = $notification->domains->pluck('name')->all();
        
        PushSubscriptionHead::where('status', 1)
            ->whereIn('domain', $domainName)
            ->select(['id','token'])
            ->orderBy('id')
            ->chunkById(500, function ($subs) use ($factory, $webPushData) {
                $ids    = $subs->pluck('id')->all();
                $tokens = $subs->pluck('token')->all();
                SendNotificationBatchJob::dispatch(
                    $this->notificationId,
                    $factory,
                    $webPushData,
                    $ids,
                    $tokens
                );
            });
    }

    public function failed(\Throwable $e): void
    {
        Log::error("Master send failed [notif={$this->notificationId}]: " . $e->getMessage());
    }
}