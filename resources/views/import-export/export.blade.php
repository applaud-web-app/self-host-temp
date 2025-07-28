@extends('layouts.master')

@section('content')
<section class="content-body" id="export_page">
    <div class="container-fluid">
        <div class="text-head mb-3">
            <h2 class="mb-0">Export Management</h2>
        </div>

        <!-- New Export Card -->
        <div class="card h-auto mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-download me-2"></i> Export Data</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    Click the button below to download the export file. 
                    The XLSX file will include endpoint, device keys, IP address, domain name, and VAPID keys.
                </p>

                <!-- Export Button -->
                <button id="download-export-btn" class="btn btn-primary w-100 mt-2 mt-md-0">
                    <i class="fas fa-download me-1"></i> Download Export
                </button>
            </div>
        </div>

        <!-- Restore Info Card (Static) -->
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
        const downloadBtn = document.getElementById('download-export-btn');

        // Export button click event
        downloadBtn.addEventListener('click', function() {
            // Simulate the download action
            iziToast.info({
                title: 'Processing',
                message: 'Preparing your export... Please wait.',
                position: 'topRight',
                timeout: 3000
            });

            // Simulate download action (replace with actual export logic)
            setTimeout(function() {
                // Here you would handle the actual export request
                window.location.href = "{{ asset('storage/exports/subscribers_export_2025-07-26_120000.xlsx') }}";
                iziToast.success({
                    title: 'Success',
                    message: 'Your export is ready for download!',
                    position: 'topRight',
                    timeout: 5000
                });
            }, 3000);
        });
    });
</script>
@endpush
