@extends('layouts.single-master')
@section('title', 'Setup Verification | Aplu')
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
                <div class="col-lg-9">
                    <div class="license-card card">
                        <h1 class="license-heading">🔑 License Verification</h1>
                        <p class="license-subtitle">Enter your license details to verify and activate Aplu.</p>

                        <form id="licenseForm" method="POST" action="{{ route('install.setup.post') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="license_verified" id="license_verified" value="0">

                            <div class="row">
                                <div class="form-group col-12">
                                    <label for="license_code" class="form-label">License Code <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="license_code" id="license_code" class="form-control" required>
                                    <div class="error-container" id="license_code_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="domain_name" class="form-label">Installation Domain Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="domain_name" id="domain_name" class="form-control"
                                        value="{{ request()->host() }}" readonly>
                                    <div class="error-container" id="domain_name_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="server_ip" class="form-label">Installation Server IP <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="server_ip" id="server_ip"
                                        class="form-control" required>
                                    <div class="error-container" id="server_ip_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="registered_username" class="form-label">Aplu Registered Username <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="registered_username" id="registered_username"
                                        class="form-control" required>
                                    <div class="error-container" id="registered_username_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="registered_email" class="form-label">Aplu Registered Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="registered_email" id="registered_email" class="form-control"
                                        required>
                                    <div class="error-container" id="registered_email_error"></div>
                                </div>
                            </div>

                            
                            {{-- Push Configuration Fields --}}
                            <h2 class="license-heading">🔔 Push Configuration</h2>
                            <p class="license-subtitle">Configure Firebase and VAPID keys for push notifications.</p>

                            <div class="row">
                                <div class="form-group col-6">
                                    <label for="web_apiKey" class="form-label">API Key <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="web_apiKey" name="web_apiKey" required>
                                    <div class="error-container" id="web_apiKey_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="web_authDomain" class="form-label">Auth Domain <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="web_authDomain" name="web_authDomain" required>
                                    <div class="error-container" id="web_authDomain_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="web_projectId" class="form-label">Project ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="web_projectId" name="web_projectId" required>
                                    <div class="error-container" id="web_projectId_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="web_storageBucket" class="form-label">Storage Bucket <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="web_storageBucket" name="web_storageBucket" required>
                                    <div class="error-container" id="web_storageBucket_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="web_messagingSenderId" class="form-label">Messaging Sender ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="web_messagingSenderId" name="web_messagingSenderId" required>
                                    <div class="error-container" id="web_messagingSenderId_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="web_appId" class="form-label">App ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="web_appId" name="web_appId" required>
                                    <div class="error-container" id="web_appId_error"></div>
                                </div>

                                <div class="form-group col-6">
                                    <label for="web_measurementId" class="form-label">Measurement Id <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="web_measurementId" name="web_measurementId" required>
                                    <div class="error-container" id="web_measurementId_error"></div>
                                </div> 
                                
                                {{-- Service Account JSON --}}
                                <div class="form-group col-6">
                                    <label for="service_account_json_file" class="form-label">Service Account JSON <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="service_account_json_file" name="service_account_json_file" required>
                                    <div class="error-container" id="service_account_json_file_error"></div>
                                </div>

                                <div class="form-group col-12">
                                    <label for="vapid_public_key" class="form-label">VAPID Public Key <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="vapid_public_key" name="vapid_public_key" required>
                                    <div class="error-container" id="vapid_public_key_error"></div>
                                </div>

                                <div class="form-group col-12">
                                    <label for="vapid_private_key" class="form-label">VAPID Private Key <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="vapid_private_key" name="vapid_private_key" required>
                                    <div class="error-container" id="vapid_private_key_error"></div>
                                </div>

                            </div>

                            <button type="button" id="verifyBtn" class="btn btn-primary text-white w-100">
                                <span>Verify</span>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
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
                    },
                    web_apiKey: {
                        required: true
                    },
                    web_authDomain: {
                        required: true
                    },
                    web_projectId: {
                        required: true
                    },
                    web_storageBucket: {
                        required: true
                    },
                    web_messagingSenderId: {
                        required: true
                    },
                    web_appId: {
                        required: true
                    },
                    web_measurementId: {
                        required: true
                    },
                    vapid_public_key: {
                        required: true
                    },
                    vapid_private_key: {
                        required: true
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
                    },
                    web_apiKey: {
                        required: "Please enter your Firebase API Key"
                    },
                    web_authDomain: {
                        required: "Please enter your Firebase Auth Domain"
                    },
                    web_projectId: {
                        required: "Please enter your Firebase Project ID"
                    },
                    web_storageBucket: {
                        required: "Please enter your Firebase Storage Bucket"
                    },
                    web_messagingSenderId: {
                        required: "Please enter your Messaging Sender ID"
                    },
                    web_appId: {
                        required: "Please enter your Firebase App ID"
                    },
                    web_measurementId: {
                        required: "Please enter your Firebase Measurement ID"
                    },
                    vapid_public_key: {
                        required: "Please enter your VAPID public key"
                    },
                    vapid_private_key: {
                        required: "Please enter your VAPID private key"
                    }
                },
                errorElement: "div",
                errorPlacement: function(error, element) {
                    error.appendTo(element.closest('.form-group').find('.error-container'));
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
