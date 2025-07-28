@extends('layouts.master')

@section('content')
<section class="content-body" id="utilities_page">
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
    <div class="text-head">
      <h2>Utilities</h2>
    </div>

    <div class="row">
      {{-- Purge Cache --}}
      <div class="col-md-6">
        <div class="card ">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-broom me-2"></i>Purge Cache</h5>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Completely clears all configuration, route, and view caches in one command for a clean slate.
            </p>
            <ul class="small ps-3 mb-0 text-muted">
              <li>Runs <code>php artisan optimize:clear</code> for configs and routes.</li>
              <li>Removes compiled views to rebuild fresh copies.</li>
            </ul>
          </div>
          <div class="card-footer">
            <form class="utility-action-form"
                  action="{{ route('settings.utilities.purge-cache') }}"
                  method="POST"
                  data-confirm="Are you sure you want to purge all cache?">
              @csrf
              <button type="submit" class="btn btn-danger w-100">
                <i class="fas fa-trash-alt me-1"></i>Purge Cache
              </button>
            </form>
          </div>
        </div>
      </div>

      {{-- Clear Log --}}
      <div class="col-md-6">
        <div class="card ">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Clear Log</h5>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Empties the primary Laravel log file to start fresh and prevent disk bloat.
            </p>
            <ul class="small ps-3 mb-0 text-muted">
              <li>Clears <code>storage/logs/laravel.log</code> contents.</li>
              <li>Ideal before reproducing new errors or debugging.</li>
            </ul>
          </div>
          <div class="card-footer">
            <form class="utility-action-form"
                  action="{{ route('settings.utilities.clear-log') }}"
                  method="POST"
                  data-confirm="Are you sure you want to clear all logs?">
              @csrf
              <button type="submit" class="btn btn-warning w-100">
                <i class="fas fa-eraser me-1"></i>Clear Log
              </button>
            </form>
          </div>
        </div>
      </div>

      {{-- Generate Cache --}}
      <div class="col-md-6">
        <div class="card ">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-sync-alt me-2"></i>Generate Cache</h5>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Builds and stores configuration and route caches to boost application performance.
            </p>
            <ul class="small ps-3 mb-0 text-muted">
              <li>Executes <code>php artisan config:cache</code>.</li>
              <li>Executes <code>php artisan route:cache</code>.</li>
            </ul>
          </div>
          <div class="card-footer">
            <form class="utility-action-form"
                  action="{{ route('settings.utilities.make-cache') }}"
                  method="POST"
                  data-confirm="Generate fresh configuration and route cache?">
              @csrf
              <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-cogs me-1"></i>Generate Cache
              </button>
            </form>
          </div>
        </div>
      </div>

      {{-- Queue Management --}}
      <div class="col-md-6">
        <div class="card ">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Queue Management</h5>
          </div>
          <div class="card-body">
            <p class="mb-2">
              Clears all failed jobs and restarts queue workers to resume job processing cleanly.
            </p>
            <ul class="small ps-3 mb-0 text-muted">
              <li>Runs <code>php artisan queue:clear</code> for failed jobs.</li>
              <li>Runs <code>php artisan queue:restart</code> to reload workers.</li>
            </ul>
          </div>
          <div class="card-footer">
            <form class="utility-action-form"
                  action="{{ route('settings.utilities.queue-manage') }}"
                  method="POST"
                  data-confirm="Run queue maintenance (clear failed jobs & restart)?">
              @csrf
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-play-circle me-1"></i>Run Queue Maintenance
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
