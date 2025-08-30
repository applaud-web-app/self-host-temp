<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PushApiController;
use App\Http\Controllers\Api\PluginController;
use App\Http\Controllers\GlobalController;

Route::get('/push-notify.js', [PushApiController::class, 'sdk'])->name('api.push.notify')->withoutMiddleware('throttle:api');
Route::post('/push/subscribe', [PushApiController::class,'subscribe'])->name('api.subscribe')->withoutMiddleware('throttle:api');
Route::post('/push/unsubscribe', [PushApiController::class,'unSubscribe'])->name('api.unsubscribe')->withoutMiddleware('throttle:api');
// Route::post('/push/analytics', [PushApiController::class,'analytics'])->name('api.analytics')->withoutMiddleware('throttle:api');

Route::middleware(['cors'])
    ->post('/push/analytics', [PushApiController::class,'analytics'])
    ->name('api.analytics')->withoutMiddleware('throttle:api');

Route::middleware('global')->controller(GlobalController::class)->group(function () {
    Route::get('permission.html','subsStore')->name('api.permission.ask');
});

// PLUGIN ROUTES
Route::post('/plugin/verify', [PluginController::class,'verifyLicenseKey'])->name('api.plugin.verify')->withoutMiddleware('throttle:api');
Route::post('/plugin/stats', [PluginController::class,'domainWiseStats'])->name('api.plugin.stats')->withoutMiddleware('throttle:api');
Route::post('/plugin/notify', [PluginController::class,'pluginNotify'])->name('api.plugin.notify')->withoutMiddleware('throttle:api');