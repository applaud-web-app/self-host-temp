<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessClickAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 10;

    protected string $messageId;
    protected string $event;
    protected string $domain; 

    public function __construct(string $messageId, string $event)
    {
        $this->messageId = $messageId;
        $this->event     = $event;
        $this->domain    = $domain;
    }

    public function handle(): void
    {
        try {
            DB::table('push_event_counts')->updateOrInsert(
                ['message_id' => $this->messageId, 'event' => $this->event, 'domain' => $this->domain],
                ['count' => DB::raw("count + 1")]
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to process push analytics', [
                'message_id' => $this->messageId,
                'event'      => $this->event,
                'domain'     => $this->domain,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessClickAnalytics job permanently failed', [
            'message_id' => $this->messageId,
            'event'      => $this->event,
            'domain'     => $this->domain,
            'error'      => $exception->getMessage(),
        ]);
    }
}
