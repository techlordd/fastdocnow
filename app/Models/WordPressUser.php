<?php

namespace App\Models;

use Corcel\Model\User as CorcelUser;
use Illuminate\Notifications\Notifiable;

class WordPressUser extends CorcelUser
{
    use Notifiable;

    protected $fillable = [
        'user_login',
        'user_email',
        'user_nicename',
        'display_name',
    ];

    protected $hidden = [
        'user_pass',
    ];

    // Override connection to use our configured WordPress connection
    protected $connection = 'wordpress';

    public function getEmailForVerification()
    {
        return $this->user_email;
    }

    // Custom accessors to match Laravel User model and enhance Corcel functionality
    public function getEmailAttribute()
    {
        return $this->user_email;
    }

    public function getNameAttribute()
    {
        return $this->display_name ?: $this->user_nicename;
    }

    public function getFirstNameAttribute()
    {
        // First try to get from meta fields
        $firstName = $this->getMeta('first_name');
        if ($firstName) {
            return $firstName;
        }

        // Fallback to parsing display name
        $name_parts = explode(' ', $this->display_name ?: $this->user_nicename);
        return $name_parts[0] ?? '';
    }

    public function getLastNameAttribute()
    {
        // First try to get from meta fields
        $lastName = $this->getMeta('last_name');
        if ($lastName) {
            return $lastName;
        }

        // Fallback to parsing display name
        $name_parts = explode(' ', $this->display_name ?: $this->user_nicename);
        return count($name_parts) > 1 ? implode(' ', array_slice($name_parts, 1)) : '';
    }

    public function getInitialsAttribute()
    {
        $first_name = $this->first_name;
        $last_name = $this->last_name;
        return strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
    }

    public function getUsernameAttribute()
    {
        return $this->user_login;
    }

    // Enhanced meta access using Corcel's built-in functionality
    public function getMeta($key, $default = null)
    {
        try {
            // Use Corcel's built-in meta functionality
            $value = parent::getMeta($key, $default);

            // Handle WordPress serialized data
            if (is_string($value) && is_serialized($value)) {
                return maybe_unserialize($value);
            }

            return $value;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("WordPress meta retrieval failed for key: {$key}", [
                'error' => $e->getMessage(),
                'user_id' => $this->ID ?? 'unknown'
            ]);
            return $default;
        }
    }

    // Get avatar URL from WordPress using Corcel and various avatar plugins
    public function getAvatarUrlAttribute()
    {
        // Try to get custom avatar from various WordPress avatar plugins
        $avatarKeys = config('wordpress.avatar.meta_keys', [
            'wp_user_avatar',
            'simple_local_avatar',
            'custom_avatar',
            'user_avatar',
            'avatar'
        ]);

        foreach ($avatarKeys as $key) {
            $avatar = $this->getMeta($key);
            if ($avatar) {
                // Handle array format (like Simple Local Avatars plugin)
                if (is_array($avatar)) {
                    return $avatar['url'] ?? $avatar['full'] ?? null;
                }
                // Handle string format
                if (is_string($avatar) && !empty($avatar)) {
                    return $avatar;
                }
            }
        }

        // Try to get avatar from attachments (for plugins that store avatars as attachments)
        $avatarId = $this->getMeta('wp_user_avatar');
        if ($avatarId && is_numeric($avatarId)) {
            $attachment = \Corcel\Model\Attachment::find($avatarId);
            if ($attachment && $attachment->url) {
                return $attachment->url;
            }
        }

        // Fallback to Gravatar if enabled
        if (config('wordpress.avatar.fallback_to_gravatar', true)) {
            $email = $this->user_email;
            $hash = md5(strtolower(trim($email)));
            $size = config('wordpress.avatar.default_size', 200);
            return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
        }

        return null;
    }

    // Enhanced capability checking using WordPress prefix and Corcel functionality
    public function hasCapability($capability)
    {
        try {
            $prefix = config('corcel.prefix', 'wp_');
            $capabilities = $this->getMeta($prefix . 'capabilities');

            if ($capabilities) {
                // Corcel automatically handles unserialization
                if (is_array($capabilities)) {
                    return array_key_exists($capability, $capabilities);
                }
                // Fallback for string format
                if (is_string($capabilities)) {
                    $caps = maybe_unserialize($capabilities);
                    return is_array($caps) && array_key_exists($capability, $caps);
                }
            }

            // Also check user level for backward compatibility
            $userLevel = $this->getMeta($prefix . 'user_level');
            if ($userLevel && $capability === 'administrator' && $userLevel >= 10) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("WordPress capability check failed", [
                'capability' => $capability,
                'user_id' => $this->ID ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // Get all user capabilities
    public function getCapabilities()
    {
        $prefix = config('corcel.prefix', 'wp_');
        $capabilities = $this->getMeta($prefix . 'capabilities');

        if (is_array($capabilities)) {
            return array_keys($capabilities);
        }

        if (is_string($capabilities)) {
            $caps = maybe_unserialize($capabilities);
            return is_array($caps) ? array_keys($caps) : [];
        }

        return [];
    }

    // Get user role
    public function getRole()
    {
        $capabilities = $this->getCapabilities();

        // Common WordPress roles in order of hierarchy
        $roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];

        foreach ($roles as $role) {
            if (in_array($role, $capabilities)) {
                return $role;
            }
        }

        return 'subscriber'; // Default role
    }

    // Check if user is WordPress admin
    public function getIsAdminAttribute()
    {
        return $this->hasCapability('administrator');
    }

    // Enhanced conversion to Laravel user with better data mapping
    public function toLaravelUser()
    {
        try {
            // Validate required fields
            if (empty($this->user_email) || empty($this->user_login)) {
                throw new \Exception("WordPress user missing required fields: email or login");
            }

            // Generate unique username if needed
            $username = sanitize_user($this->user_login, true);
            if (empty($username)) {
                $username = 'wp_user_' . $this->ID;
            }

            $originalUsername = $username;
            $counter = 1;

            while (User::where('username', $username)->where('wp_user_id', '!=', $this->ID)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;

                // Prevent infinite loop
                if ($counter > 100) {
                    $username = 'wp_user_' . $this->ID . '_' . time();
                    break;
                }
            }

            // Prepare user data
            $userData = [
                'first_name' => $this->first_name ?: 'User',
                'last_name' => $this->last_name ?: '',
                'username' => $username,
                'email' => $this->user_email,
                'password' => $this->user_pass, // Keep WordPress hash
                'email_verified_at' => now(),
                'is_admin' => $this->is_admin,
                'avatar' => $this->avatar_url,
                'bio' => $this->getMeta('description') ?: '',
                'phone' => $this->getMeta('phone') ?: '',
            ];

            // Create or update the Laravel user
            $user = User::updateOrCreate(
                ['wp_user_id' => $this->ID],
                $userData
            );

            \Illuminate\Support\Facades\Log::info('Laravel user created/updated from WordPress user', [
                'laravel_user_id' => $user->id,
                'wp_user_id' => $this->ID,
                'email' => $this->user_email,
                'username' => $username
            ]);

            return $user;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to convert WordPress user to Laravel user', [
                'wp_user_id' => $this->ID ?? 'unknown',
                'wp_email' => $this->user_email ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    // Get WordPress posts for this user
    public function posts()
    {
        return $this->hasMany(\Corcel\Model\Post::class, 'post_author', 'ID');
    }

    // Get WordPress pages for this user
    public function pages()
    {
        return $this->hasMany(\Corcel\Model\Page::class, 'post_author', 'ID');
    }

    // Get user's comments
    public function comments()
    {
        return $this->hasMany(\Corcel\Model\Comment::class, 'user_id', 'ID');
    }

    // Check if user can access the chat system
    public function canAccessChat()
    {
        try {
            // Check if user is active (user_status = 0 in WordPress)
            if (isset($this->user_status) && $this->user_status != 0) {
                return false;
            }

            $allowedCapabilities = config('wordpress.capabilities.allowed_capabilities', [
                'administrator', 'editor', 'author', 'contributor', 'subscriber'
            ]);

            // Check each allowed capability
            foreach ($allowedCapabilities as $capability) {
                if ($this->hasCapability($capability)) {
                    return true;
                }
            }

            // Fallback: check if user has any capabilities at all
            $userCapabilities = $this->getCapabilities();
            if (!empty($userCapabilities)) {
                // If user has any WordPress capabilities, they can probably access chat
                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("WordPress canAccessChat check failed", [
                'user_id' => $this->ID ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            // Default to false for security
            return false;
        }
    }
}
