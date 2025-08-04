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
                            <pre id="scriptToCopy" class="bg-light p-3 rounded">
&lt;script src='{{asset('widget.js')}}'&gt;&lt;/script&gt;

&lt;script&gt;
    let apluPush = new ApluPush(
        "We want to notify you about the latest updates.", // Heading
        "You can unsubscribe anytime later.", // Subheading
        "Yes", // Yes button text
        "Later", // No Button Text 
        "{{asset('images\push\icons\alarm-clock.png')}}", // Bell Icon
        "Please click 'Allow' when asked about notifications to subscribe to updates." // Popup Text
    );
    apluPush.init(); 
&lt;/script&gt;
                            </pre>
                            <!-- Copy Icon Button -->
                            <button id="copyButton" class="btn btn-outline-primary mt-2">
                                <i class="fas fa-copy"></i> Copy Code
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
    <script>
        // Copy to Clipboard Function
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

            // Alert the user
            alert("Script copied to clipboard!");
        });
    </script>
@endpush
