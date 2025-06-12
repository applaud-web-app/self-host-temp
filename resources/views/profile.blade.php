@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column: Profile Details + Address -->
            <div class="col-lg-8">
                <div class="profile-card card h-auto">
                    <div class="card-header">
                        <h4 class="card-title fs-20 mb-0">Profile Details</h4>
                    </div>
                    <div class="card-body">
                        {{-- Avatar Upload --}}
                        <div class="row justify-content-center mb-4">
                            <div class="col-auto">
                                <div class="position-relative avatar-wrapper">
                                    <img src="https://img.freepik.com/premium-vector/vector-flat-illustration-grayscale-avatar-user-profile-person-icon-gender-neutral-silhouette-profile-picture-suitable-social-media-profiles-icons-screensavers-as-templatex9xa_719432-875.jpg?semt=ais_hybrid&w=740"
                                        alt="User Avatar" id="avatarPreview" class="rounded-circle avatar-img">
                                    <label for="avatar" class="avatar-edit-icon">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <input type="file" class="d-none" name="avatar" id="avatar" accept="image/*"
                                        onchange="previewAvatar(this)">
                                </div>
                            </div>
                        </div>

                        <form action="#" method="POST" enctype="multipart/form-data" id="profileForm" novalidate>
                            @csrf

                            <div class="row">
                                <!-- First & Last Name -->
                                <div class="col-md-6 mb-3">
                                    <label for="fname" class="form-label">First Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="fname" id="fname" value="John"
                                        required>
                                    <div class="invalid-feedback">Please enter your first name.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lname" class="form-label">Last Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="lname" id="lname" value="Doe"
                                        required>
                                    <div class="invalid-feedback">Please enter your last name.</div>
                                </div>

                                <!-- Email Address -->
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" id="email"
                                        value="john.doe@example.com" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>

                                <!-- Phone Number + Country Code -->
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <select id="country_code" name="country_code" class="form-select form-control"
                                            style="max-width:120px;">
                                            <option value="" selected>+91</option>
                                        </select>
                                        <input type="text" class="form-control" name="phone" id="phone"
                                            value="555 123 4567" placeholder="Phone Number">
                                    </div>
                                </div>

                                <!-- Address Line 1 -->
                                <div class="col-md-12 mb-3">
                                    <label for="address_line1" class="form-label">Address</label>
                                    <textarea class="form-control" name="address_line1" id="address_line1"
                                        placeholder="123 Main St"></textarea>
                                </div>

                                <!-- Country / State / City / ZIP -->
                                <div class="col-md-6 mb-3">
                                    <label for="country" class="form-label">Country <span
                                            class="text-danger">*</span></label>
                                    <select id="country" name="country" class="form-select form-control" required>
                                        <option value="">Select Country</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a country.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="state" class="form-label">State/Province <span
                                            class="text-danger">*</span></label>
                                    <select id="state" name="state" class="form-select form-control" required disabled>
                                        <option value="">Select State</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a state.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                    <select id="city" name="city" class="form-select form-control" required disabled>
                                        <option value="">Select City</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a city.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="zip" class="form-label">ZIP/Postal Code</label>
                                    <input type="text" class="form-control" name="zip" id="zip"
                                        placeholder="e.g. 10001">
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
                        <form action="#" method="POST" id="passwordForm" novalidate>
                            @csrf

                            <!-- Current Password -->
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="current_password"
                                        id="current_password" required>
                                    <span class="input-group-text toggle-password" data-target="current_password"
                                        style="cursor: pointer;">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                    <div class="invalid-feedback">Please enter your current password.</div>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="new_password" id="new_password"
                                        required>
                                    <span class="input-group-text toggle-password" data-target="new_password"
                                        style="cursor: pointer;">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                    <div class="invalid-feedback">Please enter a new password.</div>
                                </div>
                            </div>

                            <!-- Confirm New Password -->
                            <div class="mb-3">
                                <label for="new_password_confirmation" class="form-label">Confirm New Password <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="new_password_confirmation"
                                        id="new_password_confirmation" required>
                                    <span class="input-group-text toggle-password"
                                        data-target="new_password_confirmation" style="cursor: pointer;">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                    <div class="invalid-feedback">Passwords do not match.</div>
                                </div>
                            </div>

                            <div class="profile-actions">
                                <button type="submit" class="btn w-100 btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Custom Styles --}}
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

{{-- Custom Scripts --}}
<script>
    (function () {
        'use strict';
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
    })();

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // Preview avatar
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => document.getElementById('avatarPreview').src = e.target.result;
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const countrySelect = document.getElementById('country');
        const stateSelect = document.getElementById('state');
        const citySelect = document.getElementById('city');
    const countryCodeSelect = document.getElementById('country_code');

  fetch('countries_data.json')
    .then(res => res.json())
    .then(list => {
      list.forEach(({ country_code }) => {   // <-- grab the right key
        const opt = document.createElement('option');
        opt.value = country_code;            // e.g. "93"
        opt.textContent = `+${country_code}`; // what the user sees
        countryCodeSelect.appendChild(opt);
      });
    })
    .catch(console.error);

        // 2. On country-change, load states
        countrySelect.addEventListener('change', () => {
            const country = countrySelect.value;
            stateSelect.innerHTML = '<option value="">Select State</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';
            stateSelect.disabled = citySelect.disabled = true;
            if (!country) return;

            fetch('https://countriesnow.space/api/v0.1/countries/states', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ country })
            })
                .then(res => res.json())
                .then(json => {
                    json.data.states.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.name;
                        opt.textContent = s.name;
                        stateSelect.appendChild(opt);
                    });
                    stateSelect.disabled = false;
                }).catch(console.error);
        });

        // 3. On state-change, load cities
        stateSelect.addEventListener('change', () => {
            const country = countrySelect.value;
            const state = stateSelect.value;
            citySelect.innerHTML = '<option value="">Select City</option>';
            citySelect.disabled = true;
            if (!state) return;

            fetch('https://countriesnow.space/api/v0.1/countries/state/cities', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ country, state })
            })
                .then(res => res.json())
                .then(json => {
                    json.data.forEach(city => {
                        const opt = document.createElement('option');
                        opt.value = city;
                        opt.textContent = city;
                        citySelect.appendChild(opt);
                    });
                    citySelect.disabled = false;
                }).catch(console.error);
        });
    });
</script>
@endsection
