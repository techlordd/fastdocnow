<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\PusherService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatInterface extends Component
{
    use WithFileUploads;

    public $conversation;
    public $messageText = '';
    public $selectedFiles = [];
    public $attachmentCaption = '';
    public $showAttachmentPreview = false;
    public $isTyping = false;
    public $typingUsers = [];
    public $isSendingVoiceMessage = false;

    protected $listeners = [
        'conversationSelected' => 'loadConversation',
        'messageReceived' => 'messageReceived'
    ];

    protected $pusherService;

    public function boot(PusherService $pusherService)
    {
        $this->pusherService = $pusherService;
    }

    public function mount($conversationId = null)
    {
        if ($conversationId) {
            $this->loadConversation($conversationId);
        }
    }

    public function loadConversation($conversationId)
    {
        $this->conversation = Conversation::with(['participants', 'contact'])
            ->where('id', $conversationId)
            ->whereHas('participants', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->first();

        if ($this->conversation) {
            $this->dispatch('conversationLoaded', $this->conversation->id);
            $this->dispatch('scroll-to-bottom');
            
            // Update user's last seen timestamp and broadcast online status
            Auth::user()->updateLastSeen();
            app(PusherService::class)->userJoinedConversation(Auth::user(), $this->conversation->id);

            $this->dispatch('loadMessagesForConversation', $this->conversation->id);
        }
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
        if ($this->conversation) {
            // Broadcast user leaving conversation
            app(PusherService::class)->userLeftConversation(Auth::user(), $this->conversation->id);
        }
        
        $this->conversation = null;
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

    public function refreshMessages()
    {
        \Log::info('ChatInterface: Refresh messages called');
        if ($this->conversation) {
            $this->dispatch('refreshChatMessages', $this->conversation->id);
        }
        $this->dispatch('scroll-to-bottom');
    }

    public function forceRefresh()
    {
        \Log::info('ChatInterface: Force refresh called');
        if ($this->conversation) {
            $this->dispatch('refreshChatMessages', $this->conversation->id);
            $this->dispatch('scroll-to-bottom');
            $this->dispatch('messageAdded');
        }
    }

    public function sendMessage()
    {
        \Log::info('SendMessage called', [
            'messageText' => $this->messageText,
            'selectedFiles' => count($this->selectedFiles ?? []),
            'conversation' => $this->conversation ? $this->conversation->id : null
        ]);

        if (empty(trim($this->messageText)) && empty($this->selectedFiles)) {
            \Log::info('SendMessage: Empty message and no files');
            $this->dispatch('error', 'Please enter a message or select files to send.');
            return;
        }

        if (!$this->conversation) {
            \Log::warning('SendMessage: No conversation selected');
            $this->dispatch('error', 'Please select a conversation first.');
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

            // Update conversation timestamp
            $this->conversation->touch();
            Auth::user()->updateLastSeen();

            // Clear form first
            $this->reset(['messageText', 'selectedFiles', 'attachmentCaption', 'showAttachmentPreview']);

            // Use PusherService to broadcast message (this handles notifications)
            app(PusherService::class)->broadcastMessage($message);

            // Only refresh messages locally - Pusher will handle other users
            $this->dispatch('refreshChatMessages', $this->conversation->id);
            $this->dispatch('conversationUpdated', $this->conversation->id);
            $this->dispatch('scroll-to-bottom');

        } catch (\Exception $e) {
            $this->dispatch('error', 'Failed to send message: ' . $e->getMessage());
        }
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
            app(PusherService::class)->broadcastTyping($this->conversation->id, Auth::user(), true);
            $this->dispatch('typing-started');
        }
    }

    public function stopTyping()
    {
        if ($this->isTyping && $this->conversation) {
            $this->isTyping = false;
            app(PusherService::class)->broadcastTyping($this->conversation->id, Auth::user(), false);
            $this->dispatch('typing-stopped');
        }
    }

    public function messageReceived($event)
    {
        \Log::info('ChatInterface: Message received', ['event' => $event, 'current_conversation' => $this->conversation?->id]);

        if (!$this->conversation) {
            \Log::warning('ChatInterface: No conversation loaded when message received');
            return;
        }

        if (!isset($event['message']['conversation_id']) ||
            $event['message']['conversation_id'] != $this->conversation->id) {
            \Log::info('ChatInterface: Message not for current conversation', [
                'message_conversation' => $event['message']['conversation_id'] ?? 'unknown',
                'current_conversation' => $this->conversation->id
            ]);
            return;
        }

        // Only forward to ChatMessages component to refresh messages
        $this->dispatch('refreshChatMessages', $this->conversation->id);

        // Only scroll to bottom and update conversation list for UI updates
        $this->dispatch('scroll-to-bottom');
        $this->dispatch('conversationUpdated', $this->conversation->id);

        \Log::info('ChatInterface: Message event processed successfully');
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

            // Store file directly in audio messages directory
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

            // Update conversation timestamp
            $this->conversation->touch();
            Auth::user()->updateLastSeen();

            // Use PusherService to broadcast message and handle all related events
            app(PusherService::class)->broadcastMessage($message);

            // Refresh messages immediately
            $this->dispatch('refreshChatMessages', $this->conversation->id);
            $this->dispatch('conversationUpdated', $this->conversation->id);
            $this->dispatch('scroll-to-bottom');

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
