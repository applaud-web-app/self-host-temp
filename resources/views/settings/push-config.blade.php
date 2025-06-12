{{-- resources/views/settings/push-config.blade.php --}}
@extends('layouts.master')

@section('content')
    <section class="content-body">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center text-head">
                <h2 class="mb-3 me-auto">Push Configuration</h2>
            </div>
            <div class="row justify-content-center">
                {{-- Form Column --}}
                <div class="col-lg-8">
                    <div class="card mb-4 h-auto">
                        <div class="card-body">
                            <form id="pushConfigForm" action="{{ route('settings.push.save') }}" method="POST"
                                enctype="multipart/form-data" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="web_apiKey" class="form-label">
                                            API Key <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="web_apiKey" name="web_apiKey"
                                            value="{{ old('web_apiKey', $config->web_app_config['apiKey'] ?? '') }}"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="web_authDomain" class="form-label">
                                            Auth Domain <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="web_authDomain" name="web_authDomain"
                                            value="{{ old('web_authDomain', $config->web_app_config['authDomain'] ?? '') }}"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="web_projectId" class="form-label">
                                            Project ID <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="web_projectId" name="web_projectId"
                                            value="{{ old('web_projectId', $config->web_app_config['projectId'] ?? '') }}"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="web_storageBucket" class="form-label">
                                            Storage Bucket <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="web_storageBucket" name="web_storageBucket"
                                            value="{{ old('web_storageBucket', $config->web_app_config['storageBucket'] ?? '') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="web_messagingSenderId" class="form-label">
                                            Messaging Sender ID <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="web_messagingSenderId"
                                            name="web_messagingSenderId"
                                            value="{{ old('web_messagingSenderId', $config->web_app_config['messagingSenderId'] ?? '') }}"
                                            required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="web_appId" class="form-label">
                                            App ID <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="web_appId" name="web_appId"
                                            value="{{ old('web_appId', $config->web_app_config['appId'] ?? '') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="web_measurementId" class="form-label">
                                            Measurement Id <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="web_measurementId" name="web_measurementId"
                                            value="{{ old('web_measurementId', $config->web_app_config['measurementId'] ?? '') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                {{-- Service Account JSON --}}
                                <div class="mb-3">
                                    <label class="form-label">
                                        Service Account JSON <span class="text-danger">*</span>
                                    </label>
                                    <p class="text-sm text-muted mb-1">
                                        Download from
                                        <code>Firebase Console → Project Settings → Service Accounts → Generate new private
                                            key</code>.
                                    </p>
                                    <input type="file" class="form-control" id="service_account_json_file"
                                        name="service_account_json_file" accept=".json,application/json">
                                </div>

                                {{-- VAPID keys --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="vapid_public_key" class="form-label">
                                            VAPID Public Key <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="vapid_public_key"
                                            name="vapid_public_key"
                                            value="{{ old('vapid_public_key', $config->vapid_public_key ?? '') }}"
                                            placeholder="e.g. BP...kQ">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="vapid_private_key" class="form-label">
                                            VAPID Private Key <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="vapid_private_key"
                                            name="vapid_private_key"
                                            value="{{ old('vapid_private_key', $config->vapid_private_key ?? '') }}"
                                            placeholder="e.g. Jm...7w">
                                    </div>
                                </div>
                                @if (! isset($config))
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            Save Push Config
                                        </button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Help & Info Sidebar --}}
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Help & Info</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Service Account JSON:</strong><br>
                                Download from <code>Firebase Console → Project Settings → Service Accounts → Generate new
                                    private key</code>.
                            </p>
                            <p class="mb-2">
                                <strong>VAPID Keys:</strong><br>
                                In Firebase Console → <code>Cloud Messaging → Web Push Certificates</code>.
                            </p>
                            <p class="mb-2">
                                <strong>Enable API:</strong><br>
                                In Google Cloud Console → <code>APIs & Services → Library</code>, enable
                                <em>Firebase Cloud Messaging API</em>.
                            </p>
                            <p class="mb-0">
                                <strong>Permissions:</strong><br>
                                Ensure your service account has the “Firebase Admin SDK Administrator Service Agent” role.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $.validator.addMethod("extension", function(value, element, param) {
            return this.optional(element) ||
                new RegExp("\\.(" + param + ")$", "i").test(value);
        }, "Please upload a file with a valid extension.");
    </script>
    <script>
        $(function() {
            $('#pushConfigForm').validate({
                rules: {
                    service_account_json_file: {
                        required: true,
                        extension: "json"
                    },
                    vapid_public_key: {
                        required: true
                    },
                    vapid_private_key: {
                        required: true
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
                    web_messagingSenderId: {
                        required: true
                    },
                    web_appId: {
                        required: true
                    },
                    web_measurementId: {
                        required: true
                    },
                    web_storageBucket: {
                        required: true
                    }
                },
                messages: {
                    service_account_json_file: {
                        required: "Please upload your Service Account JSON.",
                        extension: "Only .json files allowed."
                    },
                    vapid_public_key: "Please enter your VAPID public key.",
                    vapid_private_key: "Please enter your VAPID private key.",
                    web_apiKey: "Please enter your Firebase API key.",
                    web_authDomain: "Please enter your Firebase authDomain.",
                    web_projectId: "Please enter your Firebase projectId.",
                    web_messagingSenderId: "Please enter your Messaging Sender ID.",
                    web_appId: "Please enter your Firebase App ID.",
                    web_measurementId: "Please enter your Firebase Measurement ID.",
                    web_storageBucket: "Please enter your Firebase Storage Bucket."
                },
                errorClass: 'invalid-feedback',
                errorElement: 'div',
                highlight(el) {
                    $(el).addClass('is-invalid');
                },
                unhighlight(el) {
                    $(el).removeClass('is-invalid').addClass('is-valid');
                },
                errorPlacement(err, el) {
                    el.closest('.mb-3, .row').append(err);
                },
                submitHandler(form) {
                    const btn = $(form).find('button[type=submit]');
                    btn.prop('disabled', true).html('Processing...');
                    form.submit();
                }
            });
        });
    </script>
@endpush