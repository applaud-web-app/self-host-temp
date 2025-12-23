<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

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
->dailyAt('01:00')
->timezone('Asia/Kolkata')
->withoutOverlapping();

Schedule::command('app:deactive-token')
->dailyAt('02:00')
->timezone('Asia/Kolkata')
->when(fn () => Setting::dailyCleanupEnabled())
->onOneServer()
->withoutOverlapping();     

// Schedule::command('notifications:fix-stuck')
// ->everyMinute()
// ->timezone('Asia/Kolkata')
// ->withoutOverlapping();