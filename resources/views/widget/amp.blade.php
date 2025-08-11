@extends('layouts.master')

@section('content')
    <section class="content-body">
        <div class="container-fluid">
            <div class="text-head mb-3">
                <h2 class="fw-bold mb-0 ">AMP Widget Integration</h2>
            </div>

            <div class="row">
                <div class="col-12">
                    <!-- Step 1: Download Files Card -->
                    <div class="card h-auto mb-4">
                        <div class="card-body p-4">
                            <div class="step-header d-flex align-items-center mb-3">
                                <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">1</div>
                                <h4 class="mb-0">Download Required Files</h4>
                            </div>
                            <p class="text-muted mb-3">Start by downloading these essential files and upload them to your website's root directory.</p>
                            <div class="row g-3">
                                <div class="col-lg-4 col-md-6">
                                    <div class="download-card p-4 border text-primary rounded-2 bg-white h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-file-code text-primary me-2"></i>
                                            <h6 class="mb-0">Firebase Messaging</h6>
                                        </div>
                                        <p class="small text-muted mb-2">Core functionality for push notifications</p>
                                        <a href="{{ $downloadSwEncryptUrl }}" class="btn btn-sm btn-outline-primary w-100" download>
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="download-card p-4 border text-success rounded-2 bg-white h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-file-alt text-success me-2"></i>
                                            <h6 class="mb-0">Helper Frame</h6>
                                        </div>
                                        <p class="small text-muted mb-2">Supporting HTML for the widget</p>
                                        <a href="{{ asset('amp.js') }}" class="btn btn-sm btn-outline-success w-100" download>
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="download-card p-4 border text-info rounded-2 bg-white h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-file-word text-info me-2"></i>
                                            <h6 class="mb-0">Permission Dialog</h6>
                                        </div>
                                        <p class="small text-muted mb-2">User permission interface</p>
                                        <a href="{{ route('widget.amp-permission') }}" class="btn btn-sm btn-outline-info w-100" download="permission.html">
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Embed Script Card -->
<div class="card h-auto mb-4">
    <div class="card-body p-4">
        <div class="step-header d-flex align-items-center mb-3">
            <div class="step-number bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">2</div>
            <h4 class="mb-0">Embed the Script</h4>
        </div>
        <p class="text-muted mb-3">Add this code to your <code>&lt;head&gt;</code> section to initialize the widget.</p>
        <div class="position-relative">
            <pre class="line-numbers rounded-2 mb-3"><code class="language-html">&lt;script async custom-element="amp-web-push" src="https://cdn.ampproject.org/v0/amp-web-push-0.1.js"&gt;&lt;/script&gt;
&lt;amp-web-push id="amp-web-push" layout="nodisplay" 
    helper-iframe-url="{{ $clientDomain }}amp-helper-frame.html" 
    permission-dialog-url="{{ $clientDomain }}permission.html" 
    service-worker-url="{{ $clientDomain }}apluselfhost-messaging-sw.js"&gt;
&lt;/amp-web-push&gt;
&lt;style amp-custom&gt;
    .apluPushBtn{
        background-color:#007bff;
        color:#fff;
        border:none;
        padding:10px 20px;
        border-radius:5px;
        cursor:pointer;
        font-size:16px;
    }
    .apluPushBtn:hover{ 
        background-color:#0056b3;
    }
    .apluPushAmpBtn{ 
        display:inline-flex;
        align-items:center;
        gap:.5rem;
    }
&lt;/style&gt;</code></pre>
            <button id="copyScriptButton" class="btn btn-sm btn-success position-absolute top-0 end-0 m-2">
                <i class="fas fa-copy me-1"></i> Copy
            </button>
        </div>
    </div>
</div>

                    <!-- Step 3: Button Code Card -->
<div class="card h-auto mb-4">
    <div class="card-body p-4">
        <div class="step-header d-flex align-items-center mb-3">
            <div class="step-number bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">3</div>
            <h4 class="mb-0">Add Trigger Button</h4>
        </div>
        <p class="text-muted mb-3">Place this widget anywhere in your <code>&lt;body&gt;</code> where you want users to enable notifications.</p>
        <div class="position-relative">
            <pre class="line-numbers rounded-2 mb-3"><code class="language-html">&lt;amp-web-push-widget visibility="unsubscribed" layout="fixed" width="250" height="50"&gt;
    &lt;button class="apluPushAmpBtn apluPushBtn" on="tap:amp-web-push.subscribe"&gt;
        &lt;i class="fas fa-bell me-2" aria-hidden="true"&gt;&lt;/i&gt; Enable Notifications
    &lt;/button&gt;
&lt;/amp-web-push-widget&gt;</code></pre>
            <button id="copyButtonCodeButton" class="btn btn-sm btn-success position-absolute top-0 end-0 m-2">
                <i class="fas fa-copy me-1"></i> Copy
            </button>
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <!-- Prism.js Stylesheet for syntax highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/themes/prism-okaidia.min.css" rel="stylesheet" />
    <style>
        .download-card {
            transition: transform 0.2s ease;
        }
        .download-card:hover {
            transform: translateY(-5px);
            border: 1px solid !important;
        }
        .step-number {
            font-weight: bold;
            font-size: 14px;
        }
        pre {
            position: relative;
            background: #2d2d2d;
            border-left: 4px solid #4CAF50;
        }
    </style>
@endpush

@push('scripts')
    <!-- Include Prism.js for syntax highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/plugins/line-numbers/prism-line-numbers.min.js"></script>

    <!-- Include iziToast CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <!-- Include iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>

    <script>
        // Copy Script to Clipboard
        document.getElementById('copyScriptButton').addEventListener('click', function() {
            const scriptContent = `<script async custom-element="amp-web-push" src="https://cdn.ampproject.org/v0/amp-web-push-0.1.js"><\/script>
        <amp-web-push id="amp-web-push" layout="nodisplay" 
            helper-iframe-url="{{ $clientDomain }}amp-helper-frame.html" 
            permission-dialog-url="{{ $clientDomain }}permission.html" 
            service-worker-url="{{ $clientDomain }}apluselfhost-messaging-sw.js">
        </amp-web-push>
        <style amp-custom>
            .apluPushBtn{
                background-color:#007bff;
                color:#fff;
                border:none;
                padding:10px 20px;
                border-radius:5px;
                cursor:pointer;
                font-size:16px;
            }
            .apluPushBtn:hover{ 
                background-color:#0056b3;
            }
            .apluPushAmpBtn{ 
                display:inline-flex;
                align-items:center;
                gap:.5rem;
            }
        </style>`;
            copyToClipboard(scriptContent, "Script copied to clipboard!");
        });

        // Copy Button Code to Clipboard
        document.getElementById('copyButtonCodeButton').addEventListener('click', function() {
            const buttonContent = `<amp-web-push-widget visibility="unsubscribed" layout="fixed" width="250" height="50">
            <button class="apluPushAmpBtn apluPushBtn" on="tap:amp-web-push.subscribe">
                <i class="fas fa-bell me-2" aria-hidden="true"></i> Enable Notifications
            </button>
        </amp-web-push-widget>`;
            copyToClipboard(buttonContent, "Button widget code copied to clipboard!");
        });

        // Reusable Copy Function with iziToast
        function copyToClipboard(text, successMessage) {
            navigator.clipboard.writeText(text).then(function() {
                iziToast.success({
                    title: 'Success',
                    message: successMessage,
                    position: 'topRight',
                    timeout: 3000,
                    close: true,
                    displayMode: 'replace'
                });
            }, function() {
                iziToast.error({
                    title: 'Error',
                    message: 'Failed to copy text',
                    position: 'topRight'
                });
            });
        }
    </script>
@endpush