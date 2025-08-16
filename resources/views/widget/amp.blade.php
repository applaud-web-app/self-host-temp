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
                                <div class="step-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                    style="width: 32px; height: 32px;">1</div>
                                <h4 class="mb-0">Download Required Files</h4>
                            </div>
                            <p class="text-muted mb-3">Start by downloading these essential files and upload them to your
                                website's root directory.</p>
                            <div class="row g-3">
                                <div class="col-lg-4 col-md-6">
                                    <div class="download-card p-4 border text-primary rounded-2 bg-white h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-file-code text-primary me-2"></i>
                                            <h6 class="mb-0">Firebase Messaging</h6>
                                        </div>
                                        <p class="small text-muted mb-2">Core functionality for push notifications</p>
                                        <a href="{{ $downloadSwEncryptUrl }}" class="btn btn-sm btn-outline-primary w-100"
                                            download>
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
                                        <a href="{{ asset('amp.js') }}" class="btn btn-sm btn-outline-success w-100"
                                            download>
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
                                        <a href="{{ route('widget.amp-permission') }}"
                                            class="btn btn-sm btn-outline-info w-100" download="permission.html">
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
                                <div class="step-number bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                    style="width: 32px; height: 32px;">2</div>
                                <h4 class="mb-0">Embed the Script</h4>
                            </div>
                            <p class="text-muted mb-3">Add this code to your <code>&lt;head&gt;</code> section to initialize
                                the widget.</p>
                            <div class="position-relative">
                                <pre class="line-numbers rounded-2 mb-3"><code class="language-html">&lt;script async custom-element="amp-web-push" src="https://cdn.ampproject.org/v0/amp-web-push-0.1.js"&gt;&lt;/script&gt;
&lt;amp-web-push id="amp-web-push" layout="nodisplay" 
    helper-iframe-url="{{ $clientDomain }}amp.js" 
    permission-dialog-url="{{ $clientDomain }}permission.html" 
    service-worker-url="{{ $clientDomain }}apluselfhost-messaging-sw.js"&gt;
&lt;/amp-web-push&gt;
&lt;style amp-custom&gt;
    .amp-only { 
        display: block;
        text-align:center;
    }
    html:not([amp]) .amp-only {
        display: none;
    }
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
    amp-web-push-widget {
        visibility: visible !important;
    }
&lt;/style&gt;</code></pre>
                                <button id="copyScriptButton"
                                    class="btn btn-sm btn-success position-absolute top-0 end-0 m-2">
                                    <i class="fas fa-copy me-1"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Button Code Card -->
                    <div class="card h-auto mb-4">
                        <div class="card-body p-4">
                            <div class="step-header d-flex align-items-center mb-3">
                                <div class="step-number bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                    style="width: 32px; height: 32px;">3</div>
                                <h4 class="mb-0">Add Trigger Button</h4>
                            </div>
                            <p class="text-muted mb-3">Place this widget anywhere in your <code>&lt;body&gt;</code> where
                                you want users to enable notifications.</p>
                            <div class="position-relative">
                                <pre class="line-numbers rounded-2 mb-3"><code class="language-html">&lt;div class="amp-only"&gt;
    &lt;amp-web-push-widget visibility="unsubscribed" layout="fixed" width="250" height="50"&gt;
        &lt;button class="apluPushAmpBtn apluPushBtn" on="tap:amp-web-push.subscribe"&gt;
        Enable Notifications
        &lt;/button&gt;
    &lt;/amp-web-push-widget&gt;
&lt;/div&gt;</code></pre>
                                <button id="copyButtonCodeButton"
                                    class="btn btn-sm btn-success position-absolute top-0 end-0 m-2">
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/plugins/line-numbers/prism-line-numbers.min.js">
    </script>

    <!-- Include iziToast CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <!-- Include iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>

    <script>
        // Copy Script to Clipboard
        document.getElementById('copyScriptButton').addEventListener('click', function() {
            const scriptContent = `<script async custom-element="amp-web-push" src="https://cdn.ampproject.org/v0/amp-web-push-0.1.js"><\/script>
        <amp-web-push id="amp-web-push" layout="nodisplay" 
            helper-iframe-url="{{ $clientDomain }}amp.js" 
            permission-dialog-url="{{ $clientDomain }}permission.html" 
            service-worker-url="{{ $clientDomain }}apluselfhost-messaging-sw.js">
        </amp-web-push>
        <style amp-custom>
            .amp-only { 
                display: block;
                text-align:center;
            }
            html:not([amp]) .amp-only {
                display: none;
            }
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
            amp-web-push-widget {
                visibility: visible !important;
            }
        </style>`;
            copyToClipboard(scriptContent, "Script copied to clipboard!");
        });

        // Copy Button Code to Clipboard
        document.getElementById('copyButtonCodeButton').addEventListener('click', function() {
            const buttonContent = `<div class="amp-only">
                <amp-web-push-widget visibility="unsubscribed" layout="fixed" width="250" height="50">
                    <button class="apluPushAmpBtn apluPushBtn" on="tap:amp-web-push.subscribe">
                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M12 1c3.681 0 7 2.565 7 6v4.539c0 .642.189 1.269.545 1.803l2.2 3.298A1.517 1.517 0 0 1 20.482 19H15.5a3.5 3.5 0 1 1-7 0H3.519a1.518 1.518 0 0 1-1.265-2.359l2.2-3.299A3.25 3.25 0 0 0 5 11.539V7c0-3.435 3.318-6 7-6ZM6.5 7v4.539a4.75 4.75 0 0 1-.797 2.635l-2.2 3.298-.003.01.001.007.004.006.006.004.007.001h16.964l.007-.001.006-.004.004-.006.001-.006a.017.017 0 0 0-.003-.01l-2.199-3.299a4.753 4.753 0 0 1-.798-2.635V7c0-2.364-2.383-4.5-5.5-4.5S6.5 4.636 6.5 7ZM14 19h-4a2 2 0 1 0 4 0Z"></path></svg> Enable Notifications
                    </button>
                </amp-web-push-widget>
            </div>`;
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