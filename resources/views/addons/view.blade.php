@extends('layouts.master')

@push('styles')
    <style>
        .addon-card:hover {
            border-color: var(--primary);
        }

        .addon-icon-bg {
            width: 64px;
            height: 64px;
            display: flex;
            padding: 5px;
            overflow: hidden;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            border-radius: 50%;
            border: 1px solid var(--primary);
        }

        .addon-card .addon-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 12px;
        }

        .addon-title {
            font-size: 1.18rem;
            font-weight: 600;
            margin-bottom: .35rem;
        }

        .addon-desc {
            font-size: .95rem;
            color: #666;
            min-height: 32px;
        }
    </style>
@endpush

@section('content')
    <section class="content-body" id="addon_list">
        <div class="container-fluid position-relative">
            <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
                <h2 class="me-auto mb-0">Addons & Modules</h2>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row gy-4">
                @isset($addons)
                    @foreach ($addons as $addon)
                        <div class="col-xl-3 col-md-4 col-sm-6">
                            <div class="card addon-card position-relative">
                                {{-- Badge: remote status or local state --}}
                                <span
                                    class="badge addon-badge
                    @if ($addon['is_local']) {{ $addon['local_status'] === 'installed' ? 'bg-success' : 'bg-info' }}
                    @else
                      {{ $addon['status'] === 'available' ? 'bg-success' : 'bg-secondary' }} @endif
                  ">
                                    @if ($addon['is_local'])
                                        {{ $addon['local_status'] === 'installed' ? 'Installed' : 'Uploaded' }}
                                    @else
                                        {{ ucfirst($addon['status']) }}
                                    @endif
                                </span>

                                <div class="card-body text-center d-flex flex-column">
                                    <div class="addon-icon-bg mb-3 mt-3">
                                        <img src="{{ $addon['icon'] }}" alt="{{ $addon['name'] }} icon" class="img-fluid">
                                    </div>

                                    <h5 class="addon-title">
                                        {{ $addon['name'] }}
                                        <small class="text-secondary">({{ $addon['version'] }})</small>
                                    </h5>

                                    <p class="addon-desc mb-3">
                                        {{ $addon['description'] ?: 'No description available.' }}
                                    </p>

                                    <div class="fw-bold fs-4 text-primary mb-3">
                                        {{ $addon['price'] }}
                                    </div>

                                    <div class="addon-actions">
                                        @if ($addon['is_local'])
                                            {{-- Already in DB --}}
                                            @if ($addon['local_status'] === 'installed')
                                                <span class="btn btn-success btn-sm w-100">Activated</span>
                                            @else
                                                <button type="button" data-name="{{$addon['preferred_name']}}"  data-key="{{$addon['key']}}" data-version="{{$addon['version']}}" data-bs-toggle="modal" data-bs-target="#purchaseModal" class="btn btn-info btn-sm w-100">
                                                    <i class="fas fa-play me-1"></i> Activate
                                                </button>
                                            @endif
                                        @else
                                            {{-- Not yet uploaded locally --}}
                                            @if ($addon['status'] !== 'available')
                                                @php
                                                    $param = [
                                                        'key' => $addon['key'],
                                                        'name' => $addon['name'],
                                                        'version' => $addon['version'],
                                                        'preferred_name' => $addon['preferred_name'],
                                                    ];
                                                    $uploadUrl = encryptUrl(route('addons.upload'), $param);
                                                @endphp
                                                <a href="{{ $uploadUrl }}" class="btn btn-warning btn-sm w-100">
                                                    <i class="fas fa-upload me-1"></i> Upload
                                                </a>
                                            @else
                                                <a href="{{ $addon['purchase_url'] }}" target="_blank"
                                                    class="btn btn-primary btn-sm w-100">
                                                    <i class="fas fa-shopping-cart me-1"></i> Purchase Now
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endisset
            </div>
        </div>

        {{-- ===== Purchase Modal (unchanged) ===== --}}
        <div class="modal fade" id="purchaseModal" tabindex="-1" aria-labelledby="purchaseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="purchaseForm" autocomplete="off">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="purchaseModalLabel">Activate Module</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Username Field --}}
                            <div class="mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                            </div>

                            {{-- License Key Field --}}
                            <div class="mb-3">
                                <label for="licenseKey" class="form-label">License Key <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="licenseKey" name="license_key" placeholder="Enter your license key" required>
                            </div>

                            {{-- Email Field --}}
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-unlock me-1"></i> Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </section>
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize iziToast settings (you can customize these)
        iziToast.settings({
            timeout: 5000,
            resetOnHover: true,
            position: 'topRight',
            transitionIn: 'flipInX',
            transitionOut: 'flipOutX'
        });

        // When Activate button is clicked
        $('button[data-bs-toggle="modal"]').on('click', function() {
            var addonName = $(this).data('name');
            var addonVersion = $(this).data('version');
            var addonKey = $(this).data('key');

            $('#purchaseForm').data('addonName', addonName);
            $('#purchaseForm').data('addonVersion', addonVersion);
            $('#purchaseForm').data('addonKey', addonKey);

            // Reset form when modal is shown
            $('#purchaseForm')[0].reset();
            $('#purchaseForm').find('.is-invalid').removeClass('is-invalid');
        });

        // Form validation and submission
        $('#purchaseForm').validate({
            rules: {
                username: { required: true },
                license_key: { required: true },
                email: { required: true, email: true }
            },
            messages: {
                username: { required: "Please enter your username" },
                license_key: { required: "Please enter your license key" },
                email: { 
                    required: "Please enter your email",
                    email: "Please enter a valid email address"
                }
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback');
                element.closest('.mb-3').append(error);
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
            },
            submitHandler: function(form) {
                var form = $('#purchaseForm');
                var url = "{{$url}}";
                var addonName = form.data('addonName');
                var addonVersion = form.data('addonVersion');
                var addonKey = form.data('addonKey');

                var formData = {
                    _token: $('input[name="_token"]').val(),
                    license_key: $('#licenseKey').val(),
                    username: $('#username').val(),
                    email: $('#email').val(),
                    addon_name: addonName,
                    addon_version: addonVersion,
                    addon_key: addonKey
                };

                var submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        submitBtn.prop('disabled', false).html('<i class="fas fa-unlock me-1"></i> Submit');
                        
                        if (response.valid) {

                            // If external validation succeeds, call local activation
                            $.ajax({
                                url: "{{route('addons.activate')}}",
                                method: 'POST',
                                data: formData,
                                success: function(activateResponse) {
                                    submitBtn.prop('disabled', false).html('<i class="fas fa-unlock me-1"></i> Submit');
                                    
                                    iziToast.success({
                                        title: 'Success',
                                        message: 'Addon activated successfully!',
                                        onClosed: function() {
                                            $('#purchaseModal').modal('hide');
                                            location.reload();
                                        }
                                    });
                                },
                                error: function(xhr) {
                                    submitBtn.prop('disabled', false).html('<i class="fas fa-unlock me-1"></i> Submit');
                                    
                                    var errorMsg = 'Activation failed. Please try again.';
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMsg = xhr.responseJSON.message;
                                    }
                                    
                                    iziToast.error({
                                        title: 'Error',
                                        message: errorMsg
                                    });
                                }
                            });
                            
                            // Success response
                            // iziToast.success({
                            //     title: 'Success',
                            //     message: response.message || 'Addon activated successfully!',
                            //     onClosed: function() {
                            //         $('#purchaseModal').modal('hide');
                            //         location.reload();
                            //     }
                            // });
                        } else {
                            // Error response (invalid license)
                            iziToast.error({
                                title: 'Error',
                                message: response.message || 'Addon license not found or not eligible for activation.'
                            });
                        }
                    },
                    error: function(xhr) {
                        submitBtn.prop('disabled', false).html('<i class="fas fa-unlock me-1"></i> Submit');
                        
                        var errorMsg = 'An unexpected error occurred. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.statusText) {
                            errorMsg = xhr.statusText;
                        }
                        
                        iziToast.error({
                            title: 'Error',
                            message: errorMsg
                        });
                    }
                });
            }
        });
    });
</script>
@endpush


