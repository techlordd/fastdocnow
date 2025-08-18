<?php

use Illuminate\Support\Facades\Broadcast;

// Private channel for conversation messages
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    return $conversation && $conversation->participants->contains($user);
});

// Presence channel for online status (optional)
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    if ($conversation && $conversation->participants->contains($user)) {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar' => $user->avatar
        ];
    }
    return false;
});
