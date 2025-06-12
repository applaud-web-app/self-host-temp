<?php

use Illuminate\Support\Facades\Route;
use Modules\ApluAnalytics\Http\Controllers\ApluAnalyticsController;


Route::group([
    'prefix' => 'aplu-analytics',
    'middleware' => ['aplu.license']
], function () {
    Route::get('/site-monitoring', [ApluAnalyticsController::class, 'siteMonitoring'])->name('apluanalytics.site-monitoring');
    Route::get('/url', [ApluAnalyticsController::class, 'url'])->name('apluanalytics.url');
    Route::get('/status-tracker', [ApluAnalyticsController::class, 'statusTracker'])->name('apluanalytics.status-tracker');
    Route::get('/user-activity', [ApluAnalyticsController::class, 'userActivity'])->name('apluanalytics.user-activity');
});
