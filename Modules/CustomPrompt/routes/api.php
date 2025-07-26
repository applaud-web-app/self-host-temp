<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomPrompt\Http\Controllers\CustomPromptController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('customprompts', CustomPromptController::class)->names('customprompt');
});
