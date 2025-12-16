<?php

use Illuminate\Support\Facades\Route;
use Modules\AdvanceAnalytics\Http\Controllers\AdvanceAnalyticsController;
// ,'verify_advance_analytics'
Route::prefix('advance-analytics')->middleware(['auth'])->name('advance-analytics.')->controller(AdvanceAnalyticsController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/performance', 'performance')->name('performance');
    Route::get('/subscribers', 'subscribers')->name('subscribers');
    Route::get('/subscribers/fetch', 'subscribersFetch')->name('subscribers.fetch');
    Route::get('/fetch', 'fetch')->name('fetch');
    Route::get('/subscriber', 'subscriber')->name('subscriber');
    Route::get('/notification', 'notification')->name('notification');
});
