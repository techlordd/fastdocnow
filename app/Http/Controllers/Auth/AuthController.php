<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WordPressAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/chat');
        }
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');
        $email = $credentials['email'];

        // Check if WordPress is enabled
        $wordpressEnabled = config('wordpress.enabled', false);
        $wordpressAuth = app(WordPressAuthService::class);

        // First, try Laravel authentication for existing Laravel users
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Update user's last login
            Auth::user()->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            Log::info('User logged in successfully via Laravel authentication', [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'wp_user_id' => Auth::user()->wp_user_id ?? 'none'
            ]);

            return redirect()->intended('/chat');
        }

        // If WordPress is enabled and Laravel auth failed, try WordPress authentication
        if ($wordpressEnabled) {
            try {
                if (!$wordpressAuth->isWordPressConnectionAvailable()) {
                    Log::error('WordPress database connection not available');
                    return back()->withErrors([
                        'email' => 'Authentication service temporarily unavailable. Please try again later.',
                    ])->withInput();
                }

                if ($wordpressAuth->attemptWordPressLogin($credentials, $remember)) {
                    $request->session()->regenerate();

                    Log::info('User logged in successfully via WordPress authentication', [
                        'user_id' => Auth::id(),
                        'email' => Auth::user()->email,
                        'wp_user_id' => Auth::user()->wp_user_id ?? 'none'
                    ]);

                    return redirect()->intended('/chat')
                        ->with('success', 'Welcome! You have been authenticated via WordPress.');
                }
            } catch (\Exception $e) {
                Log::error('WordPress authentication error', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->withErrors([
                    'email' => 'Authentication error occurred. Please contact support if this continues.',
                ])->withInput();
            }
        }

        // Both authentication methods failed
        Log::warning('Login attempt failed for both Laravel and WordPress authentication', [
            'email' => $email,
            'ip' => $request->ip(),
            'wordpress_enabled' => $wordpressEnabled
        ]);

        return back()->withErrors([
            'email' => 'Invalid credentials provided.',
        ])->withInput();
    }

    public function showSignup()
    {
        if (Auth::check()) {
            return redirect('/chat');
        }
        
        return view('auth.signup');
    }

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'username' => 'required|string|max:30|unique:users|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
            'agree_terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_notifications' => $request->has('email_updates'),
            'email_verified_at' => now(), // Auto-verify for demo
        ]);

        Auth::login($user);

        return redirect('/chat')->with('success', 'Account created successfully!');
    }

    public function logout(Request $request)
    {
        // Update user's last seen
        if (Auth::check()) {
            Auth::user()->update(['last_seen_at' => now()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect('/login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
