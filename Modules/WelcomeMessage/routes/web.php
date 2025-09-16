<?php

use Illuminate\Support\Facades\Route;
use Modules\WelcomeMessage\Http\Controllers\WelcomeMessageController;

Route::prefix('welcome-message')->middleware(['auth','verify_license'])->name('welcome-message.')->controller(WelcomeMessageController::class)->group(function () {
    Route::get('/', 'index')->name('index');
});