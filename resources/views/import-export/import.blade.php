@extends('layouts.master')

<!-- Optional: Custom Styling for Dropzone -->
@push('styles')
<style>
    /* Styling for dropzone container */
    #myDropzone {
        border: 2px dashed var(--primary);
        padding: 20px;
        border-radius: 8px;
        background-color: #f8f9fa;
    }

    .dropzone-text {
        font-size: 16px;
        font-weight: bold;
        color: var(--primary);
        text-align: center;
        margin: 0;
    }

    .dropzone .dz-message {
        text-align: center;
    }

    /* Success message */
    #successMessage {
        padding: 10px;
        background-color: #d4edda;
        border-radius: 5px;
        font-weight: bold;
    }

    /* Styling for Instructions */
    .instructions {
        background-color: #f1f1f1;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .instructions ul {
        list-style-type: disc;
        padding-left: 20px;
    }

    .instructions li {
        margin-bottom: 10px;
    }
</style>
@endpush

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="text-head mb-3">
            <h2 class="mb-0">Import Management</h2>
        </div>

        <!-- File Upload Card -->
        <div class="card h-auto">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-download me-2"></i> Import Data</h5>
            </div>
            <div class="card-body">
                <!-- File Upload Section -->
                <h5>Upload File (CSV, XLS, XLSX)</h5>
                <form action="{{ route('import-export.import') }}" class="dropzone" id="myDropzone">
                    <div class="dz-message needsclick">
                        <p class="dropzone-text">Drag & drop a file here or click to select one</p>
                    </div>
                </form>

                <!-- Success message, initially hidden -->
                <div id="successMessage" style="display:none; color: green; margin-top: 15px;">
                    <strong>File uploaded successfully!</strong>
                </div>

                <!-- Import Subscriber Button -->
                <button class="import-btn btn btn-primary w-100 mt-3" id="submitBtn">Import Subscriber</button>
            </div>
        </div>

        <!-- Instructions Card -->
        <div class="card h-auto">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Instructions for File Import</h5>
            </div>
            <div class="card-body">
                <div class="instructions">
                    <h5>Instructions for File Import:</h5>
                    <ul>
                        <li><strong>Step 1:</strong> Choose a file (CSV, XLS, XLSX) to import. Ensure the file format is supported and the file size is within the limit.</li>
                        <li><strong>Step 2:</strong> Drag and drop the file into the dropzone or click to manually select the file from your computer.</li>
                        <li><strong>Step 3:</strong> After uploading the file, the "Import Subscriber" button will be activated. Click the "Import Subscriber" button to initiate the import process.</li>
                        <li><strong>Step 4:</strong> Confirm the import, and the file will be uploaded. A success message will appear once the process is complete.</li>
                    </ul>
                    <p><strong>Important Notes:</strong></p>
                    <ul>
                        <li>Ensure that the file contains valid data in the correct format (CSV, XLS, XLSX).</li>
                        <li>The maximum file size for upload is 5MB.</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection

@push('scripts')
<!-- Dropzone.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.7.0/dist/min/dropzone.min.js"></script>
<!-- Dropzone CSS -->
<link href="https://cdn.jsdelivr.net/npm/dropzone@5.7.0/dist/min/dropzone.min.css" rel="stylesheet">

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.5.6/dist/sweetalert2.all.min.js"></script>

<script>
    // Flags to check if file is uploaded
    let isFileUploaded = false;

    // Dropzone configuration
    Dropzone.options.myDropzone = {
        url: "{{ route('import-export.import') }}",  // This will later handle the file upload action
        maxFilesize: 5, // Max file size in MB
        acceptedFiles: '.csv, .xls, .xlsx', // Only accept CSV, Excel files
        dictDefaultMessage: 'Drag & drop a file here or click to select one', // Default message
        init: function() {
            this.on("success", function(file, response) {
                // File uploaded successfully
                isFileUploaded = true;
                document.getElementById('successMessage').style.display = 'block'; // Show success message
            });

            this.on("removedfile", function(file) {
                // If the user removes the file, disable submit button again
                isFileUploaded = false;
                document.getElementById('successMessage').style.display = 'none'; // Hide success message
            });
        }
    };

    // Import Subscriber button action using SweetAlert2
    document.getElementById('submitBtn').addEventListener('click', function() {
        // Check if file is uploaded
        if (isFileUploaded) {
            // Show SweetAlert2 confirmation dialog
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to import the selected file. Please confirm.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Import it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Simulate form submission success
                    Swal.fire(
                        'Success!',
                        'The file has been successfully imported.',
                        'success'
                    );
                    // Replace this with your actual form submission logic
                    console.log('Form submitted');
                }
            });
        } else {
            Swal.fire(
                'Error!',
                'Please upload a valid file before submitting.',
                'error'
            );
        }
    });
</script>
@endpush
