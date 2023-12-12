## Supervisor for poor

This is a custom Laravel Artisan command that checks if specified commands are running and starts them if not.

```php
// Config
php artisan vendor:publish --provider="Flamix\LaravelBackup\LaravelBackupProvider" --tag="config"

// Add to scheduler: App\Console\Kernel
$schedule->command('backup:database')->->dailyAt('19:02')->runInBackground();
```

## Mannually run command

```bash
php artisan backup:database
```
