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

        $user = Auth::user();
        $contact = Contact::find($this->selectedContact);

        if (!$contact) {
            $this->addError('selectedContact', 'Selected contact not found.');
            return;
        }

        $conversation = Conversation::findOrCreateContact($user, $contact);
        $this->dispatch('conversationCreated', $conversation->id);
        
        // Reset the modal
        $this->selectedContact = null;
        $this->searchTerm = '';
    }

    public function getAvailableContactsProperty()
    {
        $query = Contact::query();

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
            });
        }

        return $query->orderBy('first_name')->take(20)->get();
    }

    public function render()
    {
        return view('livewire.new-conversation-modal');
    }
}
