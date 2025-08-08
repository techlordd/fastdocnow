<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $sender = $this->message->user;

        return (new MailMessage)
            ->subject('New message from ' . $sender->name)
            ->greeting('Hello!')
            ->line('You have received a new message from ' . $sender->name . '.')
            ->line('Message: ' . $this->message->content)
            ->action('View Message', url('/chat/' . $this->message->conversation_id))
            ->line('Thank you for using our application!');
    }
}
