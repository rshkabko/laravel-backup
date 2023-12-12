<?php

namespace Flamix\LaravelBackup;

use Illuminate\Support\Facades\File;
use ZipArchive;

class FilesController
{
    private ?int $time = null;

    public function path(?string $file = null): string
    {
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
        if (File::isDirectory(storage_path("backup"))) {
            File::deleteDirectory(storage_path("backup"));
        }
    }

    public function createDirectory(): void
    {
        File::makeDirectory($this->path(), 0755, true, true);
    }

    /**
     * Zip directory and delete (optional).
     *
     * @param string $directory
     * @param bool $delete
     * @return string
     */
    public function zipping(): string
    {
        $files = File::files($this->path());
        $zip = new ZipArchive();
        $zip_path = $this->path(now()->format("Y_m_d_H_m") . '.zip');
        $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $file) {
            $relativeNameInZipFile = basename($file);
            $zip->addFile($file, $relativeNameInZipFile);
        }
        $zip->close();

        return $zip_path;
    }
}
