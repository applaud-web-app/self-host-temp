<?php

use Illuminate\Support\Facades\Route;
use Modules\Migrate\Http\Controllers\MigrateController;

Route::prefix('migrate')->middleware(['auth'])->name('migrate.')->controller(MigrateController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/report', 'report')->name('report');
    Route::get('/send-notification', 'sendNotification')->name('send-notification');

});
