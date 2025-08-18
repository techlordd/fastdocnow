import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

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

// Update presence every 30 seconds
setInterval(updateOnlinePresence, 30000);

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

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Initialize Echo with error handling
try {
    const pusherKey = process.env.MIX_PUSHER_APP_KEY;
    const pusherCluster = process.env.MIX_PUSHER_APP_CLUSTER;

    if (pusherKey && pusherCluster) {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: pusherCluster,
            forceTLS: true
        });
        console.log('🟢 Echo initialized successfully with key:', pusherKey.substring(0, 8) + '...');
    } else {
        console.warn('🟡 Pusher credentials not found - real-time messaging disabled');
        console.warn('Available env vars:', {
            key: pusherKey ? 'present' : 'missing',
            cluster: pusherCluster ? 'present' : 'missing'
        });
        window.Echo = null;
    }
} catch (error) {
    console.error('🔴 Failed to initialize Echo:', error);
    window.Echo = null;
}
