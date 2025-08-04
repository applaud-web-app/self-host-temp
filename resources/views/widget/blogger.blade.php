@extends('layouts.master')

@section('content')
    <section class="content-body" id="profile_page">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <!-- Form Card -->
                    <div class="card  mb-4" id="configFormCard">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary rounded-pill px-3 py-2">
                                    <i class="fas fa-bell text-white"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 text-dark font-weight-bold">Notification Widget Configuration</h4>
                                    <p class="small mb-0 text-muted">Customize the appearance and behavior of your
                                        notification widget</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="widgetConfigForm">
                                <div class="row">
                                    <div class="col-md-6 form-group mb-4">
                                        <label class="mb-2">Heading Text</label>
                                        <input type="text" id="heading" class="form-control"
                                            value="We want to notify you about the latest updates.">
                                        <small class="form-text text-muted">Main title of the widget</small>
                                    </div>

                                    <div class="col-md-6 form-group mb-4">
                                        <label class="mb-2">Subheading</label>
                                        <input type="text" id="subheading" class="form-control"
                                            value="You can unsubscribe anytime later.">
                                        <small class="form-text text-muted">Secondary text below the title</small>
                                    </div>

                                    <div class="col-md-6 form-group mb-4">
                                        <label class="mb-2">Accept Button</label>
                                        <input type="text" id="yesText" class="form-control" value="Yes">
                                        <small class="form-text text-muted">Positive action button text</small>
                                    </div>

                                    <div class="col-md-6 form-group mb-4">
                                        <label class="mb-2">Decline Button</label>
                                        <input type="text" id="noText" class="form-control" value="Later">
                                        <small class="form-text text-muted">Negative action button text</small>
                                    </div>

                                    <div class="col-md-6 form-group mb-4">
                                        <label class="mb-2">Button Color</label>
                                        <div class="d-flex align-items-center">
                                            <input type="color" id="btnColor" class="form-control p-1 w-100" value="#4e73df">
                                        </div>
                                        <small class="form-text text-muted">Primary color for buttons</small>
                                    </div>

                                    <div class="col-md-6 form-group mb-4">
                                        <label class="mb-2">Icon URL</label>
                                        <div class="input-group">
                                            <input type="text" id="iconUrl" class="form-control"
                                                value="{{ asset('images/push/icons/alarm-clock.png') }}">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button"
                                                    id="iconPreviewBtn">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">URL of the notification icon</small>
                                    </div>
                                </div>

                                <div class="col-md-12 form-group mb-4">
                                    <label class="mb-2">Popup Message</label>
                                    <textarea class="form-control" id="popupText" maxlength="20" rows="1">Please click 'Allow' when asked about notifications to subscribe to updates.</textarea>
                                    <small class="form-text text-muted">Message shown in the browser permission
                                        popup</small>
                                </div>

                                <div class="text-center mt-4 pt-3">
                                    <button type="button" id="generateScriptBtn"
                                        class="btn btn-primary px-5 py-2 font-weight-bold">
                                        <i class="fas fa-code mr-2"></i> Generate Embed Code
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Script Display Card (Initially Hidden) -->
                    <div class="card shadow-sm" id="scriptDisplayCard" style="display: none;">
                        <div class="card-header bg-white py-3">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success rounded-pill px-3 py-2">
                                        <i class="fas fa-check-circle text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-0 text-dark font-weight-bold">Your Widget Code</h4>
                                        <p class="small mb-0 text-muted">Copy and paste this code to your website</p>
                                    </div>
                                </div>
                                <button type="button" id="backToFormBtn" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-primary border-left-4 border-primary mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle me-3 fa-lg"></i>
                                    <div>
                                        <strong>Implementation Instructions:</strong> Copy this code and paste it before the
                                        closing &lt;/body&gt; tag on your website.
                                    </div>
                                </div>
                            </div>

                            <div class="position-relative mb-4">
                                <pre class="bg-dark rounded p-3 mb-0" id="scriptPre"><code class="language-html" id="scriptCode"></code></pre>
                                <div class="position-absolute top-0 right-0 mt-2 mr-2">
                                    <span class="badge badge-light">HTML</span>
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-md-row">
                                <button id="copyButton"
                                    class="btn btn-primary mb-2 mb-md-0 mr-md-3 py-2 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-copy me-2"></i> Copy to Clipboard
                                </button>
                                {{-- <button id="testWidgetBtn"
                                    class="btn btn-success py-2 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-play me-2"></i> Test Widget
                                </button> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/themes/prism-okaidia.min.css" rel="stylesheet">
    <style>
        /* Code block styling */
        pre {
            background: #1e1e1e !important;
            border: 1px solid rgba(0, 0, 0, 0.1);
            max-height: 400px;
            overflow: auto;
        }

        /* Smooth transitions */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/plugins/line-numbers/prism-line-numbers.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Update color hex display when color picker changes
            $('#btnColor').on('input', function() {
                $('#colorHex').text($(this).val());
            });

            // Preview icon button
            $('#iconPreviewBtn').click(function() {
                const iconUrl = $('#iconUrl').val();
                if (iconUrl) {
                    Swal.fire({
                        title: 'Icon Preview',
                        imageUrl: iconUrl,
                        imageAlt: 'Notification icon preview',
                        showConfirmButton: false,
                        background: 'transparent',
                        backdrop: `
                            rgba(0,0,0,0.4)
                            url("${iconUrl}")
                            center left
                            no-repeat
                        `
                    });
                } else {
                    Swal.fire('Error', 'Please enter a valid icon URL first', 'error');
                }
            });

            // Generate script button
            $('#generateScriptBtn').click(function() {
                try {
                    // Get all values with proper fallbacks
                    const heading = $('#heading').val() ||
                    "We want to notify you about the latest updates.";
                    const subheading = $('#subheading').val() || "You can unsubscribe anytime later.";
                    const yesText = $('#yesText').val() || "Yes";
                    const noText = $('#noText').val() || "Later";
                    let iconUrl = $('#iconUrl').val();
                    const popupText = $('#popupText').val() ||
                        "Please click 'Allow' when asked about notifications to subscribe to updates.";
                    const btnColor = $('#btnColor').val() || "#4e73df";

                    // Validate icon URL
                    if (!iconUrl) {
                        iconUrl = "{{ asset('images/push/icons/alarm-clock.png') }}";
                    }

                    // Generate the script with proper escaping
                    const script = `
<script src='{{ asset('widget.js') }}'><\/script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let apluPush = new ApluPush(
            ${JSON.stringify(heading)},
            ${JSON.stringify(subheading)},
            ${JSON.stringify(yesText)},
            ${JSON.stringify(noText)},
            ${JSON.stringify(iconUrl)},
            ${JSON.stringify(popupText)},
            ${JSON.stringify(btnColor)}
        );
        apluPush.init();
    });
<\/script>
                    `.trim();

                    // Display the script
                    $('#scriptCode').text(script);

                    // Switch cards with animation
                    $('#configFormCard').fadeOut(300, function() {
                        $('#scriptDisplayCard').addClass('fade-in').fadeIn(300);
                        Prism.highlightAll();
                    });
                } catch (e) {
                    console.error('Error generating script:', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Generation Error',
                        text: 'An error occurred while generating the script: ' + e.message,
                        confirmButtonColor: '#4e73df'
                    });
                }
            });

            // Back to form button
            $('#backToFormBtn').click(function() {
                $('#scriptDisplayCard').fadeOut(300, function() {
                    $('#configFormCard').addClass('fade-in').fadeIn(300);
                });
            });

            // Initialize clipboard
            const clipboard = new ClipboardJS('#copyButton', {
                text: function() {
                    return $('#scriptCode').text();
                }
            });

            clipboard.on('success', function(e) {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'The embed code has been copied to your clipboard',
                    timer: 2000,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true,
                    background: '#f8f9fa'
                });
                e.clearSelection();
            });

            clipboard.on('error', function(e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Not Copied',
                    text: 'Failed to copy text to clipboard',
                    confirmButtonColor: '#4e73df'
                });
            });

            // Test widget button
            // $('#testWidgetBtn').click(function() {
            //     try {
            //         // Remove any existing widget
            //         $('.aplu-push-container').remove();

            //         const scriptText = $('#scriptCode').text();
            //         const scriptBlock = document.createElement('script');
            //         scriptBlock.innerHTML = scriptText;

            //         // Append to body and execute
            //         document.body.appendChild(scriptBlock);

            //         Swal.fire({
            //             icon: 'success',
            //             title: 'Widget Loaded',
            //             text: 'The notification widget should now appear on this page',
            //             timer: 2000,
            //             showConfirmButton: false,
            //             position: 'top-end',
            //             toast: true,
            //             background: '#f8f9fa'
            //         });
            //     } catch (e) {
            //         console.error('Error testing widget:', e);
            //         Swal.fire({
            //             icon: 'error',
            //             title: 'Test Failed',
            //             text: 'Could not test the widget: ' + e.message,
            //             confirmButtonColor: '#4e73df'
            //         });
            //     }
            // });
        });
    </script>
@endpush
