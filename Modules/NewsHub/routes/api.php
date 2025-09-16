<?php

use Illuminate\Support\Facades\Route;
use Modules\NewsHub\Http\Controllers\NewsHubController;

Route::get('/news-hub.js', [NewsHubController::class, 'newsHub'])->name('push.news-hub')->withoutMiddleware('throttle:api');