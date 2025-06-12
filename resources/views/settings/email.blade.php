{{-- resources/views/settings/email.blade.php --}}
@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid">
         <div class="d-flex flex-wrap align-items-center text-head">
            <h2 class="mb-3 me-auto">Email Settings</h2>
        </div>
        <div class="row justify-content-center">
            <!-- Main Column: Email Settings Form -->
            <div class="col-lg-8">
                <div class="card mb-4">
                   
                    <div class="card-body">
                        <form action="{{ route('settings.email') }}" method="POST" id="emailSettingsForm" novalidate>
                            @csrf

                       

                            <div class="mb-3">
                                <label for="mail_host" class="form-label">Mail Host <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="mail_host"
                                    name="mail_host"
                                    value="{{ old('mail_host', config('mail.mailers.smtp.host')) }}"
                                    placeholder="smtp.example.com"
                                    required
                                >
                                <div class="invalid-feedback">
                                    Please enter the mail host.
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="mail_port" class="form-label">Mail Port <span class="text-danger">*</span></label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="mail_port"
                                        name="mail_port"
                                        value="{{ old('mail_port', config('mail.mailers.smtp.port')) }}"
                                        placeholder="587"
                                        required
                                    >
                                    <div class="invalid-feedback">
                                        Please enter the mail port.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="mail_encryption" class="form-label">Encryption</label>
                                    <select class="form-select form-control" id="mail_encryption" name="mail_encryption">
                                        <option value="">None</option>
                                        <option value="tls" {{ old('mail_encryption', config('mail.mailers.smtp.encryption')) === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ old('mail_encryption') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="mail_username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="mail_username"
                                    name="mail_username"
                                    value="{{ old('mail_username', config('mail.mailers.smtp.username')) }}"
                                    placeholder="your_username"
                                    required
                                >
                                <div class="invalid-feedback">
                                    Please enter the mail username.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="mail_password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input
                                        type="password"
                                        class="form-control"
                                        id="mail_password"
                                        name="mail_password"
                                        value="{{ old('mail_password', config('mail.mailers.smtp.password')) }}"
                                        placeholder="••••••••"
                                        required
                                    >
                                    <span class="input-group-text toggle-password" data-target="mail_password" style="cursor: pointer;">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                    <div class="invalid-feedback">
                                        Please enter the mail password.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="mail_from_address" class="form-label">From Address <span class="text-danger">*</span></label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="mail_from_address"
                                    name="mail_from_address"
                                    value="{{ old('mail_from_address', config('mail.from.address')) }}"
                                    placeholder="no-reply@example.com"
                                    required
                                >
                                <div class="invalid-feedback">
                                    Please enter a valid from address.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="mail_from_name" class="form-label">From Name <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="mail_from_name"
                                    name="mail_from_name"
                                    value="{{ old('mail_from_name', config('mail.from.name')) }}"
                                    placeholder="Your Site Name"
                                    required
                                >
                                <div class="invalid-feedback">
                                    Please enter a from name.
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary">Save Email Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Help & Info Sidebar -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Help & Info</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Mail Driver:</strong> Select which mailer you want to use (SMTP, Mailgun, etc.).
                        </p>
                        <p class="mb-2">
                            <strong>Mail Host & Port:</strong> The SMTP server and port number for sending mail.
                        </p>
                        <p class="mb-2">
                            <strong>Encryption:</strong> Use TLS or SSL if your SMTP server requires it.
                        </p>
                        <p class="mb-2">
                            <strong>Username & Password:</strong> Credentials for authenticating with the mail server.
                        </p>
                        <p class="mb-0">
                            <strong>From Address & Name:</strong> The default “from” fields for outgoing emails.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
  /* Optionally, adjust card paddings or backgrounds */
  .avatar-bg {
    width: 80px;
    height: 80px;
    background-color: #f8f9fa;
    border-radius: 50%;
    overflow: hidden;
  }
  .avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
</style>

<script>
  // Bootstrap form validation
  (function () {
    'use strict';
    const form = document.getElementById('emailSettingsForm');
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  })();

  // Toggle password visibility in “Mail Password” field
  document.querySelectorAll('.toggle-password').forEach(toggle => {
    toggle.addEventListener('click', function() {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      const icon = this.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });
  });
</script>
@endsection
