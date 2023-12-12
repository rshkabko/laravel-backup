## Supervisor for poor

This Git repository provides a solution for free backups of "pet projects." It automates the process of creating database dumps and delivers them to your Telegram account, offering a convenient and cost-free backup solution.

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

## TODO: Split backup fot 50 MB
