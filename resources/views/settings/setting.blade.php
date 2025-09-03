@extends('layouts.master')

@section('content')
<section class="content-body">
  <div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center text-head mb-3">
      <h2 class="me-auto">App Settings</h2>
        {{-- Logout from this device --}}
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#logOutDevice">Logout from All Devices</button>
    </div>

    @if (session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-body">
            <form action="{{ route('settings.post-settings') }}" method="POST" id="settingsForm" novalidate>
              @csrf

                <div class="form-group mb-3">
                    <label>Sending Speed (decrease if panel is crashing) <span class="text-danger">*</span></label>
                    
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input class="form-check-input @error('sending_speed') is-invalid @enderror" 
                                type="radio" 
                                name="sending_speed" 
                                id="sending_speed_slow" 
                                value="slow" 
                                {{ old('sending_speed', $setting->sending_speed) === 'slow' ? 'checked' : '' }} 
                                required>
                            <label class="form-check-label" for="sending_speed_slow">Slow</label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input @error('sending_speed') is-invalid @enderror" 
                                type="radio" 
                                name="sending_speed" 
                                id="sending_speed_medium" 
                                value="medium" 
                                {{ old('sending_speed', $setting->sending_speed) === 'medium' ? 'checked' : '' }}>
                            <label class="form-check-label" for="sending_speed_medium">Medium</label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input @error('sending_speed') is-invalid @enderror" 
                                type="radio" 
                                name="sending_speed" 
                                id="sending_speed_fast" 
                                value="fast" 
                                {{ old('sending_speed', $setting->sending_speed) === 'fast' ? 'checked' : '' }}>
                            <label class="form-check-label" for="sending_speed_fast">Fast</label>
                        </div>
                    </div>
                    @error('sending_speed') 
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mb-4">
                    <label>Daily Cleanup <span class="text-danger">*</span></label>
                    
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input class="form-check-input @error('daily_cleanup') is-invalid @enderror" 
                                type="radio" 
                                name="daily_cleanup" 
                                id="daily_cleanup_yes" 
                                value="1" 
                                {{ old('daily_cleanup', $setting->daily_cleanup) ? 'checked' : '' }}
                                required>
                            <label class="form-check-label" for="daily_cleanup_yes">Yes</label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input @error('daily_cleanup') is-invalid @enderror" 
                                type="radio" 
                                name="daily_cleanup" 
                                id="daily_cleanup_no" 
                                value="0" 
                                {{ !old('daily_cleanup', $setting->daily_cleanup) ? 'checked' : '' }}>
                            <label class="form-check-label" for="daily_cleanup_no">No</label>
                        </div>
                    </div>
                    
                    @error('daily_cleanup') 
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

              <div class="text-end">
                <button type="submit" class="btn btn-primary" id="saveBtn">
                  <span>Save Settings</span>
                  <span id="saveSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header"><h5>Help & Info</h5></div>
          <div class="card-body">
            <p><strong>Batch Speed:</strong> Controls processing speed (Slow, Medium, Fast)</p>
            <p><strong>Daily Cleanup:</strong> Toggle automatic cleanup job.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


  <!-- Modal: Select Icons -->
  <div class="modal fade" id="logOutDevice" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
      aria-labelledby="logOutDeviceLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title fs-20" id="logOutDeviceLabel">Logout from All Devices</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form action="{{ route('settings.logout.device') }}" method="POST">
                @csrf
                  <label for="password">Confirm Password <span class="text-danger">*</span></label>
                  <input type="password" name="password" class="form-control" id="password" required>
                  <button class="btn btn-primary mt-3 w-100">Submit</button>
                </form>
              </div>
          </div>
      </div>
  </div>
@endsection

@push('scripts')
  {{-- SweetAlert2 (use your existing include if already bundled) --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // SweetAlert confirm for logout
    document.getElementById('logoutDeviceBtn').addEventListener('click', function () {
      Swal.fire({
        title: 'Logout from this device?',
        text: 'You will be signed out from the current browser session.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById('logoutDeviceForm').submit();
        }
      });
    });

    // Optional: disable button + show spinner on save
    document.getElementById('settingsForm').addEventListener('submit', function () {
      const btn = document.getElementById('saveBtn');
      const spin = document.getElementById('saveSpinner');
      btn.setAttribute('disabled', 'disabled');
      spin.classList.remove('d-none');
    });
  </script>
@endpush