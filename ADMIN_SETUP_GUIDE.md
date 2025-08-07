# Admin Setup Guide

## 🚀 Quick Setup

### Option 1: Run the Seeder (Recommended)
```bash
php artisan db:seed --class=SuperAdminSeeder
```

This will create:
- **Super Admin**: `admin@chat.com` / `admin123`
- **Sample Staff**: 
  - `dr.johnson@chat.com` / `password123`
  - `dr.smith@chat.com` / `password123`
  - `support@chat.com` / `password123`
- **Sample Contacts**: General Support, Dr. Johnson, Dr. Smith, Emergency Support

### Option 2: Use Artisan Commands

#### Create a new admin user:
```bash
php artisan make:admin
```
Follow the prompts to create a new admin user.

#### Create admin with specific email:
```bash
php artisan make:admin admin@yoursite.com
```

#### Grant admin privileges to existing user:
```bash
php artisan make:admin existing-user@yoursite.com
```

#### List all admin users:
```bash
php artisan list:admins
```

### Option 3: Manual Database Update

If you have an existing user and want to make them admin:

```sql
UPDATE users SET is_admin = 1 WHERE email = 'your-email@example.com';
```

Or using Tinker:
```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'your-email@example.com')->first();
$user->update(['is_admin' => true]);
```

## 📋 Admin Features

Once you have an admin user, they can:

1. **Access Admin Panel**: Visit `/admin/contacts`
2. **Manage Contacts**: Add, edit, delete, reorder contacts
3. **Assign Staff**: Link staff members to contacts
4. **Toggle Visibility**: Show/hide contacts from users
5. **Drag & Drop**: Reorder contacts with visual feedback

## 🔐 Default Credentials

If you used the seeder:

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@chat.com | admin123 |
| Doctor | dr.johnson@chat.com | password123 |
| Doctor | dr.smith@chat.com | password123 |
| Support | support@chat.com | password123 |

**⚠️ IMPORTANT: Change these passwords after first login!**

## 🎯 Next Steps

1. **Login as Admin**: Use the credentials above
2. **Change Password**: Update the default password
3. **Customize Contacts**: Edit the sample contacts to match your needs
4. **Add More Staff**: Create additional staff users and assign them to contacts
5. **Test the System**: Create a regular user and test the contact selection

## 🛠️ Managing Users

### Creating Staff Users:

Regular users who will handle contacts can be created normally. They don't need admin privileges. Just create them with:

```php
User::create([
    'first_name' => 'Staff',
    'last_name' => 'Member',
    'email' => 'staff@example.com',
    'username' => 'staff',
    'password' => bcrypt('password'),
    'is_admin' => false, // Regular staff member
    'email_verified_at' => now(),
]);
```

Then assign them to contacts through the admin panel at `/admin/contacts`.

## 📱 User Experience

Regular users will see:
- List of available contacts (Doctor 1, Doctor 2, Support, etc.)
- Can only chat with pre-defined contacts
- Cannot create arbitrary conversations
- See assigned staff info and online status

Admin users can:
- Access `/admin/contacts` for full contact management
- All regular user features plus admin panel access
