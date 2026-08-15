<?php

return [

    /**
     * The connection whose settings are used when building the Elasticsearch
     * client in AppServiceProvider.
     */

    'defaultConnection' => 'default',

    /**
     * Connection parameters used when building a client.
     */

    'connections' => [

        'default' => [

            /**
             * This is an array of hosts that the client will connect to. It can be a
             * single host, or an array if you are running a cluster of Elasticsearch
             * instances.
             */

            'hosts' => [
                [
                    'host'   => env('ELASTICSEARCH_HOST', 'localhost'),
                    'port'   => env('ELASTICSEARCH_PORT', 9200),
                    'scheme' => env('ELASTICSEARCH_SCHEME', null),
                    'user'   => env('ELASTICSEARCH_USER', null),
                    'pass'   => env('ELASTICSEARCH_PASS', null),
                ],
            ],

            /**
             * By default, the client will retry n times, where n = number of nodes in
             * your cluster. Set a number here to override that.
             */

            'retries' => null,

        ],

    ],

];
