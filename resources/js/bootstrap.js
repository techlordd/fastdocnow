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
            // Only redirect to login for non-API requests or critical failures
            if (!error.config?.url?.includes('/api/user/presence')) {
                window.location.href = '/login';
            }
        } else if (error.response?.status === 419) {
            // CSRF token mismatch - only reload for non-presence API calls
            if (!error.config?.url?.includes('/api/user/presence')) {
                window.location.reload();
            }
        } else if (error.response?.status >= 500) {
            console.error('Server error:', error.response);
        }

        return Promise.reject(error);
    }
);

// Service Worker registration removed to improve performance

// Browser notifications handled through Pusher instead of service worker

// Online/Offline status
window.addEventListener('online', function() {
    console.log('Connection restored');
    document.body.classList.remove('offline');
});

window.addEventListener('offline', function() {
    console.log('Connection lost');
    document.body.classList.add('offline');
});

// Online presence tracking - DISABLED to prevent page refreshes
// Presence is now handled through Pusher events and user interactions
function updateOnlinePresence() {
    console.log('🟡 Automatic presence tracking disabled - using Pusher events instead');
    // Presence updates are now handled by:
    // 1. PusherService when user joins/leaves conversations
    // 2. User interaction events (typing, sending messages)
    // 3. Page visibility changes through Pusher
}

// Presence tracking temporarily disabled
// setInterval(updateOnlinePresence, 30000);

// Manual presence update only on visibility change (if needed)
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        console.log('🟢 Page became visible - presence handled by Pusher');
        // updateOnlinePresence(); // Disabled to prevent refreshes
    } else {
        console.log('🟡 Page became hidden - presence handled by Pusher');
    }
});

// Remove automatic presence update on page load
// document.addEventListener('DOMContentLoaded', updateOnlinePresence);

// Presence on page unload - DISABLED to prevent refresh issues
window.addEventListener('beforeunload', () => {
    console.log('🟡 Page unloading - presence managed by Pusher timeouts');
    // Disabled to prevent refresh issues
    // Presence will timeout naturally on the server side
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
    // Use Laravel Mix environment variables
    const pusherKey = process.env.MIX_PUSHER_APP_KEY;
    const pusherCluster = process.env.MIX_PUSHER_APP_CLUSTER;

    console.log('🔵 Checking Pusher credentials:', {
        key: pusherKey ? 'present' : 'missing',
        cluster: pusherCluster ? 'present' : 'missing',
        allEnvVars: Object.keys(process.env).filter(key => key.includes('PUSHER'))
    });

    if (pusherKey && pusherCluster) {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: pusherCluster,
            forceTLS: true
        });
        console.log('🟢 Echo initialized successfully with key:', pusherKey.substring(0, 8) + '...');
        console.log('🟢 Using cluster:', pusherCluster);
    } else {
        console.warn('🟡 Pusher credentials not found - real-time messaging disabled');
        console.warn('🟡 Available env keys:', Object.keys(process.env));
        console.warn('🟡 You need MIX_PUSHER_APP_KEY and MIX_PUSHER_APP_CLUSTER in your .env file');
        window.Echo = null;
    }
} catch (error) {
    console.error('🔴 Failed to initialize Echo:', error);
    window.Echo = null;
}
