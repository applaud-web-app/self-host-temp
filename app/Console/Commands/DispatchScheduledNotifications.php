<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\DB;

class DispatchScheduledNotifications extends Command
{
    protected $signature   = 'notifications:dispatch-scheduled';
    protected $description = 'Queue up any due “Schedule” notifications (per-domain pending)';

    public function handle()
    {
        // $now = Carbon::now();

        // $due = Notification::where('schedule_type', 'schedule')
        //     ->whereNotNull('one_time_datetime')
        //     ->where('one_time_datetime', '<=', $now)
        //     ->whereHas('domains', fn($q) =>
        //         $q->wherePivot('status', 'pending')
        //     )
        //     ->get();

        // foreach ($due as $notification) {
        //     SendNotificationJob::dispatch($notification->id);
        //     $this->info("Queued Notification #{$notification->id}");
        // }

        $now = Carbon::now();
        $threshold = $now->copy()->subMinutes(30);

        // 1) Dispatch anything in [now–30m .. now] that’s still pending
        Notification::where('schedule_type','schedule')
            ->whereNotNull('one_time_datetime')
            ->whereBetween('one_time_datetime', [$threshold, $now])
            ->whereHas('domains', fn($q) =>
                $q->wherePivot('status','pending')
            )
            ->each(fn($n) => SendNotificationJob::dispatch($n->id));

        // 2) Mark stale (>30m) as failed (pivot only)
        $stale = Notification::where('schedule_type','schedule')
            ->whereNotNull('one_time_datetime')
            ->where('one_time_datetime', '<', $threshold)
            ->whereHas('domains', fn($q) =>
                $q->wherePivot('status','pending')
            )
            ->get(['id']);

        foreach ($stale as $notification) {
            DB::table('domain_notification')
              ->where('notification_id', $notification->id)
              ->where('status','pending')
              ->update([
                  'status'  => 'failed',
                  'sent_at' => $now,
              ]);
        }
    }
}