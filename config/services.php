<?php

// Adicione isso no seu config/services.php existente
// Se já tem esse arquivo, só adicione a key 'groq' no array

return [

    // ... suas outras configurações de serviços ...

    /*
    |--------------------------------------------------------------------------
    | Groq AI — chave da API de IA
    | Pega do .env: GROQ_API_KEY=gsk_xxxxxxx
    | Cadastre em: https://console.groq.com/keys
    |--------------------------------------------------------------------------
    */
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ViaCEP — não precisa de chave, é gratuito
    | Uso na etapa 1 do onboarding pra preencher endereço automaticamente
    |--------------------------------------------------------------------------
    */
    'viacep' => [
        'url' => 'https://viacep.com.br/ws',
    ],

];
