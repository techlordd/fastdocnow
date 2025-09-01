<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $conversations = $user->conversations()
            ->with(['participants', 'latestMessage.user'])
            ->latest('updated_at')
            ->get();

        return response()->json($conversations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:private,group',
            'participants' => 'required|array|min:1',
            'title' => 'nullable|string|max:100',
            'contact_id' => 'nullable|exists:contacts,id',
        ]);

        $user = Auth::user();
        $participantIds = $request->participants;

        // If this is a contact-based conversation, prevent staff from creating conversations with their own assigned contacts
        if ($request->contact_id) {
            $contact = \App\Models\Contact::find($request->contact_id);
            if ($contact && $contact->assigned_user_id === $user->id) {
                return response()->json(['error' => 'You cannot start a conversation with a contact assigned to you.'], 422);
            }
        }

        // For private chats, check if conversation already exists
        if ($request->type === 'private' && count($participantIds) === 1) {
            $otherUserId = $participantIds[0];
            $otherUser = User::find($otherUserId);

            if (!$otherUser) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $existingConversation = $user->conversations()
                ->where('type', 'private')
                ->whereHas('participants', function ($query) use ($otherUserId) {
                    $query->where('user_id', $otherUserId);
                })
                ->whereDoesntHave('participants', function ($query) use ($user, $otherUserId) {
                    $query->whereNotIn('user_id', [$user->id, $otherUserId]);
                })
                ->first();

            if ($existingConversation) {
                return response()->json(['conversation' => $existingConversation]);
            }
        }

        DB::beginTransaction();
        try {
            // Create conversation
            $conversation = Conversation::create([
                'type' => $request->type,
                'title' => $request->title,
                'created_by' => $user->id,
            ]);

            // Add participants
            $allParticipants = array_unique(array_merge((array) $participantIds, [$user->id]));

            foreach ($allParticipants as $participantId) {
                $participant = User::find($participantId);
                if ($participant) {
                    $conversation->addParticipant($participant, $participantId === $user->id ? 'admin' : 'member');
                }
            }

            DB::commit();

            $conversation->load(['participants']);

            return response()->json(['conversation' => $conversation]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create conversation'], 500);
        }
    }

    public function destroy(Conversation $conversation)
    {
        $user = Auth::user();

        // Check if user is admin or creator
        if ($conversation->created_by !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted successfully']);
    }

    public function archive(Conversation $conversation)
    {
        $user = Auth::user();

        // Check if user is participant
        $participant = $conversation->participants()->where('user_id', $user->id)->first();

        if (!$participant) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $participant->update(['archived_at' => now()]);

        return response()->json(['message' => 'Conversation archived']);
    }

    public function unarchive(Conversation $conversation)
    {
        $user = Auth::user();

        // Check if user is participant
        $participant = $conversation->participants()->where('user_id', $user->id)->first();

        if (!$participant) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $participant->update(['archived_at' => null]);

        return response()->json(['message' => 'Conversation unarchived']);
    }
}
