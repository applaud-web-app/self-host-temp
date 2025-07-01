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
      <h2 class="mb-0">Backup Management</h2>
    </div>

    {{-- New Backup Card --}}
    <div class="card h-auto mb-4">
      <div class="card-header">
         <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Generate New Backup</h5>
      </div>
      <div class="card-body">
        <p class="mb-2">
          Click the button below to generate and download a new backup of all subscribers in the system. The backup will be in XLSX format, containing all the records you need to secure.
        </p>
    
        <button id="download-backup-btn" class="btn btn-primary mt-2 mt-md-0">
          <i class="fas fa-download me-1"></i> Download New Backup
        </button>
      </div>
    </div>

    {{-- Latest Backup Card --}}
    <div class="card h-auto mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i> Latest Backup Details</h5>
      </div>
      <div class="card-body">
        @if(!$latestBackup)
          <div class="alert alert-warning mb-0">
            <i class="fas fa-info-circle me-2"></i>
            No backups have been created yet. Please generate a backup to proceed.
          </div>
        @else
          <div>
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
              <div class="mb-3 mb-md-0">
                <div class="fw-bold fs-5">{{ $latestBackup->filename }}</div>
                <div class="text-muted small mt-1">
                  <i class="fas fa-database me-1"></i>{{ $latestBackup->count }} records
                  &nbsp;|&nbsp;
                  <i class="fas fa-calendar-alt me-1"></i>{{ $latestBackup->created_at->format('Y-m-d H:i:s') }}
                </div>
              </div>
              <a href="{{ asset('storage/' . $latestBackup->path) }}"
                 class="btn btn-outline-secondary btn-sm"
                 download>
                <i class="fas fa-download me-1"></i> Download Backup
              </a>
            </div>
            <p class="mt-3">
              The most recent backup contains {{ $latestBackup->count }} subscriber records and was created on {{ $latestBackup->created_at->format('Y-m-d') }}. Click the button above to download it.
            </p>
          </div>
        @endif
      </div>
    </div>

    {{-- Restore Info Card --}}
    <div class="card h-auto mb-4">
      <div class="card-body">
        <h5 class="mb-3"><i class="fas fa-exclamation-circle me-2"></i> Need to Restore a Backup?</h5>
        <p>
          If you need to restore a backup, please <a href="mailto:info@aplu.com" class="text-primary">contact support</a> for assistance. We can help you revert to any previous backup if necessary. Restoring a backup will overwrite current data, so be sure that this is the action you want to take. If you're unsure, reach out for help.
        </p>
      </div>
    </div>

  </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>
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
        // Show "Processing" message using iziToast
        iziToast.info({
          title: 'Processing',
          message: 'Preparing your download...',
          position: 'topRight',
          timeout: 3000 // Show for 3 seconds
        });

        // Disable the button to prevent multiple clicks
        btn.disabled = true;

        // Wait for a moment before starting the download (simulating processing)
        setTimeout(function() {
          // Trigger the download
          window.location.href = @json(route('settings.backup-subscribers.download'));

          // Show the success message after the download starts
          iziToast.success({
            title: 'Success',
            message: 'Your backup is ready for download!',
            position: 'topRight',
            timeout: 5000
          });
        }, 3000); // 3-second delay to simulate processing
      }
    });
  });
});
</script>
@endpush
