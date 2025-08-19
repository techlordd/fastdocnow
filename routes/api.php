<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Protected API Routes - using web middleware to maintain session
Route::middleware(['web', 'auth'])->group(function () {
    // User presence endpoint temporarily disabled to prevent page refresh issues
    // Presence is now handled entirely through Pusher events and PusherService
    // Route::post('/user/presence', [UserController::class, 'updatePresence'])->name('api.user.presence');

    // Contact search for conversations
    Route::get('/users/search', [UserController::class, 'search'])
         ->name('api.users.search');
});

// Websocket authentication
// Broadcasting auth is now handled by BroadcastServiceProvider with web middleware
