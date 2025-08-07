<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // First create super admin and sample contacts
        $this->call(SuperAdminSeeder::class);
        // Create demo users
        $demoUser = User::updateOrCreate(
            ['email' => 'demo@DocNow.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'User',
                'username' => 'demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'last_seen_at' => now(),
            ]
        );

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@DocNow.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'last_seen_at' => now(),
            ]
        );

        // Create sample users
        $users = [
            ['first_name' => 'Sarah', 'last_name' => 'Anderson', 'username' => 'sarah_anderson', 'email' => 'sarah@example.com'],
            ['first_name' => 'Mike', 'last_name' => 'Johnson', 'username' => 'mike_johnson', 'email' => 'mike@example.com'],
            ['first_name' => 'Emma', 'last_name' => 'Wilson', 'username' => 'emma_wilson', 'email' => 'emma@example.com'],
            ['first_name' => 'David', 'last_name' => 'Chen', 'username' => 'david_chen', 'email' => 'david@example.com'],
            ['first_name' => 'Lisa', 'last_name' => 'Rodriguez', 'username' => 'lisa_rodriguez', 'email' => 'lisa@example.com'],
            ['first_name' => 'James', 'last_name' => 'Anderson', 'username' => 'james_anderson', 'email' => 'james@example.com'],
            ['first_name' => 'Maria', 'last_name' => 'Garcia', 'username' => 'maria_garcia', 'email' => 'maria@example.com'],
            ['first_name' => 'Robert', 'last_name' => 'Taylor', 'username' => 'robert_taylor', 'email' => 'robert@example.com'],
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $createdUsers[] = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    ...$userData,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'last_seen_at' => fake()->dateTimeBetween('-2 hours', 'now'),
                ]
            );
        }

        // Create friendships
        $allUsers = collect([$demoUser, $adminUser])->concat($createdUsers);

        foreach ($allUsers as $user) {
            $potentialFriends = $allUsers->except($user->id)->random(rand(3, 6));

            foreach ($potentialFriends as $friend) {
                // Check if friendship already exists (either direction)
                $existingFriendship = $user->friends()->where('friend_id', $friend->id)->exists() ||
                                    $friend->friends()->where('friend_id', $user->id)->exists();

                if (!$existingFriendship) {
                    // Create friendship
                    $user->friends()->syncWithoutDetaching([$friend->id => ['created_at' => now(), 'updated_at' => now()]]);
                }
            }
        }

        // Create private conversations
        $demoFriends = $demoUser->friends()->take(5)->get();
        foreach ($demoFriends as $friend) {
            $conversation = Conversation::create([
                'type' => 'private',
                'created_by' => $demoUser->id,
            ]);

            // Add participants
            $conversation->participants()->attach([
                $demoUser->id => ['joined_at' => now(), 'role' => 'admin'],
                $friend->id => ['joined_at' => now(), 'role' => 'member'],
            ]);

            // Create sample messages
            $messages = [
                ['user_id' => $friend->id, 'content' => 'Hey! How are you doing?', 'type' => 'text'],
                ['user_id' => $demoUser->id, 'content' => 'Hi there! I\'m doing great, thanks for asking!', 'type' => 'text'],
                ['user_id' => $friend->id, 'content' => 'That\'s awesome! Want to catch up later?', 'type' => 'text'],
                ['user_id' => $demoUser->id, 'content' => 'Absolutely! Let me know when you\'re free.', 'type' => 'text'],
            ];

            $createdAt = fake()->dateTimeBetween('-1 week', 'now');
            foreach ($messages as $index => $messageData) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $messageData['user_id'],
                    'content' => $messageData['content'],
                    'type' => $messageData['type'],
                    'created_at' => $createdAt->modify('+' . ($index * 30) . ' minutes'),
                    'updated_at' => $createdAt,
                ]);
            }

            $conversation->update(['updated_at' => $createdAt]);
        }

        // Create group conversations
        $groupMembers = $allUsers->random(6);
        $groupConversation = Conversation::create([
            'type' => 'group',
            'title' => 'Project Team',
            'created_by' => $demoUser->id,
        ]);

        $participantData = [];
        foreach ($groupMembers as $member) {
            $participantData[$member->id] = [
                'joined_at' => now(),
                'role' => $member->id === $demoUser->id ? 'admin' : 'member',
            ];
        }
        $groupConversation->participants()->attach($participantData);

        // Group messages
        $groupMessages = [
            ['user_id' => $demoUser->id, 'content' => 'Welcome everyone to the project team chat!', 'type' => 'text'],
            ['user_id' => $createdUsers[0]->id, 'content' => 'Thanks for setting this up!', 'type' => 'text'],
            ['user_id' => $createdUsers[1]->id, 'content' => 'Excited to work with everyone 🚀', 'type' => 'text'],
            ['user_id' => $adminUser->id, 'content' => 'Let\'s make this project a success!', 'type' => 'text'],
        ];

        $groupCreatedAt = fake()->dateTimeBetween('-3 days', 'now');
        foreach ($groupMessages as $index => $messageData) {
            Message::create([
                'conversation_id' => $groupConversation->id,
                'user_id' => $messageData['user_id'],
                'content' => $messageData['content'],
                'type' => $messageData['type'],
                'created_at' => $groupCreatedAt->modify('+' . ($index * 60) . ' minutes'),
                'updated_at' => $groupCreatedAt,
            ]);
        }

        $groupConversation->update(['updated_at' => $groupCreatedAt]);

        // Create family group
        $familyConversation = Conversation::create([
            'type' => 'group',
            'title' => 'Family & Friends',
            'created_by' => $demoUser->id,
        ]);

        $familyMembers = $allUsers->random(4);
        $familyParticipantData = [];
        foreach ($familyMembers as $member) {
            $familyParticipantData[$member->id] = [
                'joined_at' => now(),
                'role' => $member->id === $demoUser->id ? 'admin' : 'member',
            ];
        }
        $familyConversation->participants()->attach($familyParticipantData);

        $this->command->info('Demo data created successfully!');
        $this->command->info('Demo User: demo@DocNow.com / password');
        $this->command->info('Admin User: admin@DocNow.com / password');
    }
}
