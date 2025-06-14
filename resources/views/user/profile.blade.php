@extends('layouts.master')
@section('content')
<section class="content-body">
  <div class="container-fluid">
    <div class="row">
      <!-- Left Column: Profile Details + Avatar Upload -->
      <div class="col-lg-8">
        <div class="profile-card card h-auto">
          <div class="card-header">
            <h4 class="card-title fs-20 mb-0">Profile Details</h4>
          </div>
          <div class="card-body">

            <form action="{{ route('user.update') }}" method="POST" enctype="multipart/form-data" id="profileForm" novalidate>
              @csrf
            {{-- Avatar Upload --}}
            <div class="row justify-content-center mb-4">
              <div class="col-auto">
                <div class="position-relative avatar-wrapper">
                  <img
                    src="{{ asset(Auth::user()->image ?? 'https://img.freepik.com/premium-vector/vector-flat-illustration-grayscale-avatar-user-profile-person-icon-gender-neutral-silhouette-profile-picture-suitable-social-media-profiles-icons-screensavers-as-templatex9xa_719432-875.jpg') }}"
                    alt="User Avatar"
                    id="avatarPreview"
                    class="rounded-circle avatar-img"
                  >
                  <label for="avatar" class="avatar-edit-icon">
                    <i class="fas fa-camera"></i>
                  </label>
                  <input
                    type="file"
                    class="d-none"
                    name="avatar"
                    id="avatar"
                    accept="image/*"
                    onchange="previewAvatar(this)"
                  >
                </div>
              </div>
            </div>
              <div class="row">
                <!-- User Name -->
                <div class="col-md-12 mb-3">
                  <label for="fname" class="form-label">
                    User Name <span class="text-danger">*</span>
                  </label>
                  <input
                    type="text"
                    class="form-control @error('fname') is-invalid @enderror"
                    name="fname"
                    id="fname"
                    value="{{ old('fname', Auth::user()->name) }}"
                    required
                  >
                  @error('fname')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">Please enter your first name.</div>@enderror
                </div>

                <!-- Email Address -->
                <div class="col-md-12 mb-3">
                  <label for="email" class="form-label">
                    Email Address <span class="text-danger">*</span>
                  </label>
                  <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    name="email"
                    id="email"
                    value="{{ old('email', Auth::user()->email) }}"
                    required
                  >
                  @error('email')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">Please enter a valid email address.</div>@enderror
                </div>

                <!-- Phone Number + Country Code -->
                <div class="col-md-12 mb-3">
                  <label for="phone" class="form-label">Phone Number</label>
                  <div class="input-group">
                    <select
                      id="country_code"
                      name="country_code"
                      class="form-select form-control"
                      style="max-width:120px;"
                    >
                      <option value="{{ Auth::user()->country_code ?? '91' }}" selected>+{{ Auth::user()->country_code ?? '91' }}</option>
                    </select>
                    <input
                      type="text"
                      class="form-control @error('phone') is-invalid @enderror"
                      name="phone"
                      id="phone"
                      value="{{ old('phone', Auth::user()->phone) }}"
                      placeholder="Phone Number"
                    >
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                </div>
              </div>

              <div class="profile-actions text-end">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="reset" class="btn btn-light ms-2">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Right Column: Change Password -->
      <div class="col-lg-4">
        <div class="profile-card card h-auto">
          <div class="card-header">
            <h4 class="card-title fs-20 mb-0">Change Password</h4>
          </div>
          <div class="card-body">
            <form action="{{ route('user.update-password') }}" method="POST" id="passwordForm" novalidate>
              @csrf

              <!-- Current Password -->
              <div class="form-group mb-3">
                <label for="current_password" class="form-label">
                  Current Password <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                  <input
                    type="password"
                    class="form-control @error('current_password') is-invalid @enderror"
                    name="current_password"
                    id="current_password"
                    placeholder="••••••••"
                    required
                  >
                  <span
                    class="toggle-password"
                    onclick="togglePassword('current_password', this)"
                    style="cursor: pointer;"
                  >
                    <i class="fas fa-eye"></i>
                  </span>
                  @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">Please enter your current password.</div>@enderror
                </div>
              </div>

              <!-- New Password -->
              <div class="form-group mb-3">
                <label for="new_password" class="form-label">
                  New Password <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                  <input
                    type="password"
                    class="form-control @error('new_password') is-invalid @enderror"
                    name="new_password"
                    id="new_password"
                    placeholder="••••••••"
                    required
                  >
                  <span
                    class="toggle-password"
                    onclick="togglePassword('new_password', this)"
                    style="cursor: pointer;"
                  >
                    <i class="fas fa-eye"></i>
                  </span>
                  @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">Please enter a new password.</div>@enderror
                </div>
              </div>

              <!-- Confirm New Password -->
              <div class="form-group mb-4">
                <label for="new_password_confirmation" class="form-label">
                  Confirm New Password <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                  <input
                    type="password"
                    class="form-control @error('new_password_confirmation') is-invalid @enderror"
                    name="new_password_confirmation"
                    id="new_password_confirmation"
                    placeholder="••••••••"
                    required
                  >
                  <span
                    class="toggle-password"
                    onclick="togglePassword('new_password_confirmation', this)"
                    style="cursor: pointer;"
                  >
                    <i class="fas fa-eye"></i>
                  </span>
                  @error('new_password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@else<div class="invalid-feedback">Passwords do not match.</div>@enderror
                </div>
              </div>

              <div class="profile-actions text-end">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="reset" class="btn btn-light ms-2">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
{{-- Custom Styles & Scripts --}}
@push('styles')
<style>
  .avatar-wrapper {
    width: 140px;
    height: 140px;
  }
  .avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: 4px solid #dee2e6;
    transition: 0.3s ease-in-out;
  }
  .avatar-edit-icon {
    position: absolute;
    bottom: 0;
    width: 36px;
    height: 36px;
    line-height: 32px;
    text-align: center;
    right: 0;
    background-color: #ffffff;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  .avatar-edit-icon:hover {
    background-color: #f8f9fa;
  }
  .avatar-edit-icon i {
    color: #495057;
    font-size: 16px;
  }
</style>
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');

    [profileForm, passwordForm].forEach((form) => {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });

    // Load country codes
    const countryCodeSelect = document.getElementById('country_code');
    fetch('/countries_data.json')
      .then(res => res.json())
      .then(list => {
        list.forEach(({ country_code }) => {
          const opt = document.createElement('option');
          opt.value    = country_code;
          opt.textContent = `+${country_code}`;
          countryCodeSelect.appendChild(opt);
        });
      })
      .catch(console.error);
  });

  // Preview avatar
  function previewAvatar(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
      reader.readAsDataURL(input.files[0]);
    }
  }

  // Toggle password visibility
  function togglePassword(fieldId, toggleBtn) {
    const input = document.getElementById(fieldId);
    const icon  = toggleBtn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
  }
</script>
@endpush
