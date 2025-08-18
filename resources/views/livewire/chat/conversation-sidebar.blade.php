<div class="chat-sidebar" wire:id="{{ $this->getId() }}" data-component="conversation-sidebar" wire:key="conversation_sidebar">
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
            padding: 12px 15px;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }

        .available-contacts-section .btn-primary {
            width: 100% !important;
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
                    <button class="btn btn-light btn-sm d-flex align-items-center px-3" type="button" data-bs-toggle="dropdown">
                        <span class="d-none d-sm-inline">{{ auth()->user()->first_name }}</span>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2 border-bottom">
                            <div class="d-flex align-items-center">
                                @if(auth()->user()->avatar)
                                <img src="{{ asset('public/storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}"
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
    <div class="px-3 py-3 border-bottom available-contacts-section" wire:ignore>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newConversationModal">
            <i class="fas fa-plus me-2"></i> Start New Conversation
        </button>
    </div>
    @endif
    <!-- Active Conversations Section -->
    <div>
        @if(!empty($conversations) || !empty($conversationSearchTerm))
        <div class="px-3 pt-3 pb-2">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="text-muted mb-0">Your Conversations</h6>
                <small class="text-muted">({{ count($conversations) }})</small>
            </div>

            <!-- Search Input -->
            <div class="conversation-search-container mb-3">
                <div class="position-relative">
                    <input type="text"
                        wire:model.live.debounce.500ms="conversationSearchTerm"
                        placeholder="Search conversations..."
                        class="form-control form-control-sm conversation-search-input">
                    <i class="fas fa-search position-absolute search-icon"></i>
                    @if($conversationSearchTerm)
                    <button type="button"
                        wire:click="clearConversationSearch"
                        class="btn btn-sm position-absolute clear-search-btn">
                        <i class="fas fa-times"></i>
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @if(count($conversations) > 0)
        <div class="flex-fill overflow-auto custom-scrollbar" style="max-height: calc(100vh - 310px);">
            @foreach($conversations as $conversation)
            <a href="javascript:void(0)"
                wire:click="selectConversation({{ $conversation['id'] }})"
                class="conversation-item {{ $activeConversationId == $conversation['id'] ? 'active' : '' }} {{ !empty($conversationSearchTerm) ? 'search-result' : '' }}"
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
        </div>
        @else
        <!-- Empty Search Results -->
        @if(!empty($conversationSearchTerm))
        <div class="p-4 text-center">
            <div class="search-empty-icon mx-auto mb-3">
                <i class="fas fa-search text-muted"></i>
            </div>
            <h6 class="mb-2 text-muted">No conversations found</h6>
            <p class="text-muted small mb-3">No conversations match your search for "<strong>{{ $conversationSearchTerm }}</strong>"</p>
            <button type="button"
                wire:click="clearConversationSearch"
                class="btn btn-sm btn-outline-primary">
                <i class="fas fa-times me-1"></i> Clear search
            </button>
        </div>
        @endif
        @endif
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

    /* Conversation Search Styles */
    .conversation-search-container {
        position: relative;
    }

    .conversation-search-input {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 8px 35px 8px 35px;
        font-size: 13px;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }

    .conversation-search-input:focus {
        border-color: #6600ff;
        background: white;
        box-shadow: 0 0 0 0.1rem rgba(102, 0, 255, 0.15);
        outline: none;
    }

    .search-icon {
        top: 50%;
        left: 12px;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 12px;
        pointer-events: none;
    }

    .clear-search-btn {
        top: 50%;
        right: 8px;
        transform: translateY(-50%);
        color: #6c757d;
        background: none;
        border: none;
        padding: 4px;
        line-height: 1;
        font-size: 10px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .clear-search-btn:hover {
        color: #dc3545;
        background: #f8f9fa;
    }

    /* Search Empty State */
    .search-empty-icon {
        width: 50px;
        height: 50px;
        background: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    /* Highlight search results */
    .conversation-item.search-result {
        background: rgba(102, 0, 255, 0.05);
        border-left: 3px solid #6600ff;
    }

    /* Search result counter */
    .conversation-search-container+.conversation-list .text-muted small {
        font-size: 11px;
        color: #6600ff;
        font-weight: 500;
    }
</style>
@endpush
