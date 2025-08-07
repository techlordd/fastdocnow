<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {email?} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user or grant admin privileges to existing user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        if (!$email) {
            $email = $this->ask('Enter the email address for the admin user');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');
            return 1;
        }

        // Check if user exists
        $user = User::where('email', $email)->first();

        if ($user) {
            // Update existing user to admin
            $user->update(['is_admin' => true]);
            $this->info("✅ User {$email} has been granted admin privileges.");
        } else {
            // Create new admin user
            $firstName = $this->ask('Enter first name', 'Admin');
            $lastName = $this->ask('Enter last name', 'User');
            $username = $this->ask('Enter username', 'admin');
            
            $password = $this->option('password');
            if (!$password) {
                $password = $this->secret('Enter password (leave empty for default: admin123)');
                if (!$password) {
                    $password = 'admin123';
                }
            }

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
                'email_verified_at' => now(),
                'last_seen_at' => now(),
                'email_notifications' => true,
                'push_notifications' => true,
                'sound_notifications' => true,
            ]);

            $this->info("✅ Admin user created successfully!");
            $this->info("📧 Email: {$email}");
            $this->info("🔑 Password: {$password}");
            $this->warn("⚠️  Please change the password after first login!");
        }

        // Show current admin count
        $adminCount = User::where('is_admin', true)->count();
        $this->info("📊 Total admin users: {$adminCount}");

        return 0;
    }
}
