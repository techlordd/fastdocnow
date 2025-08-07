@extends('layouts.app')

@section('title', '| Settings')

@section('content')
<div class="min-vh-100 bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('chat.index') }}" class="btn btn-link text-decoration-none p-0 me-3">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h4 class="mb-0 fw-semibold">Settings</h4>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show m-4 mb-0" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Navigation Tabs -->
                        <nav class="border-bottom">
                            <div class="nav nav-tabs border-0" id="nav-tab" role="tablist">
                                <button class="nav-link active border-0 rounded-0" id="nav-password-tab" data-bs-toggle="tab" data-bs-target="#nav-password" type="button" role="tab">
                                    <i class="fas fa-lock me-2"></i>Security
                                </button>
                                <button class="nav-link border-0 rounded-0" id="nav-notifications-tab" data-bs-toggle="tab" data-bs-target="#nav-notifications" type="button" role="tab">
                                    <i class="fas fa-bell me-2"></i>Notifications
                                </button>
                                <button class="nav-link border-0 rounded-0" id="nav-privacy-tab" data-bs-toggle="tab" data-bs-target="#nav-privacy" type="button" role="tab">
                                    <i class="fas fa-shield-alt me-2"></i>Privacy
                                </button>
                            </div>
                        </nav>

                        <!-- Tab Content -->
                        <div class="tab-content p-4" id="nav-tabContent">
                            <!-- Security Tab -->
                            <div class="tab-pane fade show active" id="nav-password" role="tabpanel">
                                <h5 class="fw-semibold mb-4">Change Password</h5>
                                
                                @if($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('settings.password') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label fw-medium">Current Password</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="current_password" 
                                               name="current_password" 
                                               required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-medium">New Password</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               required
                                               minlength="8">
                                        <div class="form-text">Password must be at least 8 characters long</div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password_confirmation" class="form-label fw-medium">Confirm New Password</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password_confirmation" 
                                               name="password_confirmation" 
                                               required>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>
                                        Update Password
                                    </button>
                                </form>
                            </div>

                            <!-- Notifications Tab -->
                            <div class="tab-pane fade" id="nav-notifications" role="tabpanel">
                                <h5 class="fw-semibold mb-4">Notification Preferences</h5>
                                
                                <form method="POST" action="{{ route('settings.notifications') }}">
                                    @csrf
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                            <div>
                                                <h6 class="mb-1">Email Notifications</h6>
                                                <small class="text-muted">Receive notifications via email</small>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="email_notifications" 
                                                       name="email_notifications"
                                                       {{ $user->email_notifications ? 'checked' : '' }}>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                            <div>
                                                <h6 class="mb-1">Push Notifications</h6>
                                                <small class="text-muted">Receive push notifications in browser</small>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="push_notifications" 
                                                       name="push_notifications"
                                                       {{ $user->push_notifications ? 'checked' : '' }}>
                                            </div>
                                        </div>

                                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                            <div>
                                                <h6 class="mb-1">Sound Notifications</h6>
                                                <small class="text-muted">Play sound for new messages</small>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="sound_notifications" 
                                                       name="sound_notifications"
                                                       {{ $user->sound_notifications ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-bell me-2"></i>
                                            Save Notification Settings
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Privacy Tab -->
                            <div class="tab-pane fade" id="nav-privacy" role="tabpanel">
                                <h5 class="fw-semibold mb-4">Privacy Settings</h5>
                                
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item border-0 px-0">
                                        <h6 class="mb-2">Last Seen</h6>
                                        <p class="text-muted small mb-3">Control who can see when you were last online</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="last_seen" id="last_seen_everyone" value="everyone" checked>
                                            <label class="form-check-label" for="last_seen_everyone">Everyone</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="last_seen" id="last_seen_contacts" value="contacts">
                                            <label class="form-check-label" for="last_seen_contacts">My Contacts</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="last_seen" id="last_seen_nobody" value="nobody">
                                            <label class="form-check-label" for="last_seen_nobody">Nobody</label>
                                        </div>
                                    </div>

                                    <div class="list-group-item border-0 px-0 border-top pt-4">
                                        <h6 class="mb-2">Profile Photo</h6>
                                        <p class="text-muted small mb-3">Control who can see your profile photo</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="profile_photo" id="photo_everyone" value="everyone" checked>
                                            <label class="form-check-label" for="photo_everyone">Everyone</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="profile_photo" id="photo_contacts" value="contacts">
                                            <label class="form-check-label" for="photo_contacts">My Contacts</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="profile_photo" id="photo_nobody" value="nobody">
                                            <label class="form-check-label" for="photo_nobody">Nobody</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="button" class="btn btn-outline-secondary">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        Save Privacy Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="card shadow-sm mt-4 border-danger">
                    <div class="card-header bg-light border-danger">
                        <h5 class="mb-0 fw-semibold text-danger">Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Delete Account</h6>
                                <small class="text-muted">Permanently delete your account and all associated data</small>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                <p class="text-muted small">All your conversations, messages, and personal data will be permanently removed.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger">Delete My Account</button>
            </div>
        </div>
    </div>
</div>
@endsection
