<?php

namespace App\Services;

use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class NotificationService
{
    public function sendMessageNotification(Message $message)
    {
        $conversation = $message->conversation;
        
        // Only send notifications for contact-based conversations
        if (!$conversation->contact_id) {
            return;
        }

        $contact = $conversation->contact;
        $assignedUser = $contact->assignedUser;
        
        // Don't send notification if no one is assigned or if sender is the assigned user
        if (!$assignedUser || $assignedUser->id === $message->user_id) {
            return;
        }

        // Check if user wants notifications
        if (!$assignedUser->email_notifications && !$assignedUser->sms_notifications) {
            return;
        }

        // Check quiet hours
        if ($this->isQuietHours($assignedUser)) {
            return;
        }

        // Check frequency settings
        if (!$this->shouldSendNotification($assignedUser, $message)) {
            return;
        }

        // Send email notification
        if ($assignedUser->email_notifications) {
            $this->sendEmailNotification($assignedUser, $message);
        }

        // Send SMS notification
        if ($assignedUser->sms_notifications && $assignedUser->notification_phone) {
            $this->sendSmsNotification($assignedUser, $message);
        }
    }

    private function isQuietHours(User $user): bool
    {
        if (!$user->quiet_hours_start || !$user->quiet_hours_end) {
            return false;
        }

        $now = Carbon::now($user->timezone ?: 'UTC');
        $start = Carbon::createFromFormat('H:i', $user->quiet_hours_start, $user->timezone ?: 'UTC');
        $end = Carbon::createFromFormat('H:i', $user->quiet_hours_end, $user->timezone ?: 'UTC');

        if ($start->lt($end)) {
            // Same day quiet hours
            return $now->between($start, $end);
        } else {
            // Overnight quiet hours
            return $now->gte($start) || $now->lte($end);
        }
    }

    private function shouldSendNotification(User $user, Message $message): bool
    {
        $frequency = $user->notification_frequency;

        if ($frequency === 'instant') {
            return true;
        }

        $lastNotificationKey = "last_notification_{$user->id}_{$message->conversation_id}";
        $lastNotification = Cache::get($lastNotificationKey);

        if (!$lastNotification) {
            Cache::put($lastNotificationKey, now(), now()->addDay());
            return true;
        }

        $lastNotificationTime = Carbon::parse($lastNotification);

        if ($frequency === 'hourly') {
            return $lastNotificationTime->addHour()->lte(now());
        }

        if ($frequency === 'daily') {
            return $lastNotificationTime->addDay()->lte(now());
        }

        return true;
    }

    private function sendEmailNotification(User $user, Message $message)
    {
        try {
            $emailAddress = $user->notification_email ?: $user->email;
            $sender = $message->user;
            $contact = $message->conversation->contact;

            $subject = "New message from {$sender->name} - {$contact->name}";
            
            $messageContent = $message->content;
            if ($message->type !== 'text') {
                $messageContent = "[{$message->type} message]";
            }

            Mail::raw(
                "Hello {$user->first_name},\n\n" .
                "You have received a new message from {$sender->name} via {$contact->name}:\n\n" .
                "\"{$messageContent}\"\n\n" .
                "Please log in to your chat system to respond.\n\n" .
                "Time: " . $message->created_at->format('M j, Y g:i A') . "\n\n" .
                "Best regards,\n" .
                "Chat System",
                function ($mail) use ($emailAddress, $subject) {
                    $mail->to($emailAddress)->subject($subject);
                }
            );

            Log::info("Email notification sent to {$user->email} for message {$message->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send email notification to {$user->email}: " . $e->getMessage());
        }
    }

    private function sendSmsNotification(User $user, Message $message)
    {
        try {
            $smsConfig = Cache::get('sms_config');
            
            if (!$smsConfig || !$smsConfig['sms_enabled']) {
                return;
            }

            $sender = $message->user;
            $contact = $message->conversation->contact;
            
            $messageText = "New message from {$sender->first_name} via {$contact->name}. Please check your chat system to respond.";

            switch ($smsConfig['sms_provider']) {
                case 'twilio':
                    $this->sendTwilioSms($user->notification_phone, $messageText, $smsConfig);
                    break;
                case 'vonage':
                    $this->sendVonageSms($user->notification_phone, $messageText, $smsConfig);
                    break;
                case 'custom':
                    $this->sendCustomSms($user->notification_phone, $messageText, $smsConfig);
                    break;
            }

            Log::info("SMS notification sent to {$user->notification_phone} for message {$message->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send SMS notification to {$user->notification_phone}: " . $e->getMessage());
        }
    }

    private function sendTwilioSms(string $to, string $message, array $config)
    {
        // This would integrate with Twilio SDK
        // For now, just log the attempt
        Log::info("Twilio SMS would be sent to {$to}: {$message}");
        
        // Example implementation:
        /*
        $twilio = new \Twilio\Rest\Client(
            decrypt($config['sms_api_key']),
            decrypt($config['sms_api_secret'])
        );
        
        $twilio->messages->create($to, [
            'from' => $config['sms_from_number'],
            'body' => $message
        ]);
        */
    }

    private function sendVonageSms(string $to, string $message, array $config)
    {
        // This would integrate with Vonage SDK
        Log::info("Vonage SMS would be sent to {$to}: {$message}");
    }

    private function sendCustomSms(string $to, string $message, array $config)
    {
        // This would integrate with custom SMS API
        Log::info("Custom SMS would be sent to {$to}: {$message}");
    }

    public function markNotificationSent(User $user, Message $message)
    {
        $key = "last_notification_{$user->id}_{$message->conversation_id}";
        Cache::put($key, now(), now()->addDay());
    }
}
