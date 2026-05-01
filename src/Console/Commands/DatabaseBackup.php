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
        $db_host = config('database.connections.mysql.host', '127.0.0.1');
        $db_port = (string) config('database.connections.mysql.port', 3306);

        if (!config('backup.database.enabled')) {
            $this->log("Database backup is disabled!");
            return;
        }

        $this->log("Start! Delete and create directory {$filesController->path()}");
        $filesController->deleteDirectory();
        $filesController->createDirectory();

        try {
            $structurePath = $filesController->path("structure_{$db_name}.sql");
            $dataPath = $filesController->path("data_{$db_name}.sql");

            $this->log("Backuping structure...");
            $this->dump(
                ['--no-tablespaces', '--no-data', $db_name],
                $structurePath, $db_host, $db_port, $db_user, $db_pwd
            );
            $this->ensureNotEmpty($structurePath);

            $this->log("Backuping data...");
            $this->dump(
                array_merge(['--no-tablespaces'], $this->prepareIgnoreTablesArgs(), [$db_name]),
                $dataPath, $db_host, $db_port, $db_user, $db_pwd
            );
            $this->ensureNotEmpty($dataPath);

            $this->log("Zipping...");
            $zip_path = $filesController->zipping();
            $zip_size = file_exists($zip_path) ? filesize($zip_path) : 0;
            $this->log("Zip size: {$zip_size} bytes");
            if ($zip_size < 1024) {
                throw new \RuntimeException("Backup zip is suspiciously small ({$zip_size} bytes)");
            }

            if (config('backup.telegram.enabled')) {
                $this->log("Sending to telegram...");
                $telegram->sendFile($zip_path);
            }
        } catch (\Throwable $e) {
            $msg = "❌ Database backup failed for " . config('app.name') . ": " . $e->getMessage();
            $this->error($msg);
            if (config('backup.telegram.enabled')) {
                try {
                    $telegram->sendMessage($msg);
                } catch (\Throwable $te) {
                    $this->error("Failed to notify telegram: " . $te->getMessage());
                }
            }
            throw $e;
        } finally {
            $this->log("Finish! Clear directory!");
            $filesController->deleteDirectory();
        }
    }

    /**
     * Run mysqldump streaming stdout to a file. Throws on non-zero exit.
     */
    private function dump(array $args, string $output, string $host, string $port, string $user, string $pwd): void
    {
        $cmd = array_merge(
            ['mysqldump', '-h', $host, '-P', $port, '-u', $user],
            $args
        );

        $process = new Process($cmd, null, ['MYSQL_PWD' => $pwd]);
        $process->setTimeout(600);

        $fp = fopen($output, 'w');
        if ($fp === false) {
            throw new \RuntimeException("Cannot open file for write: {$output}");
        }
        try {
            $process->run(function ($type, $buffer) use ($fp) {
                if ($type === Process::OUT) {
                    fwrite($fp, $buffer);
                }
            });
        } finally {
            fclose($fp);
        }

        if (!$process->isSuccessful()) {
            $err = trim($process->getErrorOutput()) ?: 'unknown error';
            throw new \RuntimeException("mysqldump failed (exit {$process->getExitCode()}): {$err}");
        }
    }

    private function ensureNotEmpty(string $path): void
    {
        if (!file_exists($path) || filesize($path) === 0) {
            throw new \RuntimeException("Dump file is empty: " . basename($path));
        }
    }

    private function prepareIgnoreTablesArgs(): array
    {
        $db_name = config('database.connections.mysql.database');
        $ignore_tables = config('backup.database.ignore', []);
        if (empty($ignore_tables)) {
            return [];
        }
        return array_map(fn($table) => "--ignore-table={$db_name}.{$table}", $ignore_tables);
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
