<div class="chat-main" id="chatMain" wire:id="chat-interface">
    <!-- Flash Messages -->
    @if (session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(!$conversation)
    <!-- Welcome Screen -->
    <div class="welcome-screen">
        <div class="welcome-content">
            <div class="welcome-icon">
                <i class="fas fa-comments"></i>
            </div>
            <h2>Welcome to DocNow Chat</h2>
            <p>
                Select a conversation from the sidebar to start chatting, or create a new conversation to get started.
            </p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newConversationModal">
                <i class="fas fa-plus me-2"></i> Start New Conversation
            </button>
        </div>
    </div>
    @else
    <!-- Chat Header -->
    <div class="chat-main-header">

        <div class="chat-main-avatar">
            @php
            // For contact conversations, show contact or other participant info
            $contactName = $conversation['contact']['first_name'] ?? '';
            $contactLastName = $conversation['contact']['last_name'] ?? '';
            $contactAvatar = $conversation['contact']['avatar'] ?? null;

            if (!$contactName) {
                $otherUser = collect($conversation['participants'])
                ->first(fn($user) => $user['id'] !== auth()->id());
                $contactName = $otherUser['first_name'] ?? 'U';
                $contactLastName = $otherUser['last_name'] ?? 'U';
                $contactAvatar = $otherUser['avatar'] ?? null;
            }
            @endphp
            @if($contactAvatar)
            <img src="{{ asset('storage/' . $contactAvatar) }}" alt="{{ $contactName }}">
            @else
            {{ strtoupper(substr($contactName, 0, 1)) }}{{ strtoupper(substr($contactLastName, 0, 1)) }}
            @endif
        </div>
        <div class="chat-main-info">
            <h4>
                {{ $contactName }} {{ $contactLastName }}
            </h4>
            <p class="status">
                <span class="text-muted">Contact Conversation</span>
            </p>
            
            
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">

            <!-- Options Menu -->
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="chatOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="chatOptionsDropdown">
                    <li>
                        <h6 class="dropdown-header">Conversation Options</h6>
                    </li>

                    <li>
                        <button class="dropdown-item text-danger" type="button"
                            onclick="confirmDelete('Delete Conversation?', 'This conversation and all its messages will be permanently deleted.').then((result) => { if (result.isConfirmed) { @this.call('deleteConversation') } })">
                            <i class="fas fa-trash me-2"></i> Delete Conversation
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Messages Area -->
    <div class="chat-messages custom-scrollbar position-relative" wire:poll.3s="refreshMessages">
        @forelse($messages as $message)
        <div class="message-group mb-3" wire:key="message-{{ $message['id'] }}">
            <div class="message {{ $message['user_id'] === auth()->id() ? 'sent' : '' }}">
                @if ($message['user_id'] != auth()->id() && isset($message['user']))
                <div class="message-avatar">
                    @if (!empty($message['user']['avatar']))
                    <img src="{{ $message['user']['avatar'] }}" alt="{{ $message['user']['first_name'] }}">
                    @else
                    <div class="avatar-placeholder">
                        {{ strtoupper(substr($message['user']['first_name'], 0, 1)) }}
                    </div>
                    @endif
                </div>
                @endif

                <div class="message-content">
                    @if($message['attachments'])
                    @foreach($message['attachments'] as $attachment)
                    @php
                    $fileName = $attachment['name'] ?? 'unknown';
                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
                    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp'];
                    $audioExtensions = ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma'];
                    $isImage = in_array($extension, $imageExtensions);
                    $isVideo = in_array($extension, $videoExtensions);
                    $isAudio = in_array($extension, $audioExtensions);
                    @endphp

                    @if($isImage)
                    <div class="message-image-container mb-2">
                        @php
                        $imagePath = '/storage/' . $attachment['path'];
                        @endphp
                        <a href="{{ $imagePath }}" data-fancybox="chat-images" data-caption="{{ $attachment['name'] }}" class="attacthment_mesg">
                            <img src="{{ $imagePath }}" alt="{{ $attachment['name'] }}" class="message-image" loading="lazy" style="max-width: 360px; border-radius: 12px; cursor: pointer;">
                        </a>
                    </div>
                    @elseif($isVideo)
                    <div class="message-video-container mb-2">
                        @php
                        $videoMimeTypes = [
                        'mp4' => 'video/mp4',
                        'avi' => 'video/x-msvideo',
                        'mov' => 'video/quicktime',
                        'wmv' => 'video/x-ms-wmv',
                        'flv' => 'video/x-flv',
                        'webm' => 'video/webm',
                        'mkv' => 'video/x-matroska',
                        '3gp' => 'video/3gpp'
                        ];
                        $mimeType = $videoMimeTypes[$extension] ?? 'video/mp4';
                        @endphp
                        <video controls class="message-video" preload="metadata" style="max-width: 360px; border-radius: 12px;">
                            <source src="/storage/{{ $attachment['path'] }}" type="{{ $mimeType }}">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                    @elseif($isAudio)
                    <div class="message-audio-container mb-2">
                        @php
                        $audioMimeTypes = [
                        'mp3' => 'audio/mpeg',
                        'wav' => 'audio/wav',
                        'ogg' => 'audio/ogg',
                        'aac' => 'audio/aac',
                        'flac' => 'audio/flac',
                        'm4a' => 'audio/mp4',
                        'wma' => 'audio/x-ms-wma'
                        ];
                        $mimeType = $audioMimeTypes[$extension] ?? 'audio/mpeg';
                        @endphp
                        <audio controls preload="metadata">
                            <source src="/storage/{{ $attachment['path'] }}" type="{{ $mimeType }}">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                    @else
                    <div class="message-file-container mb-2">
                        <a href="/storage/{{ $attachment['path'] }}" target="_blank" class="message-file-link" rel="noopener noreferrer">
                            <div class="file-preview" style="max-width: 360px; border-radius: 12px; padding: 8px; background-color: #f0f0f0;">
                                <i class="fas fa-file-alt fa-2x text-primary"></i>
                                <div class="file-info">
                                    <div class="file-name text-dark">{{ $attachment['name'] }}</div>
                                    <div class="file-size text-muted">{{ round($attachment['size'] / 1024, 2) }} KB</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endif
                    @endforeach
                    @endif

                    @if($message['content'] || empty($message['attachments']))
                    {!! nl2br(e($message['content'])) !!}
                    @endif
                </div>
            </div>



            <div class="message-time d-flex align-items-center {{ $message['user_id'] === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                {{ \Carbon\Carbon::parse($message['created_at'])->format('H:i') }}
                @if($message['user_id'] === auth()->id())
                <button class="btn btn-sm btn-link text-danger p-0 ms-2"
                    onclick="confirmDelete('Delete Message?', 'This message will be permanently deleted.').then((result) => { if (result.isConfirmed) { @this.call('deleteMessage', {{ $message['id'] }}) } })">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center p-4 text-muted">
            <i class="fas fa-comments fa-3x mb-3"></i>
            <p>No messages yet. Start the conversation!</p>
        </div>
        @endforelse

        <!-- Scroll to Bottom Button -->
        <button id="scrollToBottomBtn"
            class="btn btn-primary rounded-circle position-absolute"
            style="bottom: 20px; right: 20px; width: 50px; height: 50px; display: none; z-index: 10;"
            title="Scroll to bottom">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>

    <!-- Typing Indicator -->
    @if(!empty($typingUsers))
    <div class="typing-indicator">
        <div class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <span class="typing-text">{{ $this->typingUsersText }}</span>
    </div>
    @endif

    <!-- Attachment Preview Area -->
    @if($showAttachmentPreview)
    <div class="attachment-preview-area" wire:ignore>
        <div class="attachment-preview-header">
            <h6>Send Files</h6>
            <button class="btn btn-sm btn-link" wire:click="$set('showAttachmentPreview', false)">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="attachment-preview-container">
            @foreach($selectedFiles as $index => $file)
            <div class="attachment-preview-item" wire:key="file-{{ $index }}">
                @php
                $isUploadedFile = is_object($file) && method_exists($file, 'getClientOriginalName');
                $fileName = $isUploadedFile ? $file->getClientOriginalName() : (is_string($file) ? basename($file) : 'unknown');
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
                $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp'];
                $isImage = in_array($extension, $imageExtensions);
                $isVideo = in_array($extension, $videoExtensions);
                @endphp
                @if ($isUploadedFile)
                @if ($isImage)
                <img src="{{ $file->temporaryUrl() }}" class="preview-image" alt="{{ $fileName }}">
                @elseif ($isVideo)
                <video class="preview-video" controls preload="metadata">
                    <source src="{{ $file->temporaryUrl() }}">
                </video>
                @else
                <div class="preview-file">
                    <i class="fas fa-file-alt fa-3x text-primary"></i>
                    <div class="file-name">{{ $fileName }}</div>
                </div>
                @endif
                <button class="remove-attachment-btn" wire:click="removeFile({{ $index }})" type="button">
                    <i class="fas fa-times"></i>
                </button>
                @endif
            </div>
            @endforeach
        </div>
        <div class="attachment-preview-input">
            <textarea wire:model="attachmentCaption" class="form-control" placeholder="Add a caption..." rows="2"></textarea>
            <button wire:click="sendWithAttachments" class="btn btn-primary mt-2" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="sendWithAttachments">
                    <i class="fas fa-paper-plane me-1"></i> Send
                </span>
                <span wire:loading wire:target="sendWithAttachments">
                    <i class="spinner-border spinner-border-sm me-1"></i> Sending...
                </span>
            </button>
        </div>
    </div>
    @endif

    <!-- Chat Input -->
    <div class="chat-input-container" wire:ignore>
        <form wire:submit="sendMessage" class="chat-input-form">
            <!-- Hidden file inputs -->
            <input type="file" id="fileInput" style="display: none;" multiple
                wire:model="selectedFiles" accept="*/*">
            <input type="file" id="imageInput" style="display: none;" multiple
                wire:model="selectedFiles" accept="image/*">
            <input type="file" id="videoInput" style="display: none;" multiple
                wire:model="selectedFiles" accept="video/*">

            <!-- Attachment Menu -->
            <div class="attachment-menu" id="attachmentMenu" style="display: none;">
                <button type="button" class="attachment-option" onclick="document.getElementById('imageInput').click()">
                    <i class="fas fa-image"></i>
                    <span>Photos</span>
                </button>
                <button type="button" class="attachment-option" onclick="document.getElementById('videoInput').click()">
                    <i class="fas fa-video"></i>
                    <span>Videos</span>
                </button>
                <button type="button" class="attachment-option" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-file"></i>
                    <span>Files</span>
                </button>
            </div>

            <button type="button" class="chat-input-btn" onclick="toggleAttachmentMenu()">
                <i class="fas fa-paperclip"></i>
            </button>

            <button type="button" class="chat-input-btn emoji_picker_btn" onclick="toggleEmojiPicker()">
                <i class="fas fa-smile"></i>
            </button>

            <div class="emoji-picker-container" id="emojiPickerContainer" style="display: none;">
                <emoji-picker class="emoji-picker"></emoji-picker>
            </div>

            <textarea class="chat-input"
                placeholder="Type a message..."
                rows="1"
                wire:model.live="messageText"
                wire:keydown.enter="sendMessage"
                wire:keydown.shift.enter.prevent=""
                wire:input="startTyping"
                wire:blur="stopTyping"
                id="chat-message-input"></textarea>

            <button type="submit" class="send-btn" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="sendMessage">
                    <i class="fas fa-paper-plane"></i>
                </span>
                <span wire:loading wire:target="sendMessage">
                    <i class="spinner-border spinner-border-sm"></i>
                </span>
            </button>
        </form>
    </div>
    @endif
</div>

@push('styles')
<style>
    .online-status {
        display: flex;
        align-items: center;
        font-size: 12px;
    }

    .online-status.online i {
        color: #22c55e;
        animation: pulse 2s infinite;
    }

    .online-status.offline i {
        color: #6b7280;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .dropdown-menu {
        min-width: 220px;
    }

    .dropdown-item i {
        width: 16px;
        text-align: center;
    }

    /* Notification styles */
    .notification-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 16px;
        max-width: 350px;
        z-index: 1050;
        border-left: 4px solid #3b82f6;
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .notification-toast .notification-header {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }

    .notification-toast .notification-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .notification-toast .notification-body {
        color: #6b7280;
        font-size: 14px;
    }

    .notification-toast .notification-close {
        position: absolute;
        top: 8px;
        right: 8px;
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
    }



    /* Attachment Menu Styles */
    .attachment-menu {
        position: absolute;
        bottom: 60px;
        left: 10px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 8px;
        display: none;
        z-index: 1000;
        min-width: 120px;
    }

    .attachment-menu.show {
        display: block;
    }

    .attachment-option {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        border: none;
        background: none;
        cursor: pointer;
        border-radius: 4px;
        transition: background-color 0.2s;
        width: 100%;
        text-align: left;
    }

    .attachment-option:hover {
        background-color: #f3f4f6;
    }

    .attachment-option i {
        margin-right: 8px;
        width: 16px;
    }

    /* Chat Input Styles */
    .chat-input-container {
        position: relative;
    }

    .chat-input-btn {
        background: none;
        border: none;
        padding: 8px;
        color: #6b7280;
        cursor: pointer;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .chat-input-btn:hover {
        background-color: #f3f4f6;
        color: #374151;
    }

    /* Attachment Preview Styles */
    .attachment-preview-area {
        border-top: 1px solid #e5e7eb;
        background: #f9fafb;
        padding: 15px;
        margin: 0 15px 15px 15px;
        border-radius: 8px;
    }

    .attachment-preview-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .attachment-preview-container {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        overflow-x: auto;
        padding-bottom: 5px;
    }

    .attachment-preview-item {
        position: relative;
        min-width: 100px;
    }

    .preview-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
    }

    .preview-video {
        width: 100px;
        height: 100px;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
    }

    .preview-file {
        width: 100px;
        height: 100px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        text-align: center;
        padding: 10px;
    }

    .preview-file .file-name {
        font-size: 10px;
        margin-top: 5px;
        color: #6b7280;
        word-break: break-all;
    }

    .remove-attachment-btn {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
    }

    .remove-attachment-btn:hover {
        background: #dc2626;
    }

    /* Fix for Bootstrap dropdown not working */
    .dropdown-menu {
        z-index: 1050;
    }

    /* Toast notification styles */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    }

    .message-toast {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        margin-bottom: 12px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .toast-header {
        display: flex;
        align-items: center;
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        position: relative;
    }

    .toast-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6600ff 0%, #4400cc 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        margin-right: 12px;
        font-size: 14px;
        flex-shrink: 0;
    }

    .toast-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .toast-content {
        flex: 1;
        min-width: 0;
    }

    .toast-title {
        font-weight: 600;
        color: #111827;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .toast-message {
        color: #6b7280;
        font-size: 13px;
        line-height: 1.4;
        word-break: break-word;
    }

    .toast-close {
        position: absolute;
        top: 8px;
        right: 8px;
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        font-size: 12px;
        padding: 4px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .toast-close:hover {
        background-color: #f3f4f6;
        color: #6b7280;
    }

    .toast-actions {
        padding: 12px 16px;
        background: #f9fafb;
        border-top: 1px solid #f3f4f6;
    }

    .toast-action-btn {
        background: #6600ff;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
        width: 100%;
    }

    .toast-action-btn:hover {
        background: #5500dd;
    }

    /* Sidebar toast specific styles */
    .sidebar-toast {
        border-left: 4px solid #22c55e;
    }

    .sidebar-toast .toast-subtitle {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 2px;
    }

    .sidebar-toast .toast-title {
        font-weight: 600;
        color: #059669;
        font-size: 14px;
        margin-bottom: 2px;
    }

    @media (max-width: 480px) {
        .toast-container {
            right: 12px;
            left: 12px;
            max-width: none;
        }

        .message-toast {
            margin-bottom: 8px;
        }
    }

    /* Message display improvements */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        scroll-behavior: smooth;
    }

    .message-group {
        margin-bottom: 20px;
    }

    .message {
        max-width: 70%;
        margin-bottom: 10px;
    }

    .message.sent {
        margin-left: auto;
        text-align: right;
    }

    .message-content {
        background: #f1f5f9;
        padding: 10px 15px;
        border-radius: 18px;
        word-wrap: break-word;
    }

    .message.sent .message-content {
        background: #3b82f6;
        color: white;
    }

    @media (max-width: 768px) {
        .chat-main-header {
            padding: 10px;
        }

        .chat-main-info h4 {
            font-size: 1.1rem;
        }

        .chat-messages {
            padding: 10px;
        }

        .message {
            max-width: 85%;
        }

        .chat-input-container {
            padding: 10px;
        }
    }
</style>
@endpush
