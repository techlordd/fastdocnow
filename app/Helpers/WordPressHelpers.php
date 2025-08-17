<?php

/**
 * WordPress Helper Functions
 * 
 * This file contains WordPress compatibility functions that are commonly
 * used in WordPress but not available in Laravel by default.
 */

if (!function_exists('maybe_unserialize')) {
    /**
     * Unserialize value only if it is serialized.
     *
     * @param string $original Maybe unserialized original, if is needed.
     * @return mixed Unserialized data can be any type.
     */
    function maybe_unserialize($original)
    {
        if (is_serialized($original)) {
            return @unserialize($original);
        }
        return $original;
    }
}

if (!function_exists('is_serialized')) {
    /**
     * Check value to find if it was serialized.
     *
     * @param string $data Value to check to see if was serialized.
     * @param bool $strict Optional. Whether to be strict about the end of the string. Default true.
     * @return bool False if not serialized and true if it was.
     */
    function is_serialized($data, $strict = true)
    {
        // If it isn't a string, it isn't serialized.
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
                // or break
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
        }
        return false;
    }
}

if (!function_exists('maybe_serialize')) {
    /**
     * Serialize data, if needed.
     *
     * @param string|array|object $data Data that might be serialized.
     * @return mixed A scalar data
     */
    function maybe_serialize($data)
    {
        if (is_array($data) || is_object($data)) {
            return serialize($data);
        }

        // Double serialization is required for backward compatibility.
        if (is_serialized($data, false)) {
            return serialize($data);
        }

        return $data;
    }
}

if (!function_exists('wp_hash_password')) {
    /**
     * Create a hash (encrypt) of a plain text password.
     *
     * @param string $password Plain text user password to hash
     * @return string The hash string of the password
     */
    function wp_hash_password($password)
    {
        // Use Laravel's bcrypt for new passwords, but keep WordPress compatibility
        if (function_exists('password_hash')) {
            return password_hash($password, PASSWORD_BCRYPT);
        }
        
        // Fallback to WordPress-style PHPass
        return wp_phpass_hash($password);
    }
}

if (!function_exists('wp_phpass_hash')) {
    /**
     * WordPress PHPass password hasher.
     *
     * @param string $password Plain text password
     * @return string Hashed password
     */
    function wp_phpass_hash($password)
    {
        $random_state = microtime() . uniqid(rand(), true);
        $random = '';
        for ($i = 0; $i < 6; $i++) {
            $random .= pack('S', mt_rand(0, 0xffff));
        }
        $salt = substr(md5($random_state . $random), 0, 8);
        $count = 8; // 2^8 iterations
        
        $hash = md5($salt . $password, true);
        do {
            $hash = md5($hash . $password, true);
        } while (--$count);

        $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $output = '$P$';
        $output .= $itoa64[8]; // log2(256) = 8
        $output .= substr(md5($random_state . $random), 0, 8);
        
        $count = 16;
        $i = 0;
        do {
            $value = ord($hash[$i++]);
            $output .= $itoa64[$value & 0x3f];
            if ($i < $count) {
                $value |= ord($hash[$i]) << 8;
            }
            $output .= $itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            if ($i < $count) {
                $value |= ord($hash[$i]) << 16;
            }
            $output .= $itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count) {
                break;
            }
            $output .= $itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }
}

if (!function_exists('wp_check_password')) {
    /**
     * Checks the plaintext password against the encrypted Password.
     *
     * @param string $password Plaintext user's password
     * @param string $hash Hash of the user's password to check against.
     * @return bool False, if the $password does not match the hashed password
     */
    function wp_check_password($password, $hash)
    {
        // Check if it's a bcrypt hash (newer WordPress versions)
        if (strlen($hash) === 60 && substr($hash, 0, 4) === '$2y$') {
            return password_verify($password, $hash);
        }

        // Check if it's a PHPass hash (WordPress default)
        if (strlen($hash) === 34 && substr($hash, 0, 3) === '$P$') {
            return wp_check_phpass($password, $hash);
        }

        // Fallback to MD5 (very old WordPress installations)
        return md5($password) === $hash;
    }
}

if (!function_exists('wp_check_phpass')) {
    /**
     * Check PHPass hash (WordPress default).
     *
     * @param string $password Plain text password
     * @param string $hash PHPass hash
     * @return bool
     */
    function wp_check_phpass($password, $hash)
    {
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
        $output .= wp_encode64($hash_result, 16);

        return $output === $hash;
    }
}

if (!function_exists('wp_encode64')) {
    /**
     * Encode hash for PHPass.
     *
     * @param string $input Input string
     * @param int $count Number of characters
     * @return string
     */
    function wp_encode64($input, $count)
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
}

if (!function_exists('wp_generate_password')) {
    /**
     * Generates a random password drawn from the defined set of characters.
     *
     * @param int $length Optional. The length of password to generate. Default 12.
     * @param bool $special_chars Optional. Whether to include special characters. Default true.
     * @param bool $extra_special_chars Optional. Whether to include extra special characters. Default false.
     * @return string The random password.
     */
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) {
            $chars .= '!@#$%^&*()';
        }
        if ($extra_special_chars) {
            $chars .= '-_ []{}<>~`+=,.;:/?|';
        }

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= substr($chars, rand(0, strlen($chars) - 1), 1);
        }

        return $password;
    }
}

if (!function_exists('sanitize_user')) {
    /**
     * Sanitizes a username, stripping out unsafe characters.
     *
     * @param string $username The username to be sanitized.
     * @param bool $strict If set limits $username to specific characters. Default false.
     * @return string The sanitized username, after passing through filters.
     */
    function sanitize_user($username, $strict = false)
    {
        $raw_username = $username;
        $username = wp_strip_all_tags($username);
        $username = remove_accents($username);
        // Kill octets
        $username = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);
        $username = preg_replace('/&.+?;/', '', $username); // Kill entities

        // If strict, reduce to ASCII for max portability.
        if ($strict) {
            $username = preg_replace('|[^a-z0-9 _.\-@]|i', '', $username);
        }

        $username = trim($username);
        // Consolidate contiguous whitespace
        $username = preg_replace('|\s+|', ' ', $username);

        return $username;
    }
}

if (!function_exists('wp_strip_all_tags')) {
    /**
     * Properly strip all HTML tags including script and style.
     *
     * @param string $string String containing HTML tags
     * @param bool $remove_breaks Optional. Whether to remove left over line breaks and white space chars
     * @return string The processed string.
     */
    function wp_strip_all_tags($string, $remove_breaks = false)
    {
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $string = strip_tags($string);

        if ($remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }

        return trim($string);
    }
}

if (!function_exists('remove_accents')) {
    /**
     * Converts all accent characters to ASCII characters.
     *
     * @param string $string Text that might have accent characters
     * @return string Filtered string with replaced "nice" characters.
     */
    function remove_accents($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        $chars = array(
            // Decompositions for Latin-1 Supplement
            'ª' => 'a', 'º' => 'o',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'Æ' => 'AE','Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'TH','ß' => 's',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'æ' => 'ae','ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y', 'Ø' => 'O',
        );

        $string = strtr($string, $chars);

        return $string;
    }
}
