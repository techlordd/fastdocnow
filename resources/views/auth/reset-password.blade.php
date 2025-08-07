@extends('layouts.app')

@section('title', '| Reset Password')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center p-4" style="background: linear-gradient(to bottom right, #3b82f6, #6366f1, #8b5cf6);">
    <div class="bg-white rounded-4 shadow-lg overflow-hidden w-100" style="max-width: 500px;">
        <div class="p-5">
            <div class="text-center mb-4">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 64px; height: 64px; background-color: #e0f0ff;">
                    <i class="fas fa-key text-primary fs-4"></i>
                </div>
                <h2 class="fw-bold text-dark mb-1">Reset Password</h2>
                <p class="text-muted">Enter your new password below.</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">New Password</label>
                    <div class="position-relative">
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               placeholder="Enter new password"
                               required>
                        <button type="button" 
                                class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2" 
                                onclick="togglePassword('password', this)">
                            <i class="fas fa-eye text-muted"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation" 
                           class="form-control" 
                           placeholder="Confirm new password"
                           required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                    Reset Password
                </button>
            </form>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-decoration-none text-primary fw-medium">
                    <i class="fas fa-arrow-left me-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>
<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>
@endsection
