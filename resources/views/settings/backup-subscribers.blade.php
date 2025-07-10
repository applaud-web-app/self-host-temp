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
      <h2 class="mb-0">Backup Management</h2>
    </div>

    <!-- New Backup Card -->
    <div class="card h-auto mb-4">
      <div class="card-header">
         <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Generate New Backup</h5>
      </div>
      <div class="card-body">
        <p class="mb-2">
          Click the button below to generate and download a new backup of all push subscribers. 
          The XLSX file will include endpoint, device keys, IP address, domain name, and VAPID keys.
        </p>
    
        <button id="download-backup-btn" class="btn btn-primary mt-2 mt-md-0">
          <i class="fas fa-download me-1"></i> Download New Backup
        </button>
      </div>
    </div>

    <!-- Latest Backup Card -->
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
              This backup includes endpoint, device keys (auth, p256dh), IP address, domain name, and VAPID credentials.
              It contains {{ $latestBackup->count }} records and was created on {{ $latestBackup->created_at->format('Y-m-d') }}.
            </p>
          </div>
        @endif
      </div>
    </div>

    <!-- Restore Info Card -->
    <div class="card h-auto mb-4">
      <div class="card-body">
        <h5 class="mb-3"><i class="fas fa-exclamation-circle me-2"></i> Need to Restore a Backup?</h5>
        <p>
          If you need to restore a backup, please <a href="mailto:info@aplu.com" class="text-primary">contact support</a> for assistance.
          Restoring a backup will overwrite current data. Contact us before proceeding.
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
        iziToast.info({
          title: 'Processing',
          message: 'Preparing your download...',
          position: 'topRight',
          timeout: 3000
        });

        btn.disabled = true;

        setTimeout(function() {
          window.location.href = @json(route('settings.backup-subscribers.download'));
          iziToast.success({
            title: 'Success',
            message: 'Your backup is ready for download!',
            position: 'topRight',
            timeout: 5000
          });

          // Reload the page after success toast is shown
          setTimeout(function() {
            location.reload();
          }, 5000); // Wait for the toast to disappear before reloading
        }, 3000);
      }
    });
  });
});
</script>
@endpush
