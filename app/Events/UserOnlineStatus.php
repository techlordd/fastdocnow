<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnlineStatus implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $isOnline;
    public $conversationId;

    public function __construct(User $user, bool $isOnline, $conversationId = null)
    {
        $this->user = $user;
        $this->isOnline = $isOnline;
        $this->conversationId = $conversationId;
    }

    public function broadcastOn(): array
    {
        $channels = [];
        
        if ($this->conversationId) {
            $channels[] = new PresenceChannel('chat.' . $this->conversationId);
        }
        
        // Also broadcast to global presence channel
        $channels[] = new PresenceChannel('global-presence');
        
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'avatar' => $this->user->avatar,
            ],
            'is_online' => $this->isOnline,
            'timestamp' => now()->toISOString(),
        ];
    }
}
