@extends('layouts.master')

@section('content')
<section class="content-body">
  {{-- iziToast on success --}}
  @if(session('status'))
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        iziToast.success({
          title: 'Success',
          message: @json(session('status')),
          position: 'topRight',
          timeout: 5000
        });
      });
    </script>
  @endif

  {{-- iziToast on validation errors --}}
  @if($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        @foreach($errors->all() as $error)
        iziToast.error({
          title: 'Error',
          message: @json($error),
          position: 'topRight',
          timeout: 7000
        });
        @endforeach
      });
    </script>
  @endif

  <div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center text-head mb-3">
      <h2 class="me-auto">Email Settings</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-body">
            <form action="{{ route('settings.email') }}" method="POST" id="emailSettingsForm" novalidate>
              @csrf

              <div class="form-group mb-3">
                <label for="mail_driver">Mail Driver <span class="text-danger">*</span></label>
                <input type="text" id="mail_driver" name="mail_driver"
                       class="form-control @error('mail_driver') is-invalid @enderror"
                       value="{{ old('mail_driver', $email->mail_driver) }}"
                       required>
                <div class="invalid-feedback">Please enter a mail driver.</div>
              </div>

              <div class="form-group mb-3">
                <label for="mail_host">Mail Host <span class="text-danger">*</span></label>
                <input type="text" id="mail_host" name="mail_host"
                       class="form-control @error('mail_host') is-invalid @enderror"
                       value="{{ old('mail_host', $email->mail_host) }}"
                       placeholder="smtp.example.com"
                       required>
                <div class="invalid-feedback">Please enter a mail host.</div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6 form-group">
                  <label for="mail_port">Mail Port <span class="text-danger">*</span></label>
                  <input type="number" id="mail_port" name="mail_port"
                         class="form-control @error('mail_port') is-invalid @enderror"
                         value="{{ old('mail_port', $email->mail_port) }}"
                         placeholder="587" required>
                  <div class="invalid-feedback">Please enter a mail port.</div>
                </div>
                <div class="col-md-6 form-group">
                  <label for="mail_encryption">Encryption</label>
                  <select id="mail_encryption" name="mail_encryption"
                          class="form-control @error('mail_encryption') is-invalid @enderror">
                    <option value="">None</option>
                    <option value="tls" {{ old('mail_encryption', $email->mail_encryption)==='tls' ? 'selected':'' }}>TLS</option>
                    <option value="ssl" {{ old('mail_encryption', $email->mail_encryption)==='ssl' ? 'selected':'' }}>SSL</option>
                  </select>
                </div>
              </div>

              <div class="form-group mb-3">
                <label for="mail_username">Username <span class="text-danger">*</span></label>
                <input type="text" id="mail_username" name="mail_username"
                       class="form-control @error('mail_username') is-invalid @enderror"
                       value="{{ old('mail_username', $email->mail_username) }}"
                       required>
                <div class="invalid-feedback">Please enter a username.</div>
              </div>

              <div class="form-group mb-3">
                <label for="mail_password">Password <span class="text-danger">*</span></label>
                <div class="position-relative">
                  <input type="password" id="mail_password" name="mail_password"
                         class="form-control @error('mail_password') is-invalid @enderror"
                         value="{{ old('mail_password', $email->mail_password) }}"
                         placeholder="••••••••" required>
                  <span class="toggle-password" onclick="togglePassword('mail_password', this)" style="cursor: pointer; position: absolute; top: 50%; right: 10px; transform: translateY(-50%);">
                    <i class="fas fa-eye"></i>
                  </span>
                  <div class="invalid-feedback">Please enter the mail password.</div>
                </div>
              </div>

              <div class="form-group mb-3">
                <label for="mail_from_address">From Address <span class="text-danger">*</span></label>
                <input type="email" id="mail_from_address" name="mail_from_address"
                       class="form-control @error('mail_from_address') is-invalid @enderror"
                       value="{{ old('mail_from_address', $email->mail_from_address) }}"
                       required>
                <div class="invalid-feedback">Please enter a valid email address.</div>
              </div>

              <div class="form-group mb-3">
                <label for="mail_from_name">From Name <span class="text-danger">*</span></label>
                <input type="text" id="mail_from_name" name="mail_from_name"
                       class="form-control @error('mail_from_name') is-invalid @enderror"
                       value="{{ old('mail_from_name', $email->mail_from_name) }}"
                       required>
                <div class="invalid-feedback">Please enter the from name.</div>
              </div>

              <div class="text-end">
                <button type="submit" id="saveEmailBtn" class="btn btn-primary">
                  <span id="saveEmailBtnText">Save Email Settings</span>
                  <span id="saveEmailSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header"><h5>Help & Info</h5></div>
          <div class="card-body">
            <p><strong>Driver:</strong> smtp, sendmail, etc.</p>
            <p><strong>Host & Port:</strong> SMTP server details.</p>
            <p><strong>Encryption:</strong> TLS or SSL.</p>
            <p><strong>Username & Password:</strong> SMTP credentials.</p>
            <p><strong>From:</strong> Default sender address & name.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  (function(){
    'use strict';
    const form = document.getElementById('emailSettingsForm');
    const saveBtn = document.getElementById('saveEmailBtn');
    const saveBtnText = document.getElementById('saveEmailBtnText');
    const saveSpinner = document.getElementById('saveEmailSpinner');

    form.addEventListener('submit', function(e){
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      } else {
        // disable button, update text & show spinner
        saveBtn.disabled = true;
        saveBtnText.textContent = 'Processing…';
        saveSpinner.classList.remove('d-none');
      }
      form.classList.add('was-validated');
    }, false);
  })();

  function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  }
</script>
@endsection
