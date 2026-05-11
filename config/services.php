<?php

// Adicione esta entrada ao seu config/services.php existente:
//
// return [
//     // ... suas outras entradas (postmark, ses, slack) ...
//
//     'groq' => [
//         'key' => env('GROQ_API_KEY'),
//     ],
// ];
//
// Seu .env já possui GROQ_API_KEY=gsk_... então basta adicionar a entrada acima.
// Depois rode: php artisan config:clear

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ✅ ENTRADA NECESSÁRIA PARA O CHATCONTROLLER
    'groq' => [
        'key' => env('GROQ_API_KEY'),
    ],

];
