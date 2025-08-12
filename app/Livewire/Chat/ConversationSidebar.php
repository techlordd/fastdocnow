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
        $this->conversations = Auth::user()->conversations()
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
            ->orderByDesc('created_at')
            ->get()
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
            })
            ->toArray();
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

    #[On('conversationUpdated')]
    public function refreshConversations()
    {
        $this->loadContactsAndConversations();
    }

    #[On('conversationDeleted')]
    public function handleConversationDeleted($conversationId)
    {
        if ($this->activeConversationId == $conversationId) {
            $this->activeConversationId = null;
        }
        $this->loadContactsAndConversations();
    }

    #[On('conversationArchived')]
    public function handleConversationArchived($conversationId)
    {
        if ($this->activeConversationId == $conversationId) {
            $this->activeConversationId = null;
        }
        $this->loadContactsAndConversations();
    }

    public function render()
    {
        return view('livewire.chat.conversation-sidebar');
    }
}
