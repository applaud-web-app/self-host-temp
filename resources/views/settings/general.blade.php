{{-- resources/views/settings/general.blade.php --}}
@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center text-head">
            <h2 class="mb-3 me-auto">General Settings</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card mb-4">
                   
                    <div class="card-body">
                        <form action="{{ route('settings.general') }}" method="POST" id="generalSettingsForm" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="site_name" class="form-label">Site Name <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="site_name"
                                    name="site_name"
                                    value="{{ old('site_name', config('app.name', '')) }}"
                                    required
                                >
                                <div class="invalid-feedback">
                                    Please enter a site name.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="site_url" class="form-label">Site URL <span class="text-danger">*</span></label>
                                <input
                                    type="url"
                                    class="form-control"
                                    id="site_url"
                                    name="site_url"
                                    value="{{ old('site_url', url('/')) }}"
                                    placeholder="https://example.com"
                                    required
                                >
                                <div class="invalid-feedback">
                                    Please enter a valid URL.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="site_tagline" class="form-label">Site Tagline</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="site_tagline"
                                    name="site_tagline"
                                    value="{{ old('site_tagline', config('app.tagline', '')) }}"
                                    placeholder="Your site’s tagline (optional)"
                                >
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="default_language" class="form-label">Default Language <span class="text-danger">*</span></label>
                                    <select class="form-select form-control" id="default_language" name="default_language" required>
                                        <option value="">Select language</option>
                                        <option value="en" {{ old('default_language') === 'en' ? 'selected' : '' }}>English</option>
                                        <option value="es" {{ old('default_language') === 'es' ? 'selected' : '' }}>Spanish</option>
                                        <option value="fr" {{ old('default_language') === 'fr' ? 'selected' : '' }}>French</option>
                                        <option value="de" {{ old('default_language') === 'de' ? 'selected' : '' }}>German</option>
                                        <!-- Add more languages as needed -->
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a default language.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="timezone" class="form-label">Timezone <span class="text-danger">*</span></label>
                                    <select class="form-select form-control" id="timezone" name="timezone" required>
                                        <option value="">Select timezone</option>
                                        <option value="UTC" {{ old('timezone') === 'UTC' ? 'selected' : '' }}>UTC</option>
                                        <option value="America/New_York" {{ old('timezone') === 'America/New_York' ? 'selected' : '' }}>America/New York (UTC−05:00)</option>
                                        <option value="Europe/London" {{ old('timezone') === 'Europe/London' ? 'selected' : '' }}>Europe/London (UTC+00:00)</option>
                                        <option value="Asia/Kolkata" {{ old('timezone') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (UTC+05:30)</option>
                                        <option value="Australia/Sydney" {{ old('timezone') === 'Australia/Sydney' ? 'selected' : '' }}>Australia/Sydney (UTC+10:00)</option>
                                        <!-- Add additional timezones as needed -->
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a timezone.
                                    </div>
                                </div>
                            </div>

                         

                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Optional Sidebar / Info -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Help & Info</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Site Name:</strong> This will appear in titles and headers.
                        </p>
                        <p class="mb-2">
                            <strong>Site URL:</strong> The base URL used for links in emails or notifications.
                        </p>
                        <p class="mb-2">
                            <strong>Default Language:</strong> Sets the fallback language for your application.
                        </p>
                        <p class="mb-0">
                            <strong>Timezone:</strong> Determines how dates and times are displayed.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<script>
  // Bootstrap form validation
  (function () {
    'use strict';
    const form = document.getElementById('generalSettingsForm');
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  })();

  // Preview avatar when choosing a file
  function previewAvatar(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function (e) {
        document.getElementById('avatarPreview').src = e.target.result;
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  // Clicking “Choose File” label should open file dialog
  document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('avatar');
    document.querySelector('label[for="avatar"]').addEventListener('click', function() {
      fileInput.click();
    });
  });
</script>
@endsection
