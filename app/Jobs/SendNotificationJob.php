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
        $notification = Notification::findOrFail($this->notificationId);

        // 1) grab your JSON straight from the DB
        $cfg = PushConfig::first();
        if (! $cfg || ! $cfg->service_account_json) {
            Log::error('FCM config missing in DB');
            return;
        }

        // 2) decrypt & decode into an array, then hand straight to the factory
        $rawJson     = decrypt($cfg->service_account_json);
        $credentials = json_decode($rawJson, true);
        if (! is_array($credentials)) {
            Log::error('Invalid FCM JSON credentials');
            return;
        }
        $factory = (new Factory())->withServiceAccount($credentials);


        // 3) prepare your payload once
        $webPushData = [
            'data' => [
                'title'           => $notification->title,
                'body'            => $notification->description,
                'icon'            => $notification->icon        ?? '',
                'image'           => $notification->image       ?? '',
                'click_action'    => $notification->click_url,
                'notification_id' => $notification->id,
            ],
            'headers' => [
                'Urgency' => 'high',
            ],
        ];

        // 4) chunk through all active subscribers
        PushSubscriptionHead::where('status', 1)
            ->select(['id', 'token'])
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
        Log::error("Master send failed [notif={$this->notificationId}]", [
            'error' => $e->getMessage(),
        ]);
    }
}