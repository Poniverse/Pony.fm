<?php
return [
    'version' => 1,
    'urls' => [
        'api' => env('PONI_API_URL', 'https://api.poniverse.net/v1/'),
        'register' => env('PONI_REGISTER_URL', 'https://poniverse.net/register?site=pony.fm'),
        'auth' => env('PONI_AUTH_URL', 'https://poniverse.net/oauth/authorize'),
        'token' => env('PONI_TOKEN_URL', 'https://poniverse.net/oauth/access_token')
    ],
    'client_id' => env('PONI_CLIENT_ID'),
    'secret' => env('PONI_CLIENT_SECRET')
];
