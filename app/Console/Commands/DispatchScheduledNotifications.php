<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DispatchScheduledNotifications extends Command
{
    protected $signature   = 'notifications:dispatch-scheduled';
    protected $description = 'Queue due one-time (Schedule) notifications';

    public function handle()
    {
        $now = Carbon::now();

        Notification::where('schedule_type','schedule')
        ->whereNotNull('one_time_datetime')
        ->where('one_time_datetime','<=',$now)
        ->whereHas('domains', function($q) {
            $q->where('domain_notification.status','pending');
        })
        ->each(function($n) {
            SendNotificationJob::dispatch($n->id);
            $this->info("Dispatched Notification #{$n->id}");
        });
    }

}