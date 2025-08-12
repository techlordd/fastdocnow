<div class="chat-sidebar">
    <style>
        .navbar-expand-lg {
            display: none !important;
        }

        .accordion-item {
            border: none !important;
            background-color: transparent;
        }

        .accordion-body,
        .accordion {
            border: none !important;
            background-color: transparent;
            padding: 10px 0 !important;
        }

        .accordion-button:focus {
            border: none !important;
            box-shadow: none !important;
        }

        #sidebar-toggle-btn {
            position: absolute;
            right: -15px;
            top: 20px;
            transform: translatex(40px);
        }
    </style>

    <!-- Header -->
    <div class="chat-header position-relative" wire:ignore>
        <button class="btn btn-light btn-sm d-lg-none me-2" id="sidebar-toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center" href="/chat">
                <div class="brand_logo">
                    <img src="{{asset('images/FastDocNow-logo.png')}}" alt="">
                </div>
            </a>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm d-flex align-items-center" type="button" data-bs-toggle="dropdown">

                        <span class="d-none d-sm-inline">{{ auth()->user()->first_name }}</span>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2 border-bottom">
                            <div class="d-flex align-items-center">
                                @if(auth()->user()->avatar)
                                <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}"
                                    class="rounded-circle me-2" width="32" height="32">
                                @else
                                <div class="user-avatar me-2" style="width: 32px; height: 32px; font-size: 14px;">
                                    {{ auth()->user()->initials }}
                                </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                    <div class="text-muted small">{{ auth()->user()->email }}</div>
                                    @if(auth()->user()->is_admin)
                                    <span class="badge bg-primary small">Administrator</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li><a class="dropdown-item" href="{{ route('profile') }}">
                                <i class="fas fa-user me-2"></i> Profile & Settings
                            </a></li>
                        @if(auth()->user()->is_admin)
                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard
                            </a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.contacts.index') }}">
                                <i class="fas fa-users me-2"></i> Manage Contacts
                            </a></li>
                        @endif
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Contacts Section -->
    @if(!empty($contacts))
    <div class="px-3 py-3 border-bottom available-contacts-section">
        <div class="accordion" id="contactsAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContacts" aria-expanded="false" aria-controls="collapseContacts">
                        <h6 class="text-muted mb-0">Available Contacts</h6>
                    </button>
                </h2>
                <div id="collapseContacts" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#contactsAccordion">
                    <div class="accordion-body">
                        @foreach($contacts as $contact)
                        <div class="contact-item mb-2" wire:key="contact-{{ $contact['id'] }}">
                            <button wire:click="startConversationWithContact({{ $contact['id'] }})"
                                class="btn w-100 text-start p-3 border rounded contact-btn">
                                <div class="d-flex align-items-center">

                                    <div class="flex-grow-1 d-">
                                        <div class="contact-type-badge">
                                            @if($contact['type'] === 'doctor')
                                            <span class="badge bg-primary"><i class="fas fa-user-md text-white me-1"></i>Doctor</span>
                                            @else
                                            <span class="badge bg-success"><i class="fas fa-headset text-white me-1"></i>Support</span>
                                            @endif
                                        </div>
                                        <div class="contact-name fw-semibold">{{ $contact['name'] }}</div>
                                        @if($contact['description'])
                                        <div class="contact-description text-muted small">{{ $contact['description'] }}</div>
                                        @endif
                                        @if($contact['assigned_user'])
                                        @php
                                            $isOnline = $contact['assigned_user'] && isset($contact['assigned_user']['last_seen_at']) && \Carbon\Carbon::parse($contact['assigned_user']['last_seen_at'])->gt(now()->subMinutes(2));
                                        @endphp
                                        <div class="assigned-user small user-status-indicator {{ $isOnline ? 'online' : 'offline' }}" data-user-id="{{ $contact['assigned_user']['id'] }}">
                                            <span class="text-muted">Handled by:</span> {{ $contact['assigned_user']['name'] }}
                                            @if($contact['assigned_user']['is_online'])
                                            <span class="badge bg-success ms-1">Online</span>
                                            @else
                                            <span class="text-muted small">
                                                Last seen {{ \Carbon\Carbon::parse($contact['assigned_user']['last_seen_at'])->diffForHumans() }}
                                            </span>
                                            @endif
                                        </div>
                                        @else
                                        <div class="text-warning small">
                                            <i class="fas fa-exclamation-triangle me-1"></i>No staff assigned
                                        </div>
                                        @endif
                                    </div>

                                </div>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Active Conversations Section -->
    <div class="flex-fill overflow-auto custom-scrollbar" wire:poll.10s="loadContactsAndConversations">
        @if(!empty($conversations))
        <div class="px-3 pt-3 pb-2">
            <h6 class="text-muted mb-3">Your Conversations</h6>
        </div>
        @foreach($conversations as $conversation)
        <a href="javascript:void(0)"
            wire:click="selectConversation({{ $conversation['id'] }})"
            class="conversation-item {{ $activeConversationId == $conversation['id'] ? 'active' : '' }}"
            wire:key="conversation-{{ $conversation['id'] }}">
            <!-- Avatar -->
            <div class="conversation-avatar">
                @if($conversation['contact_id'] != auth()->user()->id)
                @if($conversation['contact_type'] === 'doctor')
                <i class="fas fa-user-md"></i>
                @elseif($conversation['contact_type'] === 'support')
                <i class="fas fa-headset"></i>
                @endif
                @else
                <div class="chat-main-avatar">
                    @php
                    $otherUserAvatar = $conversation['other_user_avatar']
                    @endphp
                    @if($otherUserAvatar)
                    <img src="{{ $otherUserAvatar }}" alt="{{ $conversation['other_user_name'] }}" width="40px" height="40px">
                    @else
                    {{ strtoupper(substr($conversation['other_user_name'] ?? 'U', 0, 1)) }}{{ strtoupper(substr($conversation['other_user_last_name'] ?? 'U', 0, 1)) }}
                    @endif
                </div>
                @endif
            </div>

            <!-- Conversation Info -->
            <div class="conversation-info">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="conversation-name">
                        @if($conversation['contact_id'] != auth()->user()->id)
                        {{ $conversation['contact_name'] }}
                        @else
                        {{ $conversation['other_user_name'] }} {{ $conversation['other_user_last_name'] }}
                        <small class="d-block">@ {{$conversation['contact_name']}}</small>
                        @endif
                    </div>
                    @php
                    if (!function_exists('shortTimeAgo')) {
                    function shortTimeAgo($time) {
                    $diff = \Carbon\Carbon::parse($time)->diff(now());

                    if ($diff->y > 0) return $diff->y . 'y ago';
                    if ($diff->m > 0) return $diff->m . 'mo ago';
                    if ($diff->d > 0) return $diff->d . 'd ago';
                    if ($diff->h > 0) return $diff->h . 'h ago';
                    if ($diff->i > 0) return $diff->i . 'm ago';
                    if ($diff->s > 0) return $diff->s . 's ago';

                    return 'just now';
                    }
                    }
                    @endphp

                    <div class="conversation-time">
                        @if(!empty($conversation['last_message_at']))
                        {{ shortTimeAgo($conversation['last_message_at']) }}
                        @endif
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between">
                    <div class="conversation-preview latest-message">
                        @if($conversation['latest_message'])
                        @php $latestMessage = $conversation['latest_message']; @endphp
                        @if($latestMessage['type'] === 'text')
                        @if($latestMessage['is_own'])
                        <span class="text-muted">You: </span>
                        @endif
                        {{ Str::limit($latestMessage['content'], 40) }}
                        @elseif($latestMessage['type'] === 'image')
                        @if($latestMessage['is_own'])
                        <span class="text-muted">You: </span>
                        @endif
                        <i class="fas fa-image me-1"></i> Photo
                        @elseif($latestMessage['type'] === 'file')
                        @if($latestMessage['is_own'])
                        <span class="text-muted">You: </span>
                        @endif
                        <i class="fas fa-paperclip me-1"></i> File
                        @elseif($latestMessage['type'] === 'audio')
                        @if($latestMessage['is_own'])
                        <span class="text-muted">You: </span>
                        @endif
                        <i class="fas fa-microphone me-1"></i> Audio
                        @elseif($latestMessage['type'] === 'video')
                        @if($latestMessage['is_own'])
                        <span class="text-muted">You: </span>
                        @endif
                        <i class="fas fa-video me-1"></i> Video
                        @endif
                        @else
                        No messages yet
                        @endif
                    </div>

                    @if($conversation['unread_count'] > 0)
                    <span class="unread-count badge bg-danger">
                        {{ $conversation['unread_count'] > 99 ? '99+' : $conversation['unread_count'] }}
                    </span>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
        @endif

        @if(empty($contacts) && empty($conversations))
        <div class="p-4 text-center">
            <div class="welcome-icon mx-auto mb-3">
                <i class="fas fa-comments"></i>
            </div>
            <h5 class="mb-2">No contacts available</h5>
            <p class="text-muted mb-3">Please contact your administrator to set up support contacts.</p>
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .available-contacts-section .accordion-button {
        padding: 0.75rem 1.25rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .available-contacts-section .accordion-button:not(.collapsed) {
        color: #0c63e4;
        background-color: #e7f1ff;
    }

    .available-contacts-section .accordion-body {
        padding: 1rem;
        max-height: 300px;
        /* Adjust as needed */
        overflow-y: auto;
    }

    @media (max-width: 768px) {
        .available-contacts-section .accordion-body {
            max-height: 200px;
            /* Shorter height for mobile */
        }

        .chat-sidebar {
            width: 100%;
            position: absolute;
            left: -100%;
            transition: left 0.3s ease-in-out;
            z-index: 1000;
            background: #fff;
        }

        .chat-sidebar.open {
            left: 0;
        }

        .main-chat-wrapper.sidebar-open .chat-interface {
            margin-left: 100%;
        }
    }

    .contact-item .contact-btn {
        transition: all 0.2s ease;
        border-color: #e9ecef;
        background: white;
    }

    .contact-item .contact-btn:hover {
        border-color: #6600ff;
        background: #f8f9fa;
        transform: translateY(-1px);
    }

    .contact-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .conversation-item .conversation-avatar {
        background: linear-gradient(135deg, #6600ff 0%, #4400cc 100%);
        color: white;
    }

    .contact-name {
        color: #333;
        font-size: 15px;
    }

    .contact-description {
        font-size: 13px;
        line-height: 1.3;
    }

    .assigned-user {
        margin-top: 4px;
        font-size: 12px;
    }

    .contact-type-badge .badge {
        font-size: 10px;
        padding: 4px 8px;
    }

    .user-avatar-sm {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6600ff 0%, #4400cc 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 10px;
        font-weight: 600;
    }

    .dropdown-menu {
        min-width: 250px;
        border: none;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        border-radius: 10px;
    }

    .dropdown-item {
        padding: 10px 16px;
        transition: background-color 0.2s;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .dropdown-item i {
        width: 16px;
        text-align: center;
    }
</style>
@endpush