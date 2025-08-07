<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageReaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'emoji',
    ];

    /**
     * Get the message that was reacted to.
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who reacted.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for a specific emoji.
     */
    public function scopeEmoji($query, $emoji)
    {
        return $query->where('emoji', $emoji);
    }
}
