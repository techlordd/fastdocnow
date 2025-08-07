<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Conversation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'type',
        'avatar',
        'is_active',
        'created_by',
        'last_message_at',
        'settings',
        'contact_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'type', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The conversation types.
     */
    const TYPE_PRIVATE = 'private';
    const TYPE_GROUP = 'group';
    const TYPE_CHANNEL = 'channel';

    /**
     * Get the participants of the conversation.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['joined_at', 'left_at', 'role', 'archived_at', 'settings'])
            ->withTimestamps();
    }

    /**
     * Get active participants of the conversation.
     */
    public function activeParticipants()
    {
        return $this->participants()->wherePivotNull('left_at');
    }

    /**
     * Get the messages in the conversation.
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Get the latest message in the conversation.
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Get the creator of the conversation.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the contact associated with this conversation.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Scope for private conversations.
     */
    public function scopePrivate($query)
    {
        return $query->where('type', self::TYPE_PRIVATE);
    }

    /**
     * Scope for group conversations.
     */
    public function scopeGroup($query)
    {
        return $query->where('type', self::TYPE_GROUP);
    }

    /**
     * Scope for active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for conversations with a specific user.
     */
    public function scopeWithUser($query, $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Get the conversation's display name for a specific user.
     */
    public function getDisplayName(User $user)
    {
        if ($this->type === self::TYPE_PRIVATE) {
            $otherParticipant = $this->participants()
                ->where('user_id', '!=', $user->id)
                ->first();

            return $otherParticipant ? $otherParticipant->name : 'Unknown User';
        }

        return $this->title ?: 'Group Chat';
    }

    /**
     * Get the conversation's avatar URL for a specific user.
     */
    public function getAvatarUrl(User $user)
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        if ($this->type === self::TYPE_PRIVATE) {
            $otherParticipant = $this->participants()
                ->where('user_id', '!=', $user->id)
                ->first();

            return $otherParticipant ? $otherParticipant->avatar_url : null;
        }

        // Generate group avatar
        $name = urlencode($this->getDisplayName($user));
        return "https://ui-avatars.com/api/?name={$name}&size=200&background=007bff&color=fff&bold=true";
    }

    /**
     * Add a participant to the conversation.
     */
    public function addParticipant(User $user, $role = 'member')
    {
        return $this->participants()->syncWithoutDetaching([
            $user->id => [
                'joined_at' => now(),
                'role' => $role,
            ]
        ]);
    }

    /**
     * Remove a participant from the conversation.
     */
    public function removeParticipant(User $user)
    {
        return $this->participants()->updateExistingPivot($user->id, [
            'left_at' => now(),
        ]);
    }

    /**
     * Check if a user is a participant.
     */
    public function hasParticipant(User $user)
    {
        return $this->activeParticipants()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is an admin.
     */
    public function isAdmin(User $user)
    {
        return $this->participants()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Update the last message timestamp.
     */
    public function updateLastMessage()
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Get unread messages count for a specific user.
     */
    public function getUnreadCount(User $user)
    {
        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();
    }

    /**
     * Mark all messages as read for a specific user.
     */
    public function markAsRead(User $user)
    {
        $unreadMessages = $this->messages()
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        foreach ($unreadMessages as $message) {
            $message->markAsRead($user);
        }
    }

    /**
     * Find or create a private conversation between two users.
     */
    public static function findOrCreatePrivate(User $user1, User $user2)
    {
        // Look for existing private conversation between these users
        $conversation = self::private()
            ->whereHas('participants', function ($query) use ($user1) {
                $query->where('user_id', $user1->id);
            })
            ->whereHas('participants', function ($query) use ($user2) {
                $query->where('user_id', $user2->id);
            })
            ->whereDoesntHave('participants', function ($query) use ($user1, $user2) {
                $query->whereNotIn('user_id', [$user1->id, $user2->id]);
            })
            ->first();

        if (!$conversation) {
            $conversation = self::create([
                'type' => self::TYPE_PRIVATE,
                'created_by' => $user1->id,
                'is_active' => true,
            ]);

            $conversation->addParticipant($user1);
            $conversation->addParticipant($user2);
        }

        return $conversation;
    }

    /**
     * Create a group conversation.
     */
    public static function createGroup(User $creator, array $participantIds, $title = null, $description = null)
    {
        $conversation = self::create([
            'title' => $title,
            'description' => $description,
            'type' => self::TYPE_GROUP,
            'created_by' => $creator->id,
            'is_active' => true,
        ]);

        // Add creator as admin
        $conversation->addParticipant($creator, 'admin');

        // Add other participants
        foreach ($participantIds as $participantId) {
            if ($participantId != $creator->id) {
                $conversation->addParticipant(User::find($participantId));
            }
        }

        return $conversation;
    }

    /**
     * Get conversation settings.
     */
    public function getSetting($key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Update conversation settings.
     */
    public function updateSetting($key, $value)
    {
        $settings = $this->settings ?: [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }
}
