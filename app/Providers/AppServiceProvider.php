<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request; 
use Illuminate\Support\Facades\Auth;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\File;

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
    public function boot(): void
    {
        // Include helper files from all modules
        $modules = Module::all();

        foreach ($modules as $module) {
            $helperFilePath = module_path($module->getName(), 'Helper/helper.php');
            
            // Check if the helper file exists in the module and include it
            if (file_exists($helperFilePath)) {
                try {
                    require_once $helperFilePath;
                } catch (\Exception $e) {
                    Log::error("Error including file from module {$module->getName()}: " . $e->getMessage());
                }
            }
        }
    }

}
