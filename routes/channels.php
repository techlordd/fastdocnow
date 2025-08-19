<?php

use Illuminate\Support\Facades\Broadcast;

// Private channel for conversation messages
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    return $conversation && $conversation->participants->contains($user);
});

// Presence channel for conversation participants (online status)
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

// Global presence channel for overall online status
Broadcast::channel('global-presence', function ($user) {
    return [
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'avatar' => $user->avatar,
        'is_online' => true,
        'last_seen_at' => $user->last_seen_at
    ];
});

// Private channel for individual user notifications
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
