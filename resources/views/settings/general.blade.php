@extends('layouts.master')

@section('content')
<section class="content-body">
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
    <div class="d-flex flex-wrap align-items-center text-head ">
      <h2 class="me-auto mb-3">General Settings</h2>
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
                  class="form-control @error('site_name') is-invalid @enderror"
                  id="site_name"
                  name="site_name"
                  value="{{ old('site_name', $setting->site_name) }}"
                  required
                >
                <div class="invalid-feedback">Please enter a site name.</div>
              </div>

              <div class="mb-3">
                <label for="site_url" class="form-label">Site URL <span class="text-danger">*</span></label>
                <input
                  type="url"
                  class="form-control @error('site_url') is-invalid @enderror"
                  id="site_url"
                  name="site_url"
                  value="{{ old('site_url', $setting->site_url) }}"
                  placeholder="https://example.com"
                  required
                >
                <div class="invalid-feedback">Please enter a valid URL.</div>
              </div>

              <div class="mb-3">
                <label for="site_tagline" class="form-label">Site Tagline</label>
                <input
                  type="text"
                  class="form-control @error('site_tagline') is-invalid @enderror"
                  id="site_tagline"
                  name="site_tagline"
                  value="{{ old('site_tagline', $setting->site_tagline) }}"
                  placeholder="Your site’s tagline (optional)"
                >
              </div>

              <div class="text-end mt-3">
                <button
                  type="submit"
                  id="saveBtn"
                  class="btn btn-primary"
                >
                  <span id="saveBtnText">Save Settings</span>
                  <span
                    id="saveBtnSpinner"
                    class="spinner-border spinner-border-sm ms-2 d-none"
                    role="status"
                    aria-hidden="true"
                  ></span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Help & Info</h5>
          </div>
          <div class="card-body">
            <p class="mb-2"><strong>Site Name:</strong> Appears in titles and headers.</p>
            <p class="mb-2"><strong>Site URL:</strong> Base URL for links.</p>
            <p class="mb-0"><strong>Site Tagline:</strong> Optional subtitle.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  (function () {
    'use strict';
    const form = document.getElementById('generalSettingsForm');
    const saveBtn = document.getElementById('saveBtn');
    const saveBtnText = document.getElementById('saveBtnText');
    const saveBtnSpinner = document.getElementById('saveBtnSpinner');

    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      } else {
        // Disable the button, change text, and show spinner
        saveBtn.disabled = true;
        saveBtnText.textContent = 'Processing…';
        saveBtnSpinner.classList.remove('d-none');
      }
      form.classList.add('was-validated');
    }, false);
  })();
</script>
@endsection
