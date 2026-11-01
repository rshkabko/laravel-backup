# Backup

`php artisan backup:database`: two `mysqldump` files zipped and sent to Telegram.

## Schedule

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:database')->dailyAt('01:07')->runInBackground();
```

The package registers no schedule itself. The server needs the `mysqldump` binary on `PATH` (`mysql-client` or `mariadb-client`).

## Run manually

```bash
php artisan backup:database
```

## What it does

```text
storage/backup/<time>/structure_<db>.sql   # mysqldump --no-tablespaces --no-data <db>
storage/backup/<time>/data_<db>.sql        # mysqldump --no-tablespaces --ignore-table=… <db> (structure + data)
storage/backup/<time>/2026_03_05_14_37.zip # both files, Y_m_d_H_i
```

- The password goes to `mysqldump` through `MYSQL_PWD`, never in the command line.
- An empty dump file, a non-zero `mysqldump` exit or a zip under 1024 bytes fails the run.
- `storage/backup` is deleted before and after every run, success or failure.

## Telegram messages

One zip:

```text
[2026_03_05_14_37.zip] ✅ Database backup to Shop successfully created.
```

A zip over `backup.telegram.chunk_size` (Telegram rejects documents over 50 MB):

```text
[2026_03_05_14_37.zip.001] part 1/3
[2026_03_05_14_37.zip.002] part 2/3
[2026_03_05_14_37.zip.003] part 3/3
ℹ️ Backup split into 3 parts. Restore: cat *.zip.0* > backup.zip && unzip backup.zip
```

A failure, then the command throws (non-zero exit for the scheduler):

```text
❌ Database backup failed for Shop: mysqldump failed (exit 2): Access denied for user …
```

## Restore

```bash
cat 2026_03_05_14_37.zip.0* > backup.zip && unzip backup.zip
mysql shop < structure_shop.sql   # first: ignored tables exist only here
mysql shop < data_shop.sql
```
