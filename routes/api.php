<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PushApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/push-notify.js', [PushApiController::class, 'sdk'])->name('api.push.notify')->withoutMiddleware('throttle:api');
Route::post('/push/subscribe', [PushApiController::class,'subscribe'])->name('api.subscribe')->withoutMiddleware('throttle:api');
Route::post('/push/unsubscribe', [PushApiController::class,'unSubscribe'])->name('api.unsubscribe')->withoutMiddleware('throttle:api');