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
    ]
];
