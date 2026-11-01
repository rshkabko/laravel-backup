@php /** @var \Laravel\Boost\Install\GuidelineAssist $assist */ @endphp
# Flamix Laravel Backup

One artisan command that dumps the MySQL/MariaDB database with `mysqldump`, zips it and sends it to a Telegram chat. It keeps no copy anywhere else and backs up no files.

- Do not hand-roll a dump job: schedule `Schedule::command('backup:database')->dailyAt('01:07')->runInBackground()` in `routes/console.php`. The package schedules nothing itself.
- Run it: `{{ $assist->artisanCommand('backup:database') }}`. The server needs the `mysqldump` binary on `PATH`.
- It always reads `database.connections.mysql`, whatever `DB_CONNECTION` is. Only the connection named `mysql` is dumped; renaming it breaks the run.
- Env: `BACKUP_TELEGRAM_BOT_TOKEN`, `BACKUP_TELEGRAM_CHAT_ID`, `BACKUP_ENABLED`, `BACKUP_TELEGRAM_ENABLED`, `BACKUP_TELEGRAM_CHUNK_SIZE`. With Telegram disabled the dump is made and deleted: nothing is kept.
- `backup.database.ignore` takes full table names: the connection prefix is not added.
- A failure is sent to the chat (when Telegram is enabled) and rethrown, so the scheduled run exits non-zero. `storage/backup` is wiped before and after every run.
- Publish the config only to change `ignore`: `{{ $assist->artisanCommand('vendor:publish --provider="Flamix\LaravelBackup\LaravelBackupProvider" --tag="config"') }}`.
- Full docs: `vendor/flamix/laravel-backup/docs/*.md` (configuration, backup).

Detail lives in the skill: `laravel-backup-database`.
