<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class ProcessClickAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $messageId;
    protected string $event;

    public function __construct(string $messageId, string $event)
    {
        $this->messageId = $messageId;
        $this->event     = $event;
    }

    public function handle(): void
    {
        DB::table('push_event_counts')->updateOrInsert(
            ['message_id' => $this->messageId, 'event' => $this->event],
            ['count' => DB::raw("count + 1")]
        );
    }
}
