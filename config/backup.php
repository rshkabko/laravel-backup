<?php

return [
    'database' => [
        'enabled' => env('BACKUP_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Table to ignore
        |--------------------------------------------------------------------------
        |
        | When you have a table that you don't want to backup, you can add it to the array below.
        | For example: ['activity_log', 'cache']. Don not add the table prefix!!!
        |
        */
        'ignore' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram config
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'enabled' => env('BACKUP_TELEGRAM_ENABLED', true),
        'token' => env('BACKUP_TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('BACKUP_TELEGRAM_CHAT_ID'),

        /*
        |--------------------------------------------------------------------------
        | Chunk size
        |--------------------------------------------------------------------------
        |
        | Telegram Bot API limits sendDocument to 50 MB. Bigger zips are split
        | into chunks of this size (bytes) and sent as separate documents.
        |
        */
        'chunk_size' => env('BACKUP_TELEGRAM_CHUNK_SIZE', 45 * 1024 * 1024),
    ]
];
