# Corcel WordPress Integration Setup Guide

## Overview

This Laravel application integrates with WordPress using **Corcel**, a Laravel package that allows you to connect your Laravel application to an existing WordPress database. This enables seamless authentication, user management, and data sharing between WordPress and Laravel.

## What is Corcel?

Corcel is a Laravel package that provides Eloquent models for WordPress database tables, allowing you to:
- Access WordPress users, posts, pages, comments, and metadata
- Authenticate users against WordPress database
- Sync user data between WordPress and Laravel
- Use WordPress as a content management backend

## Prerequisites

- Laravel 11.x
- PHP 8.2+
- MySQL/MariaDB database
- Existing WordPress installation
- Composer

## Installation

The package is already installed in this project via Composer:

```json
"jgrossi/corcel": "^8.0"
```

## Configuration

### 1. Environment Variables

Add these WordPress database configuration variables to your `.env` file:

```env
# WordPress Database Configuration
WP_ENABLED=true
WP_CONNECTION=wordpress

# WordPress Database Credentials
WP_DB_HOST=127.0.0.1
WP_DB_PORT=3306
WP_DB_DATABASE=wordpress_db_name
WP_DB_USERNAME=wordpress_db_user
WP_DB_PASSWORD=wordpress_db_password
WP_DB_PREFIX=wp_

# WordPress URLs
WP_HOME_URL=https://yourwordpresssite.com
WP_SITE_URL=https://yourwordpresssite.com
WP_UPLOADS_URL=/wp-content/uploads

# WordPress Integration Settings
WP_SYNC_USERS=true
WP_SYNC_ON_LOGIN=true
WP_AUTO_CREATE_USERS=true
WP_UPDATE_EXISTING_USERS=true
WP_AUTO_SYNC=true
WP_SYNC_AVATARS=true

# Optional: Caching
WP_CACHE_ENABLED=true
WP_CACHE_DURATION=60

# Optional: Logging
WP_LOG_AUTH=true
```

### 2. Database Configuration

The WordPress database connection is already configured in `config/database.php`:

```php
'wordpress' => [
    'driver' => 'mysql',
    'url' => env('WP_DATABASE_URL'),
    'host' => env('WP_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'port' => env('WP_DB_PORT', env('DB_PORT', '3306')),
    'database' => env('WP_DB_DATABASE', env('DB_DATABASE', 'wordpress')),
    'username' => env('WP_DB_USERNAME', env('DB_USERNAME', 'forge')),
    'password' => env('WP_DB_PASSWORD', env('DB_PASSWORD', '')),
    'unix_socket' => env('WP_DB_SOCKET', env('DB_SOCKET', '')),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => env('WP_DB_PREFIX', 'wp_'),
    'prefix_indexes' => true,
    'strict' => false,
    'engine' => null,
],
```

### 3. Corcel Configuration

The Corcel configuration is in `config/corcel.php`:

```php
return [
    'connection' => env('CORCEL_CONNECTION', 'wordpress'),
    'prefix' => env('WP_DB_PREFIX', 'wp_'),
    'uploads' => [
        'dir' => env('WP_UPLOADS_DIR', 'wp-content/uploads'),
        'url' => env('WP_UPLOADS_URL', '/wp-content/uploads'),
    ],
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
```

### 4. WordPress Configuration

The WordPress-specific configuration is in `config/wordpress.php`, which includes:
- Authentication settings
- User synchronization options
- Avatar handling
- Capability mapping
- Security settings

## Models

### WordPressUser Model

The main model for WordPress users (`app/Models/WordPressUser.php`):

```php
use App\Models\WordPressUser;

// Get all WordPress users
$users = WordPressUser::all();

// Find user by email
$user = WordPressUser::where('user_email', 'user@example.com')->first();

// Get user capabilities
$capabilities = $user->getCapabilities();

// Check if user is admin
$isAdmin = $user->is_admin;

// Get user's avatar
$avatar = $user->avatar_url;

// Convert to Laravel user
$laravelUser = $user->toLaravelUser();
```

### Available Relationships

```php
// Get user's posts
$posts = $user->posts;

// Get user's pages
$pages = $user->pages;

// Get user's comments
$comments = $user->comments;

// Get user meta
$firstName = $user->getMeta('first_name');
$lastName = $user->getMeta('last_name');
```

## Authentication Integration

### How It Works

1. **Dual Authentication**: The system first tries Laravel authentication, then falls back to WordPress
2. **Automatic User Sync**: WordPress users are automatically created as Laravel users
3. **Password Compatibility**: Supports WordPress password hashing (PHPass and bcrypt)
4. **Capability Checking**: Validates user capabilities before allowing access

### Login Flow

```php
// In AuthController
public function login(Request $request)
{
    $credentials = $request->only('email', 'password');
    
    // Try Laravel authentication first
    if (Auth::attempt($credentials, $remember)) {
        // Laravel user authenticated
        return redirect()->intended(route('chat.index'));
    }
    
    // Try WordPress authentication
    $wordpressAuth = app(WordPressAuthService::class);
    if ($wordpressAuth->attemptWordPressLogin($credentials, $remember)) {
        // WordPress user authenticated and synced
        return redirect()->intended(route('chat.index'));
    }
    
    // Both failed
    return back()->withErrors(['email' => 'Invalid credentials']);
}
```

## Usage Examples

### Basic WordPress Data Access

```php
use App\Models\WordPressUser;
use Corcel\Model\Post;
use Corcel\Model\Page;

// Get WordPress users
$wpUsers = WordPressUser::where('user_status', 0)->get();

// Get published posts
$posts = Post::published()->get();

// Get pages
$pages = Page::published()->get();

// Get user by role
$admins = WordPressUser::whereHas('meta', function($query) {
    $query->where('meta_key', 'wp_capabilities')
          ->where('meta_value', 'like', '%administrator%');
})->get();
```

### User Synchronization

```php
use App\Services\WordPressAuthService;

$wpAuthService = app(WordPressAuthService::class);

// Test WordPress connection
$connectionTest = $wpAuthService->testWordPressConnection();

if ($connectionTest['success']) {
    echo "Connected to WordPress with {$connectionTest['user_count']} users";
} else {
    echo "Connection failed: {$connectionTest['error']}";
}
```

### Working with User Meta

```php
// Get user meta
$user = WordPressUser::find(1);
$bio = $user->getMeta('description');
$phone = $user->getMeta('phone');

// Set user meta (if you have write access)
$user->saveMeta('custom_field', 'custom_value');

// Get all meta for a user
$allMeta = $user->meta;
```

## WordPress Plugin Integration

### Avatar Plugins

The system automatically detects avatars from popular WordPress plugins:

- WP User Avatar
- Simple Local Avatars
- Custom Avatar
- User Avatar

Fallback to Gravatar if no local avatar is found.

### Capability-Based Access

Control access based on WordPress user capabilities:

```php
// In config/wordpress.php
'capabilities' => [
    'allowed_capabilities' => [
        'administrator',
        'editor',
        'author',
        'contributor',
        'subscriber',
    ],
    'admin_capabilities' => [
        'administrator',
        'editor',
    ],
],
```

## Testing Commands

Use these Artisan commands to test your WordPress integration:

```bash
# Test WordPress connection
php artisan wp:test-connection

# List WordPress admins
php artisan wp:list-admins

# Make a user admin
php artisan wp:make-admin {user_id}
```

## Troubleshooting

### Common Issues

1. **Connection Failed**
   ```
   Check your WP_DB_* environment variables
   Ensure WordPress database is accessible
   Verify table prefix (WP_DB_PREFIX)
   ```

2. **Authentication Not Working**
   ```
   Check if WP_ENABLED=true in .env
   Verify user has allowed capabilities
   Check WordPress user status (user_status = 0)
   ```

3. **User Sync Issues**
   ```
   Ensure WP_AUTO_CREATE_USERS=true
   Check Laravel users table for wp_user_id column
   Verify username uniqueness constraints
   ```

4. **Missing Meta Data**
   ```
   Check WordPress table prefix in queries
   Verify Corcel configuration
   Ensure proper database connection
   ```

### Debug Mode

Enable WordPress authentication logging:

```env
WP_LOG_AUTH=true
LOG_LEVEL=debug
```

Check logs in `storage/logs/laravel.log` for detailed authentication flow.

### Database Permissions

Ensure your WordPress database user has the following permissions:
- SELECT on all WordPress tables
- INSERT/UPDATE on `wp_usermeta` (if syncing user data)

## Security Considerations

1. **Read-Only Access**: Consider using a read-only database user for WordPress
2. **Capability Validation**: Always check user capabilities before granting access
3. **Password Security**: WordPress passwords are maintained in WordPress format
4. **Connection Security**: Use SSL connections for database access in production

## Advanced Configuration

### Custom User Fields

Extend the WordPressUser model to include custom fields:

```php
// In WordPressUser model
public function getCustomFieldAttribute()
{
    return $this->getMeta('custom_field_key');
}
```

### Custom Authentication Rules

Add custom authentication logic in `WordPressAuthService`:

```php
protected function userHasAllowedCapabilities(WordPressUser $wpUser)
{
    // Add custom logic here
    if ($wpUser->getMeta('account_status') === 'suspended') {
        return false;
    }
    
    return $wpUser->canAccessChat();
}
```

## Performance Optimization

1. **Enable Caching**: Set `WP_CACHE_ENABLED=true`
2. **Eager Loading**: Use `with()` for relationships
3. **Database Indexing**: Ensure proper indexes on WordPress tables
4. **Connection Pooling**: Configure database connection pooling

## Conclusion

This setup provides seamless integration between your Laravel chat application and WordPress, allowing users to authenticate with their WordPress credentials while maintaining all WordPress user data and capabilities.

For additional questions or issues, refer to the [Corcel documentation](https://github.com/corcel/corcel) or check the application logs for detailed error information.
