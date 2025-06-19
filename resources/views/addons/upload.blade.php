@extends('layouts.master')

@push('styles')
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
<style>
  .styled-dropzone {
    border: 1px dashed var(--primary);
    border-radius: .375rem;
    background: #ffffff;
    padding: 1rem;
    text-align: center;
    transition: background .3s, border-color .3s;
  }
  .styled-dropzone:hover {
    background: #fbfdff;
    border-color: var(--secondary);
  }
  .styled-dropzone .dz-message {
    font-size: 1rem;
    color: #495057;
  }
</style>
@endpush

@section('content')
<section class="content-body">
  <div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
      <h2>Upload Add-on</h2>
    </div>
    <div class="row">
      <!-- Upload Form -->
      <div class="col-lg-6 mb-4">
        <div class="card">
          <div class="card-header"><h5 class="mb-0 card-title">Upload Form</h5></div>
          <div class="card-body">
            @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form id="uploadForm" method="POST" enctype="multipart/form-data" novalidate>
              
              {{-- Add-on ZIP Label --}}
              <label for="zipDropzone" class="form-label">Add-on ZIP File <span class="text-danger">*</span></label>
              @csrf

              {{-- Styled Dropzone --}}
              <div id="zipDropzone" class="dropzone styled-dropzone mb-3">
                <div class="dz-message text-center">
                  <i class="fas fa-cloud-upload-alt fa-3x mb-2 text-secondary"></i>
                  <p class="mb-1">Drop ZIP here or click to select</p>
                  <small class="text-muted">Only .zip files</small>
                </div>
              </div>

              {{-- Version --}}
              <div class="mb-3">
                <label for="version">Version <span class="text-danger">*</span></label>
                <input id="version" name="version" type="text"
                       class="form-control" placeholder="e.g. 1.0.0" required>
                <div class="invalid-feedback">Please enter a version.</div>
              </div>
                
              {{-- Progress --}}
              <div class="mb-3">
                <div id="uploadProgressWrapper" class="mb-2" style="display:none;">
                  <label>Progress:</label>
                  <div class="progress">
                    <div id="uploadProgressBar" class="progress-bar" role="progressbar"
                         style="width:0%" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                </div>
              </div>

              {{-- Actions --}}
              <div class="text-end">
                <button type="button" id="submitBtn" class="btn btn-primary" disabled>
                  <i class="fas fa-upload me-2"></i>
                  <span id="btnText">Upload Module</span>
                </button>
                <a href="{{ route('addons.upload') }}" class="btn btn-light ms-2">Cancel</a>
              </div>

           
            </form>
          </div>
        </div>
      </div>

      <!-- Instructions -->
      <div class="col-lg-6 ">
        <div class="card h-auto">
          <div class="card-header"><h5 class="mb-0 card-title">Instructions</h5></div>
          <div class="card-body">
            <ul class="list-unstyled">
              <li><i class="fas fa-check-circle text-success me-2"></i>Drag & drop or click the field to select your ZIP.</li>
              <li><i class="fas fa-check-circle text-success me-2"></i>Ensure the ZIP contains the module folder.</li>
              <li><i class="fas fa-check-circle text-success me-2"></i>Version must use semantic versioning (e.g. <kbd>1.0.0</kbd>).</li>
              <li><i class="fas fa-check-circle text-success me-2"></i>Uploading replaces any existing module folder.</li>
              <li><i class="fas fa-check-circle text-success me-2"></i>Watch the progress bar to completion.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection



@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">
<script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>
<script>
  Dropzone.autoDiscover = false;
  const dz = new Dropzone('#zipDropzone', {
    url: '{{ route("addons.store") }}',
    paramName: 'zip',
    maxFiles: 1,
    acceptedFiles: '.zip',
    autoProcessQueue: false,
    previewsContainer: '#zipDropzone',
    clickable: '#zipDropzone',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'X-Requested-With': 'XMLHttpRequest'
    }
  });

  const versionInput = document.getElementById('version');
  const submitBtn    = document.getElementById('submitBtn');
  const btnText      = document.getElementById('btnText');
  const wrapper      = document.getElementById('uploadProgressWrapper');
  const bar          = document.getElementById('uploadProgressBar');
  const form         = document.getElementById('uploadForm');

  function updateButton() {
    submitBtn.disabled = !(dz.getAcceptedFiles().length && versionInput.value.trim());
  }

  dz.on('addedfile', updateButton);
  dz.on('removedfile', updateButton);
  versionInput.addEventListener('input', updateButton);

  submitBtn.addEventListener('click', () => dz.processQueue());

  dz.on('sending', (file, xhr, formData) => {
    formData.append('version', versionInput.value);
    btnText.textContent = 'Processing…';
    submitBtn.disabled = true;
    wrapper.style.display = 'block';
    bar.style.width = '0%';
  });

  dz.on('uploadprogress', (file, progress) => {
    bar.style.width = `${progress}%`;
  });

  dz.on('success', (file) => {
    iziToast.success({ title: 'Upload Complete', message: 'Your module has been installed.', position: 'topRight' });
    // Reload page after a short delay to allow the toast to display
    setTimeout(() => window.location.reload(), 1500);
});
    dz.removeAllFiles();
    versionInput.value = '';
    form.classList.remove('was-validated');
    wrapper.style.display = 'none';
    btnText.textContent = 'Upload Module';
    submitBtn.disabled = true;
  });

  dz.on('error', (file, err) => {
    iziToast.error({ title: 'Error', message: err, position: 'topRight' });
    dz.removeFile(file);
    wrapper.style.display = 'none';
    btnText.textContent = 'Upload Module';
    submitBtn.disabled = true;
  });
</script>
@endpush
