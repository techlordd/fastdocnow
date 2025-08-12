@extends('layouts.app')

@section('title', '| Chat')

@section('content')
<div class="main-chat-wrapper">
    <div class="chat-screen">
        <!-- Livewire Sidebar -->
        <livewire:chat.conversation-sidebar />

        <!-- Livewire Main Chat Area -->
        <livewire:chat.chat-interface />
    </div>
</div>

<!-- New Conversation Modal -->
<div class="modal fade" id="newConversationModal" tabindex="-1" aria-labelledby="newConversationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newConversationModalLabel">Start New Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <livewire:new-conversation-modal />
            </div>
        </div>
    </div>
</div>

<!-- Notification Container -->
<div id="notification-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('conversationCreated', function(event) {
            let modalEl = document.getElementById('newConversationModal');
            let modalInstance = bootstrap.Modal.getInstance(modalEl);

            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalEl);
            }

            modalInstance.hide();
        });
    });
</script>

@push('styles')
<style>
    /* Ensure the layout works properly with Livewire */
    .main-chat-wrapper {
        height: 100vh;
        overflow: hidden;
    }

    .chat-screen {
        height: 100vh;
        display: flex;
    }

    /* Loading states for Livewire */
    [wire\:loading] {
        opacity: 0.6;
        pointer-events: none;
    }

    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }

    /* File upload progress */
    [wire\:loading][wire\:target="selectedFiles"] {
        opacity: 1;
    }

    /* Smooth transitions for message updates */
    .message-group {
        transition: all 0.3s ease;
    }

    .conversation-item {
        transition: all 0.2s ease;
    }

    .conversation-item:hover {
        transform: translateX(2px);
    }
</style>
@endpush
@endsection