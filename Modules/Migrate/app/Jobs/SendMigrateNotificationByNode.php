<?php

namespace Modules\Migrate\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMigrateNotificationByNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public int $notificationId,
        public string $vapidPublicKey,
        public string $vapidPrivateKey,
        public array $subscribers, 
        public array $payload,
        public ?string $domainName = null
    ) {}

    public function handle(): void
    {
        try {
            // $endpoint = env('SERVER_URL').'/migrate-subscribers';
            $endpoint = "https://demo.awmtab.in/push/migrate-subscribers";
            
            $body = [
                'vapidPublicKey'  => $this->vapidPublicKey,
                'vapidPrivateKey' => $this->vapidPrivateKey,
                'subscribers'     => $this->subscribers,
                'message'         => $this->payload,
                'domainName'      => $this->domainName,
            ];

            $resp = Http::timeout(90)->retry(1, 1000)->post($endpoint, $body);

            if (!$resp->successful()) {
                $this->bumpCounts(0, count($this->subscribers));
                \Log::error('Node push failed', ['status' => $resp->status(), 'body' => $resp->body()]);
                return;
            }

            $json = $resp->json();
            $successTokens = (array)($json['successTokens'] ?? []);
            $failedTokens  = (array)($json['failedTokens'] ?? []);

            $this->bumpCounts(count($successTokens), count($failedTokens));

        } catch (\Throwable $e) {
            \Log::error("SendMigrateNotificationByNode exception: ".$e->getMessage());
            $this->bumpCounts(0, count($this->subscribers));
        }
    }

    protected function bumpCounts(int $success, int $failed): void
    {
        DB::table('notifications')
            ->where('id', $this->notificationId)
            ->update([
                'success_count' => DB::raw('COALESCE(success_count,0) + '.$success),
                'failed_count'  => DB::raw('COALESCE(failed_count,0) + '.$failed),
            ]);

        $n = DB::table('notifications')->where('id', $this->notificationId)
            ->first(['active_count','success_count','failed_count','status']);

        if ($n && ($n->success_count + $n->failed_count) >= ($n->active_count ?? 0)) {
            DB::table('notifications')->where('id', $this->notificationId)
              ->update(['status' => 'sent', 'sent_at' => now()]);
        }
    }
}