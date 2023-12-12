<?php

namespace Flamix\LaravelBackup\Console\Commands;

use Flamix\LaravelBackup\FilesController;
use Flamix\LaravelBackup\TelegramController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * php artisan backup:database
 */
class DatabaseBackup extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Run database backup and send it to telegram.';

    public function handle(FilesController $filesController, TelegramController $telegram)
    {
        $db_name = config('database.connections.mysql.database');
        $db_user = config('database.connections.mysql.username');
        $db_pwd = config('database.connections.mysql.password');

        if (!config('backup.database.enabled')) {
            $this->log("Database backup is disabled!");
            return;
        }

        $this->log("Start! Delete and create directory {$filesController->path()}");
        $filesController->deleteDirectory();
        $filesController->createDirectory();

        $this->log("Backuping structure...");
        $this->runProcess("mysqldump -u{$db_user} -p{$db_pwd} --no-tablespaces --no-data {$db_name} > {$filesController->path("structure_{$db_name}.sql")}");
        $this->log("Backuping data...");
        $this->runProcess("mysqldump -u{$db_user} -p{$db_pwd} --no-tablespaces {$this->prepareIgnoreTables()} {$db_name} > {$filesController->path("data_{$db_name}.sql")}");

        $this->log("Zipping...");
        $zip_path = $filesController->zipping();

        if (config('backup.telegram.enabled')) {
            $this->log("Sending to telegram...");
            $telegram->sendFile($zip_path);
        }

        $this->log("Finish! Clear directory!");
        $filesController->deleteDirectory();
    }

    /**
     * Logging.
     *
     * @param string $msg
     * @param array $arg
     * @return void
     */
    private function log(string $msg, array $arg = [])
    {
        // info($msg, $arg); // Debug in console
        if (!empty($arg)) {
            dump($msg, $arg);
        } else {
            $this->comment($msg);
        }
    }

    /**
     * Run process as a user.
     *
     * @param string $cmd
     * @return string
     */
    private function runProcess(string $cmd)
    {
        $process = Process::fromShellCommandline($cmd);
        $process->run();
        return $process->getOutput();
    }

    /**
     * Prepare ignore tables.
     * For example logs tables.
     *
     * @return string
     */
    private function prepareIgnoreTables(): string
    {
        $db_name = config('database.connections.mysql.database');
        $ignore_tables = config('backup.database.ignore', []);
        if (empty($ignore_tables)) {
            return '';
        }

        $ignore_tables = array_map(fn($table) => "--ignore-table={$db_name}.{$table}", $ignore_tables);
        return implode(' ', $ignore_tables);
    }
}
