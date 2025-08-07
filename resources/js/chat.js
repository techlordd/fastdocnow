import Swal from 'sweetalert2';

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
    Livewire.on('conversationLoaded', () => {
        setTimeout(() => scrollToBottom(true), 100);
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
    const emojiPicker = document.querySelector('emoji-picker');
    if (emojiPicker) {
        emojiPicker.addEventListener('emoji-click', function(event) {
            const textarea = document.getElementById('chat-message-input');
            if (textarea) {
                const emoji = event.detail.unicode;
                const start = textarea.selectionStart || 0;
                const end = textarea.selectionEnd || 0;
                const value = textarea.value || '';
                
                // Insert emoji
                const newValue = value.slice(0, start) + emoji + value.slice(end);
                textarea.value = newValue;
                
                // Update cursor position
                const newCursorPos = start + emoji.length;
                textarea.setSelectionRange(newCursorPos, newCursorPos);
                
                // Focus and trigger events
                textarea.focus();
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                
                // Auto-resize
                resizeTextarea(textarea);
                
                // Hide emoji picker
                hideEmojiPicker();
            }
        });
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
    if (container) {
        const isVisible = container.style.display !== 'none';
        container.style.display = isVisible ? 'none' : 'block';
        
        // Hide attachment menu
        const attachmentMenu = document.getElementById('attachmentMenu');
        if (attachmentMenu) {
            attachmentMenu.style.display = 'none';
        }
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
    const container = document.getElementById('emojiPickerContainer');
    if (container) {
        container.style.display = 'none';
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

// Export functions for global use
window.ChatApp = {
    scrollToBottom,
    showSuccessToast,
    showErrorToast,
    showInfoToast,
    confirmDelete: window.confirmDelete,
    confirmAction: window.confirmAction
};
