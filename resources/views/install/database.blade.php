@extends('layouts.single-master')
@section('title', 'Database Configuration | Aplu')

@php
    // Pull in existing env values for auto-fill
    $envHost     = env('DB_HOST', '');
    $envPort     = env('DB_PORT', '');
    $envDatabase = env('DB_DATABASE', '');
    $envUsername = env('DB_USERNAME', '');
    $envPassword = env('DB_PASSWORD', '');
@endphp

@section('content')
<style>
    .database-card { padding: 40px; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .database-heading { font-size: 1.8rem; font-weight: 600; color: #2d3748; margin-bottom: 1rem; }
    .database-subtitle { font-size: 1.1rem; color: #4a5568; margin-bottom: 2rem; line-height:1.5; }
    .form-group { margin-bottom:1.5rem; text-align:left; }
</style>

<section class="section-padding">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="database-card card">
          <h1 class="database-heading">🗄️ Database Configuration</h1>
          <p class="database-subtitle">Enter your database details to establish a connection for Aplu.</p>

          <form id="dbForm" method="POST" action="{{ route('install.database.post') }}" novalidate>
            @csrf

            <div class="form-group">
              <label for="db_host">Database Host <span class="text-danger">*</span></label>
              <input
                type="text"
                name="db_host"
                id="db_host"
                class="form-control"
                placeholder="e.g. 127.0.0.1"
                value="{{ old('db_host', $envHost) }}"
                required
              >
            </div>

            <div class="form-group">
              <label for="db_port">Database Port <span class="text-danger">*</span></label>
              <input
                type="number"
                name="db_port"
                id="db_port"
                class="form-control"
                placeholder="e.g. 3306"
                value="{{ old('db_port', $envPort) }}"
                required
              >
            </div>

            <div class="form-group">
              <label for="db_name">Database Name <span class="text-danger">*</span></label>
              <input
                type="text"
                name="db_name"
                id="db_name"
                class="form-control"
                placeholder="e.g. aplu_db"
                value="{{ old('db_name', $envDatabase) }}"
                required
              >
            </div>

            <div class="form-group">
              <label for="db_username">Database Username <span class="text-danger">*</span></label>
              <input
                type="text"
                name="db_username"
                id="db_username"
                class="form-control"
                placeholder="e.g. root"
                value="{{ old('db_username', $envUsername) }}"
                required
              >
            </div>

            <div class="form-group">
              <label for="db_password">Database Password</label>
              <input
                type="password"
                name="db_password"
                id="db_password"
                class="form-control"
                placeholder="Enter your database password"
                value="{{ old('db_password', $envPassword) }}"
              >
            </div>

            <button type="submit" id="submitBtn" class="btn btn-primary text-white w-100">
              Setup Database
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
@push('scripts')
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/additional-methods.min.js"></script>
  <script>
  $(document).ready(function() {
    $('#dbForm').validate({
      // Validation rules
      rules: {
        db_host: {
          required: true,
          maxlength: 255
        },
        db_port: {
          required: true,
          digits: true,
          min: 1,
          max: 65535
        },
        db_name: {
          required: true,
          maxlength: 100,
          pattern: /^[A-Za-z0-9_-]+$/
        },
        db_username: {
          required: true,
          maxlength: 100,
          pattern: /^[A-Za-z0-9_-]+$/
        },
        db_password: {
          maxlength: 255
          // In production you can uncomment:
          // required: true
        }
      },
      // Custom messages
      messages: {
        db_name: {
          pattern: 'Database name may only contain letters, numbers, underscores, and dashes.'
        },
        db_username: {
          pattern: 'Username may only contain letters, numbers, underscores, and dashes.'
        }
      },
      errorClass: 'is-invalid',
      validClass: 'is-valid',
      errorPlacement: function(error, element) {
        error.addClass('invalid-feedback');
        error.insertAfter(element);
      },
      highlight: function(element) {
        $(element).addClass('is-invalid').removeClass('is-valid');
      },
      unhighlight: function(element) {
        $(element).addClass('is-valid').removeClass('is-invalid');
      },
      submitHandler: function(form) {
        var $btn = $('#submitBtn');
        $btn.prop('disabled', true).html('Processing...');
        form.submit();
      }
    });
  });
  </script>
@endpush