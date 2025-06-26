@extends('layouts.single-master')
@section('title', 'License Verification | Aplu')
@section('content')
    <style>
        .license-card {
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .license-heading {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2D3748;
        }

        .license-subtitle {
            font-size: 1.1rem;
            color: #4A5568;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .btn-loading {
            position: relative;
        }

        .btn-loading .spinner-border {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .btn-loading span {
            opacity: 0;
        }

        .error {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        .error-container {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

    </style>
    <section class="section-padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="license-card card">
                        <h1 class="license-heading">🔑 License Verification</h1>
                        <p class="license-subtitle">Enter your license details to verify and activate Aplu.</p>

                        <form id="licenseForm" method="POST" action="{{ route('install.license.post') }}">
                            @csrf
                            <input type="hidden" name="license_verified" id="license_verified" value="0">

                            <div class="form-group">
                                <label for="license_code" class="form-label">License Code <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="license_code" id="license_code" class="form-control" required>
                                <div class="error-container" id="license_code_error"></div>
                            </div>

                            <div class="form-group">
                                <label for="domain_name" class="form-label">Installation Domain Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="domain_name" id="domain_name" class="form-control"
                                    value="{{ request()->getHost() }}" readonly>
                                <div class="error-container" id="domain_name_error"></div>
                            </div>

                            <div class="form-group">
                                <label for="registered_username" class="form-label">Aplu Registered Username <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="registered_username" id="registered_username"
                                    class="form-control" required>
                                <div class="error-container" id="registered_username_error"></div>
                            </div>

                            <div class="form-group">
                                <label for="registered_email" class="form-label">Aplu Registered Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" name="registered_email" id="registered_email" class="form-control"
                                    required>
                                <div class="error-container" id="registered_email_error"></div>
                            </div>

                            <button type="button" id="verifyBtn" class="btn btn-primary text-white w-100">
                                <span>Verify License</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize form validation
            $("#licenseForm").validate({
                rules: {
                    license_code: {
                        required: true,
                        minlength: 10
                    },
                    registered_username: {
                        required: true,
                        minlength: 3
                    },
                    registered_email: {
                        required: true,
                        email: true
                    }
                },
                messages: {
                    license_code: {
                        required: "Please enter your license code",
                        minlength: "License code must be at least 10 characters long"
                    },
                    registered_username: {
                        required: "Please enter your registered username",
                        minlength: "Username must be at least 3 characters long"
                    },
                    registered_email: {
                        required: "Please enter your registered email",
                        email: "Please enter a valid email address"
                    }
                },
                errorElement: "div",
                errorPlacement: function(error, element) {
                    error.appendTo(element.parent().find('.error-container'));
                }
            });

            // License verification handler
            $("#verifyBtn").click(function(e) {
                e.preventDefault();

                // Validate form before making API call
                if (!$("#licenseForm").valid()) {
                    return false;
                }

                var btn = $(this);
                btn.prop('disabled', true).addClass('btn-loading');
                btn.text('Verifying...');


                // Prepare request data
                var requestData = {
                    license_key: $("#license_code").val(),
                    domain: $("#domain_name").val(),
                    username: $("#registered_username").val(),
                    email: $("#registered_email").val()
                };

                $.ajax({
                    url: "{{$url}}",
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(requestData),
                    dataType: 'json',
                    success: function(data) {
                        if (!data.valid) {
                            btn.prop('disabled', false).removeClass('btn-loading');
                            btn.text('Verify License');
                            iziToast.error({
                                title: 'Error',
                                message: data.message || 'License is not valid.',
                                position: 'topRight'
                            });
                            $("#license_verified").val(0);
                            $("#installBtn").hide();
                        } else {
                            iziToast.success({
                                title: 'Success',
                                message: 'License verified! Continue installation.',
                                position: 'topRight'
                            });
                            $("#license_verified").val(1);
                            $("#installBtn").show();
                        }

                        if (!data.valid) {
                            btn.prop('disabled', false).removeClass('btn-loading');
                            btn.text('Verify License');
                            iziToast.error({
                                title: 'Error',
                                message: data.message || 'License is not valid.',
                                position: 'topRight'
                            });
                            return;
                        }
                        
                        setTimeout(() => {
                            // Create a hidden form and submit it
                            var form = document.getElementById('licenseForm');
                            
                            // Submit the form
                            form.submit();
                        }, 1000);
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false).removeClass('btn-loading');
                        btn.find('span').text('Verify License');

                        var errorMessage = "Error verifying license: ";
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage += xhr.responseJSON.message;
                        } else {
                            errorMessage += error;
                        }
                        iziToast.error({
                            title: 'Error',
                            message: errorMessage,
                            position: 'topRight'
                        });
                    }
                });
            });

            // Form submission handler
            $("#licenseForm").submit(function(e) {
                if ($("#license_verified").val() !== "1") {
                    e.preventDefault();
                    return false;
                }

                // Show loading state on install button
                $("#installBtn").prop('disabled', true).addClass('btn-loading');
                $("#installBtn").find('span').text('Installing...');
            });
        });
    </script>
@endpush
