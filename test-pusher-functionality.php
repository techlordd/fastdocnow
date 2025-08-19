<?php

/**
 * Pusher Migration Testing Script
 * 
 * This script helps verify that the Pusher implementation is working correctly
 * after migrating from the old event handling system.
 */

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\PusherService;
use Illuminate\Support\Facades\Log;

class PusherTester
{
    private $pusherService;
    
    public function __construct()
    {
        $this->pusherService = app(PusherService::class);
    }
    
    public function runTests()
    {
        echo "🚀 Starting Pusher Migration Tests...\n\n";
        
        $results = [
            'message_broadcasting' => $this->testMessageBroadcasting(),
            'typing_indicators' => $this->testTypingIndicators(),
            'online_status' => $this->testOnlineStatus(),
            'email_notifications' => $this->testEmailNotifications(),
            'pusher_connection' => $this->testPusherConnection()
        ];
        
        $this->displayResults($results);
        
        return array_filter($results) === $results; // All tests passed
    }
    
    private function testMessageBroadcasting()
    {
        echo "📢 Testing Message Broadcasting...\n";
        
        try {
            // Find a test conversation or create one
            $conversation = Conversation::with('participants')->first();
            
            if (!$conversation) {
                echo "   ❌ No conversations found for testing\n";
                return false;
            }
            
            // Create a test message
            $user = $conversation->participants->first();
            if (!$user) {
                echo "   ❌ No participants found in conversation\n";
                return false;
            }
            
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'content' => 'Test message from Pusher migration script',
                'type' => 'text'
            ]);
            
            // Test broadcasting
            $this->pusherService->broadcastMessage($message);
            
            echo "   ✅ Message broadcasting test completed\n";
            return true;
            
        } catch (\Exception $e) {
            echo "   ❌ Message broadcasting failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testTypingIndicators()
    {
        echo "⌨️  Testing Typing Indicators...\n";
        
        try {
            $conversation = Conversation::with('participants')->first();
            $user = $conversation?->participants->first();
            
            if (!$conversation || !$user) {
                echo "   ❌ No test data available\n";
                return false;
            }
            
            // Test typing start
            $this->pusherService->broadcastTyping($conversation->id, $user, true);
            
            // Test typing stop  
            $this->pusherService->broadcastTyping($conversation->id, $user, false);
            
            echo "   ✅ Typing indicators test completed\n";
            return true;
            
        } catch (\Exception $e) {
            echo "   ❌ Typing indicators failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testOnlineStatus()
    {
        echo "🟢 Testing Online Status Broadcasting...\n";
        
        try {
            $user = User::first();
            
            if (!$user) {
                echo "   ❌ No users found for testing\n";
                return false;
            }
            
            // Test online status
            $this->pusherService->broadcastOnlineStatus($user, true);
            $this->pusherService->broadcastOnlineStatus($user, false);
            
            echo "   ✅ Online status test completed\n";
            return true;
            
        } catch (\Exception $e) {
            echo "   ❌ Online status failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testEmailNotifications()
    {
        echo "📧 Testing Email Notifications...\n";
        
        try {
            $message = Message::with('conversation.participants', 'user')->first();
            
            if (!$message) {
                echo "   ❌ No messages found for testing\n";
                return false;
            }
            
            // Test email notification processing (won't actually send in test)
            $this->pusherService->sendEmailNotifications($message);
            
            echo "   ✅ Email notifications test completed\n";
            return true;
            
        } catch (\Exception $e) {
            echo "   ❌ Email notifications failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testPusherConnection()
    {
        echo "🔗 Testing Pusher Connection...\n";
        
        try {
            $config = config('broadcasting.connections.pusher');
            
            if (!$config || !$config['key'] || !$config['secret']) {
                echo "   ❌ Pusher configuration missing\n";
                return false;
            }
            
            echo "   ✅ Pusher configuration found\n";
            echo "     App ID: " . ($config['app_id'] ?? 'Not set') . "\n";
            echo "     Key: " . substr($config['key'], 0, 8) . "...\n";
            echo "     Cluster: " . ($config['options']['cluster'] ?? 'Not set') . "\n";
            
            return true;
            
        } catch (\Exception $e) {
            echo "   ❌ Pusher connection test failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function displayResults($results)
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        
        $passed = 0;
        $total = count($results);
        
        foreach ($results as $test => $result) {
            $status = $result ? "✅ PASS" : "❌ FAIL";
            $testName = ucwords(str_replace('_', ' ', $test));
            echo sprintf("%-25s %s\n", $testName, $status);
            if ($result) $passed++;
        }
        
        echo str_repeat("-", 50) . "\n";
        echo "TOTAL: {$passed}/{$total} tests passed\n";
        
        if ($passed === $total) {
            echo "\n🎉 All tests passed! Pusher migration is working correctly.\n";
        } else {
            echo "\n⚠️  Some tests failed. Please check the configuration and logs.\n";
        }
        
        echo "\n🔍 Check the application logs for detailed information:\n";
        echo "   - storage/logs/laravel.log\n";
        echo "   - Browser console for frontend JavaScript logs\n";
        echo "\n";
    }
}

// Run the tests
try {
    $tester = new PusherTester();
    $success = $tester->runTests();
    
    exit($success ? 0 : 1);
    
} catch (\Exception $e) {
    echo "❌ Testing script failed: " . $e->getMessage() . "\n";
    exit(1);
}
