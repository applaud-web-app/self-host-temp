<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PushConfigController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SegmentationController;
use App\Http\Controllers\AddonController; 
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\IconController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\RemoveDeactiveTokens;
use App\Http\Controllers\CustomWidgetController;
use App\Http\Controllers\GlobalController;

Route::prefix('install')->withoutMiddleware('install')->group(function () {
    Route::get('/setup', [InstallController::class, 'installSetup'])->name('install.setup');
    Route::post('/setup', [InstallController::class, 'postInstallSetup'])->name('install.setup.post');
    Route::get('/sync-middleware',   [InstallController::class, 'syncMiddlewareTokens'])->name('install.sync-middleware');
});

Route::middleware('global')->controller(GlobalController::class)->name('global.')->group(function () {
    Route::get('subs-store','subsStore')->name('subs-store');
});

Route::get('/', [Controller::class, 'index'])->name('home');

// First page is login
Route::controller(AuthController::class)->group(function () {
    Route::get('login', 'login')->name('login');
    Route::post('login', 'doLogin')->name('login.doLogin');
});
         
Route::middleware('auth')->controller(DashboardController::class)->name('dashboard.')->group(function () {
    Route::get('/','dashboard')->name('view');
    Route::get('domain-stats','getDomainStats')->name('domain-stats');
    Route::get('notification-stats','getNotificationStats')->name('notification-stats');
    Route::get('weekly-stats','getWeeklyStats')->name('weekly-stats');
});

Route::middleware(['auth','ensure_push_config'])->group(function() {
      
    Route::controller(UserController::class)->prefix('user')->name('user.')->group(function () {
        Route::get('profile', 'profile')->name('profile');
        Route::post('update', 'updateProfile')->name('update');
        Route::get('status', 'statusUpdate')->name('status');
        Route::post('update-password', 'updatePassword')->name('update-password');
        Route::get('subscription', 'subscription')->name('subscription');
    });

    // Addons routes
    Route::controller(AddonController::class)->prefix('addons')->name('addons.')->group(function () {
        Route::get('/', 'addons')->name('view');
        Route::get('/upload', 'upload')->name('upload');
        Route::post('/store', 'store')->name('store');
        Route::post('/activate', 'activate')->name('activate');
    });

    // Settings routes
    Route::controller(AuthController::class)->group(function () {
        Route::post('logout', 'logout')->name('logout');
    });

    // icon routes
    Route::controller(IconController::class)->prefix('icons')->name('icons.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/upload', 'upload')->name('upload');
        Route::delete('/{filename}', 'delete')->name('delete');
        Route::get('/list', 'list')->name('list');
    });

    // Domain routes
    Route::controller(DomainController::class)->prefix('domain')->name('domain.')->group(function () {
        Route::get('/', 'view')->name('view');
        Route::post('/create', 'create')->name('store');
        Route::post('check', 'check')->name('check');
        Route::post('/update-status', 'updateStatus')->name('update-status');
        Route::get('/integrate', 'integrate')->name('integrate');
        Route::get('/verify-integration', 'verifyIntegration')->name('verify-integration');
        Route::get('/domain-list', 'domainList')->name('domain-list');
        Route::get('/download-sw', 'downloadSW')->name('download-sw');
        Route::post('/generate-plugin', 'generatePlugin')->name('generate-plugin');
        Route::get('/download-plugin', 'downloadPlugin')->name('download-plugin');
    });

    
    Route::controller(ImportExportController::class)->name('migration.')->group(function () {
        Route::get('/import', 'importView')->name('import');
        Route::post('/import', 'importData')->name('import-data');
        Route::get('/export', 'showExportForm')->name('export');
        Route::post('/export-data', 'exportData')->name('export-data');
    });
    
    // Send Notification routes
    Route::controller(NotificationController::class)->prefix('notification')->name('notification.')->group(function () {
        Route::get('/','view')->name('view');
        Route::get('details', 'details')->name('details');
        Route::get('/create','create')->name('create');
        Route::post('/send','store')->name('send');
        Route::get('/clone','clone')->name('clone');
        Route::get('/cancel','cancel')->name('cancel');
        Route::post('/fetch-meta', 'fetchMeta')->name('fetchMeta');
    });

    // Segmentation routes (only list & create)
    Route::controller(SegmentationController::class)->prefix('segmentation')->name('segmentation.')->group(function () {
        Route::get('/','view')->name('view');
        Route::get('/create','create')->name('create');
        Route::post('/','store')->name('store');
        Route::post('refresh-data','refreshData')->name('refresh-data');
        Route::post('/update-status','updateStatus')->name('update-status');
        Route::get ('info','info' )->name('info');
        Route::get('segment-list','segmentList')->name('segment-list');
    });

    // Route::get('/domain', [Controller::class, 'domain'])->name('domain');
    // Route::get('/integrate-domain', [Controller::class, 'integrateDomain'])->name('integrate-domain');
    // Route::get('/send-notification', [Controller::class, 'sendNotification'])->name('send-notification');
    // Route::get('/campaign-reports', [Controller::class, 'campaignReports'])->name('campaign-reports');

    Route::middleware(['auth','ensure_push_config'])->group(function() {
        Route::prefix('settings')->controller(SettingsController::class)->name('settings.')->group(function () {
            Route::get('/email', 'emailSettings')->name('email');
            Route::post('/email', 'updateEmailSettings');
            Route::get('/server-info', 'serverInfo')->name('server-info');
            Route::get('/server-info/metrics', 'serverMetrics')->name('server-info.metrics');
            Route::get('/utilities', 'utilities')->name('utilities');
            Route::post('/utilities/purge-cache', 'purgeCache')->name('utilities.purge-cache');
            Route::post('/utilities/clear-log', 'clearLog')->name('utilities.clear-log');
            Route::post('/utilities/make-cache', 'makeCache')->name('utilities.make-cache');
            Route::post('/utilities/queue-manage', 'queueManage')->name('utilities.queue-manage');
            Route::get('/upgrade', 'upgrade')->name('upgrade');
            Route::get('/backup-subscribers', 'backupSubscribers')->name('backup-subscribers');
            Route::get('/backup-subscribers/download', 'downloadBackupSubscribers')->name('backup-subscribers.download');
            Route::get('/view-log', 'viewLog')->name('view-log');
        });
    });
    
    Route::prefix('update')->group(function () {
        Route::get('/', [UpdateController::class, 'index'])->name('update.index');
        Route::post('/upload', [UpdateController::class, 'upload'])->name('update.upload'); // <-- NEW
        Route::post('/install', [UpdateController::class, 'install'])->name('update.install');
        Route::get('/progress', [UpdateController::class, 'progress'])->name('update.progress');
    });

    Route::controller(RemoveDeactiveTokens::class)->prefix('deactive')->name('deactive.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/remove-token', 'removeToken')->name('remove-token');
    });

    // FOR BLOGGERS
    Route::controller(CustomWidgetController::class)->prefix('widget')->name('widget.')->group(function () {
        Route::get('/blogger', 'blogger')->name('blogger');
    });
    

});

