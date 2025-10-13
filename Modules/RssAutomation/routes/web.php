<?php

use Illuminate\Support\Facades\Route;
use Modules\RssAutomation\Http\Controllers\RssAutomationController;

Route::group([ 'prefix' => 'rss', 'middleware' => ['auth']], function () {
    Route::get('/view', [RssAutomationController::class, 'view'])->name('rss.view');
    Route::get('/create', [RssAutomationController::class, 'create'])->name('rss.create');
    Route::post('/store', [RssAutomationController::class, 'store'])->name('rss.store');
    Route::post('/fetch', [RssAutomationController::class, 'fetch'])->name('rss.fetch');
    Route::get('/edit', [RssAutomationController::class, 'edit'])->name('rss.edit');
    Route::post('/update', [RssAutomationController::class, 'update'])->name('rss.update');
    Route::post('/delete', [RssAutomationController::class, 'delete'])->name('rss.delete');
    Route::post('/status', [RssAutomationController::class, 'status'])->name('rss.status');
    Route::get('/report', [RssAutomationController::class, 'report'])->name('rss.report');
});
