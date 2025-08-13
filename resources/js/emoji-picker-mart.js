import { Picker } from 'emoji-mart'
import data from '@emoji-mart/data'

class EmojiPickerMart {
    constructor() {
        this.picker = null;
        this.container = null;
        this.isVisible = false;
        this.targetInput = null;
    }

    async initialize(containerId = 'emojiPickerContainer') {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Emoji picker container not found:', containerId);
            return false;
        }

        // Clean up any existing picker
        if (this.picker && typeof this.picker.remove === 'function') {
            try {
                this.picker.remove();
            } catch (e) {
                console.warn('Error removing existing picker:', e);
            }
        }

        try {
            this.container.innerHTML = '';

            this.picker = new Picker({
                data: data,
                onEmojiSelect: (emoji) => this.handleEmojiSelect(emoji),
                theme: 'light',
                previewPosition: 'none',
                skinTonePosition: 'none',
                maxFrequentRows: 2,
                perLine: 8,
                emojiSize: 20,
                emojiButtonSize: 28,
                autoFocus: false,
                searchPosition: 'sticky',
                navPosition: 'bottom',
                categories: [
                    'frequent',
                    'people',
                    'nature',
                    'foods',
                    'activity',
                    'travel',
                    'objects',
                    'symbols',
                    'flags'
                ]
            });

            this.container.appendChild(this.picker);
            return true;
        } catch (error) {
            console.error('Failed to initialize emoji picker:', error);
            this.createFallbackPicker();
            return true;
        }
    }

    createFallbackPicker() {
        const emojis = [
            '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇',
            '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚',
            '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩',
            '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣',
            '👍', '👎', '👌', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉',
            '👆', '🖕', '👇', '☝️', '👋', '🤚', '🖐️', '✋', '🖖', '👏',
            '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔',
            '🔥', '✨', '💯', '💥', '💫', '⭐', '🌟', '⚡', '💦', '💨'
        ];

        const grid = document.createElement('div');
        grid.className = 'emoji-grid fallback-emoji-picker';
        grid.style.cssText = `
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 4px;
            padding: 10px;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border-radius: 8px;
        `;

        emojis.forEach(emoji => {
            const button = document.createElement('button');
            button.textContent = emoji;
            button.className = 'emoji-btn';
            button.style.cssText = `
                border: none;
                background: none;
                font-size: 20px;
                padding: 5px;
                cursor: pointer;
                border-radius: 4px;
                transition: background-color 0.2s;
            `;
            button.onmouseover = () => button.style.backgroundColor = '#f0f0f0';
            button.onmouseout = () => button.style.backgroundColor = 'transparent';
            button.onclick = () => this.handleEmojiSelect({ native: emoji });
            grid.appendChild(button);
        });

        this.container.appendChild(grid);
    }

    handleEmojiSelect(emoji) {
        const emojiChar = emoji.native || emoji.emoji || emoji;
        
        if (this.targetInput) {
            this.insertEmojiIntoInput(this.targetInput, emojiChar);
        }

        this.hide();
    }

    insertEmojiIntoInput(input, emoji) {
        const start = input.selectionStart || 0;
        const end = input.selectionEnd || 0;
        const value = input.value || '';

        const newValue = value.slice(0, start) + emoji + value.slice(end);
        input.value = newValue;

        const newCursorPos = start + emoji.length;
        input.setSelectionRange(newCursorPos, newCursorPos);

        input.focus();
        input.dispatchEvent(new Event('input', { bubbles: true }));

        if (window.Livewire) {
            const component = window.Livewire.find(input.closest('[wire\\:id]')?.getAttribute('wire:id'));
            if (component) {
                component.set('messageText', newValue);
            }
        }

        if (input.tagName === 'TEXTAREA') {
            this.resizeTextarea(input);
        }
    }

    resizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }

    show(targetInput = null) {
        if (!this.container) return false;

        this.targetInput = targetInput || document.getElementById('chat-message-input');
        
        if (!this.picker && !this.container.children.length) {
            this.initialize();
        }

        this.container.style.display = 'block';
        this.container.offsetHeight;
        this.container.classList.add('show');
        this.isVisible = true;

        return true;
    }

    hide() {
        if (!this.container) return;

        this.container.classList.remove('show');
        setTimeout(() => {
            this.container.style.display = 'none';
        }, 200);
        this.isVisible = false;
    }

    toggle(targetInput = null) {
        if (this.isVisible) {
            this.hide();
        } else {
            this.show(targetInput);
        }
    }

    destroy() {
        if (this.picker && this.picker.remove) {
            this.picker.remove();
        }
        if (this.container) {
            this.container.innerHTML = '';
        }
        this.picker = null;
        this.targetInput = null;
        this.isVisible = false;
    }
}

window.EmojiPickerMart = EmojiPickerMart;
export default EmojiPickerMart;
