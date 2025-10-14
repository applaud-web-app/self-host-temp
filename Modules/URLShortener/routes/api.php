<?php

use Illuminate\Support\Facades\Route;
use Modules\URLShortener\Http\Controllers\URLShortenerController;


Route::get('/{type}/{code}', [URLShortenerController::class, 'shorturlSubs'])->name('shorturl.subs')->withoutMiddleware('throttle:api');

Route::middleware('global')->controller(URLShortenerController::class)->group(function () {
    Route::get('push-permission','pushPermission')->name('push.permission');
});