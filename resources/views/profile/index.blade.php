@extends('layouts.app')

@section('title', '- Profile Settings')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-xxl-10 col-xl-11">
            <!-- Profile Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="profile-avatar-large me-2">
                                    @if(auth()->user()->avatar)
                                    <img src="{{ asset('public/storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}"
                                        class="rounded-circle me-2" width="50" height="50">
                                    @else
                                    <div class="user-avatar me-2" style="width: 50px; height: 50px; font-size: 14px;">
                                        {{ auth()->user()->initials }}
                                    </div>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="fw-bold mb-1">{{ $user->name }}</h3>
                                    <p class="text-muted mb-2">{{ $user->email }}</p>
                                    <div class="d-flex gap-2">
                                        @if($user->is_admin)
                                        <span class="badge bg-primary px-3 py-2">
                                            <i class="fas fa-shield-alt me-1"></i>Administrator
                                        </span>
                                        @endif
                                        <span class="badge bg-success px-3 py-2">
                                            <i class="fas fa-circle me-1"></i>Online
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <div class="stat-value">{{ $user->created_at->format('M Y') }}</div>
                                    <div class="stat-label">Member Since</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">{{ $user->last_seen_at ? $user->last_seen_at->diffForHumans() : 'Never' }}</div>
                                    <div class="stat-label">Last Active</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Navigation & Content -->
            <div class="row g-4">
                <!-- Settings Navigation -->
                <div class="col-lg-3">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 1rem;">
                        <div class="card-body p-0">
                            <div class="nav nav-pills flex-column settings-nav" role="tablist">
                                <a class="nav-link active" data-bs-toggle="pill" href="#profile-tab" role="tab">
                                    <div class="nav-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="nav-content">
                                        <h6 class="nav-title">Profile Information</h6>
                                        <p class="nav-subtitle">Basic details and bio</p>
                                    </div>
                                </a>

                                <a class="nav-link" data-bs-toggle="pill" href="#security-tab" role="tab">
                                    <div class="nav-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div class="nav-content">
                                        <h6 class="nav-title">Security</h6>
                                        <p class="nav-subtitle">Password and authentication</p>
                                    </div>
                                </a>

                                <a class="nav-link" data-bs-toggle="pill" href="#notifications-tab" role="tab">
                                    <div class="nav-icon">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <div class="nav-content">
                                        <h6 class="nav-title">Notifications</h6>
                                        <p class="nav-subtitle">Email and sound alerts</p>
                                    </div>
                                </a>



                                <a class="nav-link text-danger" data-bs-toggle="pill" href="#danger-tab" role="tab">
                                    <div class="nav-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="nav-content">
                                        <h6 class="nav-title">Danger Zone</h6>
                                        <p class="nav-subtitle">Account deletion</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="col-lg-9">
                    <div class="tab-content">
                        <!-- Profile Information -->
                        <div class="tab-pane fade show active" id="profile-tab" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h5 class="card-title fw-bold mb-1">Profile Information</h5>
                                    <p class="text-muted small mb-0">Update your personal details and profile picture</p>
                                </div>
                                <div class="card-body">
                                    <form id="profileForm" enctype="multipart/form-data">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">First Name</label>
                                                <input type="text" class="form-control" name="first_name" value="{{ $user->first_name }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Last Name</label>
                                                <input type="text" class="form-control" name="last_name" value="{{ $user->last_name }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Username</label>
                                                <input type="text" class="form-control" name="username" value="{{ $user->username }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Email Address</label>
                                                <input type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Phone Number</label>
                                                <input type="tel" class="form-control" name="phone" value="{{ $user->phone }}" placeholder="+1 (555) 123-4567">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Timezone</label>
                                                <select class="form-select" name="timezone">
                                                    <option value="">Select Timezone</option>
                                                    <option value="UTC" {{ $user->timezone === 'UTC' ? 'selected' : '' }}>UTC (Coordinated Universal Time)</option>
                                                    <option value="America/New_York" {{ $user->timezone === 'America/New_York' ? 'selected' : '' }}>Eastern Time (EST/EDT)</option>
                                                    <option value="America/Chicago" {{ $user->timezone === 'America/Chicago' ? 'selected' : '' }}>Central Time (CST/CDT)</option>
                                                    <option value="America/Denver" {{ $user->timezone === 'America/Denver' ? 'selected' : '' }}>Mountain Time (MST/MDT)</option>
                                                    <option value="America/Los_Angeles" {{ $user->timezone === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time (PST/PDT)</option>
                                                    <option value="Europe/London" {{ $user->timezone === 'Europe/London' ? 'selected' : '' }}>London (GMT/BST)</option>
                                                    <option value="Europe/Paris" {{ $user->timezone === 'Europe/Paris' ? 'selected' : '' }}>Paris (CET/CEST)</option>
                                                    <option value="Asia/Tokyo" {{ $user->timezone === 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo (JST)</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium">Bio</label>
                                                <textarea class="form-control" name="bio" rows="4" placeholder="Tell us about yourself...">{{ $user->bio }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium">Profile Picture</label>
                                                <input type="file" class="form-control" name="avatar" accept="image/*">
                                                <div class="form-text">Maximum file size: 2MB. Supported formats: JPEG, PNG, JPG, GIF</div>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <span class="button-text">Update Profile</span>
                                                <span class="button-spinner spinner-border spinner-border-sm ms-2 d-none"></span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Security -->
                        <div class="tab-pane fade" id="security-tab" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h5 class="card-title fw-bold mb-1">Security Settings</h5>
                                    <p class="text-muted small mb-0">Manage your password and account security</p>
                                </div>
                                <div class="card-body">
                                    <form id="passwordForm">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-medium">Current Password</label>
                                                <input type="password" class="form-control" name="current_password" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">New Password</label>
                                                <input type="password" class="form-control" name="password" required>
                                                <div class="form-text">Password must be at least 8 characters long</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Confirm New Password</label>
                                                <input type="password" class="form-control" name="password_confirmation" required>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <span class="button-text">Update Password</span>
                                                <span class="button-spinner spinner-border spinner-border-sm ms-2 d-none"></span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>


                        </div>

                        <!-- Notifications -->
                        <div class="tab-pane fade" id="notifications-tab" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-transparent border-0 pb-0">
                                    <h5 class="card-title fw-bold mb-1">Notification Preferences</h5>
                                    <p class="text-muted small mb-0">Configure how you receive notifications and alerts</p>
                                </div>
                                <div class="card-body">
                                    <form id="notificationsForm">
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <h6 class="fw-semibold mb-3">Notification Types</h6>

                                                <div class="notification-item">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="email_notifications"
                                                            {{ $user->email_notifications ? 'checked' : '' }} id="email_notifications">
                                                        <label class="form-check-label" for="email_notifications">
                                                            <div class="notification-content">
                                                                <div class="notification-title">Email Notifications</div>
                                                                <div class="notification-subtitle">Receive important updates via email</div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>



                                                <div class="notification-item">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="push_notifications"
                                                            {{ $user->push_notifications ? 'checked' : '' }} id="push_notifications">
                                                        <label class="form-check-label" for="push_notifications">
                                                            <div class="notification-content">
                                                                <div class="notification-title">Browser Notifications</div>
                                                                <div class="notification-subtitle">Show desktop notifications</div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="notification-item">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="sound_notifications"
                                                            {{ $user->sound_notifications ? 'checked' : '' }} id="sound_notifications">
                                                        <label class="form-check-label" for="sound_notifications">
                                                            <div class="notification-content">
                                                                <div class="notification-title">Sound Alerts</div>
                                                                <div class="notification-subtitle">Play sound for new messages</div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <h6 class="fw-semibold mb-3">Contact Information</h6>

                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Notification Email</label>
                                                    <input type="email" class="form-control" name="notification_email"
                                                        value="{{ $user->notification_email ?: $user->email }}">
                                                    <div class="form-text">Leave empty to use your primary email</div>
                                                </div>



                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Notification Frequency</label>
                                                    <select class="form-select" name="notification_frequency">
                                                        <option value="instant" {{ $user->notification_frequency === 'instant' ? 'selected' : '' }}>Instant</option>
                                                        <option value="hourly" {{ $user->notification_frequency === 'hourly' ? 'selected' : '' }}>Hourly Summary</option>
                                                        <option value="daily" {{ $user->notification_frequency === 'daily' ? 'selected' : '' }}>Daily Summary</option>
                                                    </select>
                                                </div>

                                                <h6 class="fw-semibold mb-3 mt-4">Quiet Hours</h6>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <label class="form-label fw-medium">Start Time</label>
                                                        <input type="time" class="form-control" name="quiet_hours_start"
                                                            value="{{ $user->quiet_hours_start ? $user->quiet_hours_start->format('H:i') : '' }}">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fw-medium">End Time</label>
                                                        <input type="time" class="form-control" name="quiet_hours_end"
                                                            value="{{ $user->quiet_hours_end ? $user->quiet_hours_end->format('H:i') : '' }}">
                                                    </div>
                                                </div>
                                                <div class="form-text">No notifications will be sent during these hours</div>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <button type="submit" class="btn btn-primary">
                                                <span class="button-text">Update Notifications</span>
                                                <span class="button-spinner spinner-border spinner-border-sm ms-2 d-none"></span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>



                        <!-- Danger Zone -->
                        <div class="tab-pane fade" id="danger-tab" role="tabpanel">
                            <div class="card border-0 shadow-sm border-danger-subtle">
                                <div class="card-header bg-danger bg-opacity-10 border-0">
                                    <h5 class="card-title text-danger fw-bold mb-1">Danger Zone</h5>
                                    <p class="text-danger small mb-0 opacity-75">Irreversible and destructive actions</p>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-danger border-0">
                                        <div class="d-flex">
                                            <div class="me-3">
                                                <i class="fas fa-exclamation-triangle fs-4"></i>
                                            </div>
                                            <div>
                                                <h6 class="alert-heading">Delete Account</h6>
                                                <p class="mb-0">Once you delete your account, there is no going back. All your data, conversations, and settings will be permanently removed.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <form id="deleteForm">
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Confirm with your password</label>
                                            <input type="password" class="form-control" name="password"
                                                placeholder="Enter your current password" required>
                                        </div>

                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash-alt me-2"></i>
                                            <span class="button-text">Delete My Account</span>
                                            <span class="button-spinner spinner-border spinner-border-sm ms-2 d-none"></span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Profile Header Styles */
    .profile-avatar-large {
        width: 100px;
        height: 100px;
        position: relative;
        cursor: pointer;
    }

    .profile-avatar-large img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-placeholder-large {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
        font-weight: bold;
    }

    .avatar-upload-overlay {
        position: absolute;
        bottom: 0;
        right: 0;
        background: var(--primary-color);
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        border: 3px solid white;
        transition: all 0.3s ease;
    }

    .avatar-upload-overlay:hover {
        transform: scale(1.1);
    }

    .profile-stats {
        display: flex;
        gap: 2rem;
        justify-content: flex-end;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--primary-color);
    }

    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
    }

    /* Settings Navigation */
    .settings-nav {
        padding: 1rem 0;
    }

    .settings-nav .nav-link {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        border: none;
        border-radius: 0;
        color: #495057;
        text-decoration: none;
        transition: all 0.3s ease;
        margin: 0;
        border-left: 3px solid transparent;
    }

    .settings-nav .nav-link:hover {
        background-color: rgba(var(--primary-rgb), 0.05);
        color: var(--primary-color);
        border-left-color: rgba(var(--primary-rgb), 0.3);
    }

    .settings-nav .nav-link.active {
        background-color: rgba(var(--primary-rgb), 0.1);
        color: var(--primary-color);
        border-left-color: var(--primary-color);
    }

    .nav-icon {
        width: 40px;
        height: 40px;
        background: rgba(var(--primary-rgb), 0.1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 1.125rem;
        transition: all 0.3s ease;
    }

    .settings-nav .nav-link.active .nav-icon {
        background: var(--primary-color);
        color: white;
    }

    .nav-content {
        flex: 1;
    }

    .nav-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .nav-subtitle {
        font-size: 0.875rem;
        opacity: 0.7;
    }

    /* Notification Items */
    .notification-item {
        padding: 1rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .notification-content {
        margin-left: 1rem;
    }

    .notification-title {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .notification-subtitle {
        font-size: 0.875rem;
        color: #6c757d;
    }

    /* Color Preview */
    .color-preview-section {
        margin-top: 1rem;
    }

    .color-preview-card {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        background: #f9fafb;
        transition: all 0.3s ease;
    }

    .preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .preview-content {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .preview-link {
        margin-top: 0.5rem;
    }

    /* Color Picker */
    .color-picker-container {
        max-width: 300px;
    }

    .color-input {
        width: 100% !important;
        height: 50px !important;
        border-radius: 8px !important;
    }

    .color-presets {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
    }

    .color-preset {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .color-preset:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    .color-preset.active {
        transform: scale(1.15);
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 1), 0 0 0 5px var(--primary-color);
    }

    /* Form Enhancements */
    .form-control,
    .form-select {
        border-radius: 8px;
        border: 1px solid #e0e6ed;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.25);
    }

    .form-label {
        margin-bottom: 0.5rem;
    }

    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .form-check-input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--primary-rgb), 0.25);
    }

    /* Card Styling */
    .card {
        border-radius: 12px !important;
        border: 1px solid rgba(0, 0, 0, 0.08) !important;
    }

    .card-header {
        border-radius: 12px 12px 0 0 !important;
    }

    /* Responsive Design */
    @media (max-width: 992px) {
        .profile-stats {
            justify-content: flex-start;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: left;
        }

        .settings-nav {
            padding: 0.5rem 0;
        }

        .settings-nav .nav-link {
            padding: 0.75rem 1rem;
        }

        .nav-icon {
            width: 35px;
            height: 35px;
            margin-right: 0.75rem;
        }

        .theme-options {
            justify-content: center;
        }

        .color-presets {
            grid-template-columns: repeat(4, 1fr);
            justify-items: center;
        }
    }

    @media (max-width: 576px) {
        .profile-avatar-large {
            width: 80px;
            height: 80px;
        }

        .avatar-placeholder-large {
            width: 80px;
            height: 80px;
            font-size: 2rem;
        }

        .profile-stats {
            flex-direction: column;
            gap: 1rem;
        }

        .theme-preview {
            width: 100px;
            height: 70px;
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

    document.addEventListener('DOMContentLoaded', function() {
        // Color presets functionality
        document.querySelectorAll('.color-preset').forEach(preset => {
            preset.addEventListener('click', function() {
                // Remove active class from all presets
                document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
                // Add active class to clicked preset
                this.classList.add('active');

                const color = this.getAttribute('data-color');
                document.getElementById('themeColorPicker').value = color;

                // Update theme immediately for preview
                window.updateTheme(color);
            });
        });

        // Color picker change handler
        document.getElementById('themeColorPicker').addEventListener('change', function() {
            // Remove active class from all presets when using color picker
            document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));

            window.updateTheme(this.value);
        });

        // Form submissions
        handleForm('profileForm', '/profile/update');
        handleForm('passwordForm', '/profile/password');
        handleForm('notificationsForm', '/profile/notifications');
        handleForm('deleteForm', '/profile/delete', function(data) {
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        });
    });

    function previewTheme() {
        const color = document.getElementById('themeColorPicker').value;

        window.updateTheme(color);
        showSuccessToast('Color preview applied! Save to make it permanent.');
    }

    function handleForm(formId, endpoint, callback = null) {
        document.getElementById(formId).addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);
            const button = form.querySelector('button[type="submit"]');
            const buttonText = button.querySelector('.button-text');
            const buttonSpinner = button.querySelector('.button-spinner');

            // Show loading state
            button.disabled = true;
            buttonSpinner.classList.remove('d-none');

            fetch(endpoint, {
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
                        if (callback) {
                            callback(data);
                        }
                        if (formId === 'passwordForm') {
                            form.reset();
                        }
                    } else {
                        showErrorToast(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorToast('An error occurred while updating settings');
                })
                .finally(() => {
                    button.disabled = false;
                    buttonSpinner.classList.add('d-none');
                });
        });
    }
</script>
@endpush
