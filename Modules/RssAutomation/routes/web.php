<?php

use Illuminate\Support\Facades\Route;
use Modules\RssAutomation\Http\Controllers\RssAutomationController;

Route::group([
    'prefix' => 'rss-automation',
    'middleware' => ['rss_license']
], function () {
    Route::get('/report', [RssAutomationController::class, 'report'])->name('rssautomation.report');
    Route::get('/add', [RssAutomationController::class, 'add'])->name('rssautomation.add');
    Route::post('/store', [RssAutomationController::class, 'store'])->name('rssautomation.store');
});
