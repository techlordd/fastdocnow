# Database Setup for Contact-Based Chat System

## Required Database Changes

Run the following commands to set up the new contact-based chat system:

```bash
# Run all migrations
php artisan migrate

# Create sample admin user (optional)
php artisan tinker
```

### In Tinker, create an admin user:

```php
$user = \App\Models\User::first(); // or find specific user
$user->update(['is_admin' => true]);

// Or create a new admin user
\App\Models\User::create([
    'first_name' => 'Admin',
    'last_name' => 'User',
    'email' => 'admin@example.com',
    'username' => 'admin',
    'password' => bcrypt('password'),
    'is_admin' => true,
    'email_verified_at' => now(),
]);
```

### Create Sample Contacts:

```php
// Create support contact
\App\Models\Contact::create([
    'name' => 'Customer Support',
    'description' => 'Get help with your account and general questions',
    'type' => 'support',
    'assigned_user_id' => 1, // Replace with actual staff user ID
    'is_active' => true,
    'sort_order' => 0,
    'created_by' => 1, // Admin user ID
]);

// Create doctor contacts
\App\Models\Contact::create([
    'name' => 'Dr. Smith - General Medicine',
    'description' => 'General health consultations and checkups',
    'type' => 'doctor',
    'assigned_user_id' => 2, // Replace with doctor user ID
    'is_active' => true,
    'sort_order' => 1,
    'created_by' => 1,
]);

\App\Models\Contact::create([
    'name' => 'Dr. Johnson - Specialist',
    'description' => 'Specialized medical consultations',
    'type' => 'doctor',
    'assigned_user_id' => 3, // Replace with doctor user ID
    'is_active' => true,
    'sort_order' => 2,
    'created_by' => 1,
]);
```

## New Features Available

### For Admin Users:
- Access `/admin/contacts` to manage all contacts
- Add, edit, delete, and reorder contacts
- Assign staff members to contacts
- Toggle contact visibility

### For Regular Users:
- Can only start conversations with pre-defined contacts
- No longer able to create arbitrary conversations
- Can see available contacts and their assigned staff
- Can view online status of assigned staff

## Files Changed

### New Files:
- `app/Models/Contact.php` - Contact model
- `app/Http/Controllers/Admin/ContactController.php` - Admin contact management
- `app/Http/Middleware/AdminMiddleware.php` - Admin access control
- `resources/views/admin/contacts/index.blade.php` - Admin interface
- Database migrations for contacts and admin users

### Modified Files:
- `app/Models/Conversation.php` - Added contact relationship
- `app/Livewire/Chat/ConversationSidebar.php` - Contact-based sidebar
- `resources/views/livewire/chat/conversation-sidebar.blade.php` - Updated UI
- `resources/js/chat.js` - Clean, working JavaScript
- `resources/js/app.js` - Simplified and working
- Routes and middleware registration

All JavaScript issues (emoji picker, toast notifications, scroll) have been fixed and cleaned up.
