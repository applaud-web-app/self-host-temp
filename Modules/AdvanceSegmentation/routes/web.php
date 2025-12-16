<?php

use Illuminate\Support\Facades\Route;
use Modules\AdvanceSegmentation\Http\Controllers\AdvanceSegmentationController;

Route::prefix('advance-segmentation')->middleware(['auth','verify_advance_segmentation'])->name('advance-segmentation.')->controller(AdvanceSegmentationController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/store', 'store')->name('store');
    Route::post('/url-list', 'urlList')->name('url-list');
    Route::post('/refresh-data', 'refreshData')->name('refresh-data');
    Route::get('/info', 'info')->name('info');
});