@extends('layouts.master')

@section('content')
<section class="content-body" id="export_page">
    <div class="container-fluid">
        <div class="text-head mb-3">
            <h2 class="mb-0">Export Management</h2><span class="text-primary">[{{$domain}}]</span>
        </div>

        <!-- New Export Card -->
        <div class="card h-auto mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-download me-2"></i> Export Data</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    Click the button below to download the export file. 
                    The XLSX file will include token, endpoint, auth, p256dh, device keys, IP address, status, subscribed_url, device, browser, platform, and VAPID keys.
                </p>

                <!-- Export Button -->
                <button id="download-export-btn" data-url="{{$encryptDownloadUrl}}" class="btn btn-primary w-100 mt-2 mt-md-0">
                    <i class="fas fa-download me-1"></i> Download Export
                </button>
                
                <!-- Progress Bar -->
                <div id="progress-bar-container" class="d-none mt-3">
                    <div class="progress">
                        <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Processing Message -->
                <div id="processing-message" class="d-none mt-3">
                    <p>Export is processing, please wait...</p>
                </div>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const downloadBtn = document.getElementById('download-export-btn');
        const progressBarContainer = document.getElementById('progress-bar-container');
        const progressBar = document.getElementById('progress-bar');
        const processingMessage = document.getElementById('processing-message');

        downloadBtn.addEventListener('click', function() {
            // Disable the button to prevent multiple clicks
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = 'Processing... <i class="fas fa-spinner fa-spin"></i>';

            // Show progress bar and processing message
            progressBarContainer.classList.remove('d-none');
            processingMessage.classList.remove('d-none');
            let progress = 0;

            // Perform the AJAX request
            $.ajax({
                url: "{{ $encryptDownloadUrl }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.downloadUrl) {
                        // Redirect to the download URL after processing
                        window.location.href = response.downloadUrl;
                        iziToast.success({
                            title: 'Success',
                            message: 'Your export is ready for download!',
                            position: 'topRight',
                            timeout: 5000
                        });
                    } else {
                        iziToast.error({
                            title: 'Error',
                            message: response.error || 'Something went wrong.',
                            position: 'topRight',
                            timeout: 5000
                        });
                    }

                    // Hide progress bar and re-enable button after success
                    progressBarContainer.classList.add('d-none');
                    downloadBtn.disabled = false;
                    downloadBtn.innerHTML = 'Download Export';
                },
                error: function() {
                    iziToast.error({
                        title: 'Error',
                        message: 'Export failed. Please try again.',
                        position: 'topRight',
                        timeout: 5000
                    });

                    // Hide progress bar and re-enable button after error
                    progressBarContainer.classList.add('d-none');
                    downloadBtn.disabled = false;
                    downloadBtn.innerHTML = 'Download Export';
                }
            });

            // Simulate progress bar (real progress can be done through polling or websockets)
            const interval = setInterval(function() {
                progress += 10;
                progressBar.style.width = progress + '%';
                progressBar.setAttribute('aria-valuenow', progress);

                if (progress >= 100) {
                    clearInterval(interval);
                }
            }, 1000);
        });
    });
</script>
@endpush