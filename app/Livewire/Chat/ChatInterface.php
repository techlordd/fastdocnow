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
        $this->conversation = Conversation::with(['participants', 'messages.user', 'messages.reactions'])
            ->where('id', $conversationId)
            ->whereHas('participants', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->first();

        if ($this->conversation) {
            $this->loadMessages();
            $this->markMessagesAsRead();
            $this->dispatch('conversationLoaded');
            $this->dispatch('scroll-to-bottom');
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
            $this->conversation = Conversation::with(['participants'])
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
        if (!$this.conversation) return;

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

    public function leaveGroup()
    {
        if (!$this->conversation || $this->conversation->type !== 'group') return;

        // Remove user from conversation participants
        $this->conversation->participants()->detach(Auth::id());

        // If no participants left, delete the conversation
        if ($this->conversation->participants()->count() === 0) {
            $this->conversation->delete();
        }

        $this->dispatch('leftGroup', ['id' => $this->conversation->id]);
        $this->clearConversation();

        session()->flash('message', 'You have left the group.');
    }

    public function loadMessages()
    {
        if (!$this->conversation) return;

        $this->messages = $this->conversation->messages()
            ->with(['user', 'reactions.user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
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
            $message->load('user', 'reactions');

            // Update conversation timestamp
            $this->conversation->touch();

            // Clear form first
            $this->reset(['messageText', 'selectedFiles', 'attachmentCaption', 'showAttachmentPreview']);

            // Refresh messages immediately
            $this->loadMessages();

            // Broadcast to other users
            broadcast(new MessageSent($message))->toOthers();

            // Send notifications to assigned staff
            $notificationService = new NotificationService();
            $notificationService->sendMessageNotification($message);

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

    public function toggleReaction($messageId, $emoji)
    {
        $message = Message::find($messageId);
        if (!$message) return;

        $existingReaction = $message->reactions()
            ->where('user_id', Auth::id())
            ->where('emoji', $emoji)
            ->first();

        if ($existingReaction) {
            $existingReaction->delete();
        } else {
            $message->reactions()->create([
                'user_id' => Auth::id(),
                'emoji' => $emoji,
            ]);
        }

        $this->loadMessages();
        $this->dispatch('reactionToggled', ['id' => $messageId, 'emoji' => $emoji]);
    }

    public function updatedMessageText()
    {
        $this->startTyping();
    }

    public function updatedSelectedFiles()
    {
        if (!empty($this->selectedFiles)) {
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

            // Determine file type
            $fileType = 'file';
            if (strpos($mimeType, 'image/') === 0) {
                $fileType = 'image';
            } elseif (strpos($mimeType, 'video/') === 0) {
                $fileType = 'video';
            } elseif (strpos($mimeType, 'audio/') === 0) {
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
            $mimeType = $attachments[0]['type'];
            if (strpos($mimeType, 'image/') === 0) return 'image';
            if (strpos($mimeType, 'video/') === 0) return 'video';
            if (strpos($mimeType, 'audio/') === 0) return 'audio';
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

    public function render()
    {
        return view('livewire.chat.chat-interface');
    }
}
