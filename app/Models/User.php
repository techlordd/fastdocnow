<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'avatar',
        'bio',
        'phone',
        'timezone',
        'language',
        'theme',
        'email_notifications',

        'push_notifications',
        'sound_notifications',
        'notification_email',

        'notification_frequency',
        'quiet_hours_start',
        'quiet_hours_end',
        'last_seen_at',
        'last_login_at',
        'last_login_ip',
        'email_verified_at',
        'is_online',
        'is_admin',
        'status',
        'status_message',
        'wp_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'email_notifications' => 'boolean',

        'push_notifications' => 'boolean',
        'sound_notifications' => 'boolean',
        'is_online' => 'boolean',
        'is_admin' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
    ];

    // Accessors
    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getInitialsAttribute()
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    public function getIsOnlineAttribute()
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('public/storage/' . $this->avatar);
        }

        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&size=200&background=random";
    }

    // Relationships
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
                    ->withPivot(['joined_at', 'left_at', 'role'])
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function sentFriendRequests()
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    public function receivedFriendRequests()
    {
        return $this->hasMany(FriendRequest::class, 'receiver_id');
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
                    ->select(['users.id', 'users.first_name', 'users.last_name', 'users.avatar', 'users.last_seen_at'])
                    ->withPivot(['created_at'])
                    ->withTimestamps();
    }

    public function friendsOf()
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
                    ->select(['users.id', 'users.first_name', 'users.last_name', 'users.avatar', 'users.last_seen_at'])
                    ->withPivot(['created_at'])
                    ->withTimestamps();
    }



    public function messageReadReceipts()
    {
        return $this->hasMany(MessageReadReceipt::class);
    }

    public function notificationPreferences()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function wordpressUser()
    {
        return $this->belongsTo(\App\Models\WordPressUser::class, 'wp_user_id', 'ID');
    }

    // Helper Methods
    public function isFriendWith(User $user)
    {
        return $this->friends()->where('friend_id', $user->id)->exists() ||
               $this->friendsOf()->where('user_id', $user->id)->exists();
    }

    public function hasPendingFriendRequestFrom(User $user)
    {
        return $this->receivedFriendRequests()
                    ->where('sender_id', $user->id)
                    ->where('status', 'pending')
                    ->exists();
    }

    public function hasSentFriendRequestTo(User $user)
    {
        return $this->sentFriendRequests()
                    ->where('receiver_id', $user->id)
                    ->where('status', 'pending')
                    ->exists();
    }

    public function canMessageUser(User $user)
    {
        return $this->isFriendWith($user) || $this->id === $user->id;
    }

    public function updateLastSeen()
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function markAsOnline()
    {
        $this->update([
            'is_online' => true,
            'last_seen_at' => now()
        ]);
    }

    public function markAsOffline()
    {
        $this->update([
            'is_online' => false,
            'last_seen_at' => now()
        ]);
    }

    // Scopes
    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>', now()->subMinutes(5));
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('username', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    // WordPress Integration Helper Methods
    public function isWordPressUser()
    {
        return !is_null($this->wp_user_id);
    }

    public function getWordPressCapabilities()
    {
        if ($this->isWordPressUser() && $this->wordpressUser) {
            return $this->wordpressUser->getCapabilities();
        }
        return [];
    }

    public function hasWordPressCapability($capability)
    {
        if ($this->isWordPressUser() && $this->wordpressUser) {
            return $this->wordpressUser->hasCapability($capability);
        }
        return false;
    }

    public function syncFromWordPress()
    {
        if ($this->isWordPressUser() && $this->wordpressUser) {
            $wpUser = $this->wordpressUser;

            $this->update([
                'first_name' => $wpUser->first_name ?: $this->first_name,
                'last_name' => $wpUser->last_name ?: $this->last_name,
                'email' => $wpUser->user_email ?: $this->email,
                'avatar' => $wpUser->avatar_url ?: $this->avatar,
                'bio' => $wpUser->getMeta('description') ?: $this->bio,
                'phone' => $wpUser->getMeta('phone') ?: $this->phone,
                'is_admin' => $wpUser->is_admin,
            ]);

            return true;
        }
        return false;
    }
}
