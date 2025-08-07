@extends('layouts.app')

@section('title', '| Forgot Password')

@section('content')
<div class="container d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="card shadow border-0" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i class="fas fa-lock text-primary fs-4"></i>
                </div>
                <h4 class="mb-1 fw-bold">Forgot Password?</h4>
                <p class="text-muted small">No worries, we'll send you reset instructions.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success small mb-3">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label small fw-medium">Email address</label>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="you@example.com"
                           required>
                    @error('email')
                        <div class="invalid-feedback small">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary">
                        Send Reset Link
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="text-decoration-none text-primary small">
                    <i class="fas fa-arrow-left me-1"></i>Back to Login
                </a>
            </div>
        </div>
    </div>
</div>


@endsection
