<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = $this->getDashboardStats();
        $recentMessages = $this->getRecentMessages();
        $systemConfig = $this->getSystemConfig();

        return view('admin.dashboard.index', compact('stats', 'recentMessages', 'systemConfig'));
    }

    public function updateEmailConfig(Request $request)
    {
        $request->validate([
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'required|string|max:255',
            'smtp_encryption' => 'required|in:tls,ssl,none',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string|max:255',
        ]);

        // Store email configuration
        $config = [
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'smtp_username' => $request->smtp_username,
            'smtp_password' => encrypt($request->smtp_password),
            'smtp_encryption' => $request->smtp_encryption,
            'mail_from_address' => $request->mail_from_address,
            'mail_from_name' => $request->mail_from_name,
        ];

        Cache::put('email_config', $config, now()->addYear());

        return response()->json([
            'success' => true,
            'message' => 'Email configuration updated successfully!'
        ]);
    }

    public function updateSmsConfig(Request $request)
    {
        $request->validate([
            'sms_provider' => 'required|in:twilio,vonage,custom',
            'sms_api_key' => 'required|string|max:255',
            'sms_api_secret' => 'required|string|max:255',
            'sms_from_number' => 'required|string|max:20',
            'sms_enabled' => 'boolean',
        ]);

        $config = [
            'sms_provider' => $request->sms_provider,
            'sms_api_key' => encrypt($request->sms_api_key),
            'sms_api_secret' => encrypt($request->sms_api_secret),
            'sms_from_number' => $request->sms_from_number,
            'sms_enabled' => $request->boolean('sms_enabled'),
        ];

        Cache::put('sms_config', $config, now()->addYear());

        return response()->json([
            'success' => true,
            'message' => 'SMS configuration updated successfully!'
        ]);
    }

    public function updateSystemTheme(Request $request)
    {
        $request->validate([
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'accent_color' => 'required|string|max:7',
            'default_theme' => 'required|in:light,dark,auto',
            'app_name' => 'required|string|max:100',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $config = [
            'primary_color' => $request->primary_color,
            'secondary_color' => $request->secondary_color,
            'accent_color' => $request->accent_color,
            'default_theme' => $request->default_theme,
            'app_name' => $request->app_name,
        ];

        // Handle logo upload
        if ($request->hasFile('app_logo')) {
            $logoPath = $request->file('app_logo')->store('system', 'public');
            $config['app_logo'] = $logoPath;
        }

        Cache::put('system_theme', $config, now()->addYear());

        return response()->json([
            'success' => true,
            'message' => 'System theme updated successfully!',
            'config' => $config
        ]);
    }

    public function testEmailConfig(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            // Send test email
            \Mail::raw('This is a test email from your chat system.', function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject('Test Email - Chat System');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testSmsConfig(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            // This would integrate with your SMS service
            // For now, just return success
            return response()->json([
                'success' => true,
                'message' => 'Test SMS functionality ready! (Integration needed with SMS provider)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test SMS: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getDashboardStats()
    {
        return [
            'total_users' => User::count(),
            'total_admins' => User::where('is_admin', true)->count(),
            'total_contacts' => Contact::count(),
            'active_contacts' => Contact::where('is_active', true)->count(),
            'total_conversations' => Conversation::count(),
            'total_messages' => Message::count(),
            'messages_today' => Message::whereDate('created_at', today())->count(),
            'online_users' => User::where('last_seen_at', '>', now()->subMinutes(5))->count(),
            'conversations_today' => Conversation::whereDate('created_at', today())->count(),
            'active_conversations' => Conversation::whereHas('messages', function($q) {
                $q->where('created_at', '>', now()->subHours(24));
            })->count(),
        ];
    }

    private function getRecentMessages()
    {
        return Message::with(['user', 'conversation.contact'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content ? \Str::limit($message->content, 100) : '[' . $message->type . ']',
                    'user' => $message->user->name ?? 'Unknown',
                    'contact' => $message->conversation->contact->name ?? 'Direct Chat',
                    'created_at' => $message->created_at->diffForHumans(),
                    'type' => $message->type,
                ];
            });
    }

    private function getSystemConfig()
    {
        return [
            'email' => Cache::get('email_config', [
                'smtp_host' => env('MAIL_HOST', ''),
                'smtp_port' => env('MAIL_PORT', 587),
                'smtp_username' => env('MAIL_USERNAME', ''),
                'smtp_encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'mail_from_address' => env('MAIL_FROM_ADDRESS', ''),
                'mail_from_name' => env('MAIL_FROM_NAME', ''),
            ]),
            'sms' => Cache::get('sms_config', [
                'sms_provider' => 'twilio',
                'sms_enabled' => false,
                'sms_from_number' => '',
            ]),
            'theme' => Cache::get('system_theme', [
                'primary_color' => '#6600ff',
                'secondary_color' => '#4400cc',
                'accent_color' => '#22c55e',
                'default_theme' => 'light',
                'app_name' => 'Chat System',
            ]),
        ];
    }
}
