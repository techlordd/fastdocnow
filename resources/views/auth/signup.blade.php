@extends('layouts.app')

@section('title', '| Sign Up')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="bg-white rounded-4 shadow-lg overflow-hidden w-100" style="max-width: 1000px; min-height: 700px;">
        <div class="row g-0 h-100">
            <!-- Left Side - Branding -->
            <div class="col-md-5 d-flex flex-column justify-content-center p-5 text-white position-relative" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.1); z-index: 1;"></div>
                <div class="position-relative" style="z-index: 2;">
                    <div class="d-flex align-items-center mb-4">
                        <div class="d-flex align-items-center justify-content-center me-3 rounded-3" style="width: 50px; height: 50px; background: rgba(255,255,255,0.2);">
                            <i class="fas fa-rocket fs-4"></i>
                        </div>
                        <h1 class="h2 mb-0 fw-bold">Join DocNow</h1>
                    </div>
                    <p class="fs-5 mb-4 opacity-75">
                        Start your journey with DocNow Chat today! Connect with people around the world and experience next-generation communication.
                    </p>
                    <div class="d-none d-md-block">
                        <div class="d-flex align-items-center mb-3">
                            <div class="d-flex align-items-center justify-content-center me-3 rounded-3" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2);">
                                <i class="fas fa-gift"></i>
                            </div>
                            <span>Free to get started</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="d-flex align-items-center justify-content-center me-3 rounded-3" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span>Setup in under 2 minutes</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="d-flex align-items-center justify-content-center me-3 rounded-3" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2);">
                                <i class="fas fa-users"></i>
                            </div>
                            <span>Join thousands of users</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="d-flex align-items-center justify-content-center me-3 rounded-3" style="width: 40px; height: 40px; background: rgba(255,255,255,0.2);">
                                <i class="fas fa-headset"></i>
                            </div>
                            <span>24/7 customer support</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Signup Form -->
            <div class="col-md-7 d-flex flex-column justify-content-center p-4">
                <div class="text-center mb-4">
                    <h2 class="fw-semibold mb-2">Create Your Account</h2>
                    <p class="text-muted mb-0">Fill in your details to get started</p>
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

                <form method="POST" action="{{ route('signup') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label fw-medium">First Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="{{ old('first_name') }}"
                                   placeholder="John" 
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label fw-medium">Last Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="{{ old('last_name') }}"
                                   placeholder="Doe" 
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}"
                               placeholder="john.doe@example.com" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label fw-medium">Username</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               value="{{ old('username') }}"
                               placeholder="johndoe" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-medium">Password</label>
                        <div class="position-relative">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Create a strong password" 
                                   required>
                            <button type="button" 
                                    class="btn position-absolute top-50 end-0 translate-middle-y pe-3" 
                                    onclick="togglePassword()"
                                    style="border: none; background: none;">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label fw-medium">Confirm Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               placeholder="Confirm your password" 
                               required>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                        <label class="form-check-label small" for="agree_terms">
                            I agree to the <a href="#" class="text-decoration-none" style="color: #28a745;">Terms of Service</a> and <a href="#" class="text-decoration-none" style="color: #28a745;">Privacy Policy</a>
                        </label>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="email_updates" name="email_updates">
                        <label class="form-check-label small" for="email_updates">
                            I'd like to receive product updates and news via email
                        </label>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 mb-4">
                        Create Account
                    </button>
                </form>

                <div class="text-center position-relative my-3">
                    <hr>
                    <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small">or sign up with</span>
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-secondary w-100">
                            <i class="fab fa-google me-2"></i>
                            Google
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-secondary w-100">
                            <i class="fab fa-github me-2"></i>
                            GitHub
                        </button>
                    </div>
                </div>

                <div class="text-center">
                    <span class="text-muted">Already have an account? </span>
                    <a href="{{ route('login') }}" class="text-decoration-none fw-semibold" style="color: #28a745;">Sign in here</a>
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
</script>
@endsection
