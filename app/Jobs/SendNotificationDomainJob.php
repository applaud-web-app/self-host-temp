<?php
// NOT IN USE 

namespace App\Jobs;

use App\Models\PushConfig;
use App\Models\Notification;
use App\Models\PushSubscriptionHead;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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

class SendNotificationDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries   = 1;
    public int $timeout = 7200;

    private const DEFAULT_CHUNK = 20;
    private const MAX_FCM_BATCH = 50; 

    public function __construct(
        protected int $notificationId,
        protected array $webPush,
        protected int $domainId,
        protected string $domainName
    ) {}

    public function handle(): void
    {
        try {
            Log::info("Fetching subscribers for domain: {$this->domainName}");

            // Fetch the list of subscribers (tokens) for the domain
            $subscribers = PushSubscriptionHead::where('status', 1)
                ->where('parent_origin', $this->domainName)
                ->select('id', 'token')
                ->orderBy('id')
                ->get();

            // Log if no subscribers were found
            if ($subscribers->isEmpty()) {
                Log::warning("No active subscribers found for domain: {$this->domainName}");
            }

            $totalSubscribers = $subscribers->count();
            Log::info("Total subscribers found for domain {$this->domainName}: {$totalSubscribers}");

            // Chunk them in batches of 50 tokens
            $batches = $subscribers->chunk(self::MAX_FCM_BATCH); // Process them in chunks

            // For each batch, dispatch a job to send the notification to those 50 tokens
            foreach ($batches as $batch) {
                Log::info("Dispatching job for batch of 50 tokens for domain: {$this->domainName}");
                // Dispatch a new job for each batch of 50 tokens
                SendMulticastNotificationJob::dispatch($this->notificationId, $this->webPush, $batch->pluck('token', 'id')->toArray(), $this->domainId, $this->domainName);
            }

        } catch (Throwable $e) {
            Log::error("Domain job failed [notif={$this->notificationId}, dom={$this->domainId}]: {$e->getMessage()}");
            DB::table('domain_notification')
              ->where('notification_id', $this->notificationId)
              ->where('domain_id', $this->domainId)
              ->update(['status' => 'failed', 'sent_at' => now()]);
        }
    }
}