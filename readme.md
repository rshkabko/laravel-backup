# Laravel Backup

Free database backups for pet projects: `mysqldump` of the MySQL/MariaDB database, zipped and sent to your Telegram chat.

## Install

```bash
composer require flamix/laravel-backup
```

```dotenv
BACKUP_TELEGRAM_BOT_TOKEN=
BACKUP_TELEGRAM_CHAT_ID=
```

```php
// routes/console.php
Schedule::command('backup:database')->dailyAt('01:07')->runInBackground();
```

## Documentation

See [docs/](docs/) for full documentation.

AI: Laravel Boost guideline and skills in resources/boost/.

- [Configuration](docs/configuration.md) — bot, `.env`, `config/backup.php`, ignored tables
- [Backup](docs/backup.md) — schedule, what a run does, Telegram parts, restore
