<?php

use Illuminate\Support\Facades\Route;
use Modules\RssAutomation\Http\Controllers\RssAutomationController;

Route::group([
    'prefix' => 'rss-automation',
    'middleware' => ['rss_license']
], function () {
    Route::get('/report', [RssAutomationController::class, 'report'])->name('rss.report');
    Route::get('/create', [RssAutomationController::class, 'create'])->name('rss.create');
    Route::post('/store', [RssAutomationController::class, 'store'])->name('rss.store');
    Route::post('/fetch', [RssAutomationController::class, 'fetch'])->name('rss.fetch');
});
