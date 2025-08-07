<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Reverb Server
    |--------------------------------------------------------------------------
    |
    | This option controls the default server used by Reverb. This server
    | configuration will be used when no server is explicitly specified
    | when publishing or subscribing to channels.
    |
    */

    'default' => env('REVERB_SERVER', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Servers
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the Reverb servers for your application.
    | Each server configuration includes connection details and the
    | server settings like scaling and limits.
    |
    */

    'servers' => [

        'reverb' => [
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'hostname' => env('REVERB_HOSTNAME', 'localhost'),
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10_000),
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
                'server' => [
                    'url' => env('REDIS_URL'),
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => env('REDIS_PORT', '6379'),
                    'username' => env('REDIS_USERNAME'),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => env('REDIS_DB', '0'),
                ],
            ],
            'pulse' => [
                'enabled' => env('REVERB_PULSE_ENABLED', true),
                'interval' => env('REVERB_PULSE_INTERVAL', 10),
            ],
            'pulse_ingest_interval' => env('REVERB_PULSE_INTERVAL', 10),
            'telescope_ingest_interval' => env('REVERB_TELESCOPE_INTERVAL', 15),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb Applications
    |--------------------------------------------------------------------------
    |
    | Here you may define the applications that can connect to your Reverb
    | server. Each application will have its own key and secret that should
    | be used when establishing a connection to the server.
    |
    */

    'apps' => [

        [
            'app_id' => env('REVERB_APP_ID', 'local'),
            'app_key' => env('REVERB_APP_KEY', 'local-key'),
            'app_secret' => env('REVERB_APP_SECRET', 'local-secret'),
            'capacity' => null,
            'allowed_origins' => ['*'],
            'ping_interval' => env('REVERB_PING_INTERVAL', 30),
            'activity_timeout' => env('REVERB_ACTIVITY_TIMEOUT', 30),
        ],

    ],

];
