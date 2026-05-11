<?php

/*
|--------------------------------------------------------------------------
| Adicione este bloco dentro do array em config/services.php
|--------------------------------------------------------------------------
|
| Exemplo:
|   return [
|       'mailgun' => [...],
|       'ses'     => [...],
|
|       // ← cole aqui:
|       'groq' => [
|           'api_key' => env('GROQ_API_KEY'),
|           'model'   => env('GROQ_MODEL', 'llama3-8b-8192'),
|       ],
|   ];
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Groq AI
    |----------------------------------------------------------------------
    */
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('GROQ_MODEL', 'llama3-8b-8192'),  // ou llama3-70b-8192
    ],

    // ... resto das suas configurações existentes

];
