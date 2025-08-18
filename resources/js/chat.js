import Swal from 'sweetalert2';
import VoiceRecorder from './voice-recorder.js';
import EmojiPickerMart from './emoji-picker-mart.js';

// Global instances
let voiceRecorder = null;
let emojiPicker = null;

// Simple, reliable chat functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeChat();
});

// Livewire event listeners
document.addEventListener('livewire:init', function() {
    initializeLivewireEvents();
});

// Listen for Livewire component updates
document.addEventListener('livewire:update', function(event) {
    // Re-initialize emoji picker if chat interface was updated
    if (event.detail.component.name === 'chat.chat-interface') {
        setTimeout(() => {
            initializeEmojiPicker();
            initializeTextareaResize();
        }, 50);
    }
});

function initializeChat() {
    // Initialize emoji picker
    initializeEmojiPicker();

    // Initialize scroll to bottom button
    initializeScrollButton();

    // Initialize file attachments
    initializeFileHandling();

    // Initialize SweetAlert confirmations
    initializeSweetAlerts();

    // Auto-resize textarea
    initializeTextareaResize();

    // Initialize voice recording
    initializeVoiceRecording();
}

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.chat-sidebar .chat-header');
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const mainChatWrapper = document.querySelector('.main-chat-wrapper');

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            mainChatWrapper.classList.toggle('sidebar-open');
        });
    }

    // Close sidebar when a conversation is selected on mobile
    if (window.Livewire) {
        window.Livewire.on('conversationSelected', () => {
            if (window.innerWidth < 991) {
                sidebar.classList.remove('open');
                mainChatWrapper.classList.remove('sidebar-open');
            }
            // Re-initialize emoji picker when conversation is selected
            setTimeout(() => {
                initializeEmojiPicker();
            }, 100);
        });
    }
});

// Also listen for Livewire navigation events
document.addEventListener('livewire:navigated', function() {
    // Re-initialize all chat components when navigating
    setTimeout(() => {
        initializeChat();
    }, 100);
});


function initializeLivewireEvents() {
    // Listen for scroll events
    Livewire.on('scroll-to-bottom', () => {
        scrollToBottom(true);
    });

    // Listen for success messages
    Livewire.on('success', (message) => {
        showSuccessToast(message);
    });

    // Listen for error messages
    Livewire.on('error', (message) => {
        showErrorToast(message);
    });

    // Listen for new messages
    Livewire.on('messageAdded', () => {
        scrollToBottom();
        updateScrollButton();
    });

    // Listen for notifications
    Livewire.on('showNotification', (data) => {
        showNotificationToast(data);
    });

    // Listen for conversation loaded
    Livewire.on('conversationLoaded', (conversationId) => {
        // Extract the ID if it's passed as an array or object
        const actualConversationId = Array.isArray(conversationId) ? conversationId[0] :
                                   (typeof conversationId === 'object' && conversationId.id) ? conversationId.id :
                                   conversationId;

        setTimeout(() => {
            scrollToBottom(true);
            // Re-initialize emoji picker when conversation loads
            initializeEmojiPicker();
            // Re-initialize textarea resize
            initializeTextareaResize();
        }, 100);

        // Only setup chat presence if we have a valid conversation ID and it's different from current
        if (actualConversationId && actualConversationId > 0) {
            if (actualConversationId !== currentConversationId) {
                console.log('🟢 New conversation loaded:', actualConversationId);
                setupChatPresence(actualConversationId);
            }
            // Remove the repetitive "same conversation" log since it's not necessary
            // and was causing console spam when the same conversation was loaded multiple times
        } else {
            console.warn('🟡 No valid conversation ID provided for real-time setup:', conversationId);
        }
    });

    // Listen for incoming message events and handle them properly
    Livewire.on('messageReceived', (event) => {
        console.log('🟢 Livewire messageReceived event triggered:', event);
        // This will be handled by the individual components that are listening
    });

    // Debug: Listen for all Livewire events
    if (window.Livewire && window.Livewire.hook) {
        window.Livewire.hook('message.sent', (message, component) => {
            if (message.payload.method === 'messageReceived' ||
                message.payload.method === 'refreshChatMessages' ||
                message.payload.method === 'refreshConversations') {
                console.log('🔧 Debug - Livewire message sent:', {
                    method: message.payload.method,
                    component: component.name,
                    payload: message.payload
                });
            }
        });

        window.Livewire.hook('message.received', (message, component) => {
            if (message.response && (
                message.response.effects?.dispatched?.some(e => ['messageReceived', 'refreshChatMessages', 'refreshConversations'].includes(e.event)) ||
                message.payload.method === 'messageReceived' ||
                message.payload.method === 'refreshChatMessages' ||
                message.payload.method === 'refreshConversations')) {
                console.log('🔧 Debug - Livewire message received:', {
                    method: message.payload?.method,
                    component: component.name,
                    response: message.response
                });
            }
        });
    }
}

function scrollToBottom(force = false) {
    const messagesContainer = document.querySelector('.chat-messages');
    if (messagesContainer) {
        if (force) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        } else {
            messagesContainer.scrollTo({
                top: messagesContainer.scrollHeight,
                behavior: 'smooth'
            });
        }
        updateScrollButton();
    }
}

function updateScrollButton() {
    const messagesContainer = document.querySelector('.chat-messages');
    const scrollButton = document.getElementById('scrollToBottomBtn');
    
    if (messagesContainer && scrollButton) {
        const isAtBottom = messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 100;
        scrollButton.style.display = isAtBottom ? 'none' : 'flex';
    }
}

function initializeScrollButton() {
    const messagesContainer = document.querySelector('.chat-messages');
    if (messagesContainer) {
        // Add scroll event listener
        messagesContainer.addEventListener('scroll', updateScrollButton);
        
        // Initial check
        updateScrollButton();
    }
}



function initializeEmojiPicker() {
    try {
        // Check if container exists first
        const container = document.getElementById('emojiPickerContainer');
        if (!container) {
            console.warn('Emoji picker container not found - chat interface may not be loaded yet');
            return null;
        }

        // If we already have an emoji picker and it's working, don't reinitialize
        if (emojiPicker && emojiPicker.container && emojiPicker.container.parentNode) {
            return emojiPicker;
        }

        // Create new emoji picker instance
        emojiPicker = new EmojiPickerMart();

        const success = emojiPicker.initialize('emojiPickerContainer');
        if (!success) {
            console.warn('Failed to initialize emoji picker');
            return null;
        }

        console.log('Emoji picker initialized successfully');
        return emojiPicker;
    } catch (error) {
        console.error('Error initializing emoji picker:', error);
        return null;
    }
}


function initializeTextareaResize() {
    const textarea = document.getElementById('chat-message-input');
    if (textarea) {
        textarea.addEventListener('input', function() {
            resizeTextarea(this);
        });
        
        // Initial resize
        resizeTextarea(textarea);
    }
}

function resizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
}

function initializeFileHandling() {
    // File input change handlers
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                console.log(`Selected ${this.files.length} file(s)`);
            }
        });
    });
}

function initializeSweetAlerts() {
    // Delete confirmations
    window.confirmDelete = function(title = 'Are you sure?', text = 'This action cannot be undone.') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });
    };
    
    // General confirmations
    window.confirmAction = function(title = 'Are you sure?', text = '', confirmText = 'Yes') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6600ff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancel'
        });
    };
}

// Toast functions using SweetAlert2
function showSuccessToast(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    
    Toast.fire({
        icon: 'success',
        title: message
    });
}

function showErrorToast(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    
    Toast.fire({
        icon: 'error',
        title: message
    });
}

function showInfoToast(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    Toast.fire({
        icon: 'info',
        title: message
    });
}

function showNotificationToast(data) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: true,
        confirmButtonText: 'View',
        timer: 5000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    Toast.fire({
        icon: 'info',
        title: data.title || 'New Message',
        text: data.body || '',
        showCloseButton: true
    }).then((result) => {
        if (result.isConfirmed && data.conversationId) {
            // Navigate to the conversation
            const sidebarComponent = findSidebarComponent();
            if (sidebarComponent) {
                sidebarComponent.call('selectConversation', data.conversationId);
            }
        }
    });
}

function findSidebarComponent() {
    // Find the sidebar component
    const sidebarElement = document.querySelector('.chat-sidebar [wire\\:id]');
    if (sidebarElement) {
        const wireId = sidebarElement.getAttribute('wire:id');
        return window.Livewire.find(wireId);
    }
    return null;
}

// Menu toggle functions
window.toggleEmojiPicker = function() {
    try {
        // Hide other menus first
        hideAttachmentMenu();
        hideVoiceRecording();

        // Check if container exists
        const container = document.getElementById('emojiPickerContainer');
        if (!container) {
            showErrorToast('Chat interface not ready. Please wait a moment and try again.');
            return;
        }

        // Initialize emoji picker if not already done
        if (!emojiPicker || !emojiPicker.container || !emojiPicker.container.parentNode) {
            const picker = initializeEmojiPicker();
            if (!picker) {
                showErrorToast('Emoji picker failed to load. Please refresh the page.');
                return;
            }
        }

        // Get target input
        const targetInput = document.getElementById('chat-message-input');
        if (!targetInput) {
            showErrorToast('Message input not found. Please refresh the page.');
            return;
        }

        // Toggle picker
        emojiPicker.toggle(targetInput);

    } catch (error) {
        console.error('Error toggling emoji picker:', error);
        showErrorToast('Emoji picker error. Please refresh the page.');
    }
};


window.toggleAttachmentMenu = function() {
    const container = document.getElementById('attachmentMenu');
    if (container) {
        const isVisible = container.style.display !== 'none';
        container.style.display = isVisible ? 'none' : 'block';
        
        // Hide emoji picker
        hideEmojiPicker();
    }
};

function hideEmojiPicker() {
    if (emojiPicker) {
        emojiPicker.hide();
    }
}

// Scroll to bottom function for button
window.scrollToBottom = function() {
    scrollToBottom();
};

// Click outside to hide menus
document.addEventListener('click', function(e) {
    const emojiContainer = document.getElementById('emojiPickerContainer');
    const attachmentMenu = document.getElementById('attachmentMenu');
    const emojiBtn = document.querySelector('[onclick*="toggleEmojiPicker"]');
    const attachmentBtn = document.querySelector('[onclick*="toggleAttachmentMenu"]');
    
    // Hide emoji picker if clicking outside
    if (emojiContainer && 
        !emojiContainer.contains(e.target) && 
        (!emojiBtn || !emojiBtn.contains(e.target))) {
        hideEmojiPicker();
    }
    
    // Hide attachment menu if clicking outside
    if (attachmentMenu && 
        !attachmentMenu.contains(e.target) && 
        (!attachmentBtn || !attachmentBtn.contains(e.target))) {
        attachmentMenu.style.display = 'none';
    }
});



// Export for global use
window.ChatApp = {
    scrollToBottom,
    showSuccessToast,
    showErrorToast,
    showInfoToast,
    confirmDelete: window.confirmDelete,
    confirmAction: window.confirmAction
};

// Real-time message handling
let currentConversationId = null;
let echoChannels = new Map();
let lastSetupTime = 0;
let setupTimeout = null;

function setupChatPresence(conversationId) {
    // Clear any pending setup timeout
    if (setupTimeout) {
        clearTimeout(setupTimeout);
        setupTimeout = null;
    }

    // Validate inputs first
    if (!conversationId || conversationId <= 0) {
        console.warn('🟡 Invalid conversation ID provided:', conversationId);
        return;
    }

    // Prevent duplicate setup for the same conversation
    if (currentConversationId === conversationId) {
        // Silently return without logging to prevent console spam
        return;
    }

    // Add debounce to prevent rapid successive calls
    const now = Date.now();
    if (now - lastSetupTime < 1000) { // Prevent calls within 1 second
        setupTimeout = setTimeout(() => setupChatPresence(conversationId), 500);
        return;
    }
    lastSetupTime = now;

    // Check if Echo is available
    if (!window.Echo) {
        console.warn('🟡 Echo not available - real-time messaging disabled');
        return;
    }

    // Leave previous channels if switching conversations
    if (currentConversationId && currentConversationId !== conversationId && echoChannels.size > 0) {
        console.log('🟡 Leaving previous conversation channels:', currentConversationId);
        echoChannels.forEach((channel, channelName) => {
            try {
                window.Echo.leave(channelName);
            } catch (error) {
                console.warn('Warning leaving channel:', channelName, error);
            }
        });
        echoChannels.clear();
    }

    currentConversationId = conversationId;
    console.log('🟢 Setting up real-time listeners for conversation:', conversationId);

    try {
        // Check Echo connection status (optional, don't fail if not available)
        if (window.Echo.connector && window.Echo.connector.pusher) {
            const pusher = window.Echo.connector.pusher;
            console.log('🟢 Pusher connection state:', pusher.connection.state);
        }

        // Listen for new messages
        console.log('🔵 Setting up Echo listener for conversation:', conversationId);
        console.log('🔵 Echo object:', window.Echo);

        const messageChannel = window.Echo.private(`conversation.${conversationId}`)
            .listen('MessageSent', (e) => {
                console.log('🟢 New message received via Echo:', e);
                console.log('🟢 About to call handleIncomingMessage');
                // Use a slight delay to ensure DOM is ready
                setTimeout(() => {
                    handleIncomingMessage(e);
                }, 50);
            })
            .listen('UserTyping', (e) => {
                console.log('🟢 User typing event:', e);
                handleUserTyping(e);
            })
            .error((error) => {
                console.error('🔴 Echo private channel error:', error);
            });

        echoChannels.set(`conversation.${conversationId}`, messageChannel);

        // Optional: Join presence channel for online status (don't fail if this doesn't work)
        try {
            const presenceChannel = window.Echo.join(`chat.${conversationId}`)
                .here((users) => {
                    console.log('🟢 Users currently in channel:', users);
                    users.forEach(user => {
                        updateUserStatus(user.id, true);
                    });
                })
                .joining((user) => {
                    console.log('🟢 User joining channel:', user.first_name);
                    updateUserStatus(user.id, true);
                })
                .leaving((user) => {
                    console.log('🟡 User leaving channel:', user.first_name);
                    updateUserStatus(user.id, false);
                })
                .error((error) => {
                    // Presence channel auth failures are common and optional - just log as warning
                    console.warn('🟡 Presence channel auth failed (optional feature):', error.error || error);
                    // Don't show user-facing error for optional presence feature
                });

            echoChannels.set(`chat.${conversationId}`, presenceChannel);
        } catch (error) {
            console.warn('🟡 Presence channel setup failed (optional feature):', error);
        }

        console.log('🟢 Real-time setup completed for conversation:', conversationId);

    } catch (error) {
        console.error('🔴 Error setting up real-time messaging:', error);
        currentConversationId = null;
    }
}
setTimeout(() => {
    handleIncomingMessage(e);
}, 50);
// Add this helper function to chat.js
function updateUserStatus(userId, isOnline) {
    const statusElement = document.getElementById(`user-status-${userId}`);
    if (statusElement) {
        const circleIcon = statusElement.querySelector('i.fas.fa-circle');
        const statusText = statusElement.querySelector('span'); // Assuming the text is in a span

        if (isOnline) {
            statusElement.classList.add('online');
            statusElement.classList.remove('offline');
            if (circleIcon) circleIcon.style.color = '#22c55e'; // Green
            if (statusText) statusText.textContent = 'Online';
        } else {
            statusElement.classList.add('offline');
            statusElement.classList.remove('online');
            if (circleIcon) circleIcon.style.color = '#6b7280'; // Gray
            // For offline, we might want to display "Last seen X minutes ago".
            // This would require fetching the last_seen_at from the server or having it available.
            // For now, just show "Offline" or "Last seen..." if the data is available in the DOM.
            // The Livewire poll will eventually update the "Last seen" text.
            if (statusText) statusText.textContent = 'Offline'; // Placeholder, Livewire will update
        }
    }
}

// Voice Recording Functions
function initializeVoiceRecording() {
    // Check if voice recording is supported at the browser level
    if (!VoiceRecorder.isSupported()) {
        const voiceBtn = document.getElementById('voiceRecorderBtn');
        const disabledBtn = document.getElementById('voiceRecorderBtnDisabled');

        if (voiceBtn) {
            voiceBtn.style.display = 'none';
        }

        if (disabledBtn) {
            disabledBtn.style.display = 'block';
            disabledBtn.setAttribute('title', VoiceRecorder.getUnsupportedReason());

            // Add click handler to show explanation
            disabledBtn.onclick = function() {
                showErrorToast(VoiceRecorder.getUnsupportedReason());
            };
        }

        console.warn('Voice recording not supported:', VoiceRecorder.getUnsupportedReason());
        return;
    }

    // Voice recording is supported - show the normal button
    const voiceBtn = document.getElementById('voiceRecorderBtn');
    const disabledBtn = document.getElementById('voiceRecorderBtnDisabled');

    if (voiceBtn) {
        voiceBtn.style.display = 'block';
    }

    if (disabledBtn) {
        disabledBtn.style.display = 'none';
    }

    // Initialize voice recorder
    voiceRecorder = new VoiceRecorder();

    // Handle recording completion
    voiceRecorder.onRecordingComplete = function(audioFile, duration) {
        sendVoiceMessage(audioFile, duration);
    };

    // Listen for recording updates
    document.addEventListener('voiceRecordingUpdate', function(event) {
        updateRecordingUI(event.detail);
    });
}

function toggleVoiceRecorder() {
    // Check if voice recording is supported
    if (!VoiceRecorder.isSupported()) {
        showErrorToast(VoiceRecorder.getUnsupportedReason());
        return;
    }

    const isRecording = voiceRecorder && voiceRecorder.isRecording;

    if (isRecording) {
        // Stop recording
        stopVoiceRecording();
    } else {
        // Start recording
        startVoiceRecording();
    }
}

async function startVoiceRecording() {
    try {
        // Check if already recording
        if (voiceRecorder && voiceRecorder.isRecording) {
            console.warn('Voice recording already in progress');
            return;
        }

        // Hide other menus
        hideEmojiPicker();
        hideAttachmentMenu();

        // Show recording UI
        const container = document.getElementById('voiceRecordingContainer');
        if (container) {
            container.style.display = 'block';
            container.classList.add('show');
        }

        // Initialize recorder if needed
        if (!voiceRecorder) {
            // Double-check support before creating
            if (!VoiceRecorder.isSupported()) {
                throw new Error(VoiceRecorder.getUnsupportedReason());
            }

            voiceRecorder = new VoiceRecorder();
            voiceRecorder.onRecordingComplete = function(audioFile, duration) {
                sendVoiceMessage(audioFile, duration);
            };
        }

        // Start recording
        const success = await voiceRecorder.startRecording();

        if (success) {
            // Update button state
            const voiceBtn = document.getElementById('voiceRecorderBtn');
            if (voiceBtn) {
                voiceBtn.classList.add('recording');
                voiceBtn.setAttribute('title', 'Stop recording');
            }

            showInfoToast('Recording started...');
        } else {
            throw new Error('Failed to start recording');
        }

    } catch (error) {
        console.error('Failed to start voice recording:', error);
        hideVoiceRecording();

        // Handle specific error types
        if (error.name === 'NotAllowedError') {
            showErrorToast('Microphone access denied. Please allow microphone access in your browser settings and refresh the page.');
        } else if (error.name === 'NotFoundError') {
            showErrorToast('No microphone found. Please connect a microphone and refresh the page to record voice messages.');
        } else if (error.name === 'NotSupportedError') {
            showErrorToast('Voice recording is not supported in this browser. Please try using Chrome, Firefox, Safari, or Edge.');
        } else if (error.name === 'NotReadableError') {
            showErrorToast('Microphone is being used by another application. Please close other apps using the microphone and try again.');
        } else if (error.name === 'OverconstrainedError') {
            showErrorToast('Microphone does not meet the required specifications. Please try with a different microphone.');
        } else if (error.name === 'SecurityError' || (error.message && error.message.includes('secure'))) {
            showErrorToast('Voice recording requires a secure connection (HTTPS). Please access this site over HTTPS.');
        } else if (error.message && error.message.includes('permission')) {
            showErrorToast('Microphone permission is required. Please allow microphone access in your browser settings.');
        } else {
            // Use the specific error message if available, otherwise use a generic message
            const errorMessage = error.message || 'Failed to start voice recording. Please try again.';
            showErrorToast(errorMessage);
        }
    }
}

function stopVoiceRecording() {
    if (voiceRecorder && voiceRecorder.isRecording) {
        const success = voiceRecorder.stopRecording();
        if (success) {
            showInfoToast('Processing voice message...');
        } else {
            showErrorToast('Failed to stop recording');
            hideVoiceRecording();
        }
    }
}

function cancelVoiceRecording() {
    if (voiceRecorder) {
        voiceRecorder.cancelRecording();
    }
    hideVoiceRecording();
    showInfoToast('Voice recording cancelled');
}

function hideVoiceRecording() {
    const container = document.getElementById('voiceRecordingContainer');
    if (container) {
        container.style.display = 'none';
        container.classList.remove('show');
    }

    const voiceBtn = document.getElementById('voiceRecorderBtn');
    if (voiceBtn) {
        voiceBtn.classList.remove('recording');
        voiceBtn.setAttribute('title', 'Record voice message');
    }

    // Reset recording time display
    const timeElement = document.getElementById('recordingTime');
    if (timeElement) {
        timeElement.textContent = '0:00';
    }
}

function hideAttachmentMenu() {
    const container = document.getElementById('attachmentMenu');
    if (container) {
        container.style.display = 'none';
    }
}

function updateRecordingUI(recordingState) {
    const timeElement = document.getElementById('recordingTime');
    if (timeElement && recordingState.isRecording) {
        timeElement.textContent = formatRecordingTime(recordingState.duration);
    }

    if (!recordingState.isRecording) {
        hideVoiceRecording();
    }
}

function formatRecordingTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}

async function sendVoiceMessage(audioFile, duration) {
    try {
        hideVoiceRecording();

        // Show loading state
        showInfoToast('Sending voice message...');

        // Convert audio file to base64 (prevent stack overflow for large files)
        const arrayBuffer = await audioFile.arrayBuffer();
        const uint8Array = new Uint8Array(arrayBuffer);

        // Convert in chunks to prevent stack overflow
        let binaryString = '';
        const chunkSize = 8192;
        for (let i = 0; i < uint8Array.length; i += chunkSize) {
            const chunk = uint8Array.slice(i, i + chunkSize);
            binaryString += String.fromCharCode.apply(null, chunk);
        }
        const base64String = btoa(binaryString);

        // Find the ChatInterface component
        const messageInputElement = document.getElementById('chat-message-input');
        const chatInterfaceElement = messageInputElement ?
            messageInputElement.closest('[wire\\:id]') :
            document.querySelector('.main-chat-area [wire\\:id]') ||
            document.querySelector('.chat-main [wire\\:id]');

        const chatComponent = chatInterfaceElement ?
            window.Livewire.find(chatInterfaceElement.getAttribute('wire:id')) :
            null;

        if (!chatComponent) {
            throw new Error('Chat component not found');
        }

        // Call direct voice message upload method
        chatComponent.call('sendVoiceMessageDirect', base64String, {
            duration: duration,
            isVoiceMessage: true,
            recordedAt: new Date().toISOString(),
            mimeType: audioFile.type || 'audio/mp4'
        }).then(() => {
            showSuccessToast('Voice message sent!');
        }).catch((error) => {
            console.error('Failed to send voice message:', error);
            showErrorToast('Failed to send voice message');
        });

    } catch (error) {
        console.error('Failed to send voice message:', error);
        showErrorToast('Failed to send voice message: ' + error.message);
        hideVoiceRecording();
    }
}

// Voice Message Playback Functions
function toggleVoiceMessage(button) {
    const player = button.closest('.voice-message-player');
    const audioSrc = player.getAttribute('data-audio-src');
    const playIcon = button.querySelector('i');

    // Check if we already have a stable audio element for this source
    let audio = activeAudioElements.get(audioSrc);

    if (!audio) {
        // Create new audio element and store it
        audio = document.createElement('audio');
        audio.src = audioSrc;
        audio.preload = 'auto'; // Changed from 'metadata' to 'auto' for better loading
        audio.style.display = 'none';

        // Store reference to prevent garbage collection
        activeAudioElements.set(audioSrc, audio);

        // Add to DOM (append to body to prevent removal during updates)
        document.body.appendChild(audio);

        // Add event listeners only once
        audio.addEventListener('ended', function() {
            playIcon.className = 'fas fa-play';
            resetVoiceMessageProgress(player);
            // Clean up reference when audio ends
            activeAudioElements.delete(audioSrc);
            if (audio.parentNode) {
                audio.parentNode.removeChild(audio);
            }
        });

        audio.addEventListener('timeupdate', function() {
            // Find current player in DOM (it might have been updated)
            const currentPlayer = document.querySelector(`[data-audio-src="${audioSrc}"]`);
            if (currentPlayer) {
                updateVoiceMessageProgress(currentPlayer, audio);
            }
        });

        audio.addEventListener('loadedmetadata', function() {
            const currentPlayer = document.querySelector(`[data-audio-src="${audioSrc}"]`);
            if (currentPlayer) {
                const durationElement = currentPlayer.querySelector('.voice-duration');
                if (durationElement && audio.duration) {
                    const minutes = Math.floor(audio.duration / 60);
                    const seconds = Math.floor(audio.duration % 60);
                    durationElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }
        });

        audio.addEventListener('canplaythrough', function() {
            console.log('Audio can play through:', audioSrc);
            // Audio is fully loaded and can play
        });

        audio.addEventListener('loadstart', function() {
            console.log('Audio loading started:', audioSrc);
        });

        audio.addEventListener('error', function(e) {
            console.error('Audio error:', e, 'Error code:', audio.error ? audio.error.code : 'unknown');
            let errorMessage = 'Error playing voice message';

            if (audio.error) {
                switch (audio.error.code) {
                    case audio.error.MEDIA_ERR_ABORTED:
                        errorMessage = 'Audio playback was aborted';
                        break;
                    case audio.error.MEDIA_ERR_NETWORK:
                        errorMessage = 'Network error while loading audio';
                        break;
                    case audio.error.MEDIA_ERR_DECODE:
                        errorMessage = 'Audio format not supported';
                        break;
                    case audio.error.MEDIA_ERR_SRC_NOT_SUPPORTED:
                        errorMessage = 'Audio source not supported';
                        break;
                    default:
                        errorMessage = 'Unknown audio error';
                }
            }

            showErrorToast(errorMessage);
            playIcon.className = 'fas fa-play';
            activeAudioElements.delete(audioSrc);
            if (audio.parentNode) {
                audio.parentNode.removeChild(audio);
            }
        });
    }

    if (audio.paused) {
        // Stop any other playing voice messages
        activeAudioElements.forEach((otherAudio, otherSrc) => {
            if (otherAudio !== audio && !otherAudio.paused) {
                otherAudio.pause();
                const otherPlayer = document.querySelector(`[data-audio-src="${otherSrc}"]`);
                if (otherPlayer) {
                    const otherButton = otherPlayer.querySelector('.voice-play-btn i');
                    if (otherButton) {
                        otherButton.className = 'fas fa-play';
                    }
                    resetVoiceMessageProgress(otherPlayer);
                }
            }
        });

        // Show loading state
        playIcon.className = 'fas fa-spinner fa-spin';

        // Check if audio is ready to play
        console.log('Audio ready state:', audio.readyState, 'HAVE_ENOUGH_DATA:', audio.HAVE_ENOUGH_DATA);
        if (audio.readyState >= audio.HAVE_ENOUGH_DATA) {
            // Audio is ready, play immediately
            console.log('Audio ready, playing immediately');
            audio.play().then(() => {
                console.log('Audio play successful');
                playIcon.className = 'fas fa-pause';
            }).catch(error => {
                console.error('Error playing audio immediately:', error);
                showErrorToast('Could not play voice message');
                playIcon.className = 'fas fa-play';
            });
        } else {
            // Audio is not ready, wait for it to load
            console.log('Audio not ready, waiting for canplay event. Current state:', audio.readyState);
            const onCanPlay = () => {
                console.log('Audio canplay event fired');
                audio.removeEventListener('canplay', onCanPlay);
                audio.removeEventListener('error', onError);

                audio.play().then(() => {
                    console.log('Audio play successful after loading');
                    playIcon.className = 'fas fa-pause';
                }).catch(error => {
                    console.error('Error playing audio after loading:', error);
                    showErrorToast('Could not play voice message');
                    playIcon.className = 'fas fa-play';
                });
            };

            const onError = () => {
                audio.removeEventListener('canplay', onCanPlay);
                audio.removeEventListener('error', onError);
                console.error('Error loading audio for playback');
                showErrorToast('Could not load voice message');
                playIcon.className = 'fas fa-play';
            };

            audio.addEventListener('canplay', onCanPlay);
            audio.addEventListener('error', onError);

            // Trigger loading if not already started
            audio.load();
        }
    } else {
        audio.pause();
        playIcon.className = 'fas fa-play';
    }
}

function updateVoiceMessageProgress(player, audio) {
    const progressBar = player.querySelector('.voice-progress');
    const waveformBars = player.querySelectorAll('.voice-waveform-bar');
    const durationElement = player.querySelector('.voice-duration');

    if (progressBar && audio.duration) {
        const progress = (audio.currentTime / audio.duration) * 100;
        progressBar.style.width = progress + '%';

        // Update waveform bars
        const activeBarIndex = Math.floor((audio.currentTime / audio.duration) * waveformBars.length);
        waveformBars.forEach((bar, index) => {
            bar.classList.toggle('active', index <= activeBarIndex);
        });

        // Update duration countdown
        if (durationElement) {
            const remainingTime = audio.duration - audio.currentTime;
            const minutes = Math.floor(remainingTime / 60);
            const seconds = Math.floor(remainingTime % 60);
            durationElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
    }
}

function resetVoiceMessageProgress(player) {
    const progressBar = player.querySelector('.voice-progress');
    const waveformBars = player.querySelectorAll('.voice-waveform-bar');
    const durationElement = player.querySelector('.voice-duration');
    const audio = player.querySelector('audio');

    if (progressBar) {
        progressBar.style.width = '0%';
    }

    waveformBars.forEach(bar => {
        bar.classList.remove('active');
    });

    // Reset duration to full duration
    if (durationElement && audio && audio.duration) {
        const minutes = Math.floor(audio.duration / 60);
        const seconds = Math.floor(audio.duration % 60);
        durationElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
}

// Simple audio management - store audio references to prevent DOM removal
const activeAudioElements = new Map();

// Handle incoming messages from Pusher
function handleIncomingMessage(e) {
    console.log('🟢 handleIncomingMessage called with:', e);
    console.log('🟢 Livewire object:', window.Livewire);

    try {
        // First, dispatch the messageReceived event to all Livewire components
        console.log('🟢 About to dispatch messageReceived event to Livewire components');
        if (window.Livewire && window.Livewire.dispatch) {
            window.Livewire.dispatch('messageReceived', e);
            console.log('🟢 Successfully dispatched messageReceived event');
        } else {
            console.error('🔴 Livewire.dispatch not available');
        }

        // Also dispatch to individual components by name as backup
        const allComponents = window.Livewire.all();
        let updatedComponents = 0;

        Object.entries(allComponents).forEach(([id, component]) => {
            if (component.name === 'chat.chat-interface') {
                console.log('🟢 Calling messageReceived on ChatInterface component');
                component.call('messageReceived', e);
                updatedComponents++;
            } else if (component.name === 'chat.chat-messages') {
                console.log('🟢 Calling messageReceived on ChatMessages component');
                component.call('messageReceived', e);
                updatedComponents++;
            }
        });

        console.log(`🟢 Updated ${updatedComponents} components via messageReceived`);

        // Scroll to bottom and show notification for messages from others
        if (e.message && e.message.user_id !== (window.currentUserId || null)) {
            setTimeout(() => {
                scrollToBottom();
            }, 200);

            // Show notification if not on current conversation
            if (e.message.conversation_id != currentConversationId) {
                showNotificationToast({
                    title: `New Message from ${e.message.user?.first_name || 'Someone'}`,
                    body: e.message.content || 'New message',
                    conversationId: e.message.conversation_id
                });
            }
        }

    } catch (error) {
        console.error('🔴 Error handling incoming message:', error);
        console.error('Error details:', error.stack);
    }
}

// Handle user typing events
function handleUserTyping(e) {
    try {
        // Find ChatInterface component to handle typing events
        const chatMainElement = document.getElementById('chatMain');
        if (chatMainElement) {
            const wireId = chatMainElement.getAttribute('wire:id');
            if (wireId) {
                const chatInterfaceComponent = window.Livewire.find(wireId);
                if (chatInterfaceComponent) {
                    console.log('🟢 Handling typing event via ChatInterface:', e);
                    chatInterfaceComponent.call('userTyping', e);
                    return;
                }
            }
        }

        // Fallback: search for any chat component
        const allComponents = window.Livewire.all();
        for (const [id, component] of Object.entries(allComponents)) {
            if (component.name === 'chat.chat-interface') {
                component.call('userTyping', e);
                return;
            }
        }

        console.warn('🟡 No ChatInterface component found for typing event');
    } catch (error) {
        console.error('Error handling typing event:', error);
    }
}

// Add voice recording functions to global window object
window.toggleVoiceRecorder = toggleVoiceRecorder;
window.startVoiceRecording = startVoiceRecording;
window.stopVoiceRecording = stopVoiceRecording;
window.cancelVoiceRecording = cancelVoiceRecording;
window.toggleVoiceMessage = toggleVoiceMessage;

// Debug function to test message reception
window.testMessageReception = function() {
    console.log('🔧 Testing message reception...');

    // Direct approach: Use debug methods that return data
    let promises = [];

    // Test ChatMessages
    const chatMessagesElement = document.querySelector('[data-component="chat-messages"]');
    if (chatMessagesElement) {
        const wireId = chatMessagesElement.getAttribute('wire:id');
        if (wireId) {
            const component = window.Livewire.find(wireId);
            if (component) {
                console.log('🔧 Test: Testing ChatMessages with testRefresh');
                const promise = component.call('testRefresh').then(result => {
                    console.log('🔧 ChatMessages testRefresh result:', result);
                }).catch(error => {
                    console.error('🔧 ChatMessages testRefresh error:', error);
                });
                promises.push(promise);
            }
        }
    }

    // Test ConversationSidebar
    const sidebarElement = document.querySelector('[data-component="conversation-sidebar"]');
    if (sidebarElement) {
        const wireId = sidebarElement.getAttribute('wire:id');
        if (wireId) {
            const component = window.Livewire.find(wireId);
            if (component) {
                console.log('🔧 Test: Testing ConversationSidebar with testRefresh');
                const promise = component.call('testRefresh').then(result => {
                    console.log('🔧 ConversationSidebar testRefresh result:', result);
                }).catch(error => {
                    console.error('🔧 ConversationSidebar testRefresh error:', error);
                });
                promises.push(promise);
            }
        }
    }

    // Wait for all promises and show final result
    Promise.all(promises).then(() => {
        console.log('🔧 Test: All components tested. Check Laravel logs for detailed info.');
    }).catch(error => {
        console.error('🔧 Test: Error during testing:', error);
    });

    if (promises.length === 0) {
        console.error('🔧 Test: No components found!');
        // Show available components
        const allComponents = window.Livewire.all();
        console.log('Available Livewire components:');
        Object.entries(allComponents).forEach(([id, comp]) => {
            console.log(`  - ${id}: ${comp.name} (element: ${comp.el.tagName}${comp.el.id ? '#' + comp.el.id : ''})`);
        });
    }
};
