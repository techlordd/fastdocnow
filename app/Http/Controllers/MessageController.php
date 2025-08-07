<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required_if:type,text|string',
            'type' => 'required|in:text,image,file,audio',
            'file_path' => 'nullable|string',
            'file_name' => 'nullable|string',
            'file_size' => 'nullable|integer',
        ]);

        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $request->content,
            'type' => $request->type,
            'file_path' => $request->file_path,
            'file_name' => $request->file_name,
            'file_size' => $request->file_size,
        ]);

        // Update conversation timestamp
        $conversation->touch();

        // Load user relationship
        $message->load('user');

        // Broadcast message to conversation participants
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        // Check if it's an AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        // For regular web requests, redirect back
        return redirect()->back()->with('success', 'Message sent!');
    }

    public function destroy(Message $message)
    {
        $user = Auth::user();

        // Check if user owns the message or is admin
        if ($message->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete file if exists
        if ($message->file_path) {
            Storage::delete($message->file_path);
        }

        $message->delete();

        return response()->json(['message' => 'Message deleted successfully']);
    }

    public function react(Request $request, Message $message)
    {
        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $user = Auth::user();

        // Check if user is participant in conversation
        if (!$message->conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Toggle reaction
        $existingReaction = $message->reactions()
                                   ->where('user_id', $user->id)
                                   ->where('emoji', $request->emoji)
                                   ->first();

        if ($existingReaction) {
            $existingReaction->delete();
            $action = 'removed';
        } else {
            $message->reactions()->create([
                'user_id' => $user->id,
                'emoji' => $request->emoji,
            ]);
            $action = 'added';
        }

        return response()->json(['message' => "Reaction {$action} successfully"]);
    }

    public function markAsRead(Message $message)
    {
        $user = Auth::user();

        // Check if user is participant in conversation
        if (!$message->conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Don't create read receipt for own messages
        if ($message->user_id === $user->id) {
            return response()->json(['message' => 'Cannot mark own message as read']);
        }

        // Create or update read receipt
        $message->readReceipts()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );

        return response()->json(['message' => 'Message marked as read']);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $image = $request->file('image');
        $filename = time() . '_' . $image->getClientOriginalName();

        // Store original image
        $path = $image->storeAs('messages/images', $filename, 'public');

        // Create thumbnail
        $thumbnailPath = 'messages/thumbnails/' . $filename;
        $thumbnail = Image::make($image)->fit(300, 300);
        Storage::disk('public')->put($thumbnailPath, $thumbnail->stream());

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $request->caption,
            'type' => 'image',
            'file_path' => $path,
            'file_name' => $image->getClientOriginalName(),
            'file_size' => $image->getSize(),
            'metadata' => json_encode([
                'thumbnail_path' => $thumbnailPath,
                'width' => $thumbnail->width(),
                'height' => $thumbnail->height(),
            ]),
        ]);

        // Update conversation timestamp
        $conversation->touch();

        // Load user relationship
        $message->load('user');

        // Broadcast message
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
            'url' => Storage::url($path)
        ]);
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:50240', // 50MB max
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();

        // Determine file type
        $mimeType = $file->getClientMimeType();
        $fileType = 'file';
        if (strpos($mimeType, 'image/') === 0) {
            $fileType = 'image';
        } elseif (strpos($mimeType, 'video/') === 0) {
            $fileType = 'video';
        } elseif (strpos($mimeType, 'audio/') === 0) {
            $fileType = 'audio';
        }

        // Store file
        $path = $file->storeAs("messages/{$fileType}s", $filename, 'public');

        $metadata = [
            'mime_type' => $mimeType,
            'original_name' => $file->getClientOriginalName(),
        ];

        // Handle image thumbnails
        if ($fileType === 'image') {
            try {
                $thumbnailPath = "messages/thumbnails/{$filename}";
                $thumbnail = \Intervention\Image\Facades\Image::make($file)->fit(300, 300);
                Storage::disk('public')->put($thumbnailPath, $thumbnail->stream());
                $metadata['thumbnail_path'] = $thumbnailPath;
                $metadata['width'] = $thumbnail->width();
                $metadata['height'] = $thumbnail->height();
            } catch (\Exception $e) {
                // If image processing fails, continue without thumbnail
            }
        }

        // Handle video thumbnails (basic implementation)
        if ($fileType === 'video') {
            $metadata['duration'] = null; // Could be extracted with FFmpeg
        }

        // Create attachments array for new structure
        $attachments = [[
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'type' => $mimeType,
            'metadata' => $metadata
        ]];

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $request->description ?? '',
            'type' => $fileType,
            'attachments' => $attachments,
            'metadata' => $metadata,
        ]);

        // Update conversation timestamp
        $conversation->touch();

        // Load user relationship
        $message->load('user');

        // Broadcast message
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
            'url' => Storage::url($path),
            'type' => $fileType
        ]);
    }

    public function uploadMultipleFiles(Request $request)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'file|max:50240',
            'conversation_id' => 'required|exists:conversations,id',
            'message_text' => 'nullable|string|max:1000'
        ]);

        $user = Auth::user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Check if user is participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $attachments = [];
        $primaryType = 'file';

        foreach ($request->file('files') as $file) {
            $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();

            $fileType = 'file';
            if (strpos($mimeType, 'image/') === 0) {
                $fileType = 'image';
                if ($primaryType === 'file') $primaryType = 'image';
            } elseif (strpos($mimeType, 'video/') === 0) {
                $fileType = 'video';
                if ($primaryType === 'file') $primaryType = 'video';
            } elseif (strpos($mimeType, 'audio/') === 0) {
                $fileType = 'audio';
                if ($primaryType === 'file') $primaryType = 'audio';
            }

            $path = $file->storeAs("messages/{$fileType}s", $filename, 'public');

            $metadata = [
                'mime_type' => $mimeType,
                'original_name' => $file->getClientOriginalName(),
            ];

            if ($fileType === 'image') {
                try {
                    $thumbnailPath = "messages/thumbnails/{$filename}";
                    $thumbnail = \Intervention\Image\Facades\Image::make($file)->fit(300, 300);
                    Storage::disk('public')->put($thumbnailPath, $thumbnail->stream());
                    $metadata['thumbnail_path'] = $thumbnailPath;
                } catch (\Exception $e) {
                    // Continue without thumbnail
                }
            }

            $attachments[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'type' => $mimeType,
                'metadata' => $metadata
            ];
        }

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $request->message_text ?? '',
            'type' => count($attachments) > 1 ? 'file' : $primaryType,
            'attachments' => $attachments,
        ]);

        // Update conversation timestamp
        $conversation->touch();

        // Load user relationship
        $message->load('user');

        // Broadcast message
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
            'attachments' => $attachments
        ]);
    }
