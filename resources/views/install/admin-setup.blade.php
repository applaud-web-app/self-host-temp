@extends('layouts.single-master')
@section('title', 'Admin Setup | Aplu')

@section('content')
<style>
    .admin-card {
        padding: 40px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .admin-heading {
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #2d3748;
    }

    .admin-subtitle {
        font-size: 1.1rem;
        color: #4a5568;
        margin-bottom: 2rem;
        line-height: 1.5;
    }

    .form-group {
        margin-bottom: 1.5rem;
        text-align: left;
        position: relative; /* Added for eye icon positioning */
    }

    .password-toggle {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        cursor: pointer;
        color: #718096;
    }
</style>

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="admin-card card">
                    <h1 class="admin-heading">👤 Admin Setup</h1>
                    <p class="admin-subtitle">
                        Set up your admin account to manage your Aplu platform.
                    </p>

                    <form id="setupForm" method="POST" action="{{ route('install.admin-setup.post') }}">
                        @csrf

                        <div class="form-group">
                            <label for="admin_email" class="form-label">Admin Email or Username <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                name="admin_email" 
                                id="admin_email" 
                                class="form-control" 
                                placeholder="Enter admin email or username" 
                                required
                            >
                            <div class="invalid-feedback">Please enter a valid email or username.</div>
                        </div>

                        <div class="form-group">
                            <label for="admin_password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input 
                                    type="password" 
                                    name="admin_password" 
                                    id="admin_password" 
                                    class="form-control" 
                                    placeholder="Enter password" 
                                    required
                                >
                                <span class="password-toggle" onclick="togglePassword('admin_password', this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="invalid-feedback">Please enter a password.</div>
                        </div>

                        <div class="form-group">
                            <label for="admin_password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input 
                                    type="password" 
                                    name="admin_password_confirmation" 
                                    id="admin_password_confirmation" 
                                    class="form-control" 
                                    placeholder="Confirm password" 
                                    required
                                >
                                <span class="password-toggle" onclick="togglePassword('admin_password_confirmation', this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="invalid-feedback">Please confirm your password.</div>
                        </div>

                        <button 
                            type="submit" 
                            id="submitBtn" 
                            class="btn btn-primary w-100 btn-setup"
                        >
                            Create Admin Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Bootstrap form validation
(function () {
    'use strict';
    const forms = document.querySelectorAll('#setupForm');
    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Toggle password visibility
function togglePassword(fieldId, toggleIcon) {
    const input = document.getElementById(fieldId);
    const icon = toggleIcon.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Show processing state on button when form is valid
document.getElementById('setupForm').addEventListener('submit', function(event) {
    const form = this;
    if (form.checkValidity()) {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Processing...
        `;
    }
});
</script>
@endsection
