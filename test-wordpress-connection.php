<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WordPressAuthService;
use App\Models\WordPressUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

echo "🔍 Testing WordPress Connection...\n\n";

try {
    // Test 1: Database Connection
    echo "1️⃣  Testing database connection...\n";
    $pdo = DB::connection('wordpress')->getPdo();
    echo "✅ Database connection successful\n\n";

    // Test 2: Configuration
    echo "2️⃣  Checking configuration...\n";
    echo "WordPress Enabled: " . (config('wordpress.enabled') ? 'Yes' : 'No') . "\n";
    echo "Connection: " . config('corcel.connection') . "\n";
    echo "Prefix: " . config('corcel.prefix') . "\n";
    echo "Host: " . config('database.connections.wordpress.host') . "\n";
    echo "Database: " . config('database.connections.wordpress.database') . "\n\n";

    // Test 3: WordPress Tables
    echo "3️⃣  Testing WordPress tables...\n";
    $prefix = config('corcel.prefix', 'wp_');
    $tables = [
        $prefix . 'users',
        $prefix . 'usermeta',
        $prefix . 'posts',
        $prefix . 'options'
    ];

    foreach ($tables as $table) {
        $exists = DB::connection('wordpress')->select("SHOW TABLES LIKE '" . $table . "'");
        if (empty($exists)) {
            echo "❌ Table {$table} does not exist\n";
        } else {
            echo "✅ Table {$table} exists\n";
        }
    }
    echo "\n";

    // Test 4: Corcel User Model
    echo "4️⃣  Testing Corcel integration...\n";
    $userCount = WordPressUser::count();
    echo "✅ Found {$userCount} WordPress users\n";

    // Test sample user
    $sampleUser = WordPressUser::first();
    if ($sampleUser) {
        echo "✅ Sample user: {$sampleUser->display_name} ({$sampleUser->user_email})\n";
        
        // Test capabilities
        $capabilities = $sampleUser->getCapabilities();
        echo "✅ User capabilities: " . implode(', ', $capabilities) . "\n";
        
        // Test can access chat
        $canAccess = $sampleUser->canAccessChat();
        echo "✅ Can access chat: " . ($canAccess ? 'Yes' : 'No') . "\n";
        
        // Test avatar
        $avatar = $sampleUser->avatar_url;
        echo "✅ Avatar URL: " . ($avatar ?: 'None') . "\n";
    }
    echo "\n";

    // Test 5: WordPress Auth Service
    echo "5️⃣  Testing WordPress Auth Service...\n";
    $authService = app(WordPressAuthService::class);
    $connectionTest = $authService->testWordPressConnection();
    
    if ($connectionTest['success']) {
        echo "✅ Auth service test successful\n";
        echo "   Total users: {$connectionTest['user_count']}\n";
        echo "   Admin users: {$connectionTest['admin_count']}\n";
        echo "   Corcel working: " . ($connectionTest['corcel_working'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ Auth service test failed: {$connectionTest['error']}\n";
    }

    echo "\n🎉 WordPress connection test completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
