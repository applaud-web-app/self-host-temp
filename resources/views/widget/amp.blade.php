@extends('layouts.master')

@section('content')
    <section class="content-body" id="profile_page">
        <div class="container-fluid">

            <div class="d-flex flex-wrap align-items-center justify-content-between text-head mb-3">
                <h2 class="me-auto mb-0">AMP Widget</h2>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                       
                            <!-- Step 1: Download Buttons -->
                            <div class="border rounded-1 p-4 mb-4">
                                <h4 class="mb-3">Step 1: Download Required Files</h4>
                                <p>Start by downloading the required JavaScript files and hosting them on your server. Please upload the downloaded files to the <code>root</code> directory of your website.</p>
                               
                                <div class="d-flex gap-3">
                                    <a href="{{ asset('firebase-messaging.js') }}" class="btn btn-secondary" download>
                                        <i class="fas fa-download"></i> Download Firebase Messaging JS
                                    </a>
                                    <a href="{{ asset('widget.js') }}" class="btn btn-secondary" download>
                                        <i class="fas fa-download"></i> Download Widget JS
                                    </a>
                                </div>
                            </div>

                            <!-- Step 2: Embed Script -->
                            <div class="border rounded-1 p-4 mb-4">
                                <h4 class="mb-3">Step 2: Embed Script</h4>
                                <p>Next, copy and paste the following script into the <strong><code>&lt;head&gt;</code></strong> tag of your HTML file. This will load the widget functionality on your website.</p>
                                
                                <!-- Code Preview: Syntax Highlighting -->
                                <pre class="line-numbers  rounded-1 mb-3">
<code class="language-html">
&lt;script src='{{ asset('widget.js') }}'&gt;&lt;/script&gt;

&lt;script&gt;
    let apluPush = new ApluPush(
        "We want to notify you about the latest updates.", // Heading
        "You can unsubscribe anytime later.", // Subheading
        "Yes", // Yes button text
        "Later", // No Button Text 
        "{{ asset('images/push/icons/alarm-clock.png') }}", // Bell Icon
        "Please click 'Allow' when asked about notifications to subscribe to updates." // Popup Text
    );
    apluPush.init(); 
&lt;/script&gt;
</code>
                                </pre>

                                <button id="copyScriptButton" class="btn btn-sm w-100 btn-outline-success">
                                    <i class="fas fa-copy"></i> Copy Script
                                </button>
                            </div>

                            <!-- Step 3: Button Code -->
                            <div class="border rounded-1 p-4 mb-4">
                                <h4 class="mb-3">Step 3: Add Button Code</h4>
                                <p>To trigger the notification widget, you will need to place the following button code in the <strong><code>&lt;body&gt;</code></strong> tag of your HTML, where you want the button to appear.</p>
                                
                                <!-- Code Preview: Syntax Highlighting -->
                                <pre class="line-numbers  rounded-1 mb-3">
<code class="language-html">
&lt;button id="notificationButton" class="btn btn-primary"&gt;
    &lt;i class="fas fa-bell"&gt;&lt;/i&gt; Enable Notifications
&lt;/button&gt;
</code>
                                </pre>

                                <button id="copyButtonCodeButton" class="btn btn-sm w-100 btn-outline-success">
                                    <i class="fas fa-copy"></i> Copy Button Code
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
            copyToClipboard('scriptToCopy', "Script copied to clipboard!");
        });

        // Copy Button Code to Clipboard
        document.getElementById('copyButtonCodeButton').addEventListener('click', function() {
            copyToClipboard('buttonCodeToCopy', "Button code copied to clipboard!");
        });

        // Reusable Copy Function with iziToast
        function copyToClipboard(elementId, successMessage) {
            var copyText = document.getElementById(elementId).innerText;
            var textArea = document.createElement('textarea');
            textArea.value = copyText;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);
            
            // Show iziToast notification
            iziToast.success({
                title: 'Success',
                message: successMessage,
                position: 'topRight',
                timeout: 3000,
                close: true,
                displayMode: 'replace'
            });
        }
    </script>
@endpush
