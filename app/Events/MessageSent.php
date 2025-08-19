<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'user_id' => $this->message->user_id,
                'content' => $this->message->content,
                'type' => $this->message->type,
                'attachments' => $this->message->attachments,
                'metadata' => $this->message->metadata,
                'created_at' => $this->message->created_at,
                'user' => $this->message->user ? [
                    'id' => $this->message->user->id,
                    'first_name' => $this->message->user->first_name,
                    'last_name' => $this->message->user->last_name,
                    'avatar' => $this->message->user->avatar ?? null,
                ] : null,
            ]
        ];
    }
}
