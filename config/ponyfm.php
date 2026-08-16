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

    'files_directory' => env('PONYFM_DATASTORE') ?: storage_path('app/datastore'),
    'ponify_directory' => env('PONIFY_DIRECTORY'),

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

    'user_slug_minimum_length' => 3,

    /*
     |--------------------------------------------------------------------------
     | Web Push (VAPID) keys
     |--------------------------------------------------------------------------
     |
     | VAPID keypair used to send push notifications to users who have
     | enabled them. Generate a keypair with:
     |
     |     vendor/bin/web-push generate-vapid-keys
     |
     | Push notifications are disabled when no keypair is configured.
     |
     */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', env('APP_URL', 'https://pony.fm')),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
     |--------------------------------------------------------------------------
     | ffmpeg prefix
     |--------------------------------------------------------------------------
     |
     | This is the prefix to the ffmpeg binary to use when encoding tracks
     |
     | On system where ffmpeg is installed it can be left as default.
     |
     | On systems where ffmpeg isn't installed, but docker is, it's preferable
     | change this so it can use a prebuilt version of ffmpeg.
     |
     | E.G "docker run -v "$(pwd):$(pwd)" -w "$(pwd)" jrottenberg/ffmpeg:4.3-alpine312"
     */

    'ffmpeg_prefix' => env('FFMPEG_PREFIX', 'ffmpeg'),

    /*
     |--------------------------------------------------------------------------
     | Sendfile (X-Accel-Redirect)
     |--------------------------------------------------------------------------
     |
     | Outside the local environment, audio streams, downloads and images
     | are served by handing the file path to the fronting server
     | (Caddy/nginx) via the X-Accel-Redirect header. Locally there is no
     | server configured to intercept it, so PHP streams the files itself
     | (Range-aware, so seeking still works).
     */

    'use_sendfile' => env('APP_ENV', 'production') !== 'local',
];
