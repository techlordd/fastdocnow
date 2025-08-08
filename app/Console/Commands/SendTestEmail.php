<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    protected $signature = 'email:test {email}';

    protected $description = 'Send a test email to the specified address';

    public function handle()
    {
        $email = $this->argument('email');

        try {
            Mail::raw('This is a test email to confirm your SMTP settings are working correctly.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Email from FastDocNow');
            });

            $this->info("Successfully sent test email to {$email}");
        } catch (\Exception $e) {
            $this->error("Failed to send test email: " . $e->getMessage());
        }
    }
}
