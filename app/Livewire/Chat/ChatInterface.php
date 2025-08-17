<?php

namespace App\Livewire\Chat;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatInterface extends Component
{
    use WithFileUploads;

    public $conversation;
    public $messages = [];
    public $messageText = '';
    public $selectedFiles = [];
    public $attachmentCaption = '';
    public $showAttachmentPreview = false;
    public $isTyping = false;
    public $typingUsers = [];
    public $isSendingVoiceMessage = false;

    protected $listeners = [
        'conversationSelected' => 'loadConversation',
        'refreshMessages' => 'loadMessages'
    ];

    public function mount($conversationId = null)
    {
        if ($conversationId) {
            $this->loadConversation($conversationId);
        }
    }

    public function loadConversation($conversationId)
    {
        $this->conversation = Conversation::with(['participants', 'messages.user', 'contact'])
            ->where('id', $conversationId)
            ->whereHas('participants', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->first();

        if ($this->conversation) {
            $this->loadMessages();
            $this->markMessagesAsRead();
            $this->dispatch('conversationLoaded', $this->conversation->id);
            $this->dispatch('scroll-to-bottom');
            // Add this line to update last_seen_at
            Auth::user()->updateLastSeen();
        }
    }

    public function setupConversationListeners()
    {
        if (!$this->conversation) return;

        // Subscribe to conversation-specific events
        $this->dispatch('subscribe-to-conversation', ['id' => $this->conversation->id]);
    }

    public function refreshConversationData()
    {
        if ($this->conversation) {
            $this->conversation = Conversation::with(['participants' => function($query) {
                $query->select('users.id', 'users.first_name', 'users.last_name', 'users.avatar', 'users.last_seen_at', 'users.is_online');
            }])
                ->find($this->conversation->id);
        }
    }

    public function clearConversation()
    {
        $this->conversation = null;
        $this->messages = [];
        $this->typingUsers = [];
        $this->dispatch('leave-conversation');
    }

    public function archiveConversation()
    {
        if (!$this->conversation) return;

        $this->conversation->update(['is_archived' => true]);
        $this->dispatch('conversationArchived', ['id' => $this->conversation->id]);
        $this->clearConversation();

        session()->flash('message', 'Conversation archived successfully.');
    }

    public function deleteConversation()
    {
        if (!$this->conversation) return;

        $conversationId = $this->conversation->id;

        // Delete all messages and their attachments
        foreach ($this->conversation->messages as $message) {
            if ($message->attachments) {
                foreach ($message->attachments as $attachment) {
                    if (isset($attachment['path']) && Storage::disk('public')->exists($attachment['path'])) {
                        Storage::disk('public')->delete($attachment['path']);
                    }
                    if (isset($attachment['metadata']['thumbnail_path']) && Storage::disk('public')->exists($attachment['metadata']['thumbnail_path'])) {
                        Storage::disk('public')->delete($attachment['metadata']['thumbnail_path']);
                    }
                }
            }
        }

        $this->conversation->delete();
        $this->dispatch('conversationDeleted', ['id' => $conversationId]);
        $this->clearConversation();

        session()->flash('message', 'Conversation deleted successfully.');
    }

    public function unarchiveConversation()
    {
        if (!$this->conversation) return;

        $this->conversation->update(['is_archived' => false]);
        $this->dispatch('conversationUnarchived', ['id' => $this->conversation->id]);

        session()->flash('message', 'Conversation unarchived successfully.');
    }

    public function markAsUnread()
    {
        if (!$this->conversation) return;

        // Remove all read receipts for current user
        $this->conversation->messages()
            ->whereHas('readReceipts', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->get()
            ->each(function($message) {
                $message->readReceipts()->where('user_id', Auth::id())->delete();
            });

        $this->dispatch('conversationMarkedUnread', ['id' => $this->conversation->id]);
        session()->flash('message', 'Conversation marked as unread.');
    }



    public function loadMessages()
    {
        if (!$this->conversation) return;

        $this->messages = $this->conversation->messages()
            ->with(['user'])
            ->select(['id', 'conversation_id', 'user_id', 'content', 'type', 'attachments', 'created_at', 'updated_at'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function refreshMessages()
    {
        \Log::info('ChatInterface: Refresh messages called');
        $this->loadMessages();
        $this->dispatch('scroll-to-bottom');
    }

    public function forceRefresh()
    {
        \Log::info('ChatInterface: Force refresh called');
        if ($this->conversation) {
            $this->loadMessages();
            $this->dispatch('scroll-to-bottom');
            $this->dispatch('messageAdded');
        }
    }

    public function sendMessage()
    {
        if (empty(trim($this->messageText)) && empty($this->selectedFiles)) {
            return;
        }

        if (!$this->conversation) {
            session()->flash('error', 'Please select a conversation first.');
            return;
        }

        try {
            // Handle file attachments
            $attachments = $this->processAttachments();

            // Create message
            $message = Message::create([
                'conversation_id' => $this->conversation->id,
                'user_id' => Auth::id(),
                'content' => trim($this->messageText),
                'type' => $this->determineMessageType($attachments),
                'attachments' => $attachments,
            ]);

            // Load relationships for broadcasting
            $message->load('user');

            // Update conversation timestamp
            $this->conversation->touch();

            // Add this line to update last_seen_at
            Auth::user()->updateLastSeen();

            // Clear form first
            $this->reset(['messageText', 'selectedFiles', 'attachmentCaption', 'showAttachmentPreview']);

            // Refresh messages immediately
            $this->loadMessages();

            // Broadcast to other users
            broadcast(new MessageSent($message))->toOthers();

            // Send email notifications
            $recipients = $this->conversation->participants()->where('user_id', '!=', Auth::id())->get();
            foreach ($recipients as $recipient) {
                if ($recipient->email_notifications) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($recipient->notification_email ?? $recipient->email)->send(new \App\Mail\NewMessageEmail($message, Auth::user()));
                    } catch (\Exception $e) {
                        // Fail silently
                    }
                }
            }

            // Dispatch events
            $this->dispatch('messageAdded');
            $this->dispatch('scroll-to-bottom');
            $this->dispatch('success', 'Message sent successfully!');

        } catch (\Exception $e) {
            $this->dispatch('error', 'Failed to send message: ' . $e->getMessage());
        }
    }

    public function deleteMessage($messageId)
    {
        $message = Message::find($messageId);

        if (!$message || $message->user_id !== Auth::id()) {
            $this->dispatch('error', 'You can only delete your own messages.');
            return;
        }

        // Delete associated files
        if ($message->attachments) {
            foreach ($message->attachments as $attachment) {
                if (isset($attachment['path']) && Storage::disk('public')->exists($attachment['path'])) {
                    Storage::disk('public')->delete($attachment['path']);
                }
                // Delete thumbnail if exists
                if (isset($attachment['metadata']['thumbnail_path']) && Storage::disk('public')->exists($attachment['metadata']['thumbnail_path'])) {
                    Storage::disk('public')->delete($attachment['metadata']['thumbnail_path']);
                }
            }
        }

        $message->delete();
        $this->loadMessages();
        $this->dispatch('success', 'Message deleted successfully!');
    }



    public function updatedMessageText()
    {
        $this->startTyping();
    }

    public function updatedSelectedFiles()
    {
        if (!empty($this->selectedFiles) && !$this->isSendingVoiceMessage) {
            $this->showAttachmentPreview = true;
        }
    }

    public function startTyping()
    {
        if (!$this->isTyping && $this->conversation) {
            $this->isTyping = true;
            broadcast(new UserTyping($this->conversation->id, Auth::user(), true));

            $this->dispatch('typing-started');
        }
    }

    public function stopTyping()
    {
        if ($this->isTyping && $this->conversation) {
            $this->isTyping = false;
            broadcast(new UserTyping($this->conversation->id, Auth::user(), false));

            $this->dispatch('typing-stopped');
        }
    }

    public function messageReceived($event)
    {
        \Log::info('ChatInterface: Message received', ['event' => $event, 'current_conversation' => $this->conversation?->id]);

        // Always reload messages if we have a conversation loaded
        if ($this->conversation) {
            $this->loadMessages();

            // Mark as read if this is the active conversation
            if ($event['message']['conversation_id'] == $this->conversation->id) {
                $this->markMessagesAsRead();
            }

            // Dispatch notification
            if ($event['message']['user_id'] !== Auth::id()) {
                $this->dispatch('showNotification', [
                    'title' => 'New Message from ' . $event['message']['user']['first_name'],
                    'body' => $event['message']['content'],
                    'conversationId' => $event['message']['conversation_id']
                ]);
            }

            // Force immediate refresh of messages in chat body
            $this->dispatch('refreshChatMessages', ['id' => $this->conversation->id]);
            $this->dispatch('scroll-to-bottom');
            $this->dispatch('messageAdded');

            // Update sidebar
            $this->dispatch('conversationUpdated', ['id' => $this->conversation->id]);

            \Log::info('ChatInterface: Messages reloaded', ['message_count' => count($this->messages)]);
        } else {
            \Log::warning('ChatInterface: No conversation loaded when message received');
        }
    }

    public function userTyping($event)
    {
        if ($event['user_id'] !== Auth::id()) {
            if ($event['typing']) {
                $this->typingUsers[$event['user_id']] = $event['user_name'];
            } else {
                unset($this->typingUsers[$event['user_id']]);
            }
        }
    }

    public function uploadFiles($files)
    {
        $this->selectedFiles = $files;
        $this->showAttachmentPreview = !empty($files);
    }

    public function removeFile($index)
    {
        unset($this->selectedFiles[$index]);
        $this->selectedFiles = array_values($this->selectedFiles);
        $this->showAttachmentPreview = !empty($this->selectedFiles);
    }

    public function sendWithAttachments()
    {
        $this->messageText = $this->attachmentCaption;
        $this->sendMessage();
    }


    public function sendVoiceMessageDirect($audioData, $metadata = [])
    {
        if (!$this->conversation) {
            $this->dispatch('error', 'Please select a conversation first.');
            return;
        }

        try {
            // Decode base64 audio data
            $audioBlob = base64_decode($audioData);

            // Determine file extension based on MIME type
            $mimeType = $metadata['mimeType'] ?? 'audio/mp4';
            $extension = 'audio';

            if (str_contains($mimeType, 'mp4')) $extension = 'm4a';
            elseif (str_contains($mimeType, 'mpeg')) $extension = 'mp3';
            elseif (str_contains($mimeType, 'ogg')) $extension = 'ogg';
            elseif (str_contains($mimeType, 'wav')) $extension = 'wav';
            elseif (str_contains($mimeType, 'webm')) $extension = 'webm';

            // Generate filename with proper extension
            $filename = time() . '_' . uniqid() . '_voice_message.' . $extension;

            // Store file directly in audio messages directory (same as regular audio files)
            $path = "messages/audios/{$filename}";
            Storage::disk('public')->put($path, $audioBlob);

            // Create attachment data with voice message metadata
            $voiceMetadata = [
                'mime_type' => $mimeType,
                'original_name' => 'voice_message.' . $extension,
                'is_voice_message' => true,
                'duration' => $metadata['duration'] ?? null,
                'recorded_at' => $metadata['recordedAt'] ?? now()->toISOString(),
            ];

            $attachments = [[
                'path' => $path,
                'name' => 'Voice Message',
                'size' => strlen($audioBlob),
                'type' => $mimeType,
                'metadata' => $voiceMetadata
            ]];

            // Create message
            $duration = $metadata['duration'] ?? 0;
            $durationText = $this->formatDuration($duration);

            $message = Message::create([
                'conversation_id' => $this->conversation->id,
                'user_id' => Auth::id(),
                'content' => "Voice message ({$durationText})",
                'type' => Message::TYPE_AUDIO,
                'attachments' => $attachments,
            ]);

            // Load relationships for broadcasting
            $message->load('user');

            // Update conversation timestamp
            $this->conversation->touch();
            Auth::user()->updateLastSeen();

            // Refresh messages immediately
            $this->loadMessages();

            // Broadcast to other users
            broadcast(new MessageSent($message))->toOthers();

            // Send email notifications
            $recipients = $this->conversation->participants()->where('user_id', '!=', Auth::id())->get();
            foreach ($recipients as $recipient) {
                if ($recipient->email_notifications) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($recipient->notification_email ?? $recipient->email)->send(new \App\Mail\NewMessageEmail($message, Auth::user()));
                    } catch (\Exception $e) {
                        // Fail silently
                    }
                }
            }

            // Dispatch events
            $this->dispatch('messageAdded');
            $this->dispatch('scroll-to-bottom');
            $this->dispatch('success', 'Voice message sent successfully!');

        } catch (\Exception $e) {
            $this->dispatch('error', 'Failed to send voice message: ' . $e->getMessage());
        }
    }

    private function processAttachments()
    {
        if (empty($this->selectedFiles)) {
            return null;
        }

        $attachments = [];

        foreach ($this->selectedFiles as $file) {
            if (!$file) continue;

            $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

            // Determine file type based on extension
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp'];
            $audioExtensions = ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma', 'webm'];

            $fileType = 'file';
            if (in_array($extension, $imageExtensions)) {
                $fileType = 'image';
            } elseif (in_array($extension, $videoExtensions)) {
                $fileType = 'video';
            } elseif (in_array($extension, $audioExtensions)) {
                $fileType = 'audio';
            }

            // Store file
            $path = $file->storeAs("messages/{$fileType}s", $filename, 'public');

            $metadata = [
                'mime_type' => $mimeType,
                'original_name' => $file->getClientOriginalName(),
            ];

            // Handle image thumbnails
            if ($fileType === 'image') {
                try {
                    $thumbnailPath = "messages/thumbnails/{$filename}";
                    // Skip thumbnail generation if Image class doesn't exist
                    $metadata['thumbnail_path'] = $thumbnailPath;
                } catch (\Exception $e) {
                    // Continue without thumbnail
                }
            }

            $attachments[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'type' => $mimeType,
                'metadata' => $metadata
            ];
        }

        return $attachments;
    }


    private function determineMessageType($attachments)
    {
        if (empty($attachments)) {
            return 'text';
        }

        if (count($attachments) === 1) {
            $fileName = $attachments[0]['name'];
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
            $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp'];
            $audioExtensions = ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma', 'webm'];

            if (in_array($extension, $imageExtensions)) return 'image';
            if (in_array($extension, $videoExtensions)) return 'video';
            if (in_array($extension, $audioExtensions)) return 'audio';
        }

        return 'file';
    }

    private function markMessagesAsRead()
    {
        if (!$this->conversation) return;

        $this->conversation->messages()
            ->where('user_id', '!=', Auth::id())
            ->whereDoesntHave('readReceipts', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->get()
            ->each(function ($message) {
                $message->readReceipts()->firstOrCreate([
                    'user_id' => Auth::id(),
                ], [
                    'read_at' => now(),
                ]);
            });
    }

    public function getTypingUsersTextProperty()
    {
        if (empty($this->typingUsers)) {
            return '';
        }

        $names = array_values($this->typingUsers);
        if (count($names) === 1) {
            return $names[0] . ' is typing...';
        } elseif (count($names) === 2) {
            return $names[0] . ' and ' . $names[1] . ' are typing...';
        } else {
            return 'Several people are typing...';
        }
    }

    private function formatDuration($seconds)
    {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function render()
    {
        return view('livewire.chat.chat-interface');
    }
}
