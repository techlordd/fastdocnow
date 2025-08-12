<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get user's conversations with latest message and unread count
        $conversations = $user->conversations()
            ->with(['participants', 'latestMessage.user', 'messages' => function($query) use ($user) {
                $query->where('user_id', '!=', $user->id)
                      ->whereDoesntHave('readReceipts', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            }])
            ->latest('updated_at')
            ->get()
            ->map(function ($conversation) use ($user) {
                $conversation->unread_count = $conversation->messages->count();
                $conversation->other_participant = $conversation->participants
                    ->where('user_id', '!=', $user->id)
                    ->first();
                return $conversation;
            });

        // Get online friends
        $onlineFriends = $user->friends()
            ->where('users.last_seen_at', '>', now()->subMinutes(5))
            ->get();

        // Get recent friend requests
        $friendRequests = $user->receivedFriendRequests()
            ->with('sender')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        return view('chat.index', compact('conversations', 'onlineFriends', 'friendRequests'));
    }

    public function show(Conversation $conversation)
    {
        $user = Auth::user();

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            abort(403, 'You are not a participant in this conversation.');
        }

        // Load conversation with messages and participants
        $conversation->load([
            'messages.user',
            'messages.readReceipts',
            'messages.reactions.user',
            'participants'
        ]);

        // Mark messages as read
        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->each(function ($message) use ($user) {
                $message->readReceipts()->create([
                    'user_id' => $user->id,
                    'read_at' => now(),
                ]);
            });

        // Get other participants for header info
        $otherParticipants = $conversation->participants()
            ->where('user_id', '!=', $user->id)
            ->get();

        return view('chat.show', compact('conversation', 'otherParticipants'));
    }

    public function startTyping(Request $request)
    {
        $conversationId = $request->conversation_id;
        $user = Auth::user();

        broadcast(new \App\Events\UserTyping($conversationId, $user, true));

        return response()->json(['status' => 'typing_started']);
    }

    public function stopTyping(Request $request)
    {
        $conversationId = $request->conversation_id;
        $user = Auth::user();

        broadcast(new \App\Events\UserTyping($conversationId, $user, false));

        return response()->json(['status' => 'typing_stopped']);
    }

    public function onlineUsers()
    {
        $user = Auth::user();

        $onlineUsers = $user->friends()
            ->where('users.last_seen_at', '>', now()->subMinutes(5))
            ->get();

        return response()->json($onlineUsers);
    }

    public function getData(Conversation $conversation)
    {   
        $user = Auth::user();

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Load conversation with messages and participants
        $conversation->load([
            'messages.user',
            'messages.readReceipts',
            'messages.reactions.user',
            'participants'
        ]);

        // Mark messages as read
        $conversation->messages()
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('readReceipts', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->each(function ($message) use ($user) {
                $message->readReceipts()->firstOrCreate([
                    'user_id' => $user->id,
                ], [
                    'read_at' => now(),
                ]);
            });

        // Get other participants for header info
        $otherParticipants = $conversation->participants->where('user_id', $user->id);

        // Prepare conversation data
        $title = '';
        $status = '';
        $avatar = '';

        if ($conversation->type === 'private') {
            $otherUser = $otherParticipants->first();
            if ($otherUser) {
                $title = $otherUser->first_name . ' ' . $otherUser->last_name;
                $isOnline = $otherUser->last_seen_at && $otherUser->last_seen_at > now()->subMinutes(5);
                $status = $isOnline ? 'Online' : 'Last seen ' . ($otherUser->last_seen_at ? $otherUser->last_seen_at->diffForHumans() : 'recently');
                $initials = strtoupper(substr($otherUser->first_name ?? 'U', 0, 1)) . strtoupper(substr($otherUser->last_name ?? 'U', 0, 1));
                $avatar = '<div class="chat-main-avatar">' . $initials . '</div>';
            } else {
                $title = 'Unknown User';
                $status = 'Offline';
                $avatar = '<div class="chat-main-avatar">U</div>';
            }
        } else {
            $title = $conversation->title ?? 'Group Chat';
            $status = ($otherParticipants->count() + 1) . ' members';
            $avatar = '<div class="chat-main-avatar"><i class="fas fa-users"></i></div>';
        }

        // Prepare messages HTML with improved attachment handling
        $messagesHtml = '';
        foreach ($conversation->messages as $message) {
            $isOwn = $message->user_id === $user->id;
            $messagesHtml .= '<div class="message-group mb-3">';
            $messagesHtml .= '<div class="message ' . ($isOwn ? 'sent' : '') . '">';

            if (!$isOwn && $message->user) {
                $messagesHtml .= '<div class="message-avatar">' . strtoupper(substr($message->user->first_name, 0, 1)) . '</div>';
            }

            $messageContent = $this->renderMessageContent($message);
            $messagesHtml .= '<div class="message-content">' . $messageContent . '</div>';

            if ($isOwn && $message->user) {
                $messagesHtml .= '<div class="message-avatar">' . strtoupper(substr($message->user->first_name, 0, 1)) . '</div>';
            }

            $messagesHtml .= '</div>';

            // Add message reactions if any
            if ($message->reactions->count() > 0) {
                $messagesHtml .= '<div class="message-reactions">';
                $reactions = $message->reactions->groupBy('emoji');
                foreach ($reactions as $emoji => $reactionGroup) {
                    $count = $reactionGroup->count();
                    $hasUserReaction = $reactionGroup->contains('user_id', $user->id);
                    $messagesHtml .= '<span class="reaction-badge ' . ($hasUserReaction ? 'user-reacted' : '') . '" data-message-id="' . $message->id . '" data-emoji="' . $emoji . '">' . $emoji . ' ' . $count . '</span>';
                }
                $messagesHtml .= '</div>';
            }

            $messagesHtml .= '<div class="message-time">' . $message->created_at->format('H:i');
            if ($isOwn) {
                $messagesHtml .= ' <button class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="deleteMessage(' . $message->id . ')"><i class="fas fa-trash"></i></button>';
            }
            $messagesHtml .= '</div>';
            $messagesHtml .= '</div>';
        }

        return response()->json([
            'title' => $title,
            'status' => $status,
            'avatar' => $avatar,
            'messages' => $messagesHtml
        ]);
    }

    private function renderMessageContent($message)
    {
        $content = '';

        switch ($message->type) {
            case 'text':
                $content = nl2br(htmlspecialchars($message->content));
                break;

            case 'image':
                if ($message->attachments && count($message->attachments) > 0) {
                    foreach ($message->attachments as $attachment) {
                        $imagePath = Storage::url($attachment['path']);
                        $thumbnailPath = isset($attachment['metadata']['thumbnail_path'])
                            ? Storage::url($attachment['metadata']['thumbnail_path'])
                            : $imagePath;

                        $content .= '<div class="message-image-container mb-2">';
                        $content .= '<a href="' . $imagePath . '" data-lightbox="chat-images" data-title="' . htmlspecialchars($attachment['name']) . '">';
                        $content .= '<img src="' . $thumbnailPath . '" alt="' . htmlspecialchars($attachment['name']) . '" class="message-image" style="max-width: 300px; border-radius: 8px;">';
                        $content .= '</a>';
                        $content .= '</div>';
                    }
                    if ($message->content) {
                        $content .= '<div class="message-caption">' . nl2br(htmlspecialchars($message->content)) . '</div>';
                    }
                }
                break;

            case 'video':
                if ($message->attachments && count($message->attachments) > 0) {
                    foreach ($message->attachments as $attachment) {
                        $videoPath = Storage::url($attachment['path']);
                        $content .= '<div class="message-video-container mb-2">';
                        $content .= '<video controls style="max-width: 300px; border-radius: 8px;">';
                        $content .= '<source src="' . $videoPath . '" type="' . $attachment['type'] . '">';
                        $content .= 'Your browser does not support the video tag.';
                        $content .= '</video>';
                        $content .= '</div>';
                    }
                    if ($message->content) {
                        $content .= '<div class="message-caption">' . nl2br(htmlspecialchars($message->content)) . '</div>';
                    }
                }
                break;

            case 'audio':
                if ($message->attachments && count($message->attachments) > 0) {
                    foreach ($message->attachments as $attachment) {
                        $audioPath = Storage::url($attachment['path']);
                        $content .= '<div class="message-audio-container mb-2">';
                        $content .= '<audio controls>';
                        $content .= '<source src="' . $audioPath . '" type="' . $attachment['type'] . '">';
                        $content .= 'Your browser does not support the audio element.';
                        $content .= '</audio>';
                        $content .= '</div>';
                    }
                    if ($message->content) {
                        $content .= '<div class="message-caption">' . nl2br(htmlspecialchars($message->content)) . '</div>';
                    }
                }
                break;

            case 'file':
                if ($message->attachments && count($message->attachments) > 0) {
                    foreach ($message->attachments as $attachment) {
                        $filePath = Storage::url($attachment['path']);
                        $fileSize = isset($attachment['size']) ? round($attachment['size'] / 1024, 2) . ' KB' : 'Unknown size';
                        $content .= '<div class="message-file-container mb-2">';
                        $content .= '<a href="' . $filePath . '" target="_blank" class="message-file-link">';
                        $content .= '<div class="file-preview">';
                        $content .= '<i class="fas fa-file-alt fa-2x text-primary"></i>';
                        $content .= '<div class="file-info">';
                        $content .= '<div class="file-name">' . htmlspecialchars($attachment['name']) . '</div>';
                        $content .= '<div class="file-size text-muted">' . $fileSize . '</div>';
                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '</a>';
                        $content .= '</div>';
                    }
                    if ($message->content) {
                        $content .= '<div class="message-caption">' . nl2br(htmlspecialchars($message->content)) . '</div>';
                    }
                }
                break;

            default:
                $content = nl2br(htmlspecialchars($message->content));
                break;
        }

        return $content;
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        // Create message
        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'content' => $request->content,
            'type' => 'text'
        ]);

        // Update conversation timestamp
        $conversation->touch();

        // Prepare message HTML
        $messageHtml = '<div class="message-group mb-3">';
        $messageHtml .= '<div class="message sent">';
        $messageHtml .= '<div class="message-content">' . htmlspecialchars($message->content) . '</div>';
        $messageHtml .= '<div class="message-avatar">' . strtoupper(substr($user->first_name, 0, 1)) . '</div>';
        $messageHtml .= '</div>';
        $messageHtml .= '<div class="message-time">' . $message->created_at->format('H:i') . '</div>';
        $messageHtml .= '<div class="message-group mb-3">';
        $messageHtml .= '<div class="message sent">';
        $messageHtml .= '<div class="message-content">' . htmlspecialchars($message->content) . '</div>';
        $messageHtml .= '<div class="message-avatar">' . strtoupper(substr($user->first_name, 0, 1)) . '</div>';
        $messageHtml .= '</div>';
        $messageHtml .= '<div class="message-time">' . $message->created_at->format('H:i') . '</div>';
        $messageHtml .= '</div>';

        // Broadcast the message to other participants
        broadcast(new \App\Events\MessageSent($message->load('user')))->toOthers();

        return response()->json([
            'success' => true,
            'messageHtml' => $messageHtml,
            'message' => $message
        ]);
    }
}
