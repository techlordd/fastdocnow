<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    return $conversation && $conversation->participants->contains($user);
});

Broadcast::channel('user.online', function ($user) {
    return $user ? ['id' => $user->id, 'name' => $user->name] : null;
});
