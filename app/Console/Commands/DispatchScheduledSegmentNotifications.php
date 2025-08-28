<?php

namespace App\Console\Commands;

use App\Jobs\SendSegmentNotificationJob;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledSegmentNotifications extends Command
{
    protected $signature   = 'notifications:dispatch-scheduled-segment';
    protected $description = 'Queue due one-time (Schedule) notifications for particular segments';

    public function handle()
    {
        $now = Carbon::now();

        Notification::where('schedule_type','schedule')
            ->whereNotNull('one_time_datetime')
            ->where('segment_type','particular')
            ->where('one_time_datetime','<=',$now)
            ->where('status','pending')
            ->each(function(Notification $n) {
                try {
                } catch (\Throwable $e) {
                    Log::error("Failed to dispatch segment #{$n->id}: {$e->getMessage()}");
                }
            });
    }
}