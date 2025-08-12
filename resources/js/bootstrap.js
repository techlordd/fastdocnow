import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// Initialize Echo for real-time features
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Set up Echo for real-time features
try {

    // Set up Pusher
    window.Pusher = Pusher;

    if (import.meta.env.VITE_PUSHER_APP_KEY) {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
            wsHost: import.meta.env.VITE_PUSHER_HOST || `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher-channels.com`,
            wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
            wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            auth: {
                headers: {
                    Authorization: `Bearer ${document.querySelector('meta[name="api-token"]')?.getAttribute('content') || ''}`,
                },
            },
        });

        // Listen for online status updates
        window.Echo.join('user.online')
            .here((users) => {
                updateOnlineStatus(users.map(u => u.id));
            })
            .joining((user) => {
                updateOnlineStatus([user.id], true);
            })
            .leaving((user) => {
                updateOnlineStatus([user.id], false);
            });

    } else {
        console.warn('No real-time broadcasting configuration found. Echo will not be initialized.');
    }
} catch (error) {
    console.error('Failed to initialize Echo:', error);
}

// Global error handling
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
});

// CSRF Token setup
const token = document.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
} else {
    console.error('CSRF token not found');
}

// API token setup for authenticated requests
const apiToken = document.querySelector('meta[name="api-token"]');
if (apiToken && apiToken.getAttribute('content')) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${apiToken.getAttribute('content')}`;
}

// Request interceptors
window.axios.interceptors.request.use(
    function (config) {
        // Show loading indicator
        document.body.classList.add('loading');
        return config;
    },
    function (error) {
        document.body.classList.remove('loading');
        return Promise.reject(error);
    }
);

// Response interceptors
window.axios.interceptors.response.use(
    function (response) {
        // Hide loading indicator
        document.body.classList.remove('loading');
        return response;
    },
    function (error) {
        document.body.classList.remove('loading');

        // Handle common errors
        if (error.response?.status === 401) {
            window.location.href = '/login';
        } else if (error.response?.status === 419) {
            // CSRF token mismatch, reload page
            window.location.reload();
        } else if (error.response?.status >= 500) {
            console.error('Server error:', error.response);
        }

        return Promise.reject(error);
    }
);

// Service Worker registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('SW registered: ', registration);
            })
            .catch(function(registrationError) {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

// Notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission().then(function(result) {
        console.log('Notification permission:', result);
    });
}

// Online/Offline status
window.addEventListener('online', function() {
    console.log('Connection restored');
    document.body.classList.remove('offline');
});

window.addEventListener('offline', function() {
    console.log('Connection lost');
    document.body.classList.add('offline');
});

// Online presence tracking
function updateOnlinePresence() {
    if (document.querySelector('meta[name="api-token"]')?.getAttribute('content')) {
        window.axios.post('/api/user/presence', { online: true }).catch(() => {
            // Silently fail - user might not be authenticated
        });
    }
}

// Function to update user status indicators on the page
function updateOnlineStatus(userIds, isOnline = true) {
    userIds.forEach(userId => {
        const indicators = document.querySelectorAll(`.user-status-indicator[data-user-id="${userId}"]`);
        indicators.forEach(indicator => {
            if (isOnline) {
                indicator.classList.add('online');
                indicator.classList.remove('offline');
            } else {
                indicator.classList.remove('online');
                indicator.classList.add('offline');
            }
        });
    });
}

// Throttle function to limit how often a function is called
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Update presence on user activity
const throttledUpdate = throttle(updateOnlinePresence, 20000);
window.addEventListener('mousemove', throttledUpdate);
window.addEventListener('keydown', throttledUpdate);
window.addEventListener('scroll', throttledUpdate);
window.addEventListener('click', throttledUpdate);


// Update presence when page becomes visible
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        updateOnlinePresence();
    }
});

// Update presence on page load
document.addEventListener('DOMContentLoaded', updateOnlinePresence);

// Mark as offline when page is about to unload
window.addEventListener('beforeunload', () => {
    if (navigator.sendBeacon && document.querySelector('meta[name="api-token"]')?.getAttribute('content')) {
        const formData = new FormData();
        formData.append('online', 'false');
        navigator.sendBeacon('/api/user/presence', formData);
    }
});

// Export for global use
window.api = {
    get: (url, config = {}) => window.axios.get(url, config),
    post: (url, data = {}, config = {}) => window.axios.post(url, data, config),
    put: (url, data = {}, config = {}) => window.axios.put(url, data, config),
    delete: (url, config = {}) => window.axios.delete(url, config),
};