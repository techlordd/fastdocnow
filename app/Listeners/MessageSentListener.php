<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class MessageSentListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MessageSent $event)
    {
        $message = $event->message;
        $conversation = $message->conversation;
        $sender = $message->user;

        $recipients = $conversation->participants()->where('user_id', '!=', $sender->id)->get();

        foreach ($recipients as $recipient) {
            if ($recipient->email_notifications) {
                Notification::send($recipient, new NewMessageNotification($message));
            }
        }
    }
}
