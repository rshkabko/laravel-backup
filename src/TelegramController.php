<?php

namespace Flamix\LaravelBackup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class TelegramController
{
    public function sendFile(string $path): array
    {
        $response = Http::attach('document', file_get_contents($path), basename($path))->timeout(600)
            ->post(
                "https://api.telegram.org/bot" . config('backup.telegram.token') . "/sendDocument",
                ['chat_id' => config('backup.telegram.chat_id'), 'caption' => '✅ Database backup to ' . config('app.name') . ' successfully created.']
            )->throw();

        return $response->json();
    }
}
