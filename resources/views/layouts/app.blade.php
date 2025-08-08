<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="api-token" content="{{ auth()->user()?->createToken('websocket')?->plainTextToken ?? '' }}">

    <title>{{ config('app.name', 'DocNow Chat') }} @yield('title')</title>

    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- SweetAlert2 CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom App CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <style id="dynamic-theme">
        :root {
            --primary-color: #fff;

            --primary-rgb:#eee;

            --primary-dark: #fefefe;
        }

        .btn-primary {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
        }

        .btn-outline-primary {
            color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .border-primary {
            border-color: var(--primary-color) !important;
        }

        .form-check-input:checked {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25) !important;
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary-color) !important;
        }

        .sidebar-nav .nav-link.active {
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark)) !important;
            color: white !important;
        }

        .stats-card.primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
        }

        .user-avatar img,
        .conversation-avatar img,
        .chat-main-avatar img{
            width: 100%;
        }
        .user-avatar,
        .conversation-avatar,
        .chat-main-avatar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
        }

        /* Light theme only - clean backgrounds */
        :root {
            --bs-body-bg: #f8fafc;
            --bs-body-color: #1a202c;
            --bs-card-bg: #ffffff;
            --bs-border-color: #e2e8f0;
            --bs-gray-50: #f9fafb;
            --bs-gray-100: #f3f4f6;
            --bs-gray-200: #e5e7eb;
        }

        body {
            background-color: var(--bs-body-bg) !important;
            color: var(--bs-body-color) !important;
        }

        .card {
            background-color: var(--bs-card-bg) !important;
            border-color: var(--bs-border-color) !important;
        }

        .navbar {
            background-color: var(--bs-card-bg) !important;
            border-color: var(--bs-border-color) !important;
        }

        .form-control,
        .form-select {
            background-color: var(--bs-card-bg) !important;
            border-color: var(--bs-border-color) !important;
            color: var(--bs-body-color) !important;
        }

        .table {
            --bs-table-bg: var(--bs-card-bg);
            --bs-table-color: var(--bs-body-color);
        }

        .stats-icon-modern  {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
    </style>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <meta name="theme-color" content="{{ auth()->user()->theme_color ?? '#6600ff' }}">

    @livewireStyles
    @stack('head')
</head>

<body class="bg-light">
    @auth
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
        <div class="container-fluid px-4">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="{{ route('chat.index') }}">
                <div class="brand-icon me-2">
                    <i class="fas fa-comments text-primary fs-4"></i>
                </div>
                <span class="fw-bold fs-5">{{ config('app.name', 'DocNow Chat') }}</span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Desktop Navigation -->
            <div class="navbar-nav ms-auto d-none d-lg-flex flex-row">
                <a class="nav-link px-3 {{ request()->routeIs('chat.*') ? 'active' : '' }}" href="{{ route('chat.index') }}">
                    <i class="fas fa-comments me-2"></i>Chat
                </a>

                @if(auth()->user()->is_admin)
                <a class="nav-link px-3 {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a class="nav-link px-3 {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}" href="{{ route('admin.contacts.index') }}">
                    <i class="fas fa-users me-2"></i>Contacts
                </a>
                @endif

                <!-- User Dropdown -->
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center px-3" href="#" data-bs-toggle="dropdown">
                        <div class="user-avatar-sm me-2">
                            @if(auth()->user()->avatar)
                            <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="Avatar" class="rounded-circle">
                            @else
                            {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
                            @endif
                        </div>
                        <span class="d-none d-xl-inline">{{ auth()->user()->first_name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <div class="dropdown-header">
                                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                <div class="small text-muted">{{ auth()->user()->email }}</div>
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="{{ route('profile') }}">
                                <i class="fas fa-user me-2"></i>Profile Settings
                            </a></li>
                        @if(auth()->user()->is_admin)
                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-cog me-2"></i>Admin Panel
                            </a></li>
                        @endif
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="dropdown-item p-0">
                                @csrf
                                <button type="submit" class="btn btn-link dropdown-item text-danger border-0 text-start w-100">
                                    <i class="fas fa-sign-out-alt me-2"></i>Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Offcanvas Menu -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div class="list-group list-group-flush">
                <a href="{{ route('chat.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                    <i class="fas fa-comments me-3"></i>Chat
                </a>
                @if(auth()->user()->is_admin)
                <a href="{{ route('admin.dashboard') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt me-3"></i>Dashboard
                </a>
                <a href="{{ route('admin.contacts.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">
                    <i class="fas fa-users me-3"></i>Contacts
                </a>
                @endif
                <a href="{{ route('profile') }}" class="list-group-item list-group-item-action {{ request()->routeIs('profile') ? 'active' : '' }}">
                    <i class="fas fa-user me-3"></i>Profile Settings
                </a>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="list-group-item list-group-item-action text-danger border-0 text-start w-100">
                        <i class="fas fa-sign-out-alt me-3"></i>Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endauth

    <!-- Main Content -->
    <main class="@guest min-vh-100 @endguest">
        @yield('content')
    </main>

    <!-- Notification Container -->
    <div id="notification-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

    <!-- Toast Notifications -->
    @if(session('success'))
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
            <div class="toast-header bg-success text-white">
                <i class="fas fa-check-circle me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('success') }}
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
            <div class="toast-header bg-danger text-white">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('error') }}
            </div>
        </div>
    </div>
    @endif

    @livewireScripts

    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Emoji Picker CDN -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js"></script>
    
    <!-- Application JavaScript -->
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/chat.js') }}"></script>

    <!-- Real-time connection status -->
    <div id="connectionStatus" class="position-fixed bottom-0 start-0 m-3 d-none" style="z-index: 1050;">
        <div class="alert alert-warning d-flex align-items-center mb-0">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            Reconnecting...
        </div>
    </div>

    @stack('scripts')

    <script>
        // Global app configuration
        window.App = {
            user: @json(auth()->user()),
            csrfToken: '{{ csrf_token() }}',
            baseUrl: '{{ url(' / ') }}',
        };

        // Theme color management (light theme only)
        function updateTheme(color) {
            if (color) {
                const rgb = hexToRgb(color);
                const darker = adjustBrightness(color, -30);

                document.documentElement.style.setProperty('--primary-color', color);
                document.documentElement.style.setProperty('--primary-rgb', `${rgb.r},${rgb.g},${rgb.b}`);
                document.documentElement.style.setProperty('--primary-dark', darker);

                // Update theme-color meta tag
                document.querySelector('meta[name="theme-color"]').setAttribute('content', color);

                // Save to localStorage for persistence
                localStorage.setItem('app-theme-color', color);
            }
        }

        function hexToRgb(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        }

        function adjustBrightness(hex, amount) {
            const num = parseInt(hex.replace("#", ""), 16);
            const amt = Math.round(2.55 * amount);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Apply saved color or user preference
            const savedColor = localStorage.getItem('app-theme-color') || '{{ auth()->user()->theme_color ?? "#6600ff" }}';

            updateTheme(savedColor);

            // Initialize dropdowns
            if (typeof bootstrap !== 'undefined') {
                document.querySelectorAll('.dropdown-toggle').forEach(function(dropdown) {
                    new bootstrap.Dropdown(dropdown);
                });
            }

            // Connection monitoring
            if (window.Echo) {
                window.Echo.connector.pusher.connection.bind('state_change', function(states) {
                    const connected = states.current === 'connected';
                    if (typeof Alpine !== 'undefined') {
                        Alpine.store('connection', {
                            connected
                        });
                    }
                });
            }
        });

        // Reinitialize dropdowns when Livewire updates content
        document.addEventListener('livewire:init', function() {
            setTimeout(function() {
                if (typeof bootstrap !== 'undefined') {
                    document.querySelectorAll('.dropdown-toggle').forEach(function(dropdown) {
                        new bootstrap.Dropdown(dropdown);
                    });
                }
            }, 100);
        });

        // Expose theme functions globally
        window.updateTheme = updateTheme;
    </script>
</body>

</html>