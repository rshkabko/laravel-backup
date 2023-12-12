<?php

namespace Flamix\LaravelBackup;

use Illuminate\Support\ServiceProvider;

class LaravelBackupProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/backup.php', 'backup');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Flamix\LaravelBackup\Console\Commands\DatabaseBackup::class,
            ]);
        }

        $this->publishes([__DIR__ . '/../config/backup.php' => config_path('backup.php')], 'config');
    }
}
