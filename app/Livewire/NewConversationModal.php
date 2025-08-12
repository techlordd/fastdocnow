<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\Contact;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NewConversationModal extends Component
{
    public $selectedContact = null;
    public $searchTerm = '';

    public function mount()
    {
        $this->selectedContact = null;
        $this->searchTerm = '';
    }

    public function selectContact($contactId)
    {
        $this->selectedContact = $contactId;
    }

    public function createConversation()
    {
        $this->validate([
            'selectedContact' => 'required|exists:contacts,id',
        ]);

        $contact = Contact::with('assignedUser')->find($this->selectedContact);

        if (!$contact || !$contact->is_active) {
            $this->addError('selectedContact', 'Contact is not available.');
            return;
        }

        if (!$contact->assigned_user_id) {
            $this->addError('selectedContact', 'No staff member is assigned to this contact.');
            return;
        }

        // Check if conversation already exists
        $existingConversation = Auth::user()->conversations()
            ->where('contact_id', $this->selectedContact)
            ->first();

        if ($existingConversation) {
            $this->dispatch('conversationCreated', $existingConversation->id);
            $this->dispatch('success', 'Switched to existing conversation with ' . $contact->name);
            $this->resetModal();
            return;
        }

        // Create new conversation (using same logic as sidebar)
        $conversation = Conversation::create([
            'type' => 'private',
            'contact_id' => $this->selectedContact,
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        // Add participants
        $conversation->addParticipant(Auth::user());
        $conversation->addParticipant($contact->assignedUser);

        $this->dispatch('conversationCreated', $conversation->id);
        $this->dispatch('success', 'Conversation started with ' . $contact->name);
        $this->resetModal();
    }

    private function resetModal()
    {
        $this->selectedContact = null;
        $this->searchTerm = '';
    }

    public function getAvailableContactsProperty()
    {
        // Use same contact loading logic as sidebar
        $query = Contact::active()->with('assignedUser');

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }

        return $query->ordered()
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
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.new-conversation-modal');
    }
}
