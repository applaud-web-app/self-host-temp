<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendSegmentNotificationJob;

class DispatchScheduledNotifications extends Command
{
    protected $signature   = 'notifications:dispatch-scheduled';
    protected $description = 'Queue due one-time (Schedule) notifications';

    public function handle()
    {
        $now = Carbon::now();

        Notification::where('schedule_type','schedule')
        ->whereNotNull('one_time_datetime')
        // ->where('segment_type','all')
        ->where('one_time_datetime','<=',$now)
        ->where('status','pending')
        ->each(function($n) {
            try {
                if ($n->segment_type === "all") {
                    dispatch(new SendNotificationJob($n->id))->onQueue('notifications');
                    $this->info("Dispatched Notification #{$n->id}");
                }else{
                    dispatch(new SendSegmentNotificationJob($n->id))->onQueue('notifications');
                    $this->info("Dispatched segment notification #{$n->id}");
                }
            } catch (\Throwable $e) {
                Log::error("Failed to dispatch Notification #{$n->id}: {$e->getMessage()}");
            }
        });
    }

}