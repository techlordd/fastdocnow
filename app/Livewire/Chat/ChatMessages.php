<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatMessages extends Component
{
    public $conversationId;
    public $messages = [];

    protected $listeners = [
        'conversationSelected' => 'loadConversation',
        'refreshChatMessages' => 'refreshMessages',
        'messageReceived' => 'messageReceived',
        'forceRefresh' => 'forceRefresh',
        'loadMessagesForConversation' => 'loadConversationInternal'
    ];

    public function mount($conversationId = null)
    {
        if ($conversationId) {
            $this->conversationId = $conversationId;
            $this->loadMessages();
        }
    }

    public function loadConversation($conversationId)
    {
        $this->conversationId = $conversationId;
        $this->loadMessages();
        $this->markMessagesAsRead();
        $this->dispatch('scroll-to-bottom');
    }

    public function loadConversationInternal($conversationId)
    {
        // Internal method for loading conversation without triggering events
        // Used to prevent infinite loops when ChatInterface loads a conversation
        $this->conversationId = $conversationId;
        $this->loadMessages();
        $this->markMessagesAsRead();
        // Don't dispatch scroll-to-bottom here as ChatInterface already does it
    }

    public function loadMessages()
    {
        if (!$this->conversationId) {
            $this->messages = [];
            return;
        }

        $conversation = Conversation::find($this->conversationId);
        if (!$conversation) {
            $this->messages = [];
            return;
        }

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', Auth::id())->exists()) {
            $this->messages = [];
            return;
        }

        $this->messages = $conversation->messages()
            ->with(['user'])
            ->select(['id', 'conversation_id', 'user_id', 'content', 'type', 'attachments', 'created_at', 'updated_at'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function refreshMessages($conversationId = null)
    {
        \Log::info('ChatMessages: Refresh messages called', ['conversationId' => $conversationId, 'current' => $this->conversationId]);

        // If a specific conversation ID is provided, only refresh if it matches
        if ($conversationId && $this->conversationId != $conversationId) {
            \Log::info('ChatMessages: Ignoring refresh for different conversation');
            return; // Not for this conversation
        }

        $this->loadMessages();
        $this->markMessagesAsRead();

        // Force component re-render - multiple approaches
        $this->skipRender = false;

        // Force Livewire to re-render by updating a property
        $this->messages = $this->messages; // This forces reactivity

        $this->dispatch('scroll-to-bottom');
        $this->dispatch('messageAdded');
        \Log::info('ChatMessages: Messages refreshed', ['count' => count($this->messages)]);
    }

    public function forceRefresh()
    {
        \Log::info('ChatMessages: Force refresh called');
        $this->loadMessages();
        $this->markMessagesAsRead();
        $this->dispatch('scroll-to-bottom');
        $this->dispatch('messageAdded');
    }

    public function messageReceived($event)
    {
        \Log::info('ChatMessages: Message received event', [
            'event' => $event,
            'current_conversation' => $this->conversationId
        ]);

        // Check if this message is for our conversation
        if (isset($event['message']['conversation_id']) &&
            $event['message']['conversation_id'] == $this->conversationId) {

            $this->loadMessages();
            $this->markMessagesAsRead();

            // Force component re-render
            $this->skipRender = false;

            \Log::info('ChatMessages: Messages reloaded for conversation', [
                'conversationId' => $this->conversationId,
                'message_count' => count($this->messages)
            ]);
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
                if (isset($attachment['path']) && \Storage::disk('public')->exists($attachment['path'])) {
                    \Storage::disk('public')->delete($attachment['path']);
                }
                // Delete thumbnail if exists
                if (isset($attachment['metadata']['thumbnail_path']) && \Storage::disk('public')->exists($attachment['metadata']['thumbnail_path'])) {
                    \Storage::disk('public')->delete($attachment['metadata']['thumbnail_path']);
                }
            }
        }

        $message->delete();
        $this->loadMessages();
        $this->dispatch('success', 'Message deleted successfully!');
    }

    private function markMessagesAsRead()
    {
        if (!$this->conversationId) return;

        $conversation = Conversation::find($this->conversationId);
        if (!$conversation) return;

        $conversation->messages()
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

    public function testRefresh()
    {
        \Log::info('ChatMessages: TEST REFRESH called', ['conversationId' => $this->conversationId]);
        $this->loadMessages();
        \Log::info('ChatMessages: TEST REFRESH loaded messages', ['count' => count($this->messages), 'messages' => $this->messages]);

        // Force multiple re-render approaches
        $this->skipRender = false;
        $this->messages = $this->messages;

        return count($this->messages);
    }

    public function render()
    {
        return view('livewire.chat.chat-messages');
    }
}
