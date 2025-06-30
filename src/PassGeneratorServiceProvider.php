<?php

namespace Byte5;

use Illuminate\Support\ServiceProvider;

class PassGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     */
    protected bool $defer = false;

    /**
     * The commands to be registered from the package.
     *
     * @var array<string>
     */
    protected array $commands = [
    ];

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->setupConfig();

        $this->publishAllConfigs();
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->app->bind('passgenerator', function ($app) {
            return new PassGenerator($app);
        });

        $this->commands($this->commands);
    }

    /**
     * It is possible that someone using the package may not publish the config file, or they only
     * have a subset of the configurable values in their local version of the config file. This uses
     * the default values unless there are published ones.
     *
     * http://stagerightlabs.com/blog/laravel5-pacakge-development-service-provider
     */
    private function setupConfig(): void
    {
        $this->mergeConfigFrom(\Safe\realpath(__DIR__.'/../config/passgenerator.php'), 'passgenerator');
    }

    /**
     * Publish all the package's config files to the app.
     */
    private function publishAllConfigs(): void
    {
        $this->publishes([
            \Safe\realpath(__DIR__.'/../config/passgenerator.php') => config_path('passgenerator.php'),
        ], 'config');
    }
}
