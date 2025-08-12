<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Protected API Routes for contact conversations
Route::middleware(['auth:sanctum'])->group(function () {
    // User presence
    Route::post('/user/presence', [UserController::class, 'updatePresence']);

    // Contact search for conversations
    Route::get('/users/search', [UserController::class, 'search']);
});

// Websocket authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/broadcasting/auth', function (Request $request) {
        return response()->json(['auth' => $request->user()->createToken('websocket')->plainTextToken]);
    });
});
