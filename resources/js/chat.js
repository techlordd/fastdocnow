import Swal from 'sweetalert2';
import VoiceRecorder from './voice-recorder.js';
import EmojiPickerMart from './emoji-picker-mart.js';

// Global instances
let voiceRecorder = null;
let emojiPicker = null;
let currentConversationId = null;
let echoChannel = null;
let presenceChannel = null;
let activeAudioElements = new Map();

// Simple, reliable chat functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeChat();
    setupPusherConnection();
});

// Livewire event listeners
document.addEventListener('livewire:init', function() {
    initializeLivewireEvents();
});

// Listen for Livewire component updates
document.addEventListener('livewire:update', function(event) {
    if (event.detail.component.name === 'chat.chat-interface') {
        setTimeout(() => {
            initializeEmojiPicker();
            initializeTextareaResize();
        }, 50);
    }
});

function initializeChat() {
    initializeEmojiPicker();
    initializeScrollButton();
    initializeFileHandling();
    initializeSweetAlerts();
    initializeTextareaResize();
    initializeVoiceRecording();
}

// Listen for conversation changes
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.chat-sidebar .chat-header');
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const mainChatWrapper = document.querySelector('.main-chat-wrapper');

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            console.log('here');
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
            setTimeout(() => {
                initializeEmojiPicker();
            }, 100);
        });
    }
});
function setupPusherConnection() {
    if (!window.Echo) {
        console.warn('🟡 Echo not available - real-time messaging disabled');
        return;
    }


    document.addEventListener('livewire:navigated', function() {
        setTimeout(() => {
            initializeChat();
        }, 100);
    });
}

function initializeLivewireEvents() {
    // Listen for scroll events
    Livewire.on('scroll-to-bottom', () => {
        scrollToBottom(true);
    });

    // Listen for conversation loaded
    Livewire.on('conversationLoaded', (conversationId) => {
        const actualConversationId = Array.isArray(conversationId) ? conversationId[0] :
                                   (typeof conversationId === 'object' && conversationId.id) ? conversationId.id :
                                   conversationId;

        setTimeout(() => {
            scrollToBottom(true);
            initializeEmojiPicker();
            initializeTextareaResize();
        }, 100);

        if (actualConversationId && actualConversationId > 0) {
            if (actualConversationId !== currentConversationId) {
                console.log('🟢 New conversation loaded:', actualConversationId);
                setupChatPresence(actualConversationId);
            }
        } else {
            console.warn('🟡 No valid conversation ID provided for real-time setup:', conversationId);
        }
    });
}

function setupChatPresence(conversationId) {
    if (!conversationId || conversationId <= 0) {
        console.warn('🟡 Invalid conversation ID provided:', conversationId);
        return;
    }

    if (currentConversationId === conversationId) {
        return;
    }

    if (!window.Echo) {
        console.warn('🟡 Echo not available - real-time messaging disabled');
        return;
    }

    // Leave previous channels
    if (echoChannel) {
        window.Echo.leave(`conversation.${currentConversationId}`);
        echoChannel = null;
    }
    if (presenceChannel) {
        window.Echo.leave(`chat.${currentConversationId}`);
        presenceChannel = null;
    }

    currentConversationId = conversationId;
    console.log('��� Setting up Pusher listeners for conversation:', conversationId);

    try {
        // Listen for new messages and events
        echoChannel = window.Echo.private(`conversation.${conversationId}`)
            .listen('MessageSent', (e) => {
                console.log('🟢 Message received via Pusher:', e);
                handlePusherMessage(e);
            })
            .listen('UserTyping', (e) => {
                console.log('��� User typing event:', e);
                handleUserTyping(e);
            })
            .listen('EmailSent', (e) => {
                console.log('🟢 Email notification sent:', e);
                showSuccessToast(`Email sent to ${e.recipient.first_name}`);
            })
            .listen('NotificationSent', (e) => {
                console.log('🟢 NotificationSent event received:', e);
                if (e.data) {
                    // Slight delay to ensure UI is ready
                    setTimeout(() => {
                        showNotificationToast(e.data);
                    }, 100);
                }
            })
            .listen('UserOnlineStatus', (e) => {
                console.log('🟢 User online status changed:', e);
                if (e.user) {
                    updateUserStatus(e.user.id, e.is_online);
                    const statusMessage = e.is_online ? 'came online' : 'went offline';
                    showInfoToast(`${e.user.first_name} ${statusMessage}`);
                }
            })
            .error((error) => {
                console.error('🔴 Pusher private channel error:', error);
            });

        // Setup presence channel for online status
        try {
            presenceChannel = window.Echo.join(`chat.${conversationId}`)
                .here((users) => {
                    console.log('🟢 Users currently online:', users);
                    users.forEach(user => updateUserStatus(user.id, true));
                })
                .joining((user) => {
                    console.log('🟢 User came online:', user.first_name);
                    updateUserStatus(user.id, true);
                    showInfoToast(`${user.first_name} is now online`);
                })
                .leaving((user) => {
                    console.log('🟡 User went offline:', user.first_name);
                    updateUserStatus(user.id, false);
                    showInfoToast(`${user.first_name} went offline`);
                })
                .error((error) => {
                    console.warn('🟡 Presence channel auth failed (optional feature):', error.error || error);
                });
        } catch (error) {
            console.warn('🟡 Presence channel setup failed (optional feature):', error);
        }

        console.log('🟢 Pusher setup completed for conversation:', conversationId);

    } catch (error) {
        console.error('🔴 Error setting up Pusher:', error);
        currentConversationId = null;
    }
}

function handlePusherMessage(e) {
    console.log('🟢 Processing Pusher message:', e);

    try {
        // Check if this is a valid message
        if (!e.message || !e.message.id) {
            console.log('🟡 Invalid message data, skipping:', e);
            return;
        }

        // Show toast notification FIRST for messages from others
        const isOwnMessage = e.message.user_id === (window.currentUserId || null);

        if (!isOwnMessage) {
            console.log('🔔 Showing toast notification for incoming message');

            const messageContent = e.message.content ||
                (e.message.type === 'image' ? '📷 Image' :
                 e.message.type === 'audio' ? '🎵 Audio message' :
                 e.message.type === 'video' ? '🎥 Video' :
                 e.message.type === 'file' ? '📎 File' : 'New message');

            // Show toast immediately
            showNotificationToast({
                title: `New Message from ${e.message.user?.first_name || 'Someone'}`,
                body: messageContent,
                conversationId: e.message.conversation_id
            });

            // Trigger email notification
            triggerEmailNotification(e.message);
        }

        // Update Livewire components
        if (window.Livewire) {
            // Dispatch global event
            if (window.Livewire.dispatch) {
                window.Livewire.dispatch('messageReceived', e);
            }

            // Update components directly but efficiently
            const allComponents = window.Livewire.all();
            let componentsUpdated = 0;

            Object.entries(allComponents).forEach(([id, component]) => {
                if (component.name === 'chat.chat-interface' || component.name === 'chat.chat-messages') {
                    component.call('messageReceived', e);
                    componentsUpdated++;
                }
            });

            console.log(`🟢 Updated ${componentsUpdated} Livewire components`);
        }

        // Scroll to bottom after a short delay
        if (!isOwnMessage) {
            setTimeout(() => {
                scrollToBottom();
            }, 200);
        }

    } catch (error) {
        console.error('🔴 Error handling Pusher message:', error);
    }
}

function triggerEmailNotification(message) {
    // Broadcast email notification event
    if (window.Echo && currentConversationId) {
        window.Echo.private(`conversation.${currentConversationId}`)
            .whisper('email-notification', {
                messageId: message.id,
                senderId: message.user_id,
                timestamp: new Date().toISOString()
            });
    }
}

function handleUserTyping(e) {
    try {
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

function updateUserStatus(userId, isOnline) {
    const statusElement = document.getElementById(`user-status-${userId}`);
    if (statusElement) {
        const circleIcon = statusElement.querySelector('i.fas.fa-circle');
        const statusText = statusElement.querySelector('span');

        if (isOnline) {
            statusElement.classList.add('online');
            statusElement.classList.remove('offline');
            if (circleIcon) circleIcon.style.color = '#22c55e';
            if (statusText) statusText.textContent = 'Online';
        } else {
            statusElement.classList.add('offline');
            statusElement.classList.remove('online');
            if (circleIcon) circleIcon.style.color = '#6b7280';
            if (statusText) statusText.textContent = 'Offline';
        }
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
        messagesContainer.addEventListener('scroll', updateScrollButton);
        updateScrollButton();
    }
}

function initializeEmojiPicker() {
    try {
        const container = document.getElementById('emojiPickerContainer');
        if (!container) {
            console.warn('Emoji picker container not found - chat interface may not be loaded yet');
            return null;
        }

        if (emojiPicker && emojiPicker.container && emojiPicker.container.parentNode) {
            return emojiPicker;
        }

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
        resizeTextarea(textarea);
    }
}

function resizeTextarea(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
}

function initializeFileHandling() {
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
    console.log('🔔 showNotificationToast called with data:', data);

    // Check if SweetAlert2 is available
    if (typeof Swal === 'undefined') {
        console.error('🔴 SweetAlert2 not available for toast notifications');
        // Fallback to browser notification
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(data.title || 'New Message', {
                body: data.body || '',
                icon: '/favicon.ico'
            });
        }
        return;
    }

    try {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: true,
            confirmButtonText: 'View',
            timer: 6000,
            timerProgressBar: true,
            customClass: {
                popup: 'message-toast',
                title: 'toast-title',
                content: 'toast-content'
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        console.log('🔔 Firing toast notification with SweetAlert2...');

        Toast.fire({
            icon: 'info',
            title: data.title || 'New Message',
            text: data.body || '',
            showCloseButton: true,
            background: '#fff',
            color: '#333'
        }).then((result) => {
            console.log('🔔 Toast notification result:', result);
            if (result.isConfirmed && data.conversationId) {
                // Navigate to conversation
                const sidebarComponent = findSidebarComponent();
                if (sidebarComponent) {
                    sidebarComponent.call('selectConversation', data.conversationId);
                } else {
                    console.log('🟡 Sidebar component not found for navigation');
                }
            }
        }).catch(error => {
            console.error('🔴 Toast notification error:', error);
        });

    } catch (error) {
        console.error('🔴 Error creating toast notification:', error);

        // Fallback to simple alert
        if (data.title && data.body) {
            showInfoToast(`${data.title}: ${data.body}`);
        }
    }
}

function findSidebarComponent() {
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
        hideAttachmentMenu();
        hideVoiceRecording();

        const container = document.getElementById('emojiPickerContainer');
        if (!container) {
            showErrorToast('Chat interface not ready. Please wait a moment and try again.');
            return;
        }

        if (!emojiPicker || !emojiPicker.container || !emojiPicker.container.parentNode) {
            const picker = initializeEmojiPicker();
            if (!picker) {
                showErrorToast('Emoji picker failed to load. Please refresh the page.');
                return;
            }
        }

        const targetInput = document.getElementById('chat-message-input');
        if (!targetInput) {
            showErrorToast('Message input not found. Please refresh the page.');
            return;
        }

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
        hideEmojiPicker();
    }
};

function hideEmojiPicker() {
    if (emojiPicker) {
        emojiPicker.hide();
    }
}

window.scrollToBottom = function() {
    scrollToBottom();
};

// Click outside to hide menus
document.addEventListener('click', function(e) {
    const emojiContainer = document.getElementById('emojiPickerContainer');
    const attachmentMenu = document.getElementById('attachmentMenu');
    const emojiBtn = document.querySelector('[onclick*="toggleEmojiPicker"]');
    const attachmentBtn = document.querySelector('[onclick*="toggleAttachmentMenu"]');
    
    if (emojiContainer && 
        !emojiContainer.contains(e.target) && 
        (!emojiBtn || !emojiBtn.contains(e.target))) {
        hideEmojiPicker();
    }
    
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
    showNotificationToast,
    confirmDelete: window.confirmDelete,
    confirmAction: window.confirmAction
};

// Debug function to test toasts
window.testToast = function() {
    console.log('🧪 Testing toast notification...');
    showNotificationToast({
        title: 'Test Notification',
        body: 'This is a test message from the debug function',
        conversationId: 1
    });
};

// Test SweetAlert2 availability
window.testSweetAlert = function() {
    if (typeof Swal === 'undefined') {
        console.error('🔴 SweetAlert2 is not available!');
        alert('SweetAlert2 is not loaded!');
    } else {
        console.log('🟢 SweetAlert2 is available');
        Swal.fire('Test', 'SweetAlert2 is working!', 'success');
    }
};

// Debug function to simulate message received
window.testMessageReceived = function() {
    console.log('🧪 Testing message received event...');
    handlePusherMessage({
        message: {
            id: 999,
            conversation_id: 1,
            user_id: 2, // Different from current user
            content: 'Test message content',
            type: 'text',
            created_at: new Date().toISOString(),
            user: {
                id: 2,
                first_name: 'Test',
                last_name: 'User'
            }
        }
    });
};

// Debug function to check Echo status
window.debugEcho = function() {
    console.log('🔍 Echo Debug Info:', {
        echoAvailable: !!window.Echo,
        currentConversationId: currentConversationId,
        echoChannel: !!echoChannel,
        presenceChannel: !!presenceChannel,
        currentUserId: window.currentUserId
    });
};

// Voice Recording Functions
function initializeVoiceRecording() {
    if (!VoiceRecorder.isSupported()) {
        const voiceBtn = document.getElementById('voiceRecorderBtn');
        const disabledBtn = document.getElementById('voiceRecorderBtnDisabled');

        if (voiceBtn) {
            voiceBtn.style.display = 'none';
        }

        if (disabledBtn) {
            disabledBtn.style.display = 'block';
            disabledBtn.setAttribute('title', VoiceRecorder.getUnsupportedReason());

            disabledBtn.onclick = function() {
                showErrorToast(VoiceRecorder.getUnsupportedReason());
            };
        }

        console.warn('Voice recording not supported:', VoiceRecorder.getUnsupportedReason());
        return;
    }

    const voiceBtn = document.getElementById('voiceRecorderBtn');
    const disabledBtn = document.getElementById('voiceRecorderBtnDisabled');

    if (voiceBtn) {
        voiceBtn.style.display = 'block';
    }

    if (disabledBtn) {
        disabledBtn.style.display = 'none';
    }

    voiceRecorder = new VoiceRecorder();

    voiceRecorder.onRecordingComplete = function(audioFile, duration) {
        sendVoiceMessage(audioFile, duration);
    };

    document.addEventListener('voiceRecordingUpdate', function(event) {
        updateRecordingUI(event.detail);
    });
}

function toggleVoiceRecorder() {
    if (!VoiceRecorder.isSupported()) {
        showErrorToast(VoiceRecorder.getUnsupportedReason());
        return;
    }

    const isRecording = voiceRecorder && voiceRecorder.isRecording;

    if (isRecording) {
        stopVoiceRecording();
    } else {
        startVoiceRecording();
    }
}

async function startVoiceRecording() {
    try {
        if (voiceRecorder && voiceRecorder.isRecording) {
            console.warn('Voice recording already in progress');
            return;
        }

        hideEmojiPicker();
        hideAttachmentMenu();

        const container = document.getElementById('voiceRecordingContainer');
        if (container) {
            container.style.display = 'block';
            container.classList.add('show');
        }

        if (!voiceRecorder) {
            if (!VoiceRecorder.isSupported()) {
                throw new Error(VoiceRecorder.getUnsupportedReason());
            }

            voiceRecorder = new VoiceRecorder();
            voiceRecorder.onRecordingComplete = function(audioFile, duration) {
                sendVoiceMessage(audioFile, duration);
            };
        }

        const success = await voiceRecorder.startRecording();

        if (success) {
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
        showInfoToast('Sending voice message...');

        const arrayBuffer = await audioFile.arrayBuffer();
        const uint8Array = new Uint8Array(arrayBuffer);

        let binaryString = '';
        const chunkSize = 8192;
        for (let i = 0; i < uint8Array.length; i += chunkSize) {
            const chunk = uint8Array.slice(i, i + chunkSize);
            binaryString += String.fromCharCode.apply(null, chunk);
        }
        const base64String = btoa(binaryString);

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

    let audio = activeAudioElements.get(audioSrc);

    if (!audio) {
        audio = document.createElement('audio');
        audio.src = audioSrc;
        audio.preload = 'auto';
        audio.style.display = 'none';

        activeAudioElements.set(audioSrc, audio);
        document.body.appendChild(audio);

        audio.addEventListener('ended', function() {
            playIcon.className = 'fas fa-play';
            resetVoiceMessageProgress(player);
            activeAudioElements.delete(audioSrc);
            if (audio.parentNode) {
                audio.parentNode.removeChild(audio);
            }
        });

        audio.addEventListener('timeupdate', function() {
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

        playIcon.className = 'fas fa-spinner fa-spin';

        if (audio.readyState >= audio.HAVE_ENOUGH_DATA) {
            audio.play().then(() => {
                playIcon.className = 'fas fa-pause';
            }).catch(error => {
                console.error('Error playing audio immediately:', error);
                showErrorToast('Could not play voice message');
                playIcon.className = 'fas fa-play';
            });
        } else {
            const onCanPlay = () => {
                audio.removeEventListener('canplay', onCanPlay);
                audio.removeEventListener('error', onError);

                audio.play().then(() => {
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

        const activeBarIndex = Math.floor((audio.currentTime / audio.duration) * waveformBars.length);
        waveformBars.forEach((bar, index) => {
            bar.classList.toggle('active', index <= activeBarIndex);
        });

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

    if (durationElement && audio && audio.duration) {
        const minutes = Math.floor(audio.duration / 60);
        const seconds = Math.floor(audio.duration % 60);
        durationElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
}

// Add voice recording functions to global window object
window.toggleVoiceRecorder = toggleVoiceRecorder;
window.startVoiceRecording = startVoiceRecording;
window.stopVoiceRecording = stopVoiceRecording;
window.cancelVoiceRecording = cancelVoiceRecording;
window.toggleVoiceMessage = toggleVoiceMessage;
