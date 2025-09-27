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
                        <form method="POST" action="{{ route('install.firebase.post') }}" enctype="multipart/form-data">
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

                            <button type="submit" id="verifyBtn" class="btn btn-primary text-white w-100">
                                <span>Verify</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
