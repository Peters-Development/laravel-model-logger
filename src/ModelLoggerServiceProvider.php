<?php

namespace PetersDevelopment\ModelLogger;

use Illuminate\Support\ServiceProvider;

class ModelLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/model-logger.php', 'model-logger');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/model-logger.php' => config_path('model-logger.php'),
            ], 'model-logger-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'model-logger-migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
