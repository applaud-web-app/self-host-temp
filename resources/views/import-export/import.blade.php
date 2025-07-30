@extends('layouts.master')

@section('content')
<section class="content-body" id="import_page">
    <div class="container-fluid">
        <div class="text-head mb-3">
            <h2 class="mb-0">Import Management</h2><span class="text-primary">[{{$domain}}]</span>
        </div>

        <!-- New Import Card -->
        <div class="card h-auto mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-upload me-2"></i> Import Data</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    Drag and drop your import file here, or click to select. 
                    The XLSX file should include token, endpoint, auth, p256dh, IP address, status, subscribed_url, device, browser, and platform.
                </p>

                <!-- Import Form -->
                <form id="import-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- Dropzone Area -->
                    <div id="dropzone" class="dropzone">
                        <p>Drag and Drop a file here</p>
                        <!-- Hidden file input -->
                        <input type="file" name="file" id="file-upload" class="file-upload" accept=".xlsx, .xls" required style="display: none;">
                    </div>
                    <button type="button" id="submit-btn" class="btn btn-primary w-100 mt-3">
                        <i class="fas fa-upload me-1"></i> Import Data
                    </button>
                </form>
                
                <!-- Progress Bar -->
                <div id="progress-bar-container" class="d-none mt-3">
                    <div class="progress">
                        <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    /* Dropzone Styling */
    #dropzone {
        border: 2px dashed #007bff;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        color: #007bff;
        background-color: #f7f7f7;
        position: relative;
        cursor: pointer;
    }

    #dropzone p {
        font-size: 18px;
        font-weight: bold;
    }

    /* On Hover Animation */
    #dropzone:hover {
        background-color: #e8f0fe;
        box-shadow: 0px 4px 10px rgba(0, 123, 255, 0.2);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }

    /* Wave Animation */
    .wave {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(0, 123, 255, 0.3);
        transform: translate(-50%, -50%);
        animation: wave-animation 1.5s ease-out infinite;
    }

    @keyframes wave-animation {
        0% {
            width: 50px;
            height: 50px;
            opacity: 1;
        }
        50% {
            opacity: 0.5;
            width: 90px;
            height: 90px;
        }
        100% {
            opacity: 0;
            width: 50px;
            height: 50px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('file-upload');
        const progressBarContainer = document.getElementById('progress-bar-container');
        const progressBar = document.getElementById('progress-bar');
        const submitBtn = document.getElementById('submit-btn');
        const form = document.getElementById('import-form');

        // Trigger the hidden file input when the dropzone is clicked
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        // Add wave animation when user starts selecting a file
        fileInput.addEventListener('change', function() {
            showWaveAnimation();
        });

        // Handle dragover and dragleave for visual feedback
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.style.backgroundColor = '#e8f0fe';
        });

        dropzone.addEventListener('dragleave', function() {
            dropzone.style.backgroundColor = '#f7f7f7';
        });

        // Handle file drop
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.style.backgroundColor = '#f7f7f7';
            const file = e.dataTransfer.files[0];
            if (file) {
                fileInput.files = e.dataTransfer.files;
                showWaveAnimation();
            }
        });

        // Show wave animation function
        function showWaveAnimation() {
            const wave = document.createElement('div');
            wave.classList.add('wave');
            dropzone.appendChild(wave);
        }

        // Submit the form via AJAX
        submitBtn.addEventListener('click', function() {
            // Disable button and show processing
            submitBtn.innerHTML = 'Processing...';
            submitBtn.disabled = true;

            let formData = new FormData(form);
            
            $.ajax({
                url: form.action, // Endpoint for import
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    // Show progress bar
                    progressBarContainer.classList.remove('d-none');
                },
                success: function(response) {
                    // Handle success response
                    iziToast.success({
                        title: 'Success',
                        message: 'Your import is completed!',
                        position: 'topRight',
                        timeout: 5000
                    });
                    progressBar.style.width = '100%';
                    progressBar.setAttribute('aria-valuenow', 100);
                    submitBtn.innerHTML = 'Import Completed';

                    // Reload the page after a successful import
                    setTimeout(function() {
                        window.location = "{{route('domain.view')}}";
                    }, 2000); // Add a delay of 2 seconds before reloading to let the success message display
                },
                error: function(xhr, status, error) {
                    // Handle error response
                    iziToast.error({
                        title: 'Error',
                        message: 'There was an error during import. Please try again.',
                        position: 'topRight',
                        timeout: 5000
                    });
                    progressBar.style.width = '0%';
                    progressBar.setAttribute('aria-valuenow', 0);
                    submitBtn.innerHTML = 'Try Again';
                    submitBtn.disabled = false;
                }
            });
        });
    });
</script>
@endpush
