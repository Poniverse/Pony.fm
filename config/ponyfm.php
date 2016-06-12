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
    | Cache duration
    |--------------------------------------------------------------------------
    |
    | Duration in minutes for track files to be stored in cache.
    |
    */

    'track_file_cache_duration' => 1440,

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch index name
    |--------------------------------------------------------------------------
    |
    | The name of the Elasticsearch index to store Pony.fm's search data in.
    |
    */

    'elasticsearch_index' => 'ponyfm',

    /*
    |--------------------------------------------------------------------------
    | Indexing queue name
    |--------------------------------------------------------------------------
    |
    | The name of the queue to process re-indexing jobs on. This is separated
    | from the default queue to avoid having a site-wide re-index clog uploads
    | and downloads.
    |
    */

    'indexing_queue' => 'indexing',

    /*
    |--------------------------------------------------------------------------
    | Global validation rules
    |--------------------------------------------------------------------------
    |
    | Data fields that are validated in multiple places have their validation
    | rules centralized here.
    |
    */

    'validation_rules' => [
        'username'      => ['required', 'min:3', 'max:26'],
        'display_name'  => ['required', 'min:3', 'max:26'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum length of a user slug
    |--------------------------------------------------------------------------
    |
    | No profile slugs shorter than this will be generated. This setting is
    | intended to pre-emptively avoid collisions with very short URL's that may
    | be desirable for future site functionality.
    |
    */
    
    'user_slug_minimum_length' => 3
	
	/*
     |--------------------------------------------------------------------------
     | Indexing queue name
     |--------------------------------------------------------------------------
     |
     | Google Cloud Messaging API key. Needs to be generated in the Google Cloud
     | Console as a browser key. This is used to send notifications to users
     | with push notifications enabled.
     |
     */

    'gcm_key' => env('GCM_KEY', 'default'),
];
