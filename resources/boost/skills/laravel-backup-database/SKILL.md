---
name: laravel-backup-database
description: "Flamix laravel-backup database run. Use when scheduling or debugging backup:database, BACKUP_TELEGRAM_BOT_TOKEN, BACKUP_TELEGRAM_CHAT_ID, BACKUP_TELEGRAM_CHUNK_SIZE, backup.database.ignore, or restoring a .zip.001 split backup. Also use for the symptoms: no backup arrives in Telegram although the command succeeds, «mysqldump failed», «Dump file is empty», «Backup zip is suspiciously small», ignored tables still in the dump, the wrong database dumped, files under storage/backup disappearing, a backup too big for Telegram. Skip for Telegram log alerts (telegram-log-delivery) and for file or storage-disk backups, which this package does not do."
---

# Laravel Backup: the database run

`backup:database` (`Flamix\LaravelBackup\Console\Commands\DatabaseBackup`) runs `mysqldump` twice, zips the two files with `FilesController`, and posts them with `TelegramController` to `https://api.telegram.org/bot<token>/sendDocument`.

Full docs: vendor/flamix/laravel-backup/docs/configuration.md and backup.md. Below is how to diagnose a run.

## When a backup does not arrive

| What you see | Cause | Do this |
|---|---|---|
| Exit 0, nothing in the chat, «Zip size: N bytes» with no «Sending to telegram (1/…)» after it | `BACKUP_TELEGRAM_ENABLED=false` (the zip is deleted at the end), or `BACKUP_TELEGRAM_CHUNK_SIZE=` present but empty: it casts to 0 and `split()` returns no parts | Check `BACKUP_TELEGRAM_ENABLED` first; then remove the empty chunk-size line or give it bytes |
| Exit 0, nothing in the chat, «Database backup is disabled!» | `BACKUP_ENABLED=false` | Set it to `true` |
| «mysqldump failed (exit 127)» | No `mysqldump` binary on the server's `PATH` | Install `mysql-client` / `mariadb-client` |
| «mysqldump failed (exit 2): Access denied» | `database.connections.mysql` credentials, not `DB_CONNECTION`'s | Fix the `mysql` connection in `config/database.php` |
| «Backup zip is suspiciously small» | The zip is under 1024 bytes: an almost empty database | Expected on a fresh app; it passes once there is data |
| A Telegram 4xx in the error message | Wrong token or chat id, or the bot never got `/start` | Check with `getUpdates` |

## Traps

- The password goes through `MYSQL_PWD`, not `-p`. Do not "fix" it into the argument list: argv shows in `ps`.
- In tests, `Http::fake()` covers Telegram, but `mysqldump` is a Symfony `Process`, so `Process::fake()` does not. Put a fake `mysqldump` script first on `PATH` (set `putenv`, `$_ENV` and `$_SERVER`), as the package's own tests do.

## Restore from parts

Steps are in docs/backup.md. The `.0*` glob there and in the chat note stops at part 099 (about 4.4 GB at the default 45 MB); past that, list the parts explicitly.
