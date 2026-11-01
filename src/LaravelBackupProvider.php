<?php

namespace Flamix\LaravelBackup;

use Illuminate\Support\ServiceProvider;

/** Merges config/backup.php and registers the backup:database command. */
class LaravelBackupProvider extends ServiceProvider
{
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
