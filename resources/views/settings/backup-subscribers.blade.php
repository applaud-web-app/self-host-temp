{{-- resources/views/settings/backup-subscribers.blade.php --}}
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

  <div class="container-fluid">
    <div class="text-head mb-3">
      <h2>Backup Subscribers</h2>
    </div>

    {{-- Download New Backup --}}
    <div class="card mb-4">
      <div class="card-body">
        <p>
          Generate and download a new XLSX backup of all subscribers.
        </p>
        <button id="download-backup-btn"
                class="btn btn-primary">
          <i class="fas fa-download me-1"></i> Download New Backup
        </button>
      </div>
    </div>

    {{-- Previous Backups --}}
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Previous Backups</h5>
      </div>
      <div class="card-body">
        @if($backups->isEmpty())
          <p class="text-muted">No backups have been created yet.</p>
        @else
          <ul class="list-group">
            @foreach($backups as $b)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  {{-- Serial Number and Filename --}}
                  <span class="me-2"><strong>#{{ $loop->iteration }}</strong></span>
                  <strong>{{ $b->filename }}</strong><br>
                  {{-- Count and Date with Icon --}}
                  <small class="text-muted">
                    <i class="fas fa-database me-1"></i>{{ $b->count }} records
                    &nbsp;|&nbsp;
                    <i class="fas fa-calendar-alt me-1"></i>{{ $b->created_at->format('Y-m-d H:i:s') }}
                  </small>
                </div>
                <a href="{{ asset('storage/' . $b->path) }}"
                   class="btn btn-sm btn-secondary"
                   download>
                  <i class="fas fa-download me-1"></i> Download
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('download-backup-btn');

  btn.addEventListener('click', function() {
    Swal.fire({
      title: 'Are you sure?',
      text: 'Generate and download a new backup of all subscribers?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, download it!',
      cancelButtonText: 'Cancel',
      reverseButtons: true
    }).then(result => {
      if (result.isConfirmed) {
        iziToast.info({
          title: 'Processing',
          message: 'Preparing your download...',
          position: 'topRight',
          timeout: 3000
        });
        window.location.href = @json(route('settings.backup-subscribers.download'));
      }
    });
  });
});
</script>
@endpush
