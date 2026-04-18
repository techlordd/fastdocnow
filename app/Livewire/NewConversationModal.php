<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\Contact;
use App\Models\User;
use App\Models\WordPressUser;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NewConversationModal extends Component
{
    public $selectedRecipient = null;
    public $searchTerm = '';

    public function mount()
    {
        $this->selectedRecipient = null;
        $this->searchTerm = '';
    }

    public function selectRecipient($recipientType, $recipientId)
    {
        $this->selectedRecipient = $recipientType . ':' . $recipientId;

        if ($recipientType === 'patient') {
            $this->createPatientConversation((int) $recipientId);
            return;
        }

        $this->createContactConversation((int) $recipientId);
    }

    public function createContactConversation($contactId)
    {
        $contact = Contact::with('assignedUser')->find($contactId);

        if (!$contact || !$contact->is_active) {
            $this->addError('selectedRecipient', 'Contact is not available.');
            return;
        }

        if (!$contact->assigned_user_id) {
            $this->addError('selectedRecipient', 'No staff member is assigned to this contact.');
            return;
        }

        // Prevent staff from creating conversations with contacts assigned to themselves
        if (Auth::id() === $contact->assigned_user_id) {
            $this->addError('selectedRecipient', 'You cannot start a conversation with a contact assigned to you.');
            return;
        }

        // Check if conversation already exists
        $existingConversation = Auth::user()->conversations()
            ->where('contact_id', $contactId)
            ->first();

        if ($existingConversation) {
            $this->dispatch('conversationCreated', $existingConversation->id);
            $this->dispatch('conversationSelected', $existingConversation->id);
            $this->dispatch('success', 'Switched to existing conversation with ' . $contact->name);
            $this->resetModal();
            return;
        }

        // Create new conversation (using same logic as sidebar)
        $conversation = Conversation::create([
            'type' => 'private',
            'contact_id' => $contactId,
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        // Add participants
        $conversation->addParticipant(Auth::user());
        $conversation->addParticipant($contact->assignedUser);

        $this->dispatch('conversationCreated', $conversation->id);
        $this->dispatch('conversationSelected', $conversation->id);
        $this->dispatch('success', 'Conversation started with ' . $contact->name);
        $this->resetModal();
    }

    public function createPatientConversation(int $wordpressUserId)
    {
        $wordpressUser = WordPressUser::find($wordpressUserId);

        if (!$wordpressUser || !$wordpressUser->canAccessChat()) {
            $this->addError('selectedRecipient', 'Patient is not available for chat.');
            return;
        }

        $patientUser = $wordpressUser->toLaravelUser();

        if (!$patientUser || $patientUser->id === Auth::id()) {
            $this->addError('selectedRecipient', 'You cannot start a conversation with this patient.');
            return;
        }

        $existingConversation = Auth::user()->conversations()
            ->where('type', 'private')
            ->whereNull('contact_id')
            ->whereHas('participants', function ($query) use ($patientUser) {
                $query->where('users.id', $patientUser->id);
            })
            ->first();

        if ($existingConversation) {
            $this->dispatch('conversationCreated', $existingConversation->id);
            $this->dispatch('conversationSelected', $existingConversation->id);
            $this->dispatch('success', 'Switched to existing conversation with ' . $patientUser->name);
            $this->resetModal();
            return;
        }

        $conversation = Conversation::create([
            'type' => 'private',
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        $conversation->addParticipant(Auth::user());
        $conversation->addParticipant($patientUser);

        $this->dispatch('conversationCreated', $conversation->id);
        $this->dispatch('conversationSelected', $conversation->id);
        $this->dispatch('success', 'Conversation started with ' . $patientUser->name);
        $this->resetModal();
    }



    private function resetModal()
    {
        $this->selectedRecipient = null;
        $this->searchTerm = '';
    }

    public function getAvailableContactsProperty()
    {
        $recipients = collect();

        if ($this->shouldShowPatients()) {
            $patientQuery = WordPressUser::query()
                ->where('user_status', 0)
                ->whereNotNull('user_email')
                ->where('user_email', '!=', '');

            if ($this->searchTerm) {
                $patientQuery->where(function ($query) {
                    $query->where('display_name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('user_email', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('user_login', 'like', '%' . $this->searchTerm . '%');
                });
            }

            $patients = $patientQuery
                ->orderBy('display_name')
                ->limit($this->searchTerm ? 100 : 24)
                ->get()
                ->filter(function ($wordpressUser) {
                    return $wordpressUser->getRole() === 'subscriber'
                        && $wordpressUser->canAccessChat()
                        && $wordpressUser->user_email !== Auth::user()->email
                        && $wordpressUser->ID !== Auth::user()->wp_user_id;
                })
                ->map(function ($wordpressUser) {
                    return [
                        'id' => $wordpressUser->ID,
                        'record_type' => 'patient',
                        'name' => trim($wordpressUser->first_name . ' ' . $wordpressUser->last_name) ?: $wordpressUser->name,
                        'description' => $wordpressUser->user_email,
                        'type' => 'patient',
                        'assigned_user' => null,
                        'avatar' => $wordpressUser->avatar_url,
                    ];
                });

            $recipients = $recipients->concat($patients);
        }

        if (!$this->shouldShowPatients() || $recipients->isEmpty()) {
            $contactQuery = Contact::active()->with('assignedUser');

            if ($this->searchTerm) {
                $contactQuery->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
                });
            }

            $contacts = $contactQuery->ordered()
                ->get()
                ->map(function ($contact) {
                    $assignedUser = $contact->assignedUser;
                    $lastSeenAt = $assignedUser ? Carbon::make($assignedUser->last_seen_at) : null;

                    return [
                        'id' => $contact->id,
                        'record_type' => 'contact',
                        'name' => $contact->name,
                        'description' => $contact->description,
                        'type' => $contact->type,
                        'assigned_user' => $assignedUser ? [
                            'id' => $assignedUser->id,
                            'name' => $assignedUser->first_name . ' ' . $assignedUser->last_name,
                            'last_seen_at' => $lastSeenAt,
                            'is_online' => $lastSeenAt ? $lastSeenAt->greaterThan(now()->subMinutes(2)) : false,
                        ] : null,
                        'avatar' => null,
                    ];
                });

            $recipients = $recipients->concat($contacts);
        }

        return $recipients->values()->toArray();
    }

    protected function shouldShowPatients(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->is_admin || !$user->isWordPressUser()) {
            return true;
        }

        if ($user->hasWordPressCapability('administrator') || $user->hasWordPressCapability('editor')) {
            return true;
        }

        return false;
    }

    public function render()
    {
        return view('livewire.new-conversation-modal');
    }
}
