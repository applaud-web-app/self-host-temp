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

  <div class="container-fluid">
    <div class="text-head mb-3">
      <h2>Utilities</h2>
    </div>

    <div class="row g-4">
    {{-- Purge Cache --}}
<div class="col-md-4">
  <div class="card h-auto">
    <div class="card-header">
      <h5 class="mb-0"><i class="fas fa-broom me-2"></i>Purge Cache</h5>
    </div>
    <div class="card-body">
      <p class="mb-2">
        Clears Laravel’s configuration, route, and view cache. Recommended after updating environment files or core settings.
      </p>
      <ul class="small ps-3 mb-3 text-muted">
        <li>Flushes config, route, and compiled views.</li>
        <li>Does <code>php artisan cache:clear</code> under the hood.</li>
      </ul>
      <form class="utility-action-form" action="{{ route('settings.utilities.purge-cache') }}" method="POST" data-confirm="Are you sure you want to purge all cache?">
        @csrf
        <button type="submit" class="btn btn-danger w-100">
          <i class="fas fa-trash-alt me-1"></i>Purge Cache
        </button>
      </form>
    </div>
  </div>
</div>

{{-- Clear Log --}}
<div class="col-md-4">
  <div class="card h-auto">
    <div class="card-header">
      <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Clear Log</h5>
    </div>
    <div class="card-body">
      <p class="mb-2">
        Deletes the main Laravel log file to help save disk space or troubleshoot cleanly.
      </p>
      <ul class="small ps-3 mb-3 text-muted">
        <li>Empties <code>storage/logs/laravel.log</code>.</li>
        <li>Useful before debugging a fresh issue.</li>
      </ul>
      <form class="utility-action-form" action="{{ route('settings.utilities.clear-log') }}" method="POST" data-confirm="Are you sure you want to clear all logs?">
        @csrf
        <button type="submit" class="btn btn-warning w-100">
          <i class="fas fa-eraser me-1"></i>Clear Log
        </button>
      </form>
    </div>
  </div>
</div>

{{-- Generate Cache --}}
<div class="col-md-4">
  <div class="card h-auto">
    <div class="card-header">
      <h5 class="mb-0"><i class="fas fa-sync-alt me-2"></i>Generate Cache</h5>
    </div>
    <div class="card-body">
      <p class="mb-2">
        Compiles and caches Laravel config and route files for better performance in production.
      </p>
      <ul class="small ps-3 mb-3 text-muted">
        <li>Runs <code>config:cache</code> and <code>route:cache</code>.</li>
        <li>Improves response time by preloading config/routes.</li>
      </ul>
      <form class="utility-action-form" action="{{ route('settings.utilities.make-cache') }}" method="POST" data-confirm="Generate fresh configuration and route cache?">
        @csrf
        <button type="submit" class="btn btn-success w-100">
          <i class="fas fa-cogs me-1"></i>Generate Cache
        </button>
      </form>
    </div>
  </div>
</div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.utility-action-form').forEach(form => {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const message = form.getAttribute('data-confirm') || 'Are you sure?';

      Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, do it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then(result => {
        if (result.isConfirmed) {
          iziToast.info({
            title: 'Processing',
            message: 'Please wait...',
            position: 'topRight',
            timeout: 2000
          });

          const btn = form.querySelector('button[type="submit"]');
          btn.disabled = true;
          btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>' + btn.textContent.trim();

          setTimeout(() => form.submit(), 300);
        }
      });
    });
  });
});
</script>
@endpush
