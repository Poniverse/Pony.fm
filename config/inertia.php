<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | The SSR server renders the initial page visit on the Node side for
    | SEO and fast first paint. When it is unreachable, inertia-laravel
    | silently falls back to client-side rendering, and page metadata is
    | unaffected because OG/oEmbed tags are emitted from Blade.
    |
    */

    'ssr' => [
        'enabled' => env('INERTIA_SSR_ENABLED', false),
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
    ],

    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/pages'),
        ],
        'page_extensions' => [
            'tsx',
        ],
    ],

];
