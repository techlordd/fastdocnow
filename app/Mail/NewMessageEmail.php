<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewMessageEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $message;
    public $sender;

    public function __construct(Message $message, User $sender)
    {
        $this->message = $message;
        $this->sender = $sender;
    }

    public function build()
    {
        return $this->subject('New message from ' . $this->sender->name)
                    ->view('emails.new-message')
                    ->with([
                        'emailMessage' => $this->message,
                        'emailSender' => $this->sender,
                    ]);
    }
}
