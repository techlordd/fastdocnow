<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $recipient;
    public $sender;

    public function __construct(Message $message, User $recipient, User $sender)
    {
        $this->message = $message;
        $this->recipient = $recipient;
        $this->sender = $sender;
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
            'type' => 'email_sent',
            'message_id' => $this->message->id,
            'recipient' => [
                'id' => $this->recipient->id,
                'first_name' => $this->recipient->first_name,
                'email' => $this->recipient->email,
            ],
            'sender' => [
                'id' => $this->sender->id,
                'first_name' => $this->sender->first_name,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
