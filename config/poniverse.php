<?php

return [
    'client_id' => env('PONI_CLIENT_ID'),
    'secret' => env('PONI_CLIENT_SECRET'),

    /*
     * Poniverse is used purely as an OpenID Connect identity provider.
     * These endpoints come from its discovery document:
     * https://poniverse.net/.well-known/openid-configuration
     */
    'urls' => [
        'register' => 'https://poniverse.net/register',
        'authorize' => env('PONI_AUTHORIZE_URL', 'https://poniverse.net/oauth/authorize'),
        'token' => env('PONI_TOKEN_URL', 'https://poniverse.net/oauth/token'),
        'userinfo' => env('PONI_USERINFO_URL', 'https://poniverse.net/oauth/userinfo'),
    ],

    'scopes' => ['openid', 'profile', 'email'],
];
