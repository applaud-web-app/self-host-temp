@extends('layouts.master')

@section('content')
<section class="content-body">
  <div class="container-fluid position-relative">
    {{-- Page title --}}
    <div class="d-flex align-items-center mb-4">
      <h2 class="fw-semibold me-auto mb-0">Application Update</h2>
    </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header ">
            <h5 class="mb-0">
              Current Version:
              <span class="badge bg-secondary">v{{ $currentVersion }}</span>
            </h5>
          </div>

          <div class="card-body">
            <div class="alert alert-warning d-flex align-items-start rounded-3">
              <i class="fas fa-exclamation-triangle fa-lg mt-1 me-3"></i>
              <div>
                <strong class="d-block">Update Available</strong>
                A new version of the application is ready for installation. Please upload the update file to proceed.
              </div>
            </div>

            {{-- Upload ZIP Form --}}
          
              <form id="upload-form" enctype="multipart/form-data" >
                @csrf
                <div class="mb-3">
                  <input type="file" name="update_zip" class="form-control form-control-lg" accept=".zip" required>
                </div>
                <div>
                  <button type="submit" class="btn  btn-success px-4" id="install-update">
                    <i class="fas fa-upload me-2"></i> Upload & Install
                  </button>
                </div>
              </form>
            

            {{-- Progress Section --}}
            <div id="install-progress" class="mt-4" style="display:none;">
              <div class="progress" style="height: .875rem;">
                <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill" style="width:0%"></div>
              </div>
              <div id="install-status" class="small text-muted mt-2">Starting the update...</div>
            </div>
          </div>

          <div class="card-footer">
            <i class="fas fa-clock me-1"></i> Last checked: 
            {{ now()->format('Y-m-d H:i:s') }}
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(function () {
  // Handle form submission
  $('#upload-form').on('submit', function (e) {
    e.preventDefault();

    // Confirm with user before starting installation
    Swal.fire({
      title: 'Are you sure you want to install the update?',
      text: 'This will replace the application files with the uploaded ZIP file.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Install',
      reverseButtons: true,
    }).then(result => {
      if (!result.isConfirmed) return;

      // Disable the button and show progress bar
      const $btn = $('#install-update')
        .prop('disabled', true)
        .html('<i class="fas fa-spinner fa-spin"></i> Installing…');

      $('#install-progress').slideDown();
      $('#install-status').html('Uploading & installing...');

      // Poll for progress updates
      const poll = setInterval(() => {
        $.get('{{ route('update.progress') }}', data => {
          $('#install-progress .progress-bar').css('width', data.progress + '%');
          $('#install-status').text(data.message);
        });
      }, 1000);

      // Send the ZIP file via AJAX
      const formData = new FormData(this);

      $.ajax({
        url: '{{ route('update.install') }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
      })
      .done(res => {
        clearInterval(poll);
        $('#install-progress .progress-bar').css('width', '100%');
        $('#install-status').html('<i class="fas fa-check-circle text-success"></i> ' + res.message);
        Swal.fire('Update Complete', res.message, 'success').then(() => location.reload());
      })
      .fail((xhr, status, error) => {
        clearInterval(poll);
        let msg = 'Installation failed.';
        if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
        else if (xhr.status === 503) msg = 'Service Unavailable – App may still be in maintenance mode.';
        else if (error) msg = error;

        $('#install-progress .progress-bar').addClass('bg-danger').css('width', '100%');
        $('#install-status').html('<i class="fas fa-times-circle text-danger"></i> ' + msg);
        $btn.prop('disabled', false).html('<i class="fas fa-upload me-2"></i> Upload & Install');
        Swal.fire('Error', msg, 'error');
      });
    });
  });
});
</script>
@endpush
