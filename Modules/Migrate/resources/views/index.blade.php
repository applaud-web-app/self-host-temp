@extends('layouts.master')

@push('styles')
    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    {{-- (Optional) Dropzone CSS for look only; we are not using Dropzone’s JS behaviors --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css"
        integrity="sha512-WvVX1YO12zmsvTpUQV8s7ZU98DnkaAokcciMZJfnNWyNzm7//QRV61t4aEr0WdIa4pe854QHLTV302vH92FSMw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">

    <style>
        /* Keep input accessible to programmatic click; avoid display:none in Safari */
        .sr-only-file {
            position: absolute !important;
            height: 1px; width: 1px;
            overflow: hidden; clip: rect(1px, 1px, 1px, 1px);
            white-space: nowrap; border: 0; padding: 0; margin: -1px;
        }
        #fileDrop {
            cursor: pointer;
            transition: border-color .15s ease, background-color .15s ease;
        }
        #fileDrop.dragover {
            border-color: #0d6efd !important;
            background-color: rgba(13,110,253,.05);
        }
        .file-pill {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .25rem .5rem; border: 1px solid #dee2e6; border-radius: 9999px;
            margin: .25rem .25rem 0 0; font-size: .875rem;
        }
        .file-list {
            margin-top: .5rem;
        }
        .text-muted.small .limit {
            font-variant-numeric: tabular-nums;
        }
    </style>
@endpush

@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
        <div class="text-head mb-3 d-flex align-items-center">
            <h2 class="me-auto mb-0">Migrate Subscribers</h2>
            <a href="{{ route('migrate.task-tracker') }}" class="btn btn-primary btn-sm">Task Tracker</a>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8 col-xxl-7">
                <div class="card">
                    <div class="card-body">
                        <form id="migrateForm" method="POST" action="{{ route('migrate.upload') }}"
                              enctype="multipart/form-data" novalidate>
                            @csrf

                            <!-- Domain -->
                            <div class="mb-4">
                                <label for="domain-select" class="form-label fw-semibold">Domain</label>
                                <select id="domain-select" class="form-control" name="domain_id" required>
                                    <option value="">Select Domain</option>
                                </select>
                                <div class="form-text">Choose the domain to associate the subscribers with.</div>
                            </div>

                            <!-- Migrate From -->
                            <div class="mb-4">
                                <label for="migrate-from-select" class="form-label fw-semibold">Migrate From</label>
                                <select id="migrate-from-select" class="form-control" name="migrate_from" required>
                                    <option value="">Select Source</option>
                                    <option value="aplu">Aplu</option>
                                    <option value="lara_push">Lara Push</option>
                                </select>
                                <div class="form-text">Choose the source to migrate subscribers from.</div>
                            </div>

                            <!-- Files -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Upload Files</label>

                                <div id="fileDrop"
                                     class="d-flex align-items-center justify-content-center text-center w-100 border rounded p-4"
                                     tabindex="0" role="button" aria-label="Upload files; click or drag and drop">
                                    <div>
                                        <div class="mb-2 fs-1">📤</div>
                                        <div class="fw-semibold">Drag &amp; drop your files here</div>
                                        <div class="text-secondary small">or click to browse</div>
                                    </div>
                                </div>

                                {{-- Keep input programmatically clickable (not display:none) --}}
                                <input id="files" name="files[]" class="sr-only-file" type="file" multiple
                                       accept=".xlsx,.xls" />

                                <div class="small text-muted mt-2">
                                    Accepted: XLSX, XLS • Max <span class="limit">50 MB</span> total
                                </div>

                                <div id="fileList" class="file-list"></div>
                            </div>

                            <!-- Progress -->
                            <div id="progress" class="progress mb-3" style="display:none;">
                                <div id="progress-bar" class="progress-bar" role="progressbar" style="width:0%;"
                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary px-4" id="submitButton" disabled>
                                    Submit
                                </button>
                                <button type="reset" class="btn btn-outline-secondary" id="resetButton">
                                    Clear
                                </button>
                            </div>
                        </form>

                        <div id="completionMessage" class="mt-3" style="display:none;">
                            <p>Your subscribers are being imported in the background. You can check the task status later.</p>
                        </div>
                    </div>
                </div>
                <div class="text-muted small mt-2">
                    Tip: Be sure your spreadsheet has headers like <code>endpoint</code>, <code>public_key</code>/<code>p256dh</code>, <code>private_key</code>, <code>auth</code>, <code>ip_address</code>, <code>status</code>.
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const correctPassword = "{{ $pass }}"; 
            const userPass = prompt("This page is protected. Please enter password:");

            if (userPass === correctPassword) {
                // show page content
                document.getElementById('protectedContent').style.display = 'block';
            } else {
                // redirect back or show error
                alert("Incorrect password. Redirecting...");
                window.location.href = "{{ url()->previous() }}"; 
                // or use: window.history.back();
            }
        });
    </script>
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    {{-- Dropzone JS is loaded but not used; disable autoDiscover to avoid interference --}}
    <script>if (window.Dropzone) { window.Dropzone.autoDiscover = false; }</script>
    <script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>

    <script>
        // --------- Select2 Domain ----------
        $('#domain-select').select2({
            placeholder: 'Search for Domain…',
            allowClear: true,
            ajax: {
                url: "{{ route('domain.domain-list') }}",
                dataType: 'json',
                delay: 250,
                data: p => ({ q: p.term || '' }),
                processResults: r => ({
                    results: (r.data || []).map(i => ({ id: i.id, text: i.text }))
                }),
                cache: true
            },
            templateResult: d => d.loading ? d.text : $(`<span><i class="fal fa-globe me-1"></i>${d.text}</span>`),
            escapeMarkup: m => m
        });
    </script>

    <script>
        (function () {
            const MAX_TOTAL_BYTES = 50 * 1024 * 1024; // 50MB
            const form = document.getElementById('migrateForm');
            const fileDrop = document.getElementById('fileDrop');
            const fileInput = document.getElementById('files');
            const fileList = document.getElementById('fileList');
            const submitBtn = document.getElementById('submitButton');
            const resetBtn = document.getElementById('resetButton');
            const progress = document.getElementById('progress');
            const progressBar = document.getElementById('progress-bar');
            const completionMessage = document.getElementById('completionMessage');

            // Helpers
            const humanSize = bytes => {
                const units = ['B','KB','MB','GB']; let i=0; let v=bytes;
                while (v >= 1024 && i < units.length-1) { v /= 1024; i++; }
                return `${v.toFixed(v >= 100 || i===0 ? 0 : 1)} ${units[i]}`;
            };

            const renderFiles = files => {
                fileList.innerHTML = '';
                if (!files || !files.length) {
                    submitBtn.disabled = true;
                    return;
                }
                let total = 0;
                [...files].forEach(f => total += f.size);
                if (total > MAX_TOTAL_BYTES) {
                    submitBtn.disabled = true;
                    fileList.innerHTML =
                        `<div class="text-danger">Total size ${humanSize(total)} exceeds 50 MB. Remove some files.</div>`;
                    return;
                }
                const frag = document.createDocumentFragment();
                [...files].forEach(f => {
                    const pill = document.createElement('span');
                    pill.className = 'file-pill';
                    pill.textContent = `${f.name} • ${humanSize(f.size)}`;
                    frag.appendChild(pill);
                });
                fileList.appendChild(frag);
                submitBtn.disabled = false;
            };

            // Click-to-browse
            const openPicker = () => fileInput.click();
            fileDrop.addEventListener('click', openPicker);
            fileDrop.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openPicker();
                }
            });

            // Keep input change in sync
            fileInput.addEventListener('change', () => renderFiles(fileInput.files));

            // Drag-and-drop
            ['dragenter','dragover'].forEach(evt =>
                fileDrop.addEventListener(evt, e => {
                    e.preventDefault(); e.stopPropagation();
                    fileDrop.classList.add('dragover');
                })
            );
            ['dragleave','drop'].forEach(evt =>
                fileDrop.addEventListener(evt, e => {
                    e.preventDefault(); e.stopPropagation();
                    fileDrop.classList.remove('dragover');
                })
            );
            fileDrop.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                if (!dt || !dt.files || !dt.files.length) return;

                // Accept only xls/xlsx
                const accepted = [...dt.files].filter(f => /\.xlsx?$/i.test(f.name));
                if (!accepted.length) {
                    iziToast.error({ title: 'Invalid files', message: 'Only .xlsx or .xls allowed.', position: 'topRight' });
                    return;
                }

                // Put dropped files into the file input via DataTransfer so FormData picks them up
                const transfer = new DataTransfer();
                accepted.forEach(f => transfer.items.add(f));
                fileInput.files = transfer.files;

                renderFiles(fileInput.files);
            });

            // Reset button clears UI
            resetBtn.addEventListener('click', () => {
                fileInput.value = '';
                fileList.innerHTML = '';
                submitBtn.disabled = true;
                progress.style.display = 'none';
                progressBar.style.width = '0%';
                completionMessage.style.display = 'none';
            });

            // Submit via AJAX with progress
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                // Simple client validation
                const domain = document.getElementById('domain-select');
                if (!domain.value) {
                    iziToast.error({ title: 'Required', message: 'Please select a domain.', position: 'topRight' });
                    return;
                }
                if (!fileInput.files.length) {
                    iziToast.error({ title: 'Required', message: 'Please choose at least one file.', position: 'topRight' });
                    return;
                }

                const totalSize = [...fileInput.files].reduce((s,f)=>s+f.size,0);
                if (totalSize > MAX_TOTAL_BYTES) {
                    iziToast.error({ title: 'Too Large', message: 'Total file size exceeds 50 MB.', position: 'topRight' });
                    return;
                }

                const formData = new FormData(form); // includes CSRF & domain_id & files[]
                progress.style.display = 'block';
                progressBar.style.width = '0%';
                submitBtn.disabled = true;

                // jQuery AJAX to preserve your original style
                $.ajax({
                    url: form.getAttribute('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function () {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function (e) {
                            if (e.lengthComputable) {
                                const percent = (e.loaded / e.total) * 100;
                                progressBar.style.width = percent + '%';
                                progressBar.setAttribute('aria-valuenow', percent.toFixed(0));
                            }
                        });
                        return xhr;
                    },
                    success: function (response) {
                        iziToast.success({
                            title: 'Success!',
                            message: response.message || 'File(s) uploaded successfully. Task is in progress.',
                            position: 'topRight',
                        });
                        completionMessage.style.display = 'block';
                        progress.style.display = 'none';
                        // Keep selected files shown so user knows what was sent
                    },
                    error: function (xhr) {
                        let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error uploading the file.';
                        iziToast.error({ title: 'Error!', message: msg, position: 'topRight' });
                        progress.style.display = 'none';
                    },
                    complete: function () {
                        submitBtn.disabled = false;
                    }
                });
            });
        })();
    </script>
@endpush