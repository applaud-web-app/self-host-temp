@extends('layouts.master')

@push('styles')
  {{-- Select2 CSS --}}
  <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
  {{-- (Optional) Dropzone CSS for your visual drop area --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" integrity="sha512-WvVX1YO12zmsvTpUQV8s7ZU98DnkaAokcciMZJfnNWyNzm7//QRV61t4aEr0WdIa4pe854QHLTV302vH92FSMw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    /* Card polish */
    .card { border-radius: 1rem; }

    /* Drop area (design only) */
    .dz-ui {
      border: 2px dashed rgba(0,0,0,.12);
      border-radius: 1rem;
      min-height: 180px;
      padding: 1.75rem;
      background:
        radial-gradient(1200px 300px at 10% 0%, rgba(0,123,255,.03), transparent 60%),
        radial-gradient(1200px 300px at 90% 100%, rgba(13,110,253,.03), transparent 60%),
        #fff;
      transition: border-color .2s ease, box-shadow .2s ease, transform .02s ease;
      cursor: pointer;
      user-select: none;
    }
    .dz-ui:hover,
    .dz-ui:focus {
      border-color: rgba(13,110,253,.35);
      box-shadow: 0 0 0 .25rem rgba(13,110,253,.08);
    }
    .dz-ui:active { transform: scale(.998); }

    .dz-icon {
      font-size: 2rem;
      line-height: 1;
      opacity: .85;
    }

    /* Improve native file input focus */
    #files:focus + .form-text,
    #files:focus-visible + .form-text { outline: none; }

    /* Subtle helper text color alignment */
    .form-text { color: var(--bs-secondary-color); }
  </style>
@endpush

@section('content')
<section class="content-body">
  <div class="container-fluid position-relative">
    <div class="text-head mb-3 d-flex align-items-center">
      <h2 class="me-auto mb-0">Migrate Subscribers</h2>
    </div>

    <div class="row">
      {{-- Left: Form (col-lg-8) --}}
      <div class="col-12 col-lg-8 col-xxl-7">
        <div class="card">
          <div class="card-body">

            <p class="text-secondary mb-3">
              First choose a domain from the list below. This will determine where your subscribers are migrated.
              Once a domain is selected, add your files using the upload area.
              We currently support <span class="fw-semibold">CSV, XLSX, and ZIP</span> files up to <span class="fw-semibold">50&nbsp;MB</span>.
              After uploading, review your selections and click <span class="fw-semibold">Submit</span> to start the migration.
            </p>

            <form id="migrateForm" method="POST" action="" enctype="multipart/form-data" novalidate>
              @csrf

              {{-- Domain --}}
              <div class="mb-4">
                <label for="domain" class="form-label fw-semibold">Domain <span class="text-danger">*</span></label>
                <select id="domain" name="domain_id" class="form-select form-control select2" required data-placeholder="Select a domain...">
                  <option value="" selected disabled>Select a domain...</option>

                  {{-- Dummy domain list --}}
                  <option value="1">example.com</option>
                  <option value="2">testdomain.net</option>
                  <option value="3">newsletter.org</option>
                  <option value="4">mailinglist.io</option>
                  <option value="5">marketing.co</option>

                  {{-- Dynamic domains (if provided) --}}
                  @foreach(($domains ?? []) as $domain)
                    <option value="{{ $domain->id }}">{{ $domain->name }}</option>
                  @endforeach
                </select>
                <div class="form-text">Pick the destination domain for these subscribers.</div>
              </div>

              {{-- Drop area (design-only) --}}
              <div class="mb-4">
                <label class="form-label fw-semibold d-block">Upload Files</label>

                <label for="files" class="dz-ui d-flex align-items-center justify-content-center text-center w-100" tabindex="0">
                  <div>
                    <div class="dz-icon mb-2" aria-hidden="true">📤</div>
                    <div class="fw-semibold">Drag &amp; drop your files here</div>
                    <div class="text-secondary small">or click to browse</div>
                  </div>
                </label>
                <input id="files" name="files[]" class="visually-hidden" type="file" multiple accept=".csv,.xlsx,.xls,.zip" />

                <div class="small text-muted mt-2">
                  Accepted: CSV, XLSX, *.XLS, ZIP • Max 50&nbsp;MB total
                </div>
              </div>

              {{-- Actions --}}
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">Submit</button>
                <button type="reset" class="btn btn-outline-secondary">Clear</button>
              </div>
            </form>

          </div>
        </div>
      </div>
    
    </div><!-- /.row -->

  </div>
</section>
@endsection


@push('scripts')
  {{-- (Optional) Dropzone JS (used only for styling here, not initialized) --}}
  <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js" integrity="sha512-oQq8uth41D+gIH/NJvSJvVB85MFk1eWpMK6glnkg6I7EdMqC1XVkW7RxLheXwmFdG03qScCM7gKS/Cx3FYt7Tg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  {{-- Select2 JS --}}
  <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>

  <script>
    (function() {
      // Initialize Select2 on the domain select
      const $domain = $('.select2');
      if ($domain.length) {
        $domain.select2({
          placeholder: $domain.data('placeholder') || 'Select a domain...',
          allowClear: true,
          width: '100%',
          minimumResultsForSearch: 0 // always show search
        });
      }

      // Make the custom drop area trigger the hidden input
      const dzUi = document.querySelector('.dz-ui');
      const filesInput = document.getElementById('files');
      if (dzUi && filesInput) {
        dzUi.addEventListener('click', () => filesInput.click());
        dzUi.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            filesInput.click();
          }
        });
      }
    })();
  </script>
@endpush
