<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Pusher\Pusher;

class TestPusherConnection extends Command
{
    protected $signature = 'pusher:test';
    protected $description = 'Test Pusher connection and configuration';

    public function handle()
    {
        $this->info('Testing Pusher connection...');
        
        try {
            // Check environment variables
            $appId = config('broadcasting.connections.pusher.app_id');
            $key = config('broadcasting.connections.pusher.key');
            $secret = config('broadcasting.connections.pusher.secret');
            $cluster = config('broadcasting.connections.pusher.options.cluster');
            
            if (!$appId || !$key || !$secret || !$cluster) {
                $this->error('Missing Pusher configuration:');
                $this->line("App ID: " . ($appId ?: 'MISSING'));
                $this->line("Key: " . ($key ?: 'MISSING'));
                $this->line("Secret: " . ($secret ? 'SET' : 'MISSING'));
                $this->line("Cluster: " . ($cluster ?: 'MISSING'));
                return 1;
            }
            
            $this->info('Configuration found:');
            $this->line("App ID: {$appId}");
            $this->line("Key: {$key}");
            $this->line("Cluster: {$cluster}");
            
            // Test Pusher connection
            $pusher = new Pusher($key, $secret, $appId, [
                'cluster' => $cluster,
                'useTLS' => true
            ]);
            
            // Test channel info (this will fail if credentials are wrong)
            $result = $pusher->getChannelInfo('test-channel');
            
            $this->info('✅ Pusher connection successful!');
            
            // Test broadcasting
            $testData = [
                'message' => 'Test message from Laravel',
                'timestamp' => now()->toISOString()
            ];
            
            $pusher->trigger('test-channel', 'test-event', $testData);
            $this->info('✅ Test message sent to Pusher!');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Pusher connection failed: ' . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'authentication')) {
                $this->warn('This usually means your Pusher credentials are incorrect.');
                $this->line('Please check your .env file and make sure PUSHER_APP_ID, PUSHER_APP_KEY, and PUSHER_APP_SECRET are correct.');
            }
            
            return 1;
        }
    }
}
