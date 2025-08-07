<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FriendRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'status',
        'responded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Friend request statuses.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';

    /**
     * Get the user who sent the friend request.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the user who received the friend request.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for accepted requests.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope for declined requests.
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', self::STATUS_DECLINED);
    }

    /**
     * Accept the friend request.
     */
    public function accept()
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        // Create friendship records
        $this->sender->friends()->attach($this->receiver_id);
        $this->receiver->friends()->attach($this->sender_id);
    }

    /**
     * Decline the friend request.
     */
    public function decline()
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
            'responded_at' => now(),
        ]);
    }
}
