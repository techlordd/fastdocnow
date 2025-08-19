<?php
/*
 * Simple WordPress Connection Test
 *
 * This file tests the basic WordPress database connection.
 * Run: php test-wp-auth.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "WordPress Connection Test\n";
echo "========================\n\n";

try {
    // Test basic database connection
    echo "Testing WordPress database connection...\n";
    $pdo = DB::connection('wordpress')->getPdo();
    echo "✅ Database connection successful!\n\n";

    // Check if WordPress tables exist
    $prefix = config('corcel.prefix', 'wp_');
    echo "Checking WordPress tables with prefix: {$prefix}\n";

    $tables = DB::connection('wordpress')->select("SHOW TABLES LIKE '{$prefix}users'");
    if (!empty($tables)) {
        echo "✅ WordPress users table found!\n";

        // Count users
        $userCount = DB::connection('wordpress')->select("SELECT COUNT(*) as count FROM {$prefix}users")[0]->count;
        echo "📊 Found {$userCount} WordPress users\n\n";

        echo "🎉 WordPress integration is ready!\n";
        echo "ℹ️  Set WP_ENABLED=true in .env to enable WordPress login\n";
    } else {
        echo "❌ WordPress users table not found\n";
        echo "Check your database configuration\n";
    }

} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "Please check your WordPress database configuration in .env file\n";
}

echo "\nTest completed.\n";
