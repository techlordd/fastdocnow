import Swal from 'sweetalert2';
import VoiceRecorder from './voice-recorder.js';

// Global voice recorder instance
let voiceRecorder = null;

// Simple, reliable chat functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeChat();
});

// Livewire event listeners
document.addEventListener('livewire:init', function() {
    initializeLivewireEvents();
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
            initializeEmojiPicker();
        });
    }
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
    
    // Listen for conversation loaded
    Livewire.on('conversationLoaded', (conversationId) => {
        setTimeout(() => scrollToBottom(true), 100);
        setupChatPresence(conversationId); // Call new function
    });
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
    const container = document.getElementById('emojiPickerContainer');
    if (!container) {
        console.warn('Emoji picker container not found');
        return null;
    }
setTimeout(()=>{
    let emojiPicker = container.querySelector('emoji-picker');

    if (!emojiPicker) {
        // Check if emoji-picker-element is loaded
        if (typeof customElements.get('emoji-picker') === 'undefined') {
            console.warn('emoji-picker-element not loaded yet, trying to wait...');

            // Try to wait for it to load
            const checkForEmojiPicker = () => {
                if (typeof customElements.get('emoji-picker') !== 'undefined') {
                    createEmojiPicker();
                } else {
                    setTimeout(checkForEmojiPicker, 100);
                }
            };

            const createEmojiPicker = () => {
                emojiPicker = document.createElement('emoji-picker');
                emojiPicker.className = 'emoji-picker';
                container.appendChild(emojiPicker);
                setupEmojiPickerEvents(emojiPicker);
            };

            checkForEmojiPicker();
            return null; // Return null for now, will be created async
        } else {
            // Create new emoji picker
            emojiPicker = document.createElement('emoji-picker');
            emojiPicker.className = 'emoji-picker';
            container.appendChild(emojiPicker);
        }
    }

    if (emojiPicker) {
        setupEmojiPickerEvents(emojiPicker);
    }

    return emojiPicker;
},2000);

}

function setupEmojiPickerEvents(emojiPicker) {
    // Remove existing listener to avoid duplicates
    emojiPicker.removeEventListener('emoji-click', handleEmojiClick);

    // Add event listener
    emojiPicker.addEventListener('emoji-click', handleEmojiClick);

    // Set picker properties for better UX
    emojiPicker.setAttribute('autoFocus', 'false');
    emojiPicker.setAttribute('skinToneEmoji', '👍');
}

function handleEmojiClick(event) {
    console.log('Emoji clicked:', event.detail);
    const textarea = document.getElementById('chat-message-input');
    if (textarea && event.detail && event.detail.unicode) {
        const emoji = event.detail.unicode;
        const start = textarea.selectionStart || 0;
        const end = textarea.selectionEnd || 0;
        const value = textarea.value || '';

        // Insert emoji at cursor position
        const newValue = value.slice(0, start) + emoji + value.slice(end);
        textarea.value = newValue;

        // Update cursor position
        const newCursorPos = start + emoji.length;
        textarea.setSelectionRange(newCursorPos, newCursorPos);

        // Focus and trigger Livewire update
        textarea.focus();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));

        // Trigger Livewire wire:model update
        if (window.Livewire) {
            const component = window.Livewire.find(textarea.closest('[wire\\:id]')?.getAttribute('wire:id'));
            if (component) {
                component.set('messageText', newValue);
            }
        }

        // Auto-resize textarea
        resizeTextarea(textarea);

        // Hide emoji picker
        hideEmojiPicker();
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

// Menu toggle functions
window.toggleEmojiPicker = function() {
    const container = document.getElementById('emojiPickerContainer');
    if (!container) {
        console.error('Emoji picker container not found');
        return;
    }

    const isVisible = container.classList.contains('show');

    if (isVisible) {
        container.classList.remove('show');
        setTimeout(() => {
            container.style.display = 'none';
        }, 200); // Wait for animation to complete
    } else {
        // Hide other menus first
        hideAttachmentMenu();
        hideVoiceRecording();

        // Initialize emoji picker first
        const emojiPicker = initializeEmojiPicker();

        if (!emojiPicker) {
            console.error('Failed to initialize emoji picker');
            showErrorToast('Emoji picker failed to load. Please refresh the page.');
            return;
        }

        // Position the picker properly
        positionEmojiPicker(container);

        container.style.display = 'block';
        // Force reflow before adding show class for animation
        container.offsetHeight;
        container.classList.add('show');
    }

    // Hide attachment menu
    const attachmentMenu = document.getElementById('attachmentMenu');
    if (attachmentMenu) {
        attachmentMenu.style.display = 'none';
    }
};

function positionEmojiPicker(container) {
    const chatInput = document.querySelector('.chat-input-container');
    const emojiBtn = document.querySelector('.emoji_picker_btn');

    if (chatInput && emojiBtn) {
        const chatInputRect = chatInput.getBoundingClientRect();
        const emojiBtnRect = emojiBtn.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const containerWidth = 320; // Default emoji picker width

        // Calculate positioning
        let left = emojiBtnRect.left;

        // Adjust if picker would go off-screen
        if (left + containerWidth > viewportWidth - 20) {
            left = viewportWidth - containerWidth - 20;
        }

        if (left < 20) {
            left = 20;
        }

        // Apply positioning
        container.style.left = left + 'px';
    }
}

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
    const container = document.getElementById('emojiPickerContainer');
    if (container && container.classList.contains('show')) {
        container.classList.remove('show');
        setTimeout(() => {
            container.style.display = 'none';
        }, 200); // Wait for animation to complete
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

// Add this function to chat.js
function setupChatPresence(conversationId) {
    if (window.Echo && conversationId) {
        window.Echo.join(`chat.${conversationId}`)
            .here((users) => {
                // This is the initial list of users in the channel
                console.log('Users in channel:', users);
                users.forEach(user => {
                    updateUserStatus(user.id, true); // Mark all initial users as online
                });
            })
            .joining((user) => {
                // A new user joined
                console.log('User joining:', user);
                updateUserStatus(user.id, true);
                showInfoToast(`${user.first_name} ${user.last_name} is now online.`);
            })
            .leaving((user) => {
                // A user left
                console.log('User leaving:', user);
                updateUserStatus(user.id, false);
                showInfoToast(`${user.first_name} ${user.last_name} went offline.`);
            })
            .error((error) => {
                console.error('Echo presence error:', error);
            });
    }
}

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

        // Convert audio file to base64
        const arrayBuffer = await audioFile.arrayBuffer();
        const uint8Array = new Uint8Array(arrayBuffer);
        const base64String = btoa(String.fromCharCode.apply(null, uint8Array));

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

    // Get or create audio element
    let audio = player.querySelector('audio');
    if (!audio) {
        audio = document.createElement('audio');
        audio.src = audioSrc;
        audio.preload = 'metadata';
        player.appendChild(audio);

        // Add event listeners
        audio.addEventListener('ended', function() {
            playIcon.className = 'fas fa-play';
            resetVoiceMessageProgress(player);
        });

        audio.addEventListener('timeupdate', function() {
            updateVoiceMessageProgress(player, audio);
        });
    }

    if (audio.paused) {
        // Stop any other playing voice messages
        document.querySelectorAll('.voice-message-player audio').forEach(otherAudio => {
            if (otherAudio !== audio && !otherAudio.paused) {
                otherAudio.pause();
                const otherButton = otherAudio.closest('.voice-message-player').querySelector('.voice-play-btn i');
                otherButton.className = 'fas fa-play';
            }
        });

        audio.play();
        playIcon.className = 'fas fa-pause';
    } else {
        audio.pause();
        playIcon.className = 'fas fa-play';
    }
}

function updateVoiceMessageProgress(player, audio) {
    const progressBar = player.querySelector('.voice-progress');
    const waveformBars = player.querySelectorAll('.voice-waveform-bar');

    if (progressBar && audio.duration) {
        const progress = (audio.currentTime / audio.duration) * 100;
        progressBar.style.width = progress + '%';

        // Update waveform bars
        const activeBarIndex = Math.floor((audio.currentTime / audio.duration) * waveformBars.length);
        waveformBars.forEach((bar, index) => {
            bar.classList.toggle('active', index <= activeBarIndex);
        });
    }
}

function resetVoiceMessageProgress(player) {
    const progressBar = player.querySelector('.voice-progress');
    const waveformBars = player.querySelectorAll('.voice-waveform-bar');

    if (progressBar) {
        progressBar.style.width = '0%';
    }

    waveformBars.forEach(bar => {
        bar.classList.remove('active');
    });
}

// Add voice recording functions to global window object
window.toggleVoiceRecorder = toggleVoiceRecorder;
window.startVoiceRecording = startVoiceRecording;
window.stopVoiceRecording = stopVoiceRecording;
window.cancelVoiceRecording = cancelVoiceRecording;
window.toggleVoiceMessage = toggleVoiceMessage;
