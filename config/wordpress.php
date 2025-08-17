<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable WordPress Integration
    |--------------------------------------------------------------------------
    |
    | Set to true to enable WordPress authentication and user synchronization.
    | When disabled, the chat system will use only Laravel authentication.
    |
    */

    'enabled' => env('WP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | WordPress Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection to use for WordPress data. This should match
    | a connection defined in config/database.php
    |
    */

    'connection' => env('WP_CONNECTION', 'wordpress'),

    /*
    |--------------------------------------------------------------------------
    | WordPress Table Prefix
    |--------------------------------------------------------------------------
    |
    | The table prefix used by your WordPress installation. Default is 'wp_'
    | but many installations use a custom prefix for security.
    |
    */

    'prefix' => env('WP_DB_PREFIX', 'wp_'),

    /*
    |--------------------------------------------------------------------------
    | WordPress URL Configuration
    |--------------------------------------------------------------------------
    |
    | URLs related to your WordPress installation for proper asset handling
    | and redirect functionality.
    |
    */

    'urls' => [
        'home' => env('WP_HOME_URL', 'https://example.com'),
        'site' => env('WP_SITE_URL', 'https://example.com'),
        'uploads' => env('WP_UPLOADS_URL', '/wp-content/uploads'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WordPress Path Configuration
    |--------------------------------------------------------------------------
    |
    | File system paths for WordPress uploads and content.
    |
    */

    'paths' => [
        'uploads' => env('WP_UPLOADS_PATH', storage_path('app/public/wp-uploads')),
        'content' => env('WP_CONTENT_PATH', '/wp-content'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Avatar Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how user avatars are handled from WordPress.
    |
    */

    'avatar' => [
        // Meta keys to check for custom avatar (in order of preference)
        'meta_keys' => [
            'wp_user_avatar',          // WP User Avatar plugin
            'simple_local_avatar',     // Simple Local Avatars plugin
            'custom_avatar',           // Custom Avatar plugin
            'user_avatar',             // User Avatar plugin
            'avatar',                  // Generic avatar
            '_user_avatar',            // Alternative format
        ],

        // Default avatar size
        'default_size' => 200,

        // Fallback to Gravatar if no local avatar found
        'fallback_to_gravatar' => true,

        // Default Gravatar type if user has no avatar
        'gravatar_default' => 'identicon', // identicon, monsterid, wavatar, retro, robohash, blank
    ],

    /*
    |--------------------------------------------------------------------------
    | Capabilities Configuration
    |--------------------------------------------------------------------------
    |
    | Define which WordPress capabilities are allowed to access the chat system
    | and how they map to Laravel permissions.
    |
    */

    'capabilities' => [
        // WordPress capabilities that can access the chat system
        'allowed_capabilities' => [
            'administrator',
            'editor',
            'author',
            'contributor',
            'subscriber',
        ],

        // Admin capabilities (for admin panel access)
        'admin_capabilities' => [
            'administrator',
            'editor',
        ],

        // Map WordPress roles to Laravel roles
        'role_mapping' => [
            'administrator' => 'admin',
            'editor' => 'moderator',
            'author' => 'user',
            'contributor' => 'user',
            'subscriber' => 'user',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Settings
    |--------------------------------------------------------------------------
    |
    | Settings related to WordPress authentication integration.
    |
    */

    'auth' => [
        // Enable automatic user synchronization
        'sync_users' => env('WP_SYNC_USERS', true),

        // Sync user data on every login
        'sync_on_login' => env('WP_SYNC_ON_LOGIN', true),

        // Allow login with email or username
        'allow_email_login' => true,
        'allow_username_login' => true,

        // Password validation settings
        'validate_wordpress_password' => true,

        // Remember token settings
        'store_remember_token' => true,
        'remember_token_meta_key' => 'laravel_remember_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Synchronization Settings
    |--------------------------------------------------------------------------
    |
    | Configure how WordPress users are synchronized with Laravel users.
    |
    */

    'sync' => [
        // Enable automatic creation of Laravel users for WordPress users
        'auto_create_laravel_users' => env('WP_AUTO_CREATE_USERS', true),

        // Update existing Laravel users with WordPress data
        'update_existing_users' => env('WP_UPDATE_EXISTING_USERS', true),

        // Enable automatic user synchronization
        'auto_sync' => env('WP_AUTO_SYNC', true),

        // Sync avatars from WordPress
        'sync_avatars' => env('WP_SYNC_AVATARS', true),

        // Fields to sync from WordPress to Laravel
        'sync_fields' => [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'user_email',
            'username' => 'user_login',
            'display_name' => 'display_name',
            'bio' => 'description',
            'phone' => 'phone',
            'website' => 'user_url',
        ],

        // Meta fields to sync
        'sync_meta_fields' => [
            'phone',
            'description',
            'nickname',
            'first_name',
            'last_name',
        ],

        // Default values for Laravel user fields
        'defaults' => [
            'first_name' => 'User',
            'last_name' => '',
            'bio' => '',
            'phone' => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for WordPress data to improve performance.
    |
    */

    'cache' => [
        // Enable caching for WordPress queries
        'enabled' => env('WP_CACHE_ENABLED', true),

        // Cache duration in minutes
        'duration' => env('WP_CACHE_DURATION', 60),

        // Cache key prefix
        'prefix' => env('WP_CACHE_PREFIX', 'wp_'),

        // Cache user data
        'cache_users' => true,

        // Cache user meta
        'cache_user_meta' => true,

        // Cache WordPress options
        'cache_options' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related settings for WordPress integration.
    |
    */

    'security' => [
        // Validate user status (active, not banned, etc.)
        'validate_user_status' => true,

        // Check user capabilities before allowing access
        'check_capabilities' => true,

        // Log authentication attempts
        'log_auth_attempts' => env('WP_LOG_AUTH', false),

        // Maximum login attempts
        'max_login_attempts' => 5,

        // Lockout duration in minutes
        'lockout_duration' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for integrating with WordPress features and plugins.
    |
    */

    'integration' => [
        // Enable WordPress shortcode support in chat messages
        'enable_shortcodes' => false,

        // WordPress timezone
        'timezone' => env('WP_TIMEZONE', 'UTC'),

        // WordPress locale
        'locale' => env('WP_LOCALE', 'en_US'),

        // Custom hooks
        'hooks' => [
            // WordPress actions to trigger on certain events
            'on_user_login' => [],
            'on_user_logout' => [],
            'on_message_sent' => [],
        ],
    ],

];
