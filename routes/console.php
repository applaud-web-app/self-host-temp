<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Schedule::command('analytics:flush')
->everyMinute()
->withoutOverlapping() 
->onOneServer()        
->runInBackground();

Schedule::command('subscriptions:flush')
->everyMinute()
->withoutOverlapping() 
->onOneServer()        
->runInBackground();

Schedule::command('notifications:dispatch-scheduled')
->everyMinute()
->withoutOverlapping() 
->onOneServer()        
->runInBackground();

Schedule::command('stats:domain-subscriptions')
->dailyAt('10:55')
->withoutOverlapping();

Schedule::command('app:deactive-token')
->dailyAt('01:00')
->withoutOverlapping();