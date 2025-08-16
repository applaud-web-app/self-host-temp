@extends('layouts.master')


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
                                            <input type="text" id="iconUrl" class="form-control" value="{{ asset('images/push/icons/alarm-1.png') }}">
                                        </div>
                                        <small class="form-text text-muted">URL of the notification icon</small>
                                    </div>
                                </div>

                                <div class="text-end">
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
                                <pre class="rounded p-3 mb-0" id="scriptPre"  contenteditable="true"><code class="language-html" id="scriptCode"></code></pre>
                            </div>

                            <div class="d-flex flex-column flex-md-row">
                                <button id="copyButton" data-clipboard-target="#scriptCode"
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

@push('scripts')
    <!-- Prism.js for syntax highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.0/plugins/line-numbers/prism-line-numbers.min.js"></script>

    <!-- ClipboardJS -->
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>

    <script>
        $(document).ready(function () {
            // Generate Script Button
            $('#generateScriptBtn').click(function () {
                const heading = $('#heading').val() || "We want to notify you about the latest updates.";
                const subheading = $('#subheading').val() || "You can unsubscribe anytime later.";
                const yesText = $('#yesText').val() || "Yes";
                const noText = $('#noText').val() || "Later";
                const popupText = $('#popupText').val() || "Please click 'Allow' when asked about notifications to subscribe to updates.";
                const btnColor = $('#btnColor').val() || "#4e73df";
                const iconUrl = $('#iconUrl').val() || "{{ asset('images/push/icons/alarm-1.png') }}";

                const script = `
<script src="{{ asset('blogger.js') }}"><\/script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let apluPush = new ApluPush(
        ${JSON.stringify(heading)},
        ${JSON.stringify(subheading)},
        ${JSON.stringify(yesText)},
        ${JSON.stringify(noText)},
        ${JSON.stringify(iconUrl)},
        ${JSON.stringify(popupText)},
        ${JSON.stringify(btnColor)},
    );
    apluPush.init();
});
<\/script>
                `.trim();

                $('#scriptCode').text(script);
                $('#configFormCard').fadeOut(300, function () {
                    $('#scriptDisplayCard').fadeIn(300);
                    Prism.highlightAll();
                });
            });

            // Back to form button
            $('#backToFormBtn').click(function () {
                $('#scriptDisplayCard').fadeOut(300, function () {
                    $('#configFormCard').fadeIn(300);
                });
            });

            // Initialize ClipboardJS
            const clipboard = new ClipboardJS('#copyButton');

            clipboard.on('success', function (e) {
                iziToast.success({
                    title: 'Copied',
                    message: 'Embed code copied to clipboard!',
                    position: 'topRight'
                });
                e.clearSelection();
            });

            clipboard.on('error', function (e) {
                iziToast.error({
                    title: 'Error',
                    message: 'Failed to copy to clipboard.',
                    position: 'topRight'
                });
            });
        });
    </script>
@endpush

