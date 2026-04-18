@extends('layouts.app')

@section('title', '| Sign In')

@section('content')
<style>
    .btn-success {
        --bs-btn-bg: #2477c8;
        --bs-btn-hover-bg: #0b578c;
    }
</style>
<div class="min-vh-100 d-flex align-items-center justify-content-center p-4" style="background: #fff;">
    <div class="bg-white rounded-4 shadow-lg overflow-hidden w-100 d-flex" style="max-width: 1000px; min-height: 700px;">
        <div class="row g-0">
            <!-- Left Side - Branding -->
            <div class="col-md-5 d-flex flex-column justify-content-center p-5 text-white position-relative" style="background: linear-gradient(135deg, #2a86e4 0%, #0b609a 100%);">
                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.1); z-index: 1;"></div>
                <div class="position-relative" style="z-index: 2;">
                    <div class="d-flex align-items-center mb-4 justify-content-center justify-sm-content-start ">
                        <div class="d-flex align-items-center justify-content-center me-3 rounded-3" style="width: 50px; height: 50px; background: rgba(255,255,255,0.2);">
                            <i class="fas fa-comments fs-4"></i>
                        </div>
                        <h1 class="h2 mb-0 fw-bold">Welcome Back</h1>
                    </div>
                    <p class="fs-5 mb-4 opacity-75 text-center text-sm-start">
                        Sign in to continue your conversations with DocNow Chat. Stay connected and productive.
                    </p>
                    <div class="d-none d-md-block">
                        <div class="d-flex align-items-center mb-3">
                            <div class="d-flex align-items-center justify-content-center me-3 rounded-3" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2);">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <span>Lightning fast messaging</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="d-flex align-items-center justify-content-center me-3 rounded-3" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2);">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <span>Secure & private chats</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="d-flex align-items-center justify-content-center me-3 rounded-3" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2);">
                                <i class="fas fa-users"></i>
                            </div>
                            <span>Perfect for teams</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Login Form -->
            <div class="col-md-7 d-flex flex-column justify-content-center p-4">
                <div class="text-center mb-4">
                    <h2 class="fw-semibold mb-2 d-flex align-items-center justify-content-center">Sign In to <div class="brand_logo ms-2">
                            <img src="{{asset('images/FastDocNow-logo.png')}}" alt="">
                        </div>
                    </h2>
                    <p class="text-muted mb-0">Use your credentials to access your account</p>
                </div>

                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <input type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="you@example.com"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-medium">Password</label>
                        <div class="position-relative">
                            <input type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required>
                            <button type="button"
                                class="btn position-absolute top-50 end-0 translate-middle-y pe-3"
                                onclick="togglePassword()"
                                style="border: none; background: none;">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label small" for="remember">
                                Remember me
                            </label>
                        </div>
                        <a href="{{ route('password.request') }}" class="text-decoration-none small">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 mb-4">
                        Sign In
                    </button>
                </form>



                <div class="text-center">
                    <span class="text-muted">Don't have an account? </span>
                    <a href="{{ route('signup') }}" class="text-decoration-none fw-semibold" style="color: #4b8bc7;">Create one now</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    // Form submission handling
    const authForm = document.querySelector('.auth-form');
    if (authForm) {
        authForm.addEventListener('submit', function() {
            const submitBtn = document.querySelector('.auth-submit-btn');
            const btnText = submitBtn ? submitBtn.querySelector('.btn-text') : null;
            const btnLoader = submitBtn ? submitBtn.querySelector('.btn-loader') : null;

            if (submitBtn) {
                submitBtn.disabled = true;
            }
            if (btnText) {
                btnText.style.opacity = '0';
            }
            if (btnLoader) {
                btnLoader.classList.remove('d-none');
            }
        });
    }

    // Social login handlers
    document.querySelectorAll('.social-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Add social login logic here
            console.log('Social login clicked:', this.textContent.trim());
        });
    });
</script>
@endsection