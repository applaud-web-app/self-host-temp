<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Schedule::command('analytics:flush')
->everyMinute()
->withoutOverlapping()   // <- key line (uses your cache driver)
->onOneServer()          // if you have >1 host
->runInBackground();

Schedule::command('subscriptions:flush')
->everyMinute()
->withoutOverlapping()   // <- key line (uses your cache driver)
->onOneServer()          // if you have >1 host
->runInBackground();

Schedule::command('notifications:dispatch-scheduled')
->everyMinute()
->withoutOverlapping()   // <- key line (uses your cache driver)
->onOneServer()          // if you have >1 host
->runInBackground()
->sendOutputTo('dispatch-scheduled.log');

// Schedule::command('notifications:dispatch-scheduled-segment')
// ->everyMinute()
// ->withoutOverlapping()
// ->sendOutputTo('dispatch-scheduled-segment.log');

Schedule::command('stats:domain-subscriptions')
->dailyAt('10:55')
->withoutOverlapping()
->sendOutputTo('daily-domain-sub-count');

Schedule::command('app:deactive-token')
->dailyAt('01:00')
->withoutOverlapping()
->sendOutputTo('deactive-token');