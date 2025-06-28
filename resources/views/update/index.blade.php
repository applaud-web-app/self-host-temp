@extends('layouts.master')

@section('content')
<section class="content-body">
  <div class="container-fluid position-relative">
    {{-- page title --}}
    <div class="d-flex align-items-center mb-4">
      <h2 class="fw-semibold me-auto mb-0">Application Update</h2>
    </div>

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
            <div class="alert alert-warning d-flex align-items-start rounded-3">
              <i class="fas fa-exclamation-triangle fa-lg mt-1 me-2"></i>
              <div>
                <strong class="d-block">Update available</strong>
                A new version is available to install.
              </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3">
              <button id="install-update" class="btn btn-lg btn-primary px-4">
                <i class="fas fa-rocket me-2"></i> Install Update
              </button>
            </div>

            <div id="install-progress" class="mt-4" style="display:none;">
              <div class="progress" style="height: .875rem;">
                <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill" style="width:0%"></div>
              </div>
              <div id="install-status" class="small text-muted mt-2">Starting the update...</div>
            </div>
          </div>

          <div class="card-footer bg-light border-0 small text-muted">
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
  // Install update
  $('#install-update').on('click', function () {
    Swal.fire({
      title: 'Install update?',
      text: 'Application will replace files with the new update.',
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
      $('#install-status').html('Downloading update...');

      const poll = setInterval(() => {
        $.get('{{ route('update.progress') }}', data => {
          $('#install-progress .progress-bar').css('width', data.progress + '%');
          $('#install-status').text(data.message);
        });
      }, 1000);

      $.ajax({
        url: '{{ route('update.install') }}',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
      })
      .done(res => {
        clearInterval(poll);
        $('#install-progress .progress-bar').css('width', '100%');
        $('#install-status').html('<i class="fas fa-check-circle text-success"></i> ' + res.message);
        Swal.fire('Done', res.message, 'success').then(() => location.reload());
      })
      .fail((xhr, status, error) => {
        clearInterval(poll);
        let msg = 'Installation failed.';
        if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
        else if (xhr.status === 503) msg = 'Service Unavailable — app may still be in maintenance mode.';
        else if (error) msg = error;

        $('#install-progress .progress-bar').addClass('bg-danger').css('width', '100%');
        $('#install-status').html('<i class="fas fa-times-circle text-danger"></i> ' + msg);
        $btn.prop('disabled', false).html('<i class="fas fa-rocket me-2"></i> Install Update');

        Swal.fire('Error', msg, 'error');
      });
    });
  });
});
</script>
@endpush
