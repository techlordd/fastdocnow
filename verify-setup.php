<?php

echo "🚀 DocNow Chat Setup Verification\n";
echo "=============================\n\n";

// Check Laravel version
echo "Laravel Version: " . app()->version() . "\n";

// Check database connection
try {
    DB::connection()->getPdo();
    echo "✅ Database: Connected (SQLite)\n";
} catch (\Exception $e) {
    echo "❌ Database: " . $e->getMessage() . "\n";
}

// Check required tables
$tables = ['users', 'conversations', 'messages', 'friendships', 'conversation_participants'];
foreach ($tables as $table) {
    try {
        DB::table($table)->count();
        echo "✅ Table '$table': Exists\n";
    } catch (\Exception $e) {
        echo "❌ Table '$table': Missing\n";
    }
}

// Check broadcasting configuration
echo "\nBroadcasting Configuration:\n";
echo "Driver: " . config('broadcasting.default') . "\n";
echo "Reverb App Key: " . (config('reverb.apps.0.app_key') ? '✅ Set' : '❌ Missing') . "\n";

// Check storage link
if (file_exists(public_path('storage'))) {
    echo "✅ Storage Link: Created\n";
} else {
    echo "❌ Storage Link: Missing (run 'php artisan storage:link')\n";
}

// Test User model friends relationship
try {
    $user = \App\Models\User::first();
    if ($user) {
        $friends = $user->friends()->count();
        echo "✅ Friends Relationship: Working ($friends friends)\n";
    } else {
        echo "⚠️ No users found in database\n";
    }
} catch (\Exception $e) {
    echo "❌ Friends Relationship: " . $e->getMessage() . "\n";
}

// Check Livewire components
$components = [
    'chat.conversation-sidebar' => \App\Livewire\Chat\ConversationSidebar::class,
    'chat.chat-interface' => \App\Livewire\Chat\ChatInterface::class,
];

echo "\nLivewire Components:\n";
foreach ($components as $name => $class) {
    if (class_exists($class)) {
        echo "✅ $name: Available\n";
    } else {
        echo "❌ $name: Missing\n";
    }
}

// Check required directories
$directories = [
    'storage/app/public/messages',
    'storage/app/public/messages/images',
    'storage/app/public/messages/videos',
    'storage/app/public/messages/files',
    'storage/app/public/messages/thumbnails',
];

echo "\nStorage Directories:\n";
foreach ($directories as $dir) {
    if (is_dir(storage_path('app/public/' . str_replace('storage/app/public/', '', $dir)))) {
        echo "✅ $dir: Exists\n";
    } else {
        echo "⚠️ $dir: Will be created on first upload\n";
    }
}

echo "\n📋 Setup Summary:\n";
echo "================\n";
echo "Environment: " . app()->environment() . "\n";
echo "URL: " . config('app.url') . "\n";
echo "Debug Mode: " . (config('app.debug') ? 'Enabled' : 'Disabled') . "\n";

echo "\n🎯 Next Steps:\n";
echo "=============\n";
echo "1. Start Reverb: php artisan reverb:start\n";
echo "2. Start Queue: php artisan queue:work\n";
echo "3. Start Server: php artisan serve\n";
echo "4. Start Frontend: npm run dev\n";
echo "5. Visit: https://pc-connection.test/\n";

echo "\n✅ Setup verification complete!\n";
