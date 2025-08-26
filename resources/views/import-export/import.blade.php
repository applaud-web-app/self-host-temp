@extends('layouts.master')

@section('content')
<section class="content-body" id="import_page">
    <div class="container-fluid">
        <div class="text-head mb-3 d-flex align-items-center gap-2">
            <h2 class="mb-0">Import Management</h2>
            <span class="text-primary">[{{ $domain }}]</span>
        </div>

        <div class="card h-auto mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-upload me-2"></i> Import Data</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    Drag and drop your import file here, or click to select.
                    The XLSX file should include
                    <strong>token, endpoint, auth, p256dh, IP address (ip), status, subscribed_url (or url), device, browser, platform, country, state, city</strong>.
                </p>

                <!-- Import Form (Dropzone-enabled) -->
                <form id="import-dropzone"
                      class="dropzone border-0 p-0"
                      action="{{ $encryptImportUrl }}"  {{-- contains ?eq=... built in controller --}}
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    {{-- ensure body carries eq to satisfy validator --}}
                    <input type="hidden" name="eq" value="{{ request('eq') }}">

                    <!-- Dropzone Area -->
                    <div class="dz-message needsclick" id="dropzone">
                        <p class="mb-2">Drag and drop your XLSX/XLS file here, or click to select.</p>
                        <small class="text-muted d-block">Max 2,000 rows. Accepted: .xlsx, .xls</small>
                    </div>

                    <!-- Previews -->
                    <div id="dz-previews" class="mt-3"></div>

                    <button type="button" id="submit-btn" class="btn btn-primary w-100 mt-3">
                        <i class="fas fa-upload me-1"></i> Import Data
                    </button>
                </form>

                <!-- Progress Bar -->
                <div id="progress-bar-container" class="d-none mt-3">
                    <div class="progress">
                        <div id="progress-bar" class="progress-bar" role="progressbar"
                             style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5/dist/min/dropzone.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css"/>
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
    #dropzone p { font-size: 18px; font-weight: bold; }

    /* Hover */
    #dropzone:hover {
        background-color: #e8f0fe;
        box-shadow: 0 4px 10px rgba(0, 123, 255, .2);
        transition: background-color .3s ease, box-shadow .3s ease;
    }

    /* Wave animation */
    .wave {
        position: absolute;
        top: 50%; left: 50%;
        width: 80px; height: 80px;
        border-radius: 50%;
        background: rgba(0, 123, 255, .3);
        transform: translate(-50%, -50%);
        animation: wave-animation 1.5s ease-out infinite;
        pointer-events: none;
    }
    @keyframes wave-animation {
        0%   { width: 50px; height: 50px; opacity: 1; }
        50%  { width: 90px; height: 90px; opacity: .5; }
        100% { width: 50px; height: 50px; opacity: 0; }
    }

    /* Preview tidy */
    #dz-previews .dz-preview {
        border: 1px dashed #ced4da;
        border-radius: 8px;
        padding: 10px;
        background: #fff;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dropzone@5/dist/min/dropzone.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const progressBarContainer = document.getElementById('progress-bar-container');
    const progressBar           = document.getElementById('progress-bar');
    const submitBtn             = document.getElementById('submit-btn');
    const dzPreviews            = document.getElementById('dz-previews');

    Dropzone.autoDiscover = false;
    const csrf = document.querySelector('input[name="_token"]').value;

    const dz = new Dropzone("#import-dropzone", {
        url: document.getElementById('import-dropzone').getAttribute('action'),
        method: 'post',
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json' // get JSON error bodies
        },
        paramName: 'file',
        maxFiles: 1,
        maxFilesize: 20,                 // MB
        uploadMultiple: false,
        acceptedFiles: '.xlsx,.xls',
        addRemoveLinks: true,
        autoProcessQueue: false,         // start on button
        previewsContainer: dzPreviews,
        dictDefaultMessage: 'Drop file here or click to upload',
        timeout: 0
    });

    dz.on('addedfile', function () {
        const dropzone = document.getElementById('dropzone');
        const wave = document.createElement('div');
        wave.classList.add('wave');
        dropzone.appendChild(wave);
        if (dz.files.length > 1) dz.removeFile(dz.files[0]);
    });

    dz.on('uploadprogress', function (file, progress) {
        progressBarContainer.classList.remove('d-none');
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', Math.floor(progress));
    });

    dz.on('sending', function (file, xhr, formData) {
        submitBtn.innerHTML = 'Processing...';
        submitBtn.disabled = true;

        // Ensure eq is present even if URL param missing
        const eqHidden = document.querySelector('input[name="eq"]');
        if (eqHidden && eqHidden.value) {
            formData.append('eq', eqHidden.value);
        }
    });

    dz.on('success', function (file, response) {
        progressBar.style.width = '100%';
        progressBar.setAttribute('aria-valuenow', 100);

        iziToast.success({
            title: 'Success',
            message: (response && response.success) ? response.success : 'Your import is completed!',
            position: 'topRight',
            timeout: 5000
        });

        submitBtn.innerHTML = 'Import Completed';

        setTimeout(function () {
            window.location = "{{ route('domain.view') }}";
        }, 2000);
    });

    dz.on('error', function (file, errorMessage, xhr) {
        let msg = 'There was an error during import. Please try again.';
        if (xhr && xhr.responseText) {
            try {
                const parsed = JSON.parse(xhr.responseText);
                if (parsed.error) msg = parsed.error;
                if (parsed.message) msg = parsed.message;
            } catch (e) {}
        } else if (typeof errorMessage === 'string') {
            msg = errorMessage;
        }

        iziToast.error({
            title: 'Error',
            message: msg,
            position: 'topRight',
            timeout: 6000
        });

        progressBar.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', 0);
        submitBtn.innerHTML = 'Try Again';
        submitBtn.disabled = false;
    });

    submitBtn.addEventListener('click', function () {
        if (dz.files.length === 0) {
            iziToast.warning({
                title: 'No file',
                message: 'Please add an .xlsx or .xls file first.',
                position: 'topRight',
                timeout: 4000
            });
            return;
        }
        dz.processQueue();
    });
});
</script>
@endpush
