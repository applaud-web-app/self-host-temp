<?php

use Illuminate\Support\Facades\Route;
use Modules\WelcomeMessage\Http\Controllers\WelcomeMessageController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('welcomemessages', WelcomeMessageController::class)->names('welcomemessage');
});
