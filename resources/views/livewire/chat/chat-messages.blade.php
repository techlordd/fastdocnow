<div class="chat-messages-container" wire:id="{{ $this->getId() }}" data-component="chat-messages" data-conversation="{{ $conversationId }}">
    <!-- Messages Area -->
    <div class="chat-messages custom-scrollbar position-relative" id="chatMessages{{ $conversationId }}">
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
                    $fileName = $attachment['path'] ?? 'unknown';
                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
                    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp'];
                    $audioExtensions = ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a', 'wma', 'webm'];
                    $isImage = in_array($extension, $imageExtensions);
                    $isVideo = in_array($extension, $videoExtensions);
                    $isAudio = in_array($extension, $audioExtensions);
                    @endphp

                    @if($isImage)
                    <div class="message-image-container mb-2">
                        @php
                        $imagePath = 'public/storage/' . $attachment['path'];
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
                            <source src="public/storage/{{ $attachment['path'] }}" type="{{ $mimeType }}">
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
                        'wma' => 'audio/x-ms-wma',
                        'webm' => 'audio/webm'
                        ];
                        $mimeType = $audioMimeTypes[$extension] ?? 'audio/mpeg';
                        $isVoiceMessage = isset($attachment['metadata']['is_voice_message']) && $attachment['metadata']['is_voice_message'];
                        $duration = $attachment['metadata']['duration'] ?? null;
                        @endphp

                        @if($isVoiceMessage)
                        <!-- Voice Message Player -->
                        <div class="voice-message-player" data-audio-src="storage/{{ $attachment['path'] }}">
                            <button class="voice-play-btn" onclick="toggleVoiceMessage(this)" title="Play voice message">
                                <i class="fas fa-play"></i>
                            </button>
                            <div class="voice-content">
                                <div class="voice-waveform-container">
                                    <div class="voice-waveform">
                                        <div class="voice-waveform-bar" style="height: 20%"></div>
                                        <div class="voice-waveform-bar" style="height: 45%"></div>
                                        <div class="voice-waveform-bar" style="height: 70%"></div>
                                        <div class="voice-waveform-bar" style="height: 35%"></div>
                                        <div class="voice-waveform-bar" style="height: 85%"></div>
                                        <div class="voice-waveform-bar" style="height: 55%"></div>
                                        <div class="voice-waveform-bar" style="height: 75%"></div>
                                        <div class="voice-waveform-bar" style="height: 45%"></div>
                                        <div class="voice-waveform-bar" style="height: 65%"></div>
                                        <div class="voice-waveform-bar" style="height: 40%"></div>
                                        <div class="voice-waveform-bar" style="height: 25%"></div>
                                        <div class="voice-waveform-bar" style="height: 50%"></div>
                                        <div class="voice-waveform-bar" style="height: 60%"></div>
                                        <div class="voice-waveform-bar" style="height: 30%"></div>
                                        <div class="voice-waveform-bar" style="height: 45%"></div>
                                    </div>
                                    <div class="voice-progress-bar">
                                        <div class="voice-progress"></div>
                                    </div>
                                </div>
                                <div class="voice-info">
                                    <div class="voice-duration">
                                        @if($duration)
                                        {{ gmdate('i:s', $duration) }}
                                        @else
                                        --:--
                                        @endif
                                    </div>
                                    <div class="voice-label">
                                        <i class="fas fa-microphone voice-icon"></i>
                                        Voice message
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <!-- Regular Audio Player -->
                        <audio controls preload="metadata">
                            <source src="public/storage/{{ $attachment['path'] }}" type="{{ $mimeType }}">
                            Your browser does not support the audio element.
                        </audio>
                        @endif
                    </div>
                    @else
                    <div class="message-file-container mb-2">
                        <a href="public/storage/{{ $attachment['path'] }}" target="_blank" class="message-file-link" rel="noopener noreferrer">
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

                    @php
                    $hasVoiceMessage = false;
                    if ($message['attachments']) {
                    foreach ($message['attachments'] as $attachment) {
                    if (isset($attachment['metadata']['is_voice_message']) && $attachment['metadata']['is_voice_message']) {
                    $hasVoiceMessage = true;
                    break;
                    }
                    }
                    }
                    @endphp

                    @if(!$hasVoiceMessage && $message['content'] && !empty(trim($message['content'])))
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
        <button id="scrollToBottomBtn-{{ $conversationId }}"
            class="scroll-to-bottom-btn btn btn-primary rounded-circle position-absolute"
            style="bottom: 20px; right: 20px; width: 50px; height: 50px; display: none; z-index: 10;"
            onclick="scrollToBottom()"
            title="Scroll to bottom">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>
    <style>
        .chat-messages-container {
            position: relative;
            height: 100%;
            overflow: hidden;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            scroll-behavior: smooth;
            height: 100%;
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

        .scroll-to-bottom-btn {
            opacity: 0.8;
            transition: opacity 0.2s ease;
        }

        .scroll-to-bottom-btn:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .chat-messages {
                padding: 10px;
            }

            .message {
                max-width: 85%;
            }
        }

        /* Voice Message Player Styles */
        .voice-message-player {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: rgba(229, 231, 235, 0.3);
            border-radius: 18px;
            max-width: 300px;
            min-width: 240px;
            transition: all 0.2s ease;
        }

        .message.sent .voice-message-player {
            background: rgba(255, 255, 255, 0.15);
        }

        .voice-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .voice-play-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: #00a884;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .voice-play-btn:hover {
            background: #008c72;
            transform: scale(1.05);
        }

        .voice-play-btn:active {
            transform: scale(0.95);
        }

        .message.sent .voice-play-btn {
            background: rgba(255, 255, 255, 0.95);
            color: #00a884;
        }

        .message.sent .voice-play-btn:hover {
            background: white;
            color: #008c72;
        }

        .voice-waveform-container {
            position: relative;
            height: 24px;
            display: flex;
            align-items: center;
            margin-bottom: 2px;
        }

        .voice-waveform {
            display: flex;
            align-items: center;
            gap: 1.5px;
            height: 100%;
            width: 100%;
            cursor: pointer;
        }

        .voice-waveform-bar {
            flex: 1;
            background: #9ca3af;
            border-radius: 1px;
            min-height: 3px;
            max-width: 3px;
            transition: all 0.1s ease;
        }

        .message.sent .voice-waveform-bar {
            background: rgba(255, 255, 255, 0.7);
        }

        .voice-waveform-bar.active {
            background: #00a884;
        }

        .message.sent .voice-waveform-bar.active {
            background: rgba(255, 255, 255, 0.95);
        }

        .voice-waveform-bar:hover {
            opacity: 0.8;
        }

        .voice-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .voice-progress {
            height: 100%;
            background: linear-gradient(90deg, rgba(0, 168, 132, 0.2) 0%, transparent 100%);
            width: 0%;
            transition: width 0.1s ease;
            border-radius: 1px;
        }

        .voice-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .voice-duration {
            font-size: 11px;
            color: #6b7280;
            font-weight: 500;
            white-space: nowrap;
            font-family: 'SF Pro Text', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .message.sent .voice-duration {
            color: rgba(255, 255, 255, 0.85);
        }

        .voice-label {
            font-size: 10px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .message.sent .voice-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .voice-icon {
            font-size: 8px;
        }
    </style>
</div>
