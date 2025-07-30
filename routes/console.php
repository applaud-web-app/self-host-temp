<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Schedule::command('analytics:flush')
->everyMinute();
Schedule::command('subscriptions:flush')
->everyMinute();

Schedule::command('notifications:dispatch-scheduled')
->everyMinute()
->sendOutputTo('dispatch-scheduled.log');

Schedule::command('notifications:dispatch-scheduled-segment')
->everyMinute()
->withoutOverlapping()
->sendOutputTo('dispatch-scheduled-segment.log');

Schedule::command('stats:domain-subscriptions')
->dailyAt('17:22')
->withoutOverlapping()
->sendOutputTo('daily-domain-sub-count');

Schedule::command('app:deactive-token')
->dailyAt('01:00')
->withoutOverlapping()
->sendOutputTo('deactive-token');