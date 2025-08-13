import 'emoji-picker-element';

// Initialize emoji picker when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeEmojiPickerElement();
});

// Initialize emoji picker element
function initializeEmojiPickerElement() {
    // Check if emoji-picker is already defined
    if (typeof customElements.get('emoji-picker') !== 'undefined') {
        console.log('Emoji picker already loaded');
        return;
    }

    // Dynamically import if not loaded
    import('emoji-picker-element').then(() => {
        console.log('Emoji picker loaded successfully');

        // Wait for the element to be registered
        customElements.whenDefined('emoji-picker').then(() => {
            console.log('Emoji picker element registered');
            // Dispatch event to notify that emoji picker is ready
            document.dispatchEvent(new CustomEvent('emoji-picker-ready'));
        });
    }).catch(err => {
        console.error('Failed to load emoji picker:', err);
    });
}

// Export for use in other modules
window.initializeEmojiPickerElement = initializeEmojiPickerElement;
