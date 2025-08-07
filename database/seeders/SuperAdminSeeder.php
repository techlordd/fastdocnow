<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if super admin already exists
        $existingAdmin = User::where('email', 'admin@chat.com')->first();
        
        if ($existingAdmin) {
            // Update existing user to be admin
            $existingAdmin->update([
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('✅ Existing user admin@chat.com has been granted admin privileges.');
        } else {
            // Create new super admin
            $admin = User::create([
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'username' => 'superadmin',
                'email' => 'admin@chat.com',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'email_verified_at' => now(),
                'last_seen_at' => now(),
                'email_notifications' => true,
                'push_notifications' => true,
                'sound_notifications' => true,
            ]);

            $this->command->info('✅ Super Admin created successfully!');
            $this->command->info('📧 Email: admin@chat.com');
            $this->command->info('🔑 Password: admin123');
            $this->command->warn('⚠️  Please change the password after first login!');
        }

        // Also create some sample staff users that can be assigned to contacts
        $this->createSampleStaff();
        
        // Create sample contacts
        $this->createSampleContacts();
    }

    private function createSampleStaff()
    {
        $staffMembers = [
            [
                'first_name' => 'Dr. Sarah',
                'last_name' => 'Johnson',
                'username' => 'dr.johnson',
                'email' => 'dr.johnson@chat.com',
                'password' => Hash::make('password123'),
            ],
            [
                'first_name' => 'Dr. Michael',
                'last_name' => 'Smith',
                'username' => 'dr.smith',
                'email' => 'dr.smith@chat.com',
                'password' => Hash::make('password123'),
            ],
            [
                'first_name' => 'Support',
                'last_name' => 'Agent',
                'username' => 'support.agent',
                'email' => 'support@chat.com',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($staffMembers as $staff) {
            $existingStaff = User::where('email', $staff['email'])->first();
            
            if (!$existingStaff) {
                User::create(array_merge($staff, [
                    'is_admin' => false,
                    'email_verified_at' => now(),
                    'last_seen_at' => now(),
                    'email_notifications' => true,
                    'push_notifications' => true,
                    'sound_notifications' => true,
                ]));
                
                $this->command->info("✅ Staff member created: {$staff['email']}");
            }
        }
    }

    private function createSampleContacts()
    {
        // Get admin user ID
        $adminId = User::where('email', 'admin@chat.com')->first()->id;
        
        // Get staff user IDs
        $drJohnson = User::where('email', 'dr.johnson@chat.com')->first();
        $drSmith = User::where('email', 'dr.smith@chat.com')->first();
        $supportAgent = User::where('email', 'support@chat.com')->first();

        $contacts = [
            [
                'name' => 'General Support',
                'description' => 'Get help with your account, billing, and general questions',
                'type' => 'support',
                'assigned_user_id' => $supportAgent ? $supportAgent->id : null,
                'is_active' => true,
                'sort_order' => 0,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Dr. Johnson - General Medicine',
                'description' => 'General health consultations, checkups, and medical advice',
                'type' => 'doctor',
                'assigned_user_id' => $drJohnson ? $drJohnson->id : null,
                'is_active' => true,
                'sort_order' => 1,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Dr. Smith - Specialist',
                'description' => 'Specialized medical consultations and treatment plans',
                'type' => 'doctor',
                'assigned_user_id' => $drSmith ? $drSmith->id : null,
                'is_active' => true,
                'sort_order' => 2,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Emergency Support',
                'description' => 'Urgent medical questions and emergency guidance',
                'type' => 'doctor',
                'assigned_user_id' => $drJohnson ? $drJohnson->id : null,
                'is_active' => true,
                'sort_order' => 3,
                'created_by' => $adminId,
            ],
        ];

        foreach ($contacts as $contactData) {
            $existingContact = \App\Models\Contact::where('name', $contactData['name'])->first();
            
            if (!$existingContact) {
                \App\Models\Contact::create($contactData);
                $this->command->info("✅ Contact created: {$contactData['name']}");
            }
        }
    }
}
