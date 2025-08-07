<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FriendRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\ContactController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Chat Interface
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::get('/chat/{conversation}/data', [ChatController::class, 'getData'])->name('chat.data');
    Route::post('/chat/{conversation}/message', [ChatController::class, 'sendMessage'])->name('chat.message');

    // Conversations
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
    Route::post('/conversations/{conversation}/archive', [ConversationController::class, 'archive'])->name('conversations.archive');
    Route::post('/conversations/{conversation}/unarchive', [ConversationController::class, 'unarchive'])->name('conversations.unarchive');

    // Messages
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::post('/messages/{message}/react', [MessageController::class, 'react'])->name('messages.react');
    Route::post('/messages/{message}/read', [MessageController::class, 'markAsRead'])->name('messages.read');

    // File Uploads
    Route::post('/upload/image', [MessageController::class, 'uploadImage'])->name('upload.image');
    Route::post('/upload/file', [MessageController::class, 'uploadFile'])->name('upload.file');
    Route::post('/upload/multiple', [MessageController::class, 'uploadMultipleFiles'])->name('upload.multiple');

    // Users & Friends
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::post('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    // Profile & Settings
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications');
    Route::post('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');
    Route::post('/profile/delete', [ProfileController::class, 'deleteAccount'])->name('profile.delete');

    // Legacy settings routes (redirect to profile)
    Route::get('/settings', function() { return redirect()->route('profile'); })->name('settings');
    Route::post('/settings/password', [ProfileController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/notifications', [ProfileController::class, 'updateNotifications'])->name('settings.notifications');

    // Friend Requests
    Route::get('/friends', [FriendRequestController::class, 'index'])->name('friends.index');
    Route::post('/friends/request', [FriendRequestController::class, 'sendRequest'])->name('friends.request');
    Route::post('/friends/accept/{friendRequest}', [FriendRequestController::class, 'accept'])->name('friends.accept');
    Route::post('/friends/decline/{friendRequest}', [FriendRequestController::class, 'decline'])->name('friends.decline');
    Route::delete('/friends/{friendship}', [FriendRequestController::class, 'removeFriend'])->name('friends.remove');

    // Admin Routes (only for admin users)
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/email-config', [\App\Http\Controllers\Admin\DashboardController::class, 'updateEmailConfig'])->name('dashboard.email-config');
        Route::post('/dashboard/sms-config', [\App\Http\Controllers\Admin\DashboardController::class, 'updateSmsConfig'])->name('dashboard.sms-config');
        Route::post('/dashboard/theme-config', [\App\Http\Controllers\Admin\DashboardController::class, 'updateSystemTheme'])->name('dashboard.theme-config');
        Route::post('/dashboard/test-email', [\App\Http\Controllers\Admin\DashboardController::class, 'testEmailConfig'])->name('dashboard.test-email');
        Route::post('/dashboard/test-sms', [\App\Http\Controllers\Admin\DashboardController::class, 'testSmsConfig'])->name('dashboard.test-sms');

        // Contacts
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
        Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::post('/contacts/{contact}/toggle-status', [ContactController::class, 'toggleStatus'])->name('contacts.toggle-status');
        Route::post('/contacts/update-order', [ContactController::class, 'updateOrder'])->name('contacts.update-order');
    });
});

// API Fallback Routes (for mobile/API access)
Route::get('/api/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});
