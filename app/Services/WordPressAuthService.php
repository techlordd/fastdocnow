<?php

namespace App\Services;

use App\Models\User;
use App\Models\WordPressUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class WordPressAuthService
{
    /**
     * Attempt to authenticate user with WordPress credentials.
     */
    public function attemptWordPressLogin($credentials, $remember = false)
    {
        // Check if WordPress authentication is enabled
        if (!config('wordpress.enabled', false)) {
            return false;
        }

        try {
            // First try to authenticate against WordPress
            $wpUser = $this->authenticateWordPressUser($credentials);

            if (!$wpUser) {
                return false;
            }

            // Check if user has allowed capabilities
            if (!$this->userHasAllowedCapabilities($wpUser)) {
                Log::warning('WordPress user does not have allowed capabilities', [
                    'user_id' => $wpUser->ID,
                    'user_login' => $wpUser->user_login
                ]);
                return false;
            }

            // Check if we have a corresponding Laravel user
            $laravelUser = $this->getOrCreateLaravelUser($wpUser);

            if (!$laravelUser) {
                Log::error('Failed to create/retrieve Laravel user for WordPress user', ['wp_user_id' => $wpUser->ID]);
                return false;
            }

            // Login the Laravel user
            Auth::login($laravelUser, $remember);

            // Update login info
            $laravelUser->update([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('WordPress authentication error', [
                'error' => $e->getMessage(),
                'credentials' => array_keys($credentials)
            ]);
            return false;
        }
    }

    /**
     * Authenticate against WordPress database.
     */
    protected function authenticateWordPressUser($credentials)
    {
        $identifier = $credentials['email'] ?? $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$identifier || !$password) {
            return null;
        }

        // Try to find WordPress user by email or username
        $wpUser = WordPressUser::where('user_email', $identifier)
                               ->orWhere('user_login', $identifier)
                               ->first();

        if (!$wpUser) {
            return null;
        }

        // Validate password using WordPress provider
        $provider = Auth::createUserProvider('wordpress');
        if (!$provider->validateCredentials($wpUser, $credentials)) {
            return null;
        }

        return $wpUser;
    }

    /**
     * Get or create corresponding Laravel user for WordPress user.
     */
    protected function getOrCreateLaravelUser(WordPressUser $wpUser)
    {
        try {
            // First check if we already have a linked Laravel user
            $existingUser = User::where('wp_user_id', $wpUser->ID)->first();
            if ($existingUser) {
                // Sync data if configured to do so
                if (config('wordpress.sync.sync_on_login', true)) {
                    $this->syncWordPressData($existingUser, $wpUser);
                }
                return $existingUser;
            }

            // Check if auto-creation is disabled
            if (!config('wordpress.sync.auto_create_laravel_users', true)) {
                return null;
            }

            // Check if user exists by email (without wp_user_id link)
            $existingUserByEmail = User::where('email', $wpUser->user_email)->first();
            if ($existingUserByEmail) {
                // Link existing user to WordPress user
                $existingUserByEmail->update(['wp_user_id' => $wpUser->ID]);
                if (config('wordpress.sync.sync_on_login', true)) {
                    $this->syncWordPressData($existingUserByEmail, $wpUser);
                }
                return $existingUserByEmail;
            }

            // Use the enhanced toLaravelUser method from Corcel model
            return $wpUser->toLaravelUser();
        } catch (\Exception $e) {
            Log::error('Failed to get or create Laravel user for WordPress user', [
                'wp_user_id' => $wpUser->ID,
                'wp_email' => $wpUser->user_email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Sync WordPress user data to existing Laravel user.
     */
    protected function syncWordPressData(User $laravelUser, WordPressUser $wpUser)
    {
        if (!config('wordpress.sync.update_existing_users', true)) {
            return;
        }

        try {
            $updateData = [
                'first_name' => $wpUser->first_name ?: $laravelUser->first_name,
                'last_name' => $wpUser->last_name ?: $laravelUser->last_name,
                'is_admin' => $wpUser->is_admin,
            ];

            // Only update avatar if WordPress has one and sync is enabled
            if (config('wordpress.sync.sync_avatars', true)) {
                $wpAvatar = $wpUser->avatar_url;
                if ($wpAvatar) {
                    $updateData['avatar'] = $wpAvatar;
                }
            }

            // Sync bio/description
            $wpBio = $wpUser->getMeta('description');
            if ($wpBio) {
                $updateData['bio'] = $wpBio;
            }

            // Sync phone if available
            $wpPhone = $wpUser->getMeta('phone');
            if ($wpPhone) {
                $updateData['phone'] = $wpPhone;
            }

            $laravelUser->update($updateData);

            Log::info('WordPress user data synced successfully', [
                'user_id' => $laravelUser->id,
                'wp_user_id' => $wpUser->ID,
                'synced_fields' => array_keys($updateData)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync WordPress user data', [
                'user_id' => $laravelUser->id,
                'wp_user_id' => $wpUser->ID,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get WordPress user avatar.
     */
    protected function getWordPressAvatar(WordPressUser $wpUser)
    {
        if (!config('wordpress.sync.sync_avatars', true)) {
            return null;
        }

        // Try to get avatar from configured meta keys
        $avatarKeys = config('wordpress.avatar.meta_keys', [
            'wp_user_avatar',
            'simple_local_avatar',
            'custom_avatar',
            'user_avatar'
        ]);

        foreach ($avatarKeys as $key) {
            $avatar = $wpUser->getMeta($key);
            if ($avatar) {
                return is_array($avatar) ? ($avatar['url'] ?? null) : $avatar;
            }
        }

        // Fallback to Gravatar if enabled
        if (config('wordpress.avatar.fallback_to_gravatar', true)) {
            $email = $wpUser->user_email;
            $hash = md5(strtolower(trim($email)));
            $size = config('wordpress.avatar.default_size', 200);
            return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
        }

        return null;
    }

    /**
     * Check if WordPress database connection is available.
     */
    public function isWordPressConnectionAvailable()
    {
        try {
            DB::connection('wordpress')->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::warning('WordPress database connection failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if WordPress user has allowed capabilities.
     */
    protected function userHasAllowedCapabilities(WordPressUser $wpUser)
    {
        // Use the enhanced canAccessChat method from the WordPressUser model
        return $wpUser->canAccessChat();
    }

    /**
     * Test WordPress connection and get user count with Corcel integration.
     */
    public function testWordPressConnection()
    {
        try {
            $userCount = WordPressUser::count();
            $adminCount = WordPressUser::whereHas('meta', function($query) {
                $prefix = config('corcel.prefix', 'wp_');
                $query->where('meta_key', $prefix . 'capabilities')
                      ->where('meta_value', 'like', '%administrator%');
            })->count();

            // Test Corcel functionality
            $sampleUser = WordPressUser::first();
            $corcelWorking = false;
            if ($sampleUser) {
                $capabilities = $sampleUser->getCapabilities();
                $corcelWorking = is_array($capabilities);
            }

            return [
                'success' => true,
                'user_count' => $userCount,
                'admin_count' => $adminCount,
                'corcel_working' => $corcelWorking,
                'message' => "Successfully connected to WordPress database with {$userCount} users ({$adminCount} admins). Corcel integration: " . ($corcelWorking ? 'Working' : 'Issue detected'),
                'config' => [
                    'enabled' => config('wordpress.enabled', false),
                    'corcel_connection' => config('corcel.connection', 'wordpress'),
                    'prefix' => config('corcel.prefix', 'wp_'),
                    'auto_sync' => config('wordpress.sync.auto_sync', true),
                    'allowed_capabilities' => config('wordpress.capabilities.allowed_capabilities', [])
                ],
                'sample_user' => $sampleUser ? [
                    'id' => $sampleUser->ID,
                    'login' => $sampleUser->user_login,
                    'email' => $sampleUser->user_email,
                    'display_name' => $sampleUser->display_name,
                    'capabilities' => $sampleUser->getCapabilities(),
                    'role' => $sampleUser->getRole(),
                    'is_admin' => $sampleUser->is_admin,
                    'can_access_chat' => $sampleUser->canAccessChat(),
                ] : null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to connect to WordPress database or Corcel integration issue.'
            ];
        }
    }
}
