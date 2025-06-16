<?php

// app/Jobs/SendNotificationJob.php

namespace App\Jobs;

use App\Models\PushConfig;
use App\Models\Notification;
use App\Models\PushSubscriptionHead;
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

    public int $tries   = 1;      // only one attempt
    public int $timeout = 3600;   // plenty of time for chunking

    protected int $notificationId;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
    }

    public function handle(): void
    {
        try {
            // ONLY SEND TO SELECT DOMAIN SUBSCRIBER NOT TO THE ALL -- PLEASE ADD THIS LOGIC
            $notification = Notification::with('domains')->findOrFail($this->notificationId);

            if (! $cfg = PushConfig::first()) {
                Log::error('FCM config missing in DB');
                return;
            }

            // 1) Mark all domains as queued, clear any old sent_at
            $domainIds = $notification->domains->pluck('id')->all();
            $notification->domains()
            ->syncWithoutDetaching(
                collect($domainIds)->mapWithKeys(fn($id) => [$id => ['status'=>'queued','sent_at'=>null]])->all()
            );

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

            $actions = [];

            // Build actions
            $actions = [];
            if ($notification->btn_1_title && $notification->btn_1_url) {
                $actions[] = ['action' => 'btn1', 'title' => $notification->btn_1_title, 'url' => $notification->btn_1_url];
            }
            if ($notification->btn_title_2 && $notification->btn_url_2) {
                $actions[] = ['action' => 'btn2', 'title' => $notification->btn_title_2, 'url' => $notification->btn_url_2];
            }
            if (count($actions) < 2) {
                $actions[] = ['action' => 'close', 'title' => 'Close'];
            }

            // 3) Attach actions into payload
            $payload['actions'] = json_encode($actions);

            $webPushData = [
                'data'    => $payload,
                'headers' => ['Urgency' => 'high'],
            ];

            $domains = $notification->domains->pluck('name')->all();
            if (empty($domains)) {
                Log::warning("⚠️ Notification {$this->notificationId} has no target domains.");
                return;
            }
            
            PushSubscriptionHead::where('status', 1)
            ->whereIn('domain', $domains)
            ->select(['id','token'])
            ->orderBy('id')
            ->chunkById(500, function ($subs) use ($factory, $webPushData, $domainIds) {
                try {
                    $ids    = $subs->pluck('id')->all();
                    $tokens = $subs->pluck('token')->all();

                    if (empty($ids) || empty($tokens)) {
                        Log::info("✅ Skipped empty token batch.");
                        return;
                    }

                    SendNotificationBatchJob::dispatch(
                        $this->notificationId,
                        $factory,
                        $webPushData,
                        $ids,
                        $tokens,
                        $domainIds 
                    );
                } catch (Throwable $e) {
                    Log::error("⚠️ Chunk dispatch failed: " . $e->getMessage());
                }
            });

        } catch (Throwable $e) {
            Log::error("❌ SendNotificationJob crashed [notif={$this->notificationId}]: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::critical("🔥 Notification master job failed permanently [notif={$this->notificationId}]: " . $e->getMessage());
    }
}