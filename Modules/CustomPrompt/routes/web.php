<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomPrompt\Http\Controllers\CustomPromptController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('customprompts', CustomPromptController::class)->names('customprompt');
});
