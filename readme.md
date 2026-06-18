# Laravel Backup

Free database backups for "pet projects": dumps the MariaDB/MySQL database,
zips it, and delivers it to your Telegram account — convenient and cost-free.

## Install

```php
// Config
php artisan vendor:publish --provider="Flamix\LaravelBackup\LaravelBackupProvider" --tag="config"

// Add to scheduler: routes/console.php (or App\Console\Kernel)
Schedule::command('backup:database')->dailyAt('01:07')->runInBackground();
```

## Run manually

```bash
php artisan backup:database
```

## Config / .env

```dotenv
BACKUP_ENABLED=true
BACKUP_TELEGRAM_ENABLED=true
BACKUP_TELEGRAM_BOT_TOKEN=...
BACKUP_TELEGRAM_CHAT_ID=...
BACKUP_TELEGRAM_CHUNK_SIZE=47185920   # optional, default 45 MB
```

Ignore tables (data is skipped, structure is kept) via `config/backup.php`:

```php
'database' => ['ignore' => ['activity_log', 'cache']], // no table prefix
```

## Large backups (> 50 MB)

Telegram Bot API limits `sendDocument` to 50 MB. When the zip is bigger it is
split into chunks of `backup.telegram.chunk_size` bytes (45 MB by default) and
sent as separate documents named `<zip>.001`, `<zip>.002`, … Each part is
captioned `part k/N`, followed by a restore note.

Restore from parts:

```bash
cat your_backup.zip.0* > backup.zip && unzip backup.zip
```
