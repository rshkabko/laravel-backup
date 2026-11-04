# Configuration

Telegram bot, `.env` and `config/backup.php`. [Telegram Bot API: sendDocument](https://core.telegram.org/bots/api#senddocument)

## Bot

1. Open [@BotFather](https://t.me/botfather), send `/newbot`, copy the token.
2. Send `/start` to the bot, then take the chat id from `https://api.telegram.org/bot<token>/getUpdates`.

## .env

```dotenv
BACKUP_ENABLED=true                 # false: the command prints "Database backup is disabled!" and exits 0
BACKUP_TELEGRAM_ENABLED=true        # false: the dump is still made, then deleted; nothing is kept
BACKUP_TELEGRAM_BOT_TOKEN=123456:ABC...
BACKUP_TELEGRAM_CHAT_ID=-1001234567890
BACKUP_TELEGRAM_CHUNK_SIZE=47185920 # optional, bytes; empty or 0 sends the zip whole
```

The database credentials are not here: they come from the default connection, `config('database.connections.<DB_CONNECTION>.*')` (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

## config/backup.php

```php
return [
    'database' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'ignore'  => [], // data skipped, structure kept
    ],
    'telegram' => [
        'enabled'    => env('BACKUP_TELEGRAM_ENABLED', true),
        'token'      => env('BACKUP_TELEGRAM_BOT_TOKEN'),
        'chat_id'    => env('BACKUP_TELEGRAM_CHAT_ID'),
        'chunk_size' => env('BACKUP_TELEGRAM_CHUNK_SIZE', 45 * 1024 * 1024),
    ],
];
```

Publish it only to change `ignore`:

```bash
php artisan vendor:publish --provider="Flamix\LaravelBackup\LaravelBackupProvider" --tag="config"
```

## Ignore tables

```php
'database' => [
    'ignore' => ['activity_log', 'cache'], // becomes --ignore-table=<DB_DATABASE>.activity_log …
],
```

The connection prefix is not added. With the default connection's `prefix` set to `app_`, write `'app_cache'`, otherwise nothing is ignored.
