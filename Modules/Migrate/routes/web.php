<?php

use Illuminate\Support\Facades\Route;
use Modules\Migrate\Http\Controllers\MigrateController;

Route::prefix('migrate')->middleware(['auth'])->name('migrate.')->controller(MigrateController::class)->group(function () {
    Route::get('/', 'index')->name('index');
});
