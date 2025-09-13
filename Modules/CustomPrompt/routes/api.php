<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomPrompt\Http\Controllers\CustomPromptController;


Route::get('/custom-prompt.js', [CustomPromptController::class, 'sdkCustom'])->name('push.custom-prompt')->withoutMiddleware('throttle:api');
