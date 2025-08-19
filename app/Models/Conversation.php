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
     * The conversation type (contact conversations only).
     */
    const TYPE_CONTACT = 'contact';

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
     * Scope for contact conversations.
     */
    public function scopeContact($query)
    {
        return $query->where('type', self::TYPE_CONTACT);
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
        if ($this->contact) {
            return $this->contact->first_name . ' ' . $this->contact->last_name;
        }

        $otherParticipant = $this->participants()
            ->where('user_id', '!=', $user->id)
            ->first();

        return $otherParticipant ? $otherParticipant->name : 'Unknown Contact';
    }

    /**
     * Get the conversation's avatar URL for a specific user.
     */
    public function getAvatarUrl(User $user)
    {
        if ($this->avatar) {
            return asset('public/storage/' . $this->avatar);
        }

        if ($this->contact && $this->contact->avatar) {
            return asset('public/storage/' . $this->contact->avatar);
        }

        $otherParticipant = $this->participants()
            ->where('user_id', '!=', $user->id)
            ->first();

        return $otherParticipant ? $otherParticipant->avatar_url : null;
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
     * Find or create a contact conversation.
     */
    public static function findOrCreateContact(User $user, Contact $contact)
    {
        // Look for existing conversation with this contact
        $conversation = self::contact()
            ->where('contact_id', $contact->id)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$conversation) {
            $conversation = self::create([
                'type' => self::TYPE_CONTACT,
                'contact_id' => $contact->id,
                'title' => $contact->first_name . ' ' . $contact->last_name,
                'created_by' => $user->id,
                'is_active' => true,
            ]);

            $conversation->addParticipant($user);
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
