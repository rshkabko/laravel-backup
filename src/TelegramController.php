<?php

namespace Flamix\LaravelBackup;

use Illuminate\Support\Facades\Http;

class TelegramController
{
    public function sendFile(string $path, ?string $caption = null): array
    {
        $caption = $caption ?: '✅ Database backup to ' . config('app.name') . ' successfully created.';

        $response = Http::attach('document', file_get_contents($path), basename($path))->timeout(600)
            ->post(
                "https://api.telegram.org/bot" . config('backup.telegram.token') . "/sendDocument",
                ['chat_id' => config('backup.telegram.chat_id'), 'caption' => $caption]
            )->throw();

        return $response->json();
    }

    public function sendMessage(string $text): array
    {
        $response = Http::timeout(30)->post(
            "https://api.telegram.org/bot" . config('backup.telegram.token') . "/sendMessage",
            ['chat_id' => config('backup.telegram.chat_id'), 'text' => $text]
        )->throw();

        return $response->json();
    }
}
