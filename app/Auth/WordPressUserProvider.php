<?php

namespace App\Auth;

use App\Models\WordPressUser;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class WordPressUserProvider extends EloquentUserProvider
{
    /**
     * Create a new WordPress user provider.
     */
    public function __construct($model)
    {
        // Call parent constructor with the WordPress user model
        parent::__construct(app('hash'), $model);
    }

    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById($identifier)
    {
        // Use Corcel's find method which handles caching and relationships better
        return $this->createModel()->find($identifier);
    }

    /**
     * Retrieve a user by the given credentials with enhanced Corcel support.
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
            (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return null;
        }

        $query = $this->createModel();

        foreach ($credentials as $key => $value) {
            if ($key === 'password') {
                continue;
            }

            if ($key === 'email') {
                $query->where('user_email', $value);
            } elseif ($key === 'username') {
                $query->where('user_login', $value);
            } elseif ($key === 'login') {
                // Support both email and username in a single field
                $query->where(function($q) use ($value) {
                    $q->where('user_email', $value)
                      ->orWhere('user_login', $value);
                });
            } else {
                $query->where($key, $value);
            }
        }

        // Eager load user meta for better performance with Corcel
        return $query->with('meta')->first();
    }

    /**
     * Validate a user against the given credentials with Corcel enhancements.
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $password = $credentials['password'] ?? '';

        if (empty($password)) {
            return false;
        }

        // Check if user can access the chat system (using Corcel capabilities)
        if (method_exists($user, 'canAccessChat') && !$user->canAccessChat()) {
            return false;
        }

        // WordPress uses a different password hashing system
        return $this->checkWordPressPassword($password, $user->getAuthPassword());
    }

    /**
     * Check WordPress password hash.
     */
    protected function checkWordPressPassword($password, $hash)
    {
        // WordPress password checking logic
        if (empty($hash)) {
            return false;
        }

        // Check if it's a standard WordPress hash
        if (strlen($hash) === 34 && substr($hash, 0, 3) === '$P$') {
            return $this->checkPhpassHash($password, $hash);
        }

        // Check if it's a bcrypt hash (newer WordPress versions)
        if (strlen($hash) === 60 && substr($hash, 0, 4) === '$2y$') {
            return password_verify($password, $hash);
        }

        // Fallback to MD5 (very old WordPress installations)
        return md5($password) === $hash;
    }

    /**
     * Check PHPass hash (WordPress default).
     */
    protected function checkPhpassHash($password, $hash)
    {
        // Extract iteration count and salt
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        
        if (strlen($hash) !== 34) {
            return false;
        }

        $count_log2 = strpos($itoa64, $hash[3]);
        if ($count_log2 < 7 || $count_log2 > 30) {
            return false;
        }

        $count = 1 << $count_log2;
        $salt = substr($hash, 4, 8);

        if (strlen($salt) !== 8) {
            return false;
        }

        $hash_result = md5($salt . $password, true);
        
        for ($i = 0; $i < $count; $i++) {
            $hash_result = md5($hash_result . $password, true);
        }

        $output = substr($hash, 0, 12);
        $output .= $this->encode64($hash_result, 16);

        return $output === $hash;
    }

    /**
     * Encode hash for PHPass.
     */
    protected function encode64($input, $count)
    {
        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $output = '';
        $i = 0;

        do {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];
            
            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }
            
            $output .= $itoa64[($value >> 6) & 0x3f];
            
            if ($i++ >= $count) {
                break;
            }
            
            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }
            
            $output .= $itoa64[($value >> 12) & 0x3f];
            
            if ($i++ >= $count) {
                break;
            }
            
            $output .= $itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }

    /**
     * Update the "remember me" token for the given user in storage using Corcel.
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Store remember token in WordPress user meta using Corcel
        if (method_exists($user, 'saveMeta')) {
            $user->saveMeta('laravel_remember_token', $token);
        } elseif (method_exists($user, 'setRememberToken')) {
            $user->setRememberToken($token);
        }
    }
}
