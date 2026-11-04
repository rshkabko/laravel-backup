<?php

namespace Flamix\LaravelBackup;

use Illuminate\Support\Facades\File;
use ZipArchive;

/** The temporary storage/backup/<time> folder: dumps, the zip and its parts. */
class FilesController
{
    private ?int $time = null;

    public function path(?string $file = null): string
    {
        // Frozen on first call: every file of one run must land in the same folder.
        if (!$this->time) {
            $this->time = time();
        }

        if ($file) {
            return storage_path("backup/{$this->time}/{$file}");
        }

        return storage_path("backup/{$this->time}");
    }

    public function deleteDirectory(): void
    {
        // The whole storage/backup, not this run's folder: leftovers of crashed runs go too.
        if (File::isDirectory(storage_path("backup"))) {
            File::deleteDirectory(storage_path("backup"));
        }
    }

    public function createDirectory(): void
    {
        File::makeDirectory($this->path(), 0755, true, true);
    }

    /** Zips every file of the run folder into <Y_m_d_H_i>.zip inside it and returns its path. */
    public function zipping(): string
    {
        $files = File::files($this->path());
        $zip = new ZipArchive();
        $zip_path = $this->path(now()->format("Y_m_d_H_i") . '.zip');
        $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $file) {
            $relativeNameInZipFile = basename($file);
            $zip->addFile($file, $relativeNameInZipFile);
        }
        $zip->close();

        return $zip_path;
    }

    /**
     * Splits a file into "<path>.001", "<path>.002", … of $chunkSize bytes, or returns [$path] when it fits.
     *
     * @return list<string>
     */
    public function split(string $path, int $chunkSize): array
    {
        // 0 comes from an empty or missing chunk_size: it must still deliver the whole file, not nothing.
        if ($chunkSize <= 0 || filesize($path) <= $chunkSize) {
            return [$path];
        }

        $in = fopen($path, 'rb');
        if ($in === false) {
            throw new \RuntimeException("Cannot open file for read: {$path}");
        }

        $parts = [];
        try {
            $index = 1;
            while (!feof($in)) {
                $written = 0;
                $partPath = sprintf('%s.%03d', $path, $index);
                $out = fopen($partPath, 'wb');
                if ($out === false) {
                    throw new \RuntimeException("Cannot open file for write: {$partPath}");
                }
                try {
                    while ($written < $chunkSize && !feof($in)) {
                        $buffer = fread($in, min(1024 * 1024, $chunkSize - $written));
                        if ($buffer === false || $buffer === '') {
                            break;
                        }
                        $written += fwrite($out, $buffer);
                    }
                } finally {
                    fclose($out);
                }

                if ($written === 0) {
                    File::delete($partPath);
                    break;
                }

                $parts[] = $partPath;
                $index++;
            }
        } finally {
            fclose($in);
        }

        return $parts;
    }
}
