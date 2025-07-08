@extends('layouts.master')

@section('content')
<section class="content-body">
  <div class="container-fluid">

    <div class="text-head mb-3">
      <h2 class="mb-0">System Upgrade</h2>
    </div>

    <div class="row g-4">

      {{-- 1️⃣  Current version ------------------------------------------------ --}}
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
              <i class="fas fa-info-circle me-2"></i> Current Version
            </h5>
            <span class="badge bg-success">Up to date</span>
          </div>
          <div class="card-body">
            <p class="mb-1"><strong>Version:</strong> 1.0.0</p>
            <p class="mb-1"><strong>Build:</strong> 1001</p>
            <p class="mb-1"><strong>Release Date:</strong> 2024-01-15</p>
            <p class="mb-0">This version is stable and secure. No critical issues detected.</p>
          </div>
        </div>
      </div>

      {{-- 2️⃣  New version card --------------------------------------------- --}}
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">
              <i class="fas fa-download me-2"></i> New Version Available
            </h5>
          </div>
          <div class="card-body">
            <p class="mb-1"><strong>Version:</strong> 1.1.0</p>
            <p class="mb-1"><strong>Build:</strong> 1102</p>
            <p class="mb-1"><strong>Release Date:</strong> 2025-05-30</p>
            <p class="mb-1"><strong>What’s New:</strong></p>
            <ul class="mb-3">
              <li>Security patch for authentication module.</li>
              <li>Performance optimisations for dashboard.</li>
              <li>New analytics features added.</li>
            </ul>

            {{-- upgrade link (SweetAlert confirmation → /update) --}}
            <a href="https://aplu.io/contact"
               id="go-to-updater"
               class="btn btn-success w-100">
               <i class="fas fa-upload me-1"></i> Upgrade to 1.1.0
            </a>
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
document.getElementById('go-to-updater').addEventListener('click', function (e) {
  e.preventDefault();
  const target = this.getAttribute('href');

  Swal.fire({
    title: 'Proceed to updater?',
    text: 'You’ll be taken to the update page where you can upload & install the new version.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, take me there',
    reverseButtons: true,
  }).then(result => {
    if (result.isConfirmed) window.location.href = target;
  });
});
</script>
@endpush
