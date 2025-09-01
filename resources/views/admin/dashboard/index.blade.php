@extends('layouts.app')

@section('title', '- Admin Dashboard')

@section('content')
<style>
    	.text-primary {
            color: var(--primary-color)!important;
        }
        .scroll_table {
            max-height: 400px;
            overflow-y: auto;
        }
</style>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold text-dark">
                <i class="fas fa-tachometer-alt text-primary me-2"></i>
                Admin Dashboard
            </h1>
            <p class="text-muted mb-0 mt-1">Monitor system performance and manage configurations</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="refreshStats()" class="btn btn-outline-primary">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
            <a href="{{ route('admin.contacts.index') }}" class="btn btn-primary">
                <i class="fas fa-users me-2"></i>Manage Contacts
            </a>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card modern-stats-card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stats-icon-modern bg-primary bg-opacity-10 text-primary mb-3">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="fw-bold mb-1 text-dark">{{ number_format($stats['total_users']) }}</h3>
                            <p class="text-muted mb-0 small">Total Users</p>
                        </div>
                        <div class="text-end">
                            <div class="trend-indicator bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill small fw-medium">
                                <i class="fas fa-arrow-up me-1"></i>+{{ $stats['total_admins'] }}
                            </div>
                            <div class="small text-muted mt-1">admins</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card modern-stats-card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stats-icon-modern bg-success bg-opacity-10 text-success mb-3">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3 class="fw-bold mb-1 text-dark">{{ number_format($stats['total_conversations']) }}</h3>
                            <p class="text-muted mb-0 small">Total Conversations</p>
                        </div>
                        <div class="text-end">
                            <div class="trend-indicator bg-info bg-opacity-10 text-info px-2 py-1 rounded-pill small fw-medium">
                                <i class="fas fa-pulse me-1"></i>{{ $stats['active_conversations'] }}
                            </div>
                            <div class="small text-muted mt-1">active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card modern-stats-card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stats-icon-modern bg-warning bg-opacity-10 text-warning mb-3">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h3 class="fw-bold mb-1 text-dark">{{ number_format($stats['messages_today']) }}</h3>
                            <p class="text-muted mb-0 small">Messages Today</p>
                        </div>
                        <div class="text-end">
                            <div class="trend-indicator bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-pill small fw-medium">
                                {{ number_format($stats['total_messages']) }}
                            </div>
                            <div class="small text-muted mt-1">total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card modern-stats-card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stats-icon-modern bg-danger bg-opacity-10 text-danger mb-3">
                                <i class="fas fa-wifi"></i>
                            </div>
                            <h3 class="fw-bold mb-1 text-dark">{{ number_format($stats['online_users']) }}</h3>
                            <p class="text-muted mb-0 small">Users Online</p>
                        </div>
                        <div class="text-end">
                            <div class="online-pulse">
                                <span class="pulse-dot bg-success"></span>
                            </div>
                            <div class="small text-muted mt-1">live</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title fw-bold mb-1">Recent Activity</h5>
                            <p class="text-muted small mb-0">Latest messages and interactions</p>
                        </div>
                    </div>
                </div>
                <div class="card-body scroll_table">
                    @if(count($recentMessages) > 0)
                        <div class="activity-list">
                            @foreach($recentMessages as $message)
                                <div class="activity-item d-flex align-items-center py-3 border-bottom">
                                    <div class="activity-avatar me-3">
                                        <div class="user-avatar-sm bg-primary bg-opacity-10 text-primary">
                                            {{ strtoupper(substr($message['user'], 0, 1)) }}
                                        </div>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <h6 class="mb-0 fw-medium">{{ $message['user'] }}</h6>
                                            <small class="text-muted">{{ $message['created_at'] }}</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-primary bg-opacity-10 text-primary small px-2 py-1">
                                                {{ $message['contact'] }}
                                            </span>
                                            @if($message['type'] !== 'text')
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary small px-2 py-1">
                                                    <i class="fas fa-paperclip me-1"></i>{{ ucfirst($message['type']) }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-muted small mb-0 message-preview">
                                            @if($message['type'] === 'text')
                                                {{ $message['content'] }}
                                            @else
                                                <em>Shared {{ $message['type'] }} file</em>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="empty-state-icon mb-3">
                                <i class="fas fa-comments text-muted"></i>
                            </div>
                            <h6 class="text-muted">No recent activity</h6>
                            <p class="text-muted small mb-0">Messages will appear here as users interact</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="row g-4">
                <!-- System Health -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h5 class="card-title fw-bold mb-0">System Health</h5>
                        </div>
                        <div class="card-body">
                            <div class="health-metric mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-medium">Active Contacts</span>
                                    <span class="small text-muted">{{ $stats['active_contacts'] }}/{{ $stats['total_contacts'] }}</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" style="width: {{ $stats['total_contacts'] > 0 ? ($stats['active_contacts'] / $stats['total_contacts']) * 100 : 0 }}%"></div>
                                </div>
                            </div>

                            <div class="health-metric mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-medium">Daily Message Goal</span>
                                    <span class="small text-muted">{{ $stats['messages_today'] }}/100</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-primary" style="width: {{ min(100, ($stats['messages_today'] / 100) * 100) }}%"></div>
                                </div>
                            </div>

                            <div class="health-metric">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-medium">System Uptime</span>
                                    <span class="small text-success">99.9%</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" style="width: 99.9%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h5 class="card-title fw-bold mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.contacts.index') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-users me-2"></i>Manage Contacts
                                </a>
                                <button onclick="showEmailTest()" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-envelope me-2"></i>Test Email
                                </button>
                                <a href="{{ route('profile') }}" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Tabs -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <ul class="nav nav-pills card-header-pills" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="pill" href="#email-config" role="tab">
                        <i class="fas fa-envelope me-2"></i>Email Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="pill" href="#theme-config" role="tab">
                        <i class="fas fa-palette me-2"></i>Appearance
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Email Configuration -->
                <div class="tab-pane fade show active" id="email-config" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8">
                            <form id="emailConfigForm">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label fw-medium">SMTP Host</label>
                                        <input type="text" class="form-control" name="smtp_host"
                                               value="{{ $systemConfig['email']['smtp_host'] ?? '' }}"
                                               placeholder="smtp.gmail.com" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">Port</label>
                                        <input type="number" class="form-control" name="smtp_port"
                                               value="{{ $systemConfig['email']['smtp_port'] ?? 587 }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">Username</label>
                                        <input type="text" class="form-control" name="smtp_username"
                                               value="{{ $systemConfig['email']['smtp_username'] ?? '' }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">Password</label>
                                        <input type="password" class="form-control" name="smtp_password"
                                               placeholder="Enter SMTP password" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">Encryption</label>
                                        <select class="form-select" name="smtp_encryption" required>
                                            <option value="tls" {{ ($systemConfig['email']['smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                                            <option value="ssl" {{ ($systemConfig['email']['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                            <option value="none" {{ ($systemConfig['email']['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">From Email</label>
                                        <input type="email" class="form-control" name="mail_from_address"
                                               value="{{ $systemConfig['email']['mail_from_address'] ?? '' }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">From Name</label>
                                        <input type="text" class="form-control" name="mail_from_name"
                                               value="{{ $systemConfig['email']['mail_from_name'] ?? '' }}" required>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <span class="button-text">Save Email Settings</span>
                                        <span class="button-spinner spinner-border spinner-border-sm ms-2 d-none"></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="showEmailTest()">
                                        <i class="fas fa-paper-plane me-1"></i>Test Configuration
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-4">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Email Setup Guide</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Gmail:</strong> Use App Password for authentication</li>
                                    <li><strong>Outlook:</strong> Enable SMTP authentication</li>
                                    <li><strong>Custom SMTP:</strong> Contact your provider for settings</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- System Theme -->
                <div class="tab-pane fade" id="theme-config" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8">
                            <form id="systemThemeForm" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">Application Name</label>
                                        <input type="text" class="form-control" name="app_name"
                                               value="{{ $systemConfig['theme']['app_name'] ?? 'Chat System' }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium">Interface Style</label>
                                        <select class="form-select" name="default_theme" required>
                                            <option value="light" selected>Light Theme (Professional)</option>
                                        </select>
                                        <div class="form-text">Clean, professional light interface for all users</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">Primary Color</label>
                                        <input type="color" class="form-control form-control-color w-100" name="primary_color"
                                               value="{{ $systemConfig['theme']['primary_color'] ?? '#6600ff' }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">Secondary Color</label>
                                        <input type="color" class="form-control form-control-color w-100" name="secondary_color"
                                               value="{{ $systemConfig['theme']['secondary_color'] ?? '#4400cc' }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-medium">Accent Color</label>
                                        <input type="color" class="form-control form-control-color w-100" name="accent_color"
                                               value="{{ $systemConfig['theme']['accent_color'] ?? '#22c55e' }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-medium">Logo Upload</label>
                                        <input type="file" class="form-control" name="app_logo" accept="image/*">
                                        <div class="form-text">Recommended size: 200x50px. Supports JPG, PNG, SVG</div>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="button-text">Save Appearance Settings</span>
                                        <span class="button-spinner spinner-border spinner-border-sm ms-2 d-none"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-4">
                            <div class="alert alert-success">
                                <h6 class="alert-heading">Global Appearance Settings</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Global Impact:</strong> Changes apply to all users</li>
                                    <li><strong>Primary color:</strong> Affects buttons, links, and UI accents</li>
                                    <li><strong>Logo:</strong> Will appear in navigation for all users</li>
                                    <li><strong>Consistent branding:</strong> Maintains uniform appearance across the system</li>
                                    <li><strong>Professional interface:</strong> Clean light theme for business use</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Modals -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Email Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testEmailForm">
                    <div class="mb-3">
                        <label class="form-label">Test Email Address</label>
                        <input type="email" class="form-control" name="test_email"
                               value="{{ auth()->user()->email }}" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestEmail()">Send Test Email</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="testSmsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test SMS Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testSmsForm">
                    <div class="mb-3">
                        <label class="form-label">Test Phone Number</label>
                        <input type="tel" class="form-control" name="test_phone"
                               placeholder="+1234567890" required>
                        <div class="form-text">Include country code</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestSMS()">Send Test SMS</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Modern Dashboard Styles */
.modern-stats-card {
    transition: all 0.3s ease;
    border-radius: 16px !important;
}

.modern-stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}

.stats-icon-modern {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.trend-indicator {
    border-radius: 20px;
    font-size: 0.75rem;
}

.online-pulse {
    position: relative;
    width: 24px;
    height: 24px;
    margin: 0 auto;
}

.pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
    }
}

.activity-list {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item:last-child {
    border-bottom: none !important;
}

.user-avatar-sm {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}

.message-preview {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.health-metric .progress {
    height: 6px;
    border-radius: 3px;
}

.progress-sm {
    height: 6px;
}

.empty-state-icon {
    font-size: 3rem;
    opacity: 0.3;
}

.nav-pills .nav-link {
    border-radius: 10px;
    padding: 10px 16px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-pills .nav-link:hover {
    background-color: rgba(var(--primary-rgb), 0.1);
    color: var(--primary-color);
}

.nav-pills .nav-link.active {
    background-color: var(--primary-color);
    color: white;
}

.card {
    border-radius: 12px !important;
    border: 1px solid rgba(0,0,0,0.08) !important;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e0e6ed;
    padding: 0.625rem 0.875rem;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.5rem 1rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .modern-stats-card .card-body {
        padding: 1.25rem !important;
    }

    .stats-icon-modern {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .activity-item {
        padding: 0.75rem 0 !important;
    }

    .message-preview {
        max-width: 200px;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Toast functions using SweetAlert2
function showSuccessToast(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
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
        timerProgressBar: true
    });
    Toast.fire({
        icon: 'error',
        title: message
    });
}

function refreshStats() {
    showSuccessToast('Refreshing dashboard...');
    setTimeout(() => window.location.reload(), 1000);
}

function showEmailTest() {
    const modal = new bootstrap.Modal(document.getElementById('testEmailModal'));
    modal.show();
}

function showSMSTest() {
    const modal = new bootstrap.Modal(document.getElementById('testSmsModal'));
    modal.show();
}

function sendTestEmail() {
    const form = document.getElementById('testEmailForm');
    const formData = new FormData(form);

    fetch('/admin/dashboard/test-email', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            bootstrap.Modal.getInstance(document.getElementById('testEmailModal')).hide();
        } else {
            showErrorToast(data.message);
        }
    })
    .catch(error => showErrorToast('Failed to send test email'));
}

function sendTestSMS() {
    const form = document.getElementById('testSmsForm');
    const formData = new FormData(form);

    fetch('/admin/dashboard/test-sms', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            bootstrap.Modal.getInstance(document.getElementById('testSmsModal')).hide();
        } else {
            showErrorToast(data.message);
        }
    })
    .catch(error => showErrorToast('Failed to send test SMS'));
}

// Form handlers
document.addEventListener('DOMContentLoaded', function() {
    handleConfigForm('emailConfigForm', '/admin/dashboard/email-config');
    handleConfigForm('systemThemeForm', '/admin/dashboard/theme-config');
});

function handleConfigForm(formId, endpoint) {
    const form = document.getElementById(formId);
    if (!form) {
        console.warn(`Form with ID '${formId}' not found`);
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const button = form.querySelector('button[type="submit"]');
        const buttonText = button.querySelector('.button-text');
        const buttonSpinner = button.querySelector('.button-spinner');

        button.disabled = true;
        buttonSpinner.classList.remove('d-none');

        console.log(`Submitting form ${formId} to ${endpoint}`);

        fetch(endpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                showSuccessToast(data.message);

                // Update theme color immediately if theme config
                if (formId === 'systemThemeForm' && data.config) {
                    console.log('Updating theme with config:', data.config);
                    if (data.config.primary_color) {
                        // Update current page
                        if (window.updateTheme) {
                            window.updateTheme(data.config.primary_color);
                            console.log('Theme updated to:', data.config.primary_color);
                        } else {
                            console.warn('updateTheme function not available');
                        }

                        // Broadcast change to all tabs/windows via localStorage
                        localStorage.setItem('theme-updated', Date.now());

                        // Also reload theme for any other pages
                        if (window.loadCurrentTheme) {
                            setTimeout(() => {
                                console.log('Reloading current theme...');
                                window.loadCurrentTheme();
                            }, 500);
                        } else {
                            console.warn('loadCurrentTheme function not available');
                        }
                    }
                }
            } else {
                console.error('Server returned error:', data.message);
                showErrorToast(data.message || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showErrorToast('An error occurred while saving configuration: ' + error.message);
        })
        .finally(() => {
            button.disabled = false;
            buttonSpinner.classList.add('d-none');
        });
    });
}
</script>
@endpush
