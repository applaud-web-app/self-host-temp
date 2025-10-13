<?php

use Illuminate\Support\Facades\Route;
use Modules\NewsHub\Http\Controllers\NewsHubController;

Route::prefix('news-hub')->middleware(['auth'])->name('news-hub.')->controller(NewsHubController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/roll', 'roll')->name('roll');
    Route::post('/roll-save', 'rollSave')->name('roll.save');
    Route::get('/flask', 'flask')->name('flask');
    Route::post('/flask-save', 'flaskSave')->name('flask.save');
    Route::post('/toggle-status', 'toggleStatus')->name('toggle.status');
    Route::post('/fetch-feed', 'fetchFeed')->name('fetch.feed');
    Route::get('/bottom-slider', 'bottomSlider')->name('bottom-slider');
    Route::post('/bottom-slider', 'bottomSliderSave')->name('bottom-slider.save');
});