@extends('layouts.master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.3.2/build/css/intlTelInput.css">

<style>
  .avatar-wrapper { width: 140px; height: 140px; }
  .avatar-img { width: 100%; height: 100%; object-fit: cover; border: 4px solid #dee2e6; transition: 0.3s; }
  .avatar-edit-icon {
    position: absolute; bottom: 0; right: 0;
    width: 36px; height: 36px; background: #fff;
    border: 2px solid #dee2e6; border-radius: 50%;
    text-align: center; line-height: 32px; cursor: pointer;
  }
  .avatar-edit-icon:hover { background-color: #f8f9fa; }
  .avatar-edit-icon i { font-size: 16px; color: #495057; }
  .iti { width: 100%; }
  .iti__search-input {
    width: 100%; padding: 10px 15px;
    border: 0; border-bottom: 1px solid #b1b1b1;
  }
</style>
@endpush

@section('content')
<section class="content-body">
  <div class="container-fluid">
    <div class="row">

      {{-- Profile + Avatar --}}
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header"><h4>Profile Details</h4></div>
          <div class="card-body">
            <form action="{{ route('user.update') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  id="profileForm"
                  novalidate>
              @csrf

              {{-- Avatar --}}
              <div class="text-center mb-4">
                <div class="avatar-wrapper position-relative mx-auto">
                  <img
                    src="{{ asset(Auth::user()->image ?? 'https://via.placeholder.com/140') }}"
                    id="avatarPreview"
                    class="rounded-circle avatar-img"
                    alt="Avatar"
                  >
                  <label for="avatar" class="avatar-edit-icon">
                    <i class="fas fa-camera"></i>
                  </label>
                  <input
                    type="file"
                    name="avatar"
                    id="avatar"
                    class="d-none"
                    accept="image/*"
                    onchange="previewAvatar(this)"
                  >
                </div>
              </div>

              {{-- Name --}}
              <div class="form-group mb-3">
                <label for="fname">Name <span class="text-danger">*</span></label>
                <input
                  type="text"
                  name="fname"
                  id="fname"
                  class="form-control @error('fname') is-invalid @enderror"
                  value="{{ old('fname', Auth::user()->name) }}"
                  required
                >
                @error('fname')
                  <div class="invalid-feedback">{{ $message }}</div>
                @else
                  <div class="invalid-feedback">Enter your name.</div>
                @enderror
              </div>

              {{-- Email --}}
              <div class="form-group mb-3">
                <label for="email">Email <span class="text-danger">*</span></label>
                <input
                  type="email"
                  name="email"
                  id="email"
                  class="form-control @error('email') is-invalid @enderror"
                  value="{{ old('email', Auth::user()->email) }}"
                  required
                >
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @else
                  <div class="invalid-feedback">Enter a valid email.</div>
                @enderror
              </div>

              {{-- Phone --}}
              <div class="form-group mb-3">
                <label for="phone">Phone</label>
                <input
                  type="tel"
                  name="phone"
                  id="phone"
                  class="form-control @error('phone') is-invalid @enderror"
                  value="{{ old('phone', Auth::user()->phone) }}"
                  placeholder="9876543210"
                >
                <input
                  type="hidden"
                  name="country_code"
                  id="country_code"
                  value="{{ old('country_code', Auth::user()->country_code) }}"
                >
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Actions --}}
              <div class="text-end">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="reset"  class="btn btn-light ms-2">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- Change Password --}}
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header"><h4>Change Password</h4></div>
          <div class="card-body">
            <form action="{{ route('user.update-password') }}"
                  method="POST"
                  id="passwordForm"
                  novalidate>
              @csrf

              {{-- New Password --}}
              <div class="form-group mb-3">
                <label for="new_password">New Password <span class="text-danger">*</span></label>
                <div class="position-relative">
                  <input
                    type="password"
                    name="new_password"
                    id="new_password"
                    class="form-control @error('new_password') is-invalid @enderror"
                    placeholder="••••••••"
                    required
                  >
                  <span class="toggle-password" onclick="togglePassword('new_password', this)">
                    <i class="fas fa-eye"></i>
                  </span>
                  @error('new_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @else
                    <div class="invalid-feedback">Please enter a new password.</div>
                  @enderror
                </div>
              </div>

              {{-- Confirm New Password --}}
              <div class="form-group mb-4">
                <label for="new_password_confirmation">Confirm New Password <span class="text-danger">*</span></label>
                <div class="position-relative">
                  <input
                    type="password"
                    name="new_password_confirmation"
                    id="new_password_confirmation"
                    class="form-control @error('new_password_confirmation') is-invalid @enderror"
                    placeholder="••••••••"
                    required
                  >
                  <span class="toggle-password" onclick="togglePassword('new_password_confirmation', this)">
                    <i class="fas fa-eye"></i>
                  </span>
                  @error('new_password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @else
                    <div class="invalid-feedback">Passwords do not match.</div>
                  @enderror
                </div>
              </div>

              <div class="text-end">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="reset"  class="btn btn-light ms-2">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.3.2/build/js/intlTelInput.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Form validation
  ['profileForm','passwordForm'].forEach(id => {
    const f = document.getElementById(id);
    f.addEventListener('submit', e => {
      if (!f.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      f.classList.add('was-validated');
    });
  });

  // Avatar preview
  window.previewAvatar = input => {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = ev => document.getElementById('avatarPreview').src = ev.target.result;
      reader.readAsDataURL(input.files[0]);
    }
  };

  // Toggle password visibility
  window.togglePassword = (fieldId, btn) => {
    const inp = document.getElementById(fieldId);
    const ico = btn.querySelector('i');
    if (inp.type === 'password') {
      inp.type = 'text'; ico.classList.replace('fa-eye','fa-eye-slash');
    } else {
      inp.type = 'password'; ico.classList.replace('fa-eye-slash','fa-eye');
    }
  };

  // intl-tel-input setup
  const phoneInput   = document.getElementById('phone');
  const countryInput = document.getElementById('country_code');

  const iti = window.intlTelInput(phoneInput, {
    initialCountry: "in",
    separateDialCode: true,
    strictMode: true,
    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.3.0/build/js/utils.js",
  });

  // Pre-populate from DB
  if (countryInput.value && phoneInput.value) {
    iti.setNumber('+' + countryInput.value + phoneInput.value);
  }

  // Strip spaces as user types
  phoneInput.addEventListener('input', function () {
    this.value = this.value.replace(/\s/g, '');
  });

  // On submit: extract dial code + subscriber
  document.getElementById('profileForm').addEventListener('submit', function () {
    const data       = iti.getSelectedCountryData();
    const fullE164   = iti.getNumber();                       // "+919876543210"
    const dialCode   = data.dialCode;                         // "91"
    const subscriber = fullE164.replace('+' + dialCode, '');  // "9876543210"

    countryInput.value = dialCode;
    phoneInput.value   = subscriber;
  });
});
</script>
@endpush
