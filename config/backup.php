<?php

return [
    'database' => [
        'enabled' => env('BACKUP_ENABLED', true),

        // The connection prefix is not added: a prefixed app must write it in.
        'ignore' => [],
    ],

    'telegram' => [
        'enabled' => env('BACKUP_TELEGRAM_ENABLED', true),
        'token' => env('BACKUP_TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('BACKUP_TELEGRAM_CHAT_ID'),

        // Under the 50 MB sendDocument limit; empty or 0 sends the zip whole.
        'chunk_size' => env('BACKUP_TELEGRAM_CHUNK_SIZE', 45 * 1024 * 1024),
    ]
];
