<?php

namespace Modules\RssAutomation\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Illuminate\Console\Scheduling\Schedule;

class RssAutomationServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'RssAutomation';
    protected string $nameLower = 'rssautomation';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->commands([
            \Modules\RssAutomation\Console\Commands\SendRssNotifications::class,
        ]);
        
        // Register middleware if required
        app('router')->aliasMiddleware('verify_rss_automation', \Modules\RssAutomation\Http\Middleware\CheckLicenseKey::class);

        // Register configurations, views, and translations
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerCommandSchedules();
        $this->addRssTypeToConfig();
        // Load migrations from the module
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config files.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path('RssAutomation', 'config/config.php') => config_path('license.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path('RssAutomation', 'config/config.php'), 'license'
        );
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('rss:send-notifications')->everyTenMinutes()->sendOutputTo('rss-command.log');
        });
    }

    protected function addRssTypeToConfig(): void
    {
        $existingTypes = config('campaign.types');

        // Check if 'rss' is not already added to prevent duplicates
        if (!array_key_exists('rss', $existingTypes)) {
            $existingTypes['rss'] = 'RSS';
            config()->set('campaign.types', $existingTypes); // Update the config at runtime
        }
    }

}