<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListAdmins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list:admins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all admin users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admins = User::where('is_admin', true)
            ->select(['id', 'first_name', 'last_name', 'email', 'username', 'created_at', 'last_seen_at'])
            ->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found.');
            $this->info('Run "php artisan make:admin" to create one.');
            return 0;
        }

        $this->info("📊 Found {$admins->count()} admin user(s):");
        $this->newLine();

        $headers = ['ID', 'Name', 'Email', 'Username', 'Created', 'Last Seen'];
        $rows = [];

        foreach ($admins as $admin) {
            $rows[] = [
                $admin->id,
                $admin->first_name . ' ' . $admin->last_name,
                $admin->email,
                $admin->username,
                $admin->created_at->format('M d, Y'),
                $admin->last_seen_at ? $admin->last_seen_at->diffForHumans() : 'Never'
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('💡 To create a new admin: php artisan make:admin');
        $this->info('💡 To grant admin to existing user: php artisan make:admin user@example.com');

        return 0;
    }
}
