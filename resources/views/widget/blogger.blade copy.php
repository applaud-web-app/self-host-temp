@extends('layouts.master')

@section('content')
    <section class="content-body" id="profile_page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h3>Embed Script</h3>
                    <p>Copy the code below to include the widget on your site.</p>

                    <!-- Script Display with Copy Icon -->
                    <div class="card">
                        <div class="card-body">
                            <!-- Code Preview with Syntax Highlighting (Prism.js) -->
                            <pre class="line-numbers  rounded-1 mb-3">
                                <code class="language-html">
&lt;script src='{{asset('widget.js')}}'&gt;&lt;/script&gt;

&lt;script&gt;
    let apluPush = new ApluPush(
        "We want to notify you about the latest updates.", // Heading
        "You can unsubscribe anytime later.", // Subheading
        "Yes", // Yes button text
        "Later", // No Button Text 
        "{{asset('images/push/icons/alarm-clock.png')}}", // Bell Icon
        "Please click 'Allow' when asked about notifications to subscribe to updates." // Popup Text
    );
    apluPush.init(); 
&lt;/script&gt;
                                </code>
                            </pre>
                            <!-- Copy Icon Button -->
                            <button id="copyButton" class="btn btn-outline-primary btn-sm w-100 mt-2">
                                <i class="fas fa-copy"></i> Copy Code
                            </button>
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

    <!-- Copy to Clipboard Function -->
    <script>
        document.getElementById('copyButton').addEventListener('click', function() {
            var copyText = document.getElementById('scriptToCopy').innerText;

            // Create a temporary textarea to hold the script text for copying
            var textArea = document.createElement('textarea');
            textArea.value = copyText;
            document.body.appendChild(textArea);

            // Select the text and copy it to clipboard
            textArea.select();
            document.execCommand("copy");

            // Remove the temporary textarea from the document
            document.body.removeChild(textArea);

            // Display a success alert with a similar vibe to Sublime's feedback
            iziToast.success({
                title: 'Copied!',
                message: 'The script has been copied to clipboard.',
                position: 'topRight',
                timeout: 3000,
                close: true,
                displayMode: 'replace'
            });
        });
    </script>

    <!-- Include iziToast CSS for the success alert -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <!-- Include iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
@endpush
