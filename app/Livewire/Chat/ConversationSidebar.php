<?php

namespace App\Livewire\Chat;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Pulse\Users;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationSidebar extends Component
{
    public $contacts = [];
    public $conversations = [];
    public $conversationsall = [];
    public $activeConversationId = null;
    public $conversationSearchTerm = '';

    public function mount()
    {
        $this->loadContactsAndConversations();
    }

    public function loadContactsAndConversations()
    {
        // Load active contacts
        $this->contacts = Contact::active()
            ->with('assignedUser')
            ->ordered()
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'description' => $contact->description,
                    'type' => $contact->type,
                    'assigned_user' => $contact->assignedUser ? [
                        'id' => $contact->assignedUser->id,
                        'name' => $contact->assignedUser->first_name . ' ' . $contact->assignedUser->last_name,
                        'last_seen_at' => $contact->assignedUser->last_seen_at,
                        'is_online' => $contact->assignedUser->last_seen_at &&
                            $contact->assignedUser->last_seen_at->gt(now()->subMinutes(2)),
                    ] : null,
                ];
            })->toArray();

        // Load existing conversations for current user
        $this->conversationsall = Auth::user()->conversations()->get();
        $conversationsQuery = Auth::user()->conversations()
            ->with(['contact', 'latestMessage.user', 'participants'])
            ->withCount([
                'messages as unread_count' => function ($query) {
                    $query->where('user_id', '!=', Auth::id())
                        ->whereDoesntHave('readReceipts', function ($q) {
                            $q->where('user_id', Auth::id());
                        });
                }
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at');

        $allConversations = $conversationsQuery->get()
            ->map(function ($conversation) {
                $otherUser = $conversation->participants
                    ? $conversation->participants->firstWhere('id', '!=', Auth::id())
                    : null;

                $latestMessage = $conversation->latestMessage;
                $latestMessageUser = $latestMessage?->user;

                return [
                    'id' => $conversation->id,
                    'contact_id' => $conversation->contact && $conversation->contact->assignedUser
                    ? $conversation->contact->assignedUser->id
                    : null,
                    'contact_name' => optional($conversation->contact)->name ?? 'Unknown Contact',
                    'contact_type' => optional($conversation->contact)->type ?? 'unknown',

                    'latest_message' => $latestMessage ? [
                        'content' => $latestMessage->content,
                        'type' => $latestMessage->type,
                        'created_at' => $latestMessage->created_at,
                        'user_name' => $latestMessageUser->first_name ?? 'Unknown',
                        'is_own' => $latestMessage->user_id === Auth::id(),
                    ] : null,

                    'unread_count' => $conversation->unread_count ?? 0,
                    'last_message_at' => $latestMessage->created_at ?? null,

                    'other_user_name' => $otherUser->first_name ?? 'Unknown',
                    'other_user_last_name' => $otherUser->last_name ?? 'Unknown',
                    'other_user_avatar' => $otherUser->avatar ?? null,
                ];
            });

        // Filter conversations based on search term
        if (!empty($this->conversationSearchTerm)) {
            $searchTerm = strtolower($this->conversationSearchTerm);
            $this->conversations = $allConversations->filter(function ($conversation) use ($searchTerm) {
                // Search in contact name
                if (str_contains(strtolower($conversation['contact_name']), $searchTerm)) {
                    return true;
                }

                // Search in other user name
                $fullName = strtolower($conversation['other_user_name'] . ' ' . $conversation['other_user_last_name']);
                if (str_contains($fullName, $searchTerm)) {
                    return true;
                }

                // Search in latest message content
                if ($conversation['latest_message'] &&
                    str_contains(strtolower($conversation['latest_message']['content']), $searchTerm)) {
                    return true;
                }

                return false;
            })->values()->toArray();
        } else {
            $this->conversations = $allConversations->toArray();
        }
    }

    public function startConversationWithContact($contactId)
    {
        $contact = Contact::with('assignedUser')->find($contactId);

        if (!$contact || !$contact->is_active) {
            $this->dispatch('error', 'Contact is not available.');
            return;
        }

        if (!$contact->assigned_user_id) {
            $this->dispatch('error', 'No staff member is assigned to this contact.');
            return;
        }

        // Check if conversation already exists
        $existingConversation = Auth::user()->conversations()
            ->where('contact_id', $contactId)
            ->first();

        if ($existingConversation) {
            $this->selectConversation($existingConversation->id);
            return;
        }

        // Create new conversation
        $conversation = Conversation::create([
            'type' => 'private',
            'contact_id' => $contactId,
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        // Add participants
        $conversation->addParticipant(Auth::user());
        $conversation->addParticipant($contact->assignedUser);

        $this->loadContactsAndConversations();
        $this->selectConversation($conversation->id);

        $this->dispatch('success', 'Conversation started with ' . $contact->name);
    }

    public function selectConversation($conversationId)
    {
        $this->activeConversationId = $conversationId;
        $this->dispatch('conversationSelected', $conversationId);
    }

    public function clearConversationSearch()
    {
        $this->conversationSearchTerm = '';
        $this->loadContactsAndConversations();
    }

    public function updatedConversationSearchTerm()
    {
        $this->loadContactsAndConversations();
    }

    #[On('conversationUpdated')]
    public function refreshConversations($conversationId = null)
    {
        \Log::info('ConversationSidebar: Refreshing conversations', ['conversationId' => $conversationId]);
        $this->loadContactsAndConversations();
    }

    #[On('refreshConversations')]
    public function refreshConversationsEvent()
    {
        \Log::info('ConversationSidebar: Event-triggered refresh conversations list');
        $this->loadContactsAndConversations();

        // Force component re-render
        $this->skipRender = false;
    }

    public function refreshConversationsList()
    {
        \Log::info('ConversationSidebar: Manual refresh conversations list called');
        $this->loadContactsAndConversations();

        // Force component re-render - multiple approaches
        $this->skipRender = false;

        // Force Livewire to re-render by updating properties
        $this->conversations = $this->conversations; // This forces reactivity
        $this->contacts = $this->contacts;
    }

    public function handleMessageReceived($event)
    {
        \Log::info('ConversationSidebar: Message received', ['event' => $event]);

        // Always refresh conversation list when a new message is received
        $this->loadContactsAndConversations();

        // If the message is not for the current active conversation, show notification
        if (isset($event['message']) &&
            $event['message']['conversation_id'] != $this->activeConversationId &&
            $event['message']['user_id'] !== auth()->id()) {

            $this->dispatch('showNotification', [
                'title' => 'New Message from ' . ($event['message']['user']['first_name'] ?? 'Someone'),
                'body' => $event['message']['content'] ?? 'New message',
                'conversationId' => $event['message']['conversation_id']
            ]);
        }
    }

    #[On('conversationDeleted')]
    public function handleConversationDeleted($data)
    {
        $conversationId = is_array($data) ? $data['id'] : $data;
        \Log::info('ConversationSidebar: Conversation deleted', ['conversationId' => $conversationId]);

        if ($this->activeConversationId == $conversationId) {
            $this->activeConversationId = null;
        }
        $this->loadContactsAndConversations();
    }

    #[On('conversationArchived')]
    public function handleConversationArchived($data)
    {
        $conversationId = is_array($data) ? $data['id'] : $data;
        \Log::info('ConversationSidebar: Conversation archived', ['conversationId' => $conversationId]);

        if ($this->activeConversationId == $conversationId) {
            $this->activeConversationId = null;
        }
        $this->loadContactsAndConversations();
    }

    public function testRefresh()
    {
        \Log::info('ConversationSidebar: TEST REFRESH called');
        $this->loadContactsAndConversations();
        \Log::info('ConversationSidebar: TEST REFRESH loaded data', [
            'conversations_count' => count($this->conversations),
            'contacts_count' => count($this->contacts)
        ]);

        // Force multiple re-render approaches
        $this->skipRender = false;
        $this->conversations = $this->conversations;
        $this->contacts = $this->contacts;

        return count($this->conversations);
    }

    public function render()
    {
        return view('livewire.chat.conversation-sidebar');
    }
}
