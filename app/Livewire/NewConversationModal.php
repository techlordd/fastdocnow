<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NewConversationModal extends Component
{
    public $step = 1;
    public $conversationType = '';
    public $selectedUsers = [];
    public $searchTerm = '';
    public $groupTitle = '';

    public function mount()
    {
        // You might not want to reset everything here, especially $step
        $this->step = 1;
        $this->conversationType = '';
        $this->selectedUsers = [];
        $this->searchTerm = '';
        $this->groupTitle = '';
    }

    public function selectType($type)
    {
        $this->conversationType = $type;
        $this->step = 2;
    }

    public function toggleUser($userId)
    {
        if ($this->conversationType === 'private') {
            $this->selectedUsers = [$userId];
        } else {
            if (in_array($userId, $this->selectedUsers)) {
                $this->selectedUsers = array_filter($this->selectedUsers, fn($id) => $id !== $userId);
            } else {
                $this->selectedUsers[] = $userId;
            }
        }
    }

    public function createConversation()
    {
        $this->validate([
            'selectedUsers' => 'required|array|min:1',
            'groupTitle' => $this->conversationType === 'group' ? 'required|string|max:100' : 'nullable',
        ]);

        $user = Auth::user();

        if ($this->conversationType === 'private' && count($this->selectedUsers) === 1) {
            $otherUser = User::find($this->selectedUsers[0]);

            if (!$otherUser) {
                $this->addError('selectedUsers', 'Selected user not found.');
                return;
            }

            $conversation = Conversation::findOrCreatePrivate($user, $otherUser);
            $this->dispatch('conversationCreated', $conversation->id);
            return;
        }

        // Create new group conversation
        $conversation = Conversation::create([
            'type' => 'group',
            'title' => $this->groupTitle,
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        // Add participants: creator as admin, others as members
        $conversation->addParticipant($user, 'admin');

        foreach ($this->selectedUsers as $userId) {
            if ($userId != $user->id) {
                $participant = User::find($userId);
                if ($participant) {
                    $conversation->addParticipant($participant, 'member');
                }
            }
        }

        $this->dispatch('conversationCreated', $conversation->id);
    }

    public function goBack()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function getAvailableUsersProperty()
    {
        $query = User::where('id', '!=', Auth::id());

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('username', 'like', '%' . $this->searchTerm . '%')
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
