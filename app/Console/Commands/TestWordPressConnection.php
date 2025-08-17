<?php

namespace App\Console\Commands;

use App\Services\WordPressAuthService;
use App\Models\WordPressUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TestWordPressConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wp:test-connection {--connection=wordpress}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WordPress database connection and Corcel integration';

    /**
     * WordPress Auth Service
     *
     * @var WordPressAuthService
     */
    protected $wordPressAuthService;

    /**
     * Create a new command instance.
     */
    public function __construct(WordPressAuthService $wordPressAuthService)
    {
        parent::__construct();
        $this->wordPressAuthService = $wordPressAuthService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing WordPress Integration...');
        $this->newLine();

        $connection = $this->option('connection');
        $verbose = $this->option('verbose');

        // Test 1: Basic Database Connection
        $this->info('1️⃣  Testing database connection...');
        if (!$this->testDatabaseConnection($connection)) {
            $this->error('❌ Database connection failed!');
            return 1;
        }
        $this->info('✅ Database connection successful');

        // Test 2: WordPress Tables
        $this->info('2️⃣  Testing WordPress tables...');
        if (!$this->testWordPressTables($connection)) {
            $this->error('❌ WordPress tables test failed!');
            return 1;
        }
        $this->info('✅ WordPress tables accessible');

        // Test 3: Corcel Integration
        $this->info('3️⃣  Testing Corcel integration...');
        $corcelTest = $this->testCorcelIntegration($verbose);
        if (!$corcelTest['success']) {
            $this->error('❌ Corcel integration failed: ' . $corcelTest['error']);
            return 1;
        }
        $this->info('✅ Corcel integration working');

        // Test 4: WordPress Users
        $this->info('4️⃣  Testing WordPress users...');
        $userTest = $this->testWordPressUsers($verbose);
        if (!$userTest['success']) {
            $this->error('❌ WordPress users test failed: ' . $userTest['error']);
            return 1;
        }
        $this->info('✅ WordPress users accessible');

        // Test 5: Authentication Service
        $this->info('5️⃣  Testing WordPress authentication service...');
        $authTest = $this->wordPressAuthService->testWordPressConnection();
        if (!$authTest['success']) {
            $this->error('❌ Authentication service test failed: ' . $authTest['error']);
            return 1;
        }
        $this->info('✅ Authentication service working');

        // Display Results
        $this->newLine();
        $this->info('🎉 All tests passed! WordPress integration is working correctly.');
        $this->newLine();

        // Display summary
        $this->displaySummary($authTest, $userTest, $verbose);

        return 0;
    }

    /**
     * Test basic database connection.
     */
    protected function testDatabaseConnection($connection): bool
    {
        try {
            DB::connection($connection)->getPdo();
            return true;
        } catch (\Exception $e) {
            if ($this->option('verbose')) {
                $this->error('Database connection error: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Test WordPress tables exist and are accessible.
     */
    protected function testWordPressTables($connection): bool
    {
        try {
            $prefix = config('corcel.prefix', 'wp_');
            $tables = [
                $prefix . 'users',
                $prefix . 'usermeta',
                $prefix . 'posts',
                $prefix . 'options'
            ];

            foreach ($tables as $table) {
                $exists = DB::connection($connection)
                    ->select("SHOW TABLES LIKE ?", [$table]);
                
                if (empty($exists)) {
                    if ($this->option('verbose')) {
                        $this->error("Table {$table} does not exist");
                    }
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            if ($this->option('verbose')) {
                $this->error('Tables check error: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Test Corcel integration.
     */
    protected function testCorcelIntegration($verbose): array
    {
        try {
            // Test basic Corcel functionality
            $userCount = WordPressUser::count();
            
            // Test if we can get a user with meta
            $user = WordPressUser::with('meta')->first();
            
            $result = [
                'success' => true,
                'user_count' => $userCount,
                'sample_user' => $user ? [
                    'id' => $user->ID,
                    'login' => $user->user_login,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                ] : null
            ];

            if ($verbose && $user) {
                $this->info("   Sample user: {$user->display_name} ({$user->user_email})");
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test WordPress users functionality.
     */
    protected function testWordPressUsers($verbose): array
    {
        try {
            $totalUsers = WordPressUser::count();
            
            // Get users with different capabilities
            $adminUsers = WordPressUser::whereHas('meta', function($query) {
                $prefix = config('corcel.prefix', 'wp_');
                $query->where('meta_key', $prefix . 'capabilities')
                      ->where('meta_value', 'like', '%administrator%');
            })->count();

            // Test user capabilities
            $sampleUser = WordPressUser::first();
            $capabilities = null;
            $canAccess = false;
            
            if ($sampleUser) {
                $capabilities = $sampleUser->getCapabilities();
                $canAccess = $sampleUser->canAccessChat();
                
                if ($verbose) {
                    $this->info("   Sample user capabilities: " . implode(', ', $capabilities));
                    $this->info("   Can access chat: " . ($canAccess ? 'Yes' : 'No'));
                }
            }

            return [
                'success' => true,
                'total_users' => $totalUsers,
                'admin_users' => $adminUsers,
                'sample_capabilities' => $capabilities,
                'sample_can_access' => $canAccess
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Display test results summary.
     */
    protected function displaySummary($authTest, $userTest, $verbose): void
    {
        $this->info('📊 Test Results Summary:');
        $this->line('┌─────────────────────────────────────────────┐');
        $this->line('│ WordPress Integration Status                │');
        $this->line('├────��────────────────────────────────────────┤');
        $this->line('│ Total Users: ' . str_pad($userTest['total_users'] ?? 0, 30, ' ', STR_PAD_LEFT) . ' │');
        $this->line('│ Admin Users: ' . str_pad($authTest['admin_count'] ?? 0, 30, ' ', STR_PAD_LEFT) . ' │');
        $this->line('│ Corcel Status: ' . str_pad($authTest['corcel_working'] ? 'Working' : 'Issues', 28, ' ', STR_PAD_LEFT) . ' │');
        $this->line('│ Connection: ' . str_pad(config('corcel.connection', 'wordpress'), 31, ' ', STR_PAD_LEFT) . ' │');
        $this->line('│ Table Prefix: ' . str_pad(config('corcel.prefix', 'wp_'), 29, ' ', STR_PAD_LEFT) . ' │');
        $this->line('└─────────────────────────────────────────────┘');

        if ($verbose && isset($authTest['sample_user'])) {
            $this->newLine();
            $this->info('👤 Sample User Information:');
            $sample = $authTest['sample_user'];
            $this->line("   ID: {$sample['id']}");
            $this->line("   Username: {$sample['login']}");
            $this->line("   Email: {$sample['email']}");
            $this->line("   Display Name: {$sample['display_name']}");
            $this->line("   Role: {$sample['role']}");
            $this->line("   Is Admin: " . ($sample['is_admin'] ? 'Yes' : 'No'));
            $this->line("   Can Access Chat: " . ($sample['can_access_chat'] ? 'Yes' : 'No'));
            
            if (!empty($sample['capabilities'])) {
                $this->line("   Capabilities: " . implode(', ', $sample['capabilities']));
            }
        }

        $this->newLine();
        $this->info('💡 Configuration:');
        $this->line("   WordPress Enabled: " . (config('wordpress.enabled', false) ? 'Yes' : 'No'));
        $this->line("   Auto Sync Users: " . (config('wordpress.sync.auto_sync', true) ? 'Yes' : 'No'));
        $this->line("   Sync Avatars: " . (config('wordpress.sync.sync_avatars', true) ? 'Yes' : 'No'));
        
        if (!config('wordpress.enabled', false)) {
            $this->newLine();
            $this->warn('⚠️  WordPress integration is disabled. Set WP_ENABLED=true in your .env file to enable it.');
        }
    }
}
