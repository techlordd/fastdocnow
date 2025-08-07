<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageReadReceipt extends Model
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
        'read_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Get the message that was read.
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who read the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
