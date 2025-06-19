<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class Controller extends \Illuminate\Routing\Controller
{

       public function index()
    {
        return view('index');
    }


    // Auth & Dashboard Pages
    public function login()
    {
        return view('login');
    }

    // public function dashboard()
    // {
    //     return view('dashboard');
    // }

    // Install Wizard View Steps Only
    public function installWelcome()
    {
        return view('install.welcome');
    }

    public function installEnvironment()
    {
        return view('install.environment');
    }

    public function installLicense()
    {
        return view('install.license');
    }

    public function installDatabase()
    {
        return view('install.database');
    }

    public function installCron()
    {
        return view('install.cron');
    }
    public function adminSetup()
    {
        return view('install.admin-setup');
    }

    public function installComplete()
    {
        return view('install.complete');
    }

    public function domain()
    {
        return view('domain');
    }

    public function integrateDomain()
    {
        return view('integrate-domain');
    }
    public function subscription()
    {
        return view('subscription');
    }
    public function sendNotification()
    {
        return view('send-notification');
    }

    public function campaignReports()
    {
        return view('campaign-reports');
    }

    public function profile()
    {
        return view('profile');
    }


     public function generalSettings()
    {
        return view('settings.general');
    }

    public function emailSettings()
    {
        return view('settings.email');
    }

    public function serverInfo()
    {
        return view('settings.server-info');
    }

public function utilities()
    {
        return view('settings.utilities');
    }

    public function purgeCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return back()->with('status', 'All caches have been purged.');
    }

    public function clearLog()
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);

        foreach ($files as $file) {
            File::delete($file->getPathname());
        }

        return back()->with('status', 'Log files have been cleared.');
    }

    public function makeCache()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return back()->with('status', 'Caches have been generated.');
    }


    public function upgrade()
    {
    return view('settings.upgrade');
    }
    public function backupSubscribersPage()
{
    return view('settings.backup-subscribers');
}

public function firebaseSetup()
{
    return view('settings.firebase-setup');
}

public function addons()
{
    return view('addons');
}

    
}
