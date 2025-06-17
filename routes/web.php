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


Route::prefix('install')->group(function () {
    // welcome
    Route::get('/',             [InstallController::class, 'installWelcome'])->name('install.welcome');
    Route::post('/',            [InstallController::class, 'postInstallWelcome']);

    // environment check
    Route::get('/environment',  [InstallController::class, 'installEnvironment'])->name('install.environment');
    Route::post('/environment', [InstallController::class, 'postInstallEnvironment'])->name('install.environment.post');

    // license
    Route::get('/license',      [InstallController::class, 'installLicense'])->name('install.license');
    Route::post('/license',     [InstallController::class, 'postInstallLicense'])->name('install.license.post');

    // database
    Route::get('/database',     [InstallController::class, 'installDatabase'])->name('install.database');
    Route::post('/database',    [InstallController::class, 'postInstallDatabase'])->name('install.database.post');

    // cron
    Route::get('/cron',         [InstallController::class, 'installCron'])->name('install.cron');
    Route::post('/cron',        [InstallController::class, 'postInstallCron'])->name('install.cron.post');

    // admin
    Route::get('/admin-setup',  [InstallController::class, 'adminSetup'])->name('install.admin-setup');
    Route::post('/admin-setup', [InstallController::class, 'postAdminSetup'])->name('install.admin-setup.post');

    // complete (GET only)
    Route::get('/complete',     [InstallController::class, 'installComplete'])->name('install.complete');
});

Route::get('/', [Controller::class, 'index'])->name('home');

// First page is login
Route::controller(AuthController::class)->group(function () {
    Route::get('login', 'login')->name('login');
    Route::post('login', 'doLogin')->name('login.doLogin');
});

// Optional: Home page (static)
Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard')->middleware('auth');

Route::middleware(['auth','ensure_push_config'])->group(function() {
      
    Route::controller(UserController::class)->prefix('user')->name('user.')->group(function () {
        Route::get('profile', 'profile')->name('profile');
        Route::post('update', 'updateProfile')->name('update');
        Route::post('update-password', 'updatePassword')->name('update-password');

        Route::get('subscription', 'subscription')->name('subscription');
        Route::get('addons', 'addons')->name('addons');
    });

    // Settings routes
    Route::controller(AuthController::class)->group(function () {
        Route::post('logout', 'logout')->name('logout');
    });

    // Push Config routes
    Route::controller(PushConfigController::class)->prefix('settings/push')->name('settings.push.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::post('/save', 'save')->name('save');
    });

    // Domain routes
    Route::controller(DomainController::class)->prefix('domain')->name('domain.')->group(function () {
        Route::get('/', 'view')->name('view');
        Route::post('/create', 'create')->name('store');
        Route::post('check', 'check')->name('check');
        Route::post('/update-status', 'updateStatus')->name('update-status');
        Route::get('/integrate', 'integrate')->name('integrate');
        Route::get('/domain-list', 'domainList')->name('domain-list');
        Route::get('/download-sw', 'downloadSW')->name('download-sw');
    });
    
    // Send Notification routes
    Route::controller(NotificationController::class)->prefix('notification')->name('notification.')->group(function () {
        Route::get('/','view')->name('view');
        Route::get('details', 'details')->name('details');
        Route::get('/create','create')->name('create');
        Route::post('/send','store')->name('send');
        Route::post('/{notification}/send','send')->name('resend');
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
    Route::get('/integrate-domain', [Controller::class, 'integrateDomain'])->name('integrate-domain');
    Route::get('/send-notification', [Controller::class, 'sendNotification'])->name('send-notification');
    Route::get('/campaign-reports', [Controller::class, 'campaignReports'])->name('campaign-reports');

    

   Route::middleware(['auth','ensure_push_config'])->group(function() {
        Route::prefix('settings')->controller(SettingsController::class)->name('settings.')->group(function () {
            Route::get('/general', 'generalSettings')->name('general');
            Route::post('/general', 'updateGeneralSettings');
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
            Route::get('/firebase-setup', 'firebaseSetup')->name('firebase-setup');
        });
    });



});

