<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\PushConfig;
use App\Models\Notification;
use App\Models\PushSubscriptionHead;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\WebPushConfig;
use Illuminate\Support\Facades\Cache;
use Kreait\Firebase\Factory;
use Throwable;
use App\Models\Setting;

class SendMulticastNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 1;
    public int $timeout = 3600;

    public function __construct(
        protected int $notificationId,
        protected array $webPush,
        protected array $idTokenMap,
        protected int $domainId,
        protected string $domainName
    ) {}

    public function handle(): void
    {
        try {
            Log::info("Sending notification to batch of " . count($this->idTokenMap) . " tokens for domain: {$this->domainName}");

            $cfg = Cache::remember("fcm:push_config:{$this->domainName}", now()->addMinutes(60), function () {
                return PushConfig::first();
            });

            if (!$cfg) {
                Log::error("FCM config missing for domain: {$this->domainName}");
                return;
            }

            $factory = (new Factory())->withServiceAccount($cfg->credentials);
            $messaging = $factory->createMessaging();

            $config = WebPushConfig::fromArray($this->webPush);
            $message = CloudMessage::new()->withWebPushConfig($config);

            // Send the notification to the 50 tokens
            $report = $messaging->sendMulticast($message, array_values($this->idTokenMap));

            $successCount = $report->successes()->count();
            $failureCount = $report->failures()->count();

            // Log success and failure counts
            Log::info("Sent notifications for domain {$this->domainName}: {$successCount} successes, {$failureCount} failures");

            // Log the failure reasons if any
            if ($failureCount > 0) {
                foreach ($report->failures() as $failure) {
                    Log::error("Failed to send notification to token {$failure->token}: {$failure->error()->getMessage()}");
                }
            }

            // Record success/failure in the database
            $now = now();
            $insertData = [];
            foreach ($this->idTokenMap as $id => $token) {
                $insertData[] = [
                    'notification_id' => $this->notificationId,
                    'subscription_head_id' => $id,
                    'status' => $failureCount > 0 ? 0 : 1,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            foreach (array_chunk($insertData, 50) as $chunk) {
                DB::table('notification_sends')->insertOrIgnore($chunk);
            }

            Log::info("Finished sending batch for domain {$this->domainName}");

        } catch (Throwable $e) {
            Log::error("Failed to send notifications for {$this->domainName}: {$e->getMessage()}");
        }
    }
}