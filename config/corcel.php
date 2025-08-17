<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Corcel Database Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration uses the existing WordPress database connection
    | that's already configured in config/database.php
    |
    */

    'connection' => env('CORCEL_CONNECTION', 'wordpress'),

    /*
    |--------------------------------------------------------------------------
    | WordPress Table Prefix
    |--------------------------------------------------------------------------
    |
    | WordPress table prefix. Default is 'wp_' but can be customized.
    |
    */

    'prefix' => env('WP_DB_PREFIX', 'wp_'),

    /*
    |--------------------------------------------------------------------------
    | WordPress Upload Path
    |--------------------------------------------------------------------------
    |
    | WordPress upload path for files and images.
    |
    */

    'uploads' => [
        'dir' => env('WP_UPLOADS_DIR', 'wp-content/uploads'),
        'url' => env('WP_UPLOADS_URL', '/wp-content/uploads'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WordPress Options
    |--------------------------------------------------------------------------
    |
    | WordPress options configuration.
    |
    */

    'options' => [
        'cache' => env('CORCEL_CACHE_OPTIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Models Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which models to use for WordPress entities.
    |
    */

    'models' => [
        'user' => App\Models\WordPressUser::class,
        'post' => Corcel\Model\Post::class,
        'page' => Corcel\Model\Page::class,
        'comment' => Corcel\Model\Comment::class,
        'option' => Corcel\Model\Option::class,
        'meta' => Corcel\Model\Meta\Meta::class,
        'user_meta' => App\Models\WordPressUserMeta::class,
    ],

];
