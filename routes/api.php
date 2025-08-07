<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FriendRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public API Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Protected API Routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Conversations
    Route::apiResource('conversations', ConversationController::class);
    Route::post('/conversations/{conversation}/archive', [ConversationController::class, 'archive']);
    Route::post('/conversations/{conversation}/unarchive', [ConversationController::class, 'unarchive']);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);

    // Messages
    Route::apiResource('messages', MessageController::class)->except(['index']);
    Route::post('/messages/{message}/react', [MessageController::class, 'react']);
    Route::post('/messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::get('/messages/unread', [MessageController::class, 'unread']);

    // File Uploads
    Route::post('/upload/image', [MessageController::class, 'uploadImage']);
    Route::post('/upload/file', [MessageController::class, 'uploadFile']);

    Route::post('/upload/audio', [MessageController::class, 'uploadAudio']);

    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users/profile', [UserController::class, 'updateProfile']);
    Route::post('/users/avatar', [UserController::class, 'updateAvatar']);
    Route::post('/users/status', [UserController::class, 'updateStatus']);
    Route::post('/user/presence', [UserController::class, 'updatePresence']);

    // Friends
    Route::get('/friends', [FriendRequestController::class, 'friends']);
    Route::get('/friends/requests', [FriendRequestController::class, 'requests']);
    Route::post('/friends/request', [FriendRequestController::class, 'sendRequest']);
    Route::post('/friends/accept/{friendRequest}', [FriendRequestController::class, 'accept']);
    Route::post('/friends/decline/{friendRequest}', [FriendRequestController::class, 'decline']);
    Route::delete('/friends/{friendship}', [FriendRequestController::class, 'removeFriend']);

    // Notifications
    Route::get('/notifications', [UserController::class, 'notifications']);
    Route::post('/notifications/{notification}/read', [UserController::class, 'markNotificationAsRead']);
    Route::post('/notifications/read-all', [UserController::class, 'markAllNotificationsAsRead']);

    // Real-time endpoints
    Route::post('/typing/start', [ChatController::class, 'startTyping']);
    Route::post('/typing/stop', [ChatController::class, 'stopTyping']);
    Route::get('/online-users', [ChatController::class, 'onlineUsers']);
});

// Websocket authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/broadcasting/auth', function (Request $request) {
        return response()->json(['auth' => $request->user()->createToken('websocket')->plainTextToken]);
    });
});
