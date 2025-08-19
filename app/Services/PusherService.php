<?php

namespace App\Services;

use App\Events\EmailSent;
use App\Events\MessageSent;
use App\Events\UserOnlineStatus;
use App\Events\UserTyping;
use App\Mail\NewMessageEmail;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PusherService
{
    /**
     * Broadcast a new message and handle all related events
     */
    public function broadcastMessage(Message $message): void
    {
        try {
            // Load user relationship for broadcasting
            $message->load('user');
            
            // Broadcast message to conversation participants
            broadcast(new MessageSent($message));
            
            Log::info('PusherService: Message broadcasted', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'user_id' => $message->user_id
            ]);
            
            // Handle email notifications asynchronously
            $this->sendEmailNotifications($message);
            
        } catch (\Exception $e) {
            Log::error('PusherService: Failed to broadcast message', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Broadcast user typing status
     */
    public function broadcastTyping(int $conversationId, User $user, bool $isTyping): void
    {
        try {
            broadcast(new UserTyping($conversationId, $user, $isTyping));
            
            Log::info('PusherService: Typing status broadcasted', [
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
                'is_typing' => $isTyping
            ]);
            
        } catch (\Exception $e) {
            Log::error('PusherService: Failed to broadcast typing status', [
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Broadcast user online status
     */
    public function broadcastOnlineStatus(User $user, bool $isOnline, int $conversationId = null): void
    {
        try {
            // Update user's online status in database
            $user->update([
                'is_online' => $isOnline,
                'last_seen_at' => now()
            ]);
            
            broadcast(new UserOnlineStatus($user, $isOnline, $conversationId));
            
            Log::info('PusherService: Online status broadcasted', [
                'user_id' => $user->id,
                'is_online' => $isOnline,
                'conversation_id' => $conversationId
            ]);
            
        } catch (\Exception $e) {
            Log::error('PusherService: Failed to broadcast online status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send email notifications for a message
     */
    public function sendEmailNotifications(Message $message): void
    {
        try {
            $conversation = $message->conversation;
            $sender = $message->user;
            
            // Get recipients (all participants except sender)
            $recipients = $conversation->participants()
                ->where('user_id', '!=', $sender->id)
                ->get();
            
            foreach ($recipients as $recipient) {
                // Check if user wants email notifications
                if ($recipient->email_notifications ?? false) {
                    $this->sendEmailToUser($message, $recipient, $sender);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('PusherService: Failed to process email notifications', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send email to individual user and broadcast confirmation
     */
    private function sendEmailToUser(Message $message, User $recipient, User $sender): void
    {
        try {
            // Send email
            Mail::to($recipient->notification_email ?? $recipient->email)
                ->send(new NewMessageEmail($message, $sender));
            
            // Broadcast email sent confirmation
            broadcast(new EmailSent($message, $recipient, $sender));
            
            Log::info('PusherService: Email sent successfully', [
                'message_id' => $message->id,
                'recipient_id' => $recipient->id,
                'sender_id' => $sender->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('PusherService: Failed to send email', [
                'message_id' => $message->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage()
            ]);
            
            // Even if email fails, we might want to broadcast the failure
            // for debugging purposes (optional)
        }
    }
    
    /**
     * Broadcast notification toast
     */
    public function broadcastNotification(int $conversationId, array $data): void
    {
        try {
            broadcast(new \App\Events\NotificationSent($conversationId, $data));
            
            Log::info('PusherService: Notification broadcasted', [
                'conversation_id' => $conversationId,
                'notification_data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('PusherService: Failed to broadcast notification', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Handle user joining conversation (for presence)
     */
    public function userJoinedConversation(User $user, int $conversationId): void
    {
        $this->broadcastOnlineStatus($user, true, $conversationId);
    }
    
    /**
     * Handle user leaving conversation (for presence)
     */
    public function userLeftConversation(User $user, int $conversationId): void
    {
        $this->broadcastOnlineStatus($user, false, $conversationId);
    }
    
    /**
     * Handle user going completely offline
     */
    public function userWentOffline(User $user): void
    {
        $this->broadcastOnlineStatus($user, false);
    }
    
    /**
     * Handle user coming online
     */
    public function userCameOnline(User $user): void
    {
        $this->broadcastOnlineStatus($user, true);
    }
}
