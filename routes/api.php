<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PushApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::get('/push-notify.js', [PushApiController::class, 'sdk'])->name('api.push.notify');
Route::post('/push/subscribe', [PushApiController::class,'subscribe']);
