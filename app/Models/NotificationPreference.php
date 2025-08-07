<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'email_new_message',
        'email_friend_request',
        'email_group_invite',
        'sms_new_message',
        'sms_friend_request',
        'push_new_message',
        'push_friend_request',
        'push_group_invite',
        'sound_enabled',
        'vibration_enabled',
        'do_not_disturb',
        'do_not_disturb_start',
        'do_not_disturb_end',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_new_message' => 'boolean',
            'email_friend_request' => 'boolean',
            'email_group_invite' => 'boolean',
            'sms_new_message' => 'boolean',
            'sms_friend_request' => 'boolean',
            'push_new_message' => 'boolean',
            'push_friend_request' => 'boolean',
            'push_group_invite' => 'boolean',
            'sound_enabled' => 'boolean',
            'vibration_enabled' => 'boolean',
            'do_not_disturb' => 'boolean',
            'do_not_disturb_start' => 'datetime:H:i',
            'do_not_disturb_end' => 'datetime:H:i',
        ];
    }

    /**
     * Get the user that owns the notification preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if notifications are currently in do not disturb mode.
     */
    public function isInDoNotDisturbMode()
    {
        if (!$this->do_not_disturb) {
            return false;
        }

        if (!$this->do_not_disturb_start || !$this->do_not_disturb_end) {
            return $this->do_not_disturb;
        }

        $now = now()->format('H:i');
        $start = $this->do_not_disturb_start->format('H:i');
        $end = $this->do_not_disturb_end->format('H:i');

        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        } else {
            // Crosses midnight
            return $now >= $start || $now <= $end;
        }
    }

    /**
     * Get default notification preferences.
     */
    public static function getDefaults()
    {
        return [
            'email_new_message' => true,
            'email_friend_request' => true,
            'email_group_invite' => true,
            'sms_new_message' => false,
            'sms_friend_request' => false,
            'push_new_message' => true,
            'push_friend_request' => true,
            'push_group_invite' => true,
            'sound_enabled' => true,
            'vibration_enabled' => true,
            'do_not_disturb' => false,
        ];
    }
}
