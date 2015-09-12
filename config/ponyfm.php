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

];
