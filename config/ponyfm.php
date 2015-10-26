<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filesystem Datastore
    |--------------------------------------------------------------------------
    |
    | Pony.fm stores audio and image files in a directory it has read/write
    | access to. This is the path to it.
    |
    */

    'files_directory' => env('PONYFM_DATASTORE'),

    /*
    |--------------------------------------------------------------------------
    | Use sendfile?
    |--------------------------------------------------------------------------
    |
    | sendfile is a way of letting the web server serve files that aren't
    | normally in its document root. If the web server is configured for it,
    | use this setting - otherwise, track files and images will be served by
    | the PHP process.
    |
    */

    'sendfile' => env('USE_SENDFILE', true),

    /*
    |--------------------------------------------------------------------------
    | Google Analytics ID
    |--------------------------------------------------------------------------
    |
    | If provided, Pony.fm will track activity in the given Google Analytics
    | profile.
    |
    */

    'google_analytics_id' => env('GOOGLE_ANALYTICS_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Show "Powered by Pony.fm" footer?
    |--------------------------------------------------------------------------
    |
    | If true, a "Powered by Pony.fm" footer is used to comply with the
    | license's attribution requirement. This should only be disabled on
    | the official Pony.fm website, since that already shares its name with
    | the open-source project.
    |
    */

    'use_powered_by_footer' => env('USE_POWERED_BY_FOOTER', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | Duration in minutes for tracks to be stored in cache.
    |
    */

    'cache_duration' => 1440,

];
