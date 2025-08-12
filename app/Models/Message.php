<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Message extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'attachments',
        'reply_to_id',
        'is_edited',
        'edited_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'metadata' => 'array',
            'is_edited' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['content', 'type', 'is_edited'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Message types.
     */
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_FILE = 'file';
    const TYPE_SYSTEM = 'system';

    /**
     * Get the conversation that owns the message.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user that sent the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the message this message is replying to.
     */
    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    /**
     * Get the replies to this message.
     */
    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    /**
     * Get the read receipts for this message.
     */
    public function readReceipts()
    {
        return $this->hasMany(MessageReadReceipt::class);
    }


    /**
     * Scope for text messages.
     */
    public function scopeText($query)
    {
        return $query->where('type', self::TYPE_TEXT);
    }

    /**
     * Scope for media messages.
     */
    public function scopeMedia($query)
    {
        return $query->whereIn('type', [self::TYPE_IMAGE, self::TYPE_VIDEO, self::TYPE_AUDIO]);
    }

    /**
     * Scope for file messages.
     */
    public function scopeFiles($query)
    {
        return $query->where('type', self::TYPE_FILE);
    }

    /**
     * Scope for system messages.
     */
    public function scopeSystem($query)
    {
        return $query->where('type', self::TYPE_SYSTEM);
    }

    /**
     * Scope for unread messages by a specific user.
     */
    public function scopeUnreadBy($query, User $user)
    {
        return $query->whereDoesntHave('readReceipts', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    /**
     * Mark this message as read by a user.
     */
    public function markAsRead(User $user)
    {
        return $this->readReceipts()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);
    }

    /**
     * Check if this message has been read by a user.
     */
    public function isReadBy(User $user)
    {
        return $this->readReceipts()->where('user_id', $user->id)->exists();
    }


    /**
     * Get formatted content for display.
     */
    public function getFormattedContentAttribute()
    {
        if ($this->type === self::TYPE_SYSTEM) {
            return $this->content;
        }

        // Process mentions, links, etc.
        $content = $this->content;
        
        // Convert URLs to links
        $content = preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" target="_blank" class="text-blue-500 hover:underline">$1</a>',
            $content
        );

        // Convert @mentions to links
        $content = preg_replace(
            '/@(\w+)/',
            '<span class="text-blue-500 font-medium">@$1</span>',
            $content
        );

        // Convert line breaks
        $content = nl2br($content);

        return $content;
    }

    /**
     * Get message time for display.
     */
    public function getTimeForDisplayAttribute()
    {
        $now = now();
        $messageTime = $this->created_at;

        if ($messageTime->isToday()) {
            return $messageTime->format('H:i');
        } elseif ($messageTime->isYesterday()) {
            return 'Yesterday ' . $messageTime->format('H:i');
        } elseif ($messageTime->isCurrentWeek()) {
            return $messageTime->format('D H:i');
        } elseif ($messageTime->isCurrentYear()) {
            return $messageTime->format('M j, H:i');
        } else {
            return $messageTime->format('M j, Y H:i');
        }
    }

    /**
     * Get attachment URLs.
     */
    public function getAttachmentUrlsAttribute()
    {
        if (!$this->attachments) {
            return [];
        }

        return collect($this->attachments)->map(function ($attachment) {
            return [
                'url' => asset('storage/' . $attachment['path']),
                'name' => $attachment['name'] ?? basename($attachment['path']),
                'size' => $attachment['size'] ?? null,
                'type' => $attachment['type'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Check if message has attachments.
     */
    public function hasAttachments()
    {
        return !empty($this->attachments);
    }

    /**
     * Get first attachment for preview.
     */
    public function getFirstAttachment()
    {
        return $this->attachments[0] ?? null;
    }

    /**
     * Check if message is a reply.
     */
    public function isReply()
    {
        return !is_null($this->reply_to_id);
    }

    /**
     * Edit the message content.
     */
    public function editContent($newContent)
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Get read status for conversation participants.
     */
    public function getReadStatus()
    {
        $participants = $this->conversation->activeParticipants()->pluck('id');
        $readBy = $this->readReceipts()->pluck('user_id');

        return [
            'total_participants' => $participants->count(),
            'read_by_count' => $readBy->count(),
            'read_by' => $readBy->toArray(),
            'unread_by' => $participants->diff($readBy)->values()->toArray(),
        ];
    }

    /**
     * Create a system message.
     */
    public static function createSystemMessage(Conversation $conversation, $content, $metadata = [])
    {
        return self::create([
            'conversation_id' => $conversation->id,
            'user_id' => null,
            'content' => $content,
            'type' => self::TYPE_SYSTEM,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Search messages by content.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('content', 'LIKE', "%{$search}%");
    }

    /**
     * Get messages from the last N days.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
