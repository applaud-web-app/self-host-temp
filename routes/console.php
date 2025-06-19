<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

// STOP DUE TO REDIS NOT AVAILABEL
// Schedule::command('analytics:flush')->everyMinute();
// Schedule::command('subscriptions:flush')->everyMinute();


Schedule::command('notifications:dispatch-scheduled')
->everyMinute()
->sendOutputTo('dispatch-scheduled.log');

Schedule::command('notifications:dispatch-scheduled-segment')
->everyMinute()
->withoutOverlapping()
->sendOutputTo('dispatch-scheduled-segment.log');

Schedule::command('stats:domain-subscriptions')
->dailyAt('17:22')
// ->everyMinute()
->withoutOverlapping()
->sendOutputTo('daily-domain-sub-count');