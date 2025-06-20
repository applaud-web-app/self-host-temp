@extends('layouts.master')
@section('content')
<section class="content-body">
    <div class="container-fluid position-relative">
    {{-- page title --------------------------------------------------------- --}}
    <div class="d-flex align-items-center mb-4">
      <h2 class="fw-semibold me-auto mb-0">
        Application Update
      </h2>
    </div>

    {{-- main card ---------------------------------------------------------- --}}
    <div class="row">
      <div class="col-lg-12">
        <div class="card h-auto">
          <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0">
              Current Version:
              <span class="badge bg-secondary">v{{ $currentVersion }}</span>
            </h5>
          </div>

          <div class="card-body">
            {{-- ----------------- 1) ZIP has been uploaded ---------------- --}}
            @if ($zipReady)
              <div class="alert alert-warning d-flex align-items-start rounded-3">
                <i class="fas fa-exclamation-triangle fa-lg mt-1 me-2"></i>
                <div>
                  <strong class="d-block">Update ready to install</strong>
                  A new version was uploaded. Make sure you’ve taken a recent
                  database backup before continuing.
                </div>
              </div>

              <div class="d-flex flex-wrap align-items-center gap-3">
                <button id="install-update" class="btn btn-lg btn-primary px-4">
                  <i class="fas fa-rocket me-2"></i> Install Update
                </button>
                <small class="text-muted">Package size : {{ $updateSize }}</small>
              </div>

              <div id="install-progress" class="mt-4" style="display:none;">
                <div class="progress" style="height: .875rem;">
                  <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill"
                       style="width:0%"></div>
                </div>
                <div id="install-status" class="small text-muted mt-2"></div>
              </div>

            {{-- ----------------- 2) no ZIP yet -- show upload form -------- --}}
            @else
              <div class="alert alert-info d-flex align-items-start rounded-3">
                <i class="fas fa-info-circle fa-lg mt-1 me-2"></i>
                <div>
                  <strong class="d-block">How to update</strong>
                  <ol class="mb-0 ps-3">
                    <li>Export the ZIP containing <code>composer.json</code>,
                        <code>app/</code> & <code>public/</code>.</li>
                    <li>Upload it with the form below.</li>
                    <li>Click <em>Install Update</em>.</li>
                  </ol>
                </div>
              </div>

              <form id="upload-form" class="row g-3 needs-validation" novalidate enctype="multipart/form-data">
                @csrf
                <div class="col-12">
                  <label for="update_file" class="form-label">Update package (ZIP)</label>
                  <input type="file" class="form-control" id="update_file" name="update_file" required>
                  <div class="invalid-feedback">Please choose a valid ZIP &lt; 100 MB.</div>
                </div>

                <div class="col-12 text-end">
                  <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-upload me-2"></i> Upload
                  </button>
                </div>

                <div id="upload-progress" class="col-12" style="display:none;">
                  <div class="progress" style="height: .875rem;">
                    <div class="progress-bar rounded-pill" style="width:0%"></div>
                  </div>
                  <div id="upload-status" class="small text-muted mt-2"></div>
                </div>
              </form>
            @endif
          </div>

          <div class="card-footer bg-light border-0 small text-muted">
            <i class="fas fa-clock me-1"></i> Last checked:
            {{ now()->format('Y-m-d H:i:s') }}
          </div>
        </div>

        {{-- backups table -------------------------------------------------- --}}
        @if ($backups->count())
        <div class="card h-auto">
            <div class="card-header">
              <h5 class="mb-0">Available Backups</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table display">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Size</th>
                      <th class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($backups as $backup)
                      <tr>
                        <td>{{ $backup['date'] }}</td>
                        <td>{{ $backup['size'] }}</td>
                        <td class="text-center">
                          <button class="btn btn-sm btn-outline-primary restore-btn"
                                  data-date="{{ $backup['date'] }}">
                            <i class="fas fa-undo me-1"></i> Restore
                          </button>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {

  /* ------------------------------------------------------ */
  /* Bootstrap client-side validation                       */
  /* ------------------------------------------------------ */
  (() => {
    'use strict';
    document.querySelectorAll('.needs-validation').forEach(form => {
      form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
          e.preventDefault();  e.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  /* ------------------------------------------------------ */
  /* Upload ZIP                                             */
  /* ------------------------------------------------------ */
  $('#upload-form').on('submit', function (e) {
    e.preventDefault();
    if (!$('#update_file').prop('files').length) return;

    const formData = new FormData(this);
    $('#upload-progress').slideDown();
    $('#upload-status').html('<i class="fas fa-spinner fa-spin"></i> Starting upload…');

    $.ajax({
      url: '{{ route('update.upload') }}',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      xhr: () => {
        const xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', evt => {
          if (evt.lengthComputable) {
            const pct = Math.round((evt.loaded / evt.total) * 100);
            $('#upload-progress .progress-bar').css('width', pct + '%');
            $('#upload-status').text('Uploading ' + pct + '%');
          }
        });
        return xhr;
      },
      success: res => {
        $('#upload-status').html('<i class="fas fa-check-circle text-success"></i> ' + res.message);
        setTimeout(() => location.reload(), 1500);
      },
      error: xhr => {
        const msg = xhr.responseJSON?.message || 'Upload failed.';
        $('#upload-status').html('<i class="fas fa-times-circle text-danger"></i> ' + msg);
        $('#upload-progress .progress-bar').addClass('bg-danger');
        Swal.fire('Upload failed', msg, 'error');
      }
    });
  });

  /* ------------------------------------------------------ */
  /* Install update                                         */
  /* ------------------------------------------------------ */
  $('#install-update').on('click', function () {
    Swal.fire({
      title: 'Install update?',
      text: 'Application will enter maintenance mode.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, install',
      reverseButtons: true,
    }).then(result => {
      if (!result.isConfirmed) return;

      const $btn = $('#install-update')
                   .prop('disabled', true)
                   .html('<i class="fas fa-spinner fa-spin"></i> Installing…');

      $('#install-progress').slideDown();
      $('#install-status').html('<i class="fas fa-spinner fa-spin"></i> Starting update…');

      const poll = setInterval(() => {
        $.get('{{ route('update.progress') }}', data => {
          $('#install-progress .progress-bar').css('width', data.progress + '%');
          $('#install-status').text(data.message);
        });
      }, 1000);

      $.post('{{ route('update.install') }}', { _token: '{{ csrf_token() }}' })
        .done(res => {
          clearInterval(poll);
          $('#install-progress .progress-bar').css('width', '100%');
          $('#install-status').html('<i class="fas fa-check-circle text-success"></i> ' + res.message);
          Swal.fire('Done', res.message, 'success')
              .then(() => location.reload());
        })
        .fail(xhr => {
          clearInterval(poll);
          const msg = xhr.responseJSON?.message || 'Installation failed.';
          $('#install-progress .progress-bar').addClass('bg-danger');
          $('#install-status').html('<i class="fas fa-times-circle text-danger"></i> ' + msg);
          $btn.prop('disabled', false).html('<i class="fas fa-rocket me-2"></i> Install Update');
          Swal.fire('Error', msg, 'error');
        });
    });
  });

  /* ------------------------------------------------------ */
  /* Restore backup                                         */
  /* ------------------------------------------------------ */
  $('.restore-btn').on('click', function () {
    const date = $(this).data('date');

    Swal.fire({
      title: 'Restore backup?',
      text: 'Your application files will be replaced by backup: ' + date,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, restore',
      reverseButtons: true,
    }).then(result => {
      if (!result.isConfirmed) return;

      const $btn = $(this).prop('disabled', true)
                          .html('<i class="fas fa-spinner fa-spin"></i> Restoring…');

      $.post('{{ route('update.restore') }}', { _token: '{{ csrf_token() }}', date })
        .done(res => {
          Swal.fire('Restored', res.message, 'success')
              .then(() => location.reload());
        })
        .fail(xhr => {
          const msg = xhr.responseJSON?.message || 'Restore failed.';
          Swal.fire('Error', msg, 'error');
          $btn.prop('disabled', false)
              .html('<i class="fas fa-undo me-1"></i> Restore');
        });
    });
  });
});
</script>
@endpush
