<?php
// NOT IN USE 

namespace App\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\PushConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Models\PushSubscriptionHead;
use Illuminate\Foundation\Bus\Dispatchable;

class SendNotificationByNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;
    public int $timeout = 7200;

    private const MAX_FCM_BATCH = 500;  // Max batch size for FCM

    protected array $tokens;
    protected array $message;
    protected string $domainName;

    // Constructor accepts tokens, message, and domain name to be sent
    public function __construct(array $tokens, array $message, string $domainName)
    {
        $this->tokens = $tokens;
        $this->message = $message;
        $this->domainName = $domainName;
    }

    public function handle(): void
    {
        try {
            // Chunk the tokens into batches of MAX_FCM_BATCH
            $batches = array_chunk($this->tokens, self::MAX_FCM_BATCH);
            $totalSuccess = 0;
            $totalFailure = 0;

            // Process each batch
            foreach ($batches as $batch) {
                Log::info("Dispatching job for batch of " . count($batch) . " tokens for domain: {$this->domainName}");

                // Prepare payload for Node.js service
                $payload = [
                    'tokens' => $batch,
                    'message' => $this->message,
                ];

                // Send HTTP POST request to Node.js service
                $response = Http::post('http://127.0.0.1:3600/send-notification', $payload);

                // Check if request was successful
                if ($response->successful()) {
                    $successCount = $response->json()['successCount'] ?? 0;
                    $failureCount = $response->json()['failureCount'] ?? 0;

                    $totalSuccess += $successCount;
                    $totalFailure += $failureCount;

                    Log::info("Successfully sent notifications for domain: {$this->domainName}, batch size: " . count($batch));
                    Log::info("Success count: {$successCount}, Failure count: {$failureCount}");
                } else {
                    Log::error("Failed to send notifications for domain: {$this->domainName}, batch size: " . count($batch));
                }
            }

            // After processing all batches, store the success and failure counts in the database
            $notif = DB::table('notifications')
            ->where('message_id', $this->message['data']['message_id'])
            ->update([
                'active_count' => $totalSuccess,
                'success_count' => $totalSuccess,
                'failed_count' => $totalFailure,
            ]);

            Log::info("Total Success: {$totalSuccess}, Total Failure: {$totalFailure} for notification: {$this->message['data']['message_id']}");
        } catch (Throwable $e) {
            // Log error if something goes wrong
            Log::error("SendNotificationByNode failed for domain: {$this->domainName}: {$e->getMessage()}");
        }
    }
}