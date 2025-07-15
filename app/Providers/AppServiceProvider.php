<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Support\LicenseCache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        require_once base_path('vendor/max-mind/src/config.php');
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(Request $request): void
    {
        if (isUserRequest($request)) {
            if (!str_starts_with(request()->path(), 'install') && !LicenseCache::validate()) {
                register_shutdown_function(function () {
                    Log::error("check DOmain AppServiceProvider");
                    \App\Support\LicenseCache::warmUpKeys();
                });
            }
        }
    }

}
