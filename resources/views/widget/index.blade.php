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

        // Function to open the URL in a smaller window
        // function openInSmallWindow(event, url) {
        //     event.preventDefault();  // Prevent the default link action
        //     const width = 600;        // Set the width of the new window
        //     const height = 400;       // Set the height of the new window
        //     const left = (window.innerWidth - width) / 2;  // Center the window horizontally
        //     const top = (window.innerHeight - height) / 2; // Center the window vertically
            
        //     window.open(url, "SingleSecondaryWindowName", `width=${width},height=${height},top=${top},left=${left}`);
        // }

        // // Function to check if the user has already interacted with the opt-in
        // function checkOptInStatus() {
        //     return localStorage.getItem('apluPushOptIn');
        // }

        // // Function to create the opt-in form
        // function createOptInForm() {
        //     // Create the main opt-in container element
        //     var optInContainer = document.createElement('div');
        //     optInContainer.id = 'apluPushOptIn';
        //     optInContainer.style.position = 'fixed';
        //     optInContainer.style.top = '12%';
        //     optInContainer.style.left = '50%';
        //     optInContainer.style.transform = 'translate(-50%, -50%)';
        //     optInContainer.style.padding = '18px 20px';
        //     optInContainer.style.backgroundColor = '#ffffff';
        //     optInContainer.style.borderRadius = '12px';
        //     optInContainer.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.15)';
        //     optInContainer.style.zIndex = '9999';
        //     optInContainer.style.maxWidth = '400px';
        //     optInContainer.style.width = '90%';
        //     optInContainer.style.textAlign = 'center';
        //     optInContainer.style.fontFamily = 'Arial, sans-serif';
        //     optInContainer.style.border = '1px solid #e0e0e0';

        //     // Add content to the container
        //     optInContainer.innerHTML = `
        //         <h2 style="margin-top: 0; color: #333; font-size: 22px;">Stay Updated!</h2>
        //         <p style="color: #666; margin-bottom: 10px; line-height: 1.5;font-size: 14px;">
        //             We'd like to send you notifications for the latest updates and news. 
        //             Would you like to enable them?
        //         </p>
        //         <div style="display: flex; gap: 10px; justify-content: center;">
        //             <button id="allowButton" style="padding: 10px 20px; background-color: rgb(249 58 11); 
        //                 color: white; border: none; border-radius: 6px; cursor: pointer; 
        //                 font-weight: bold; transition: background-color 0.3s;">
        //                 Allow
        //             </button>
        //             <button id="denyButton" style="padding: 10px 20px; background-color: #f1f1f1; 
        //                 color: #333; border: none; border-radius: 6px; cursor: pointer; 
        //                 font-weight: bold; transition: background-color 0.3s;">
        //                 Deny
        //             </button>
        //         </div>
        //     `;

        //     // Append the opt-in form to the body
        //     document.body.appendChild(optInContainer);

        //     // Add hover effects
        //     document.getElementById('allowButton').onmouseover = function() {
        //         this.style.backgroundColor = '#45a049';
        //     };
        //     document.getElementById('allowButton').onmouseout = function() {
        //         this.style.backgroundColor = '#4CAF50';
        //     };
        //     document.getElementById('denyButton').onmouseover = function() {
        //         this.style.backgroundColor = '#e0e0e0';
        //     };
        //     document.getElementById('denyButton').onmouseout = function() {
        //         this.style.backgroundColor = '#f1f1f1';
        //     };

        //     // Event listeners for the buttons
        //     document.getElementById('allowButton').addEventListener('click', function() {
        //         allowNotifications();
        //     });

        //     document.getElementById('denyButton').addEventListener('click', function() {
        //         denyNotifications();
        //     });
        // }

        // // Function to handle the "Allow" button click
        // function allowNotifications() {
        //     // Define the dynamic variables
        //     const promptText = "Please click 'Allow' when asked about notifications to subscribe to updates";

        //     // Dynamically generate the URL with encoded components
        //     const parentOrigin = new URL(window.location.origin).hostname; 
        //     const url = `https://host.awmtab.in/permission.html?parentOrigin=${encodeURIComponent(parentOrigin)}&promptText=${encodeURIComponent(promptText)}`;

        //     // Open the URL in a smaller window
        //     openInSmallWindow(event, url);

        //     // Close the opt-in form
        //     closeOptInForm();
        // }

        // // Function to handle the "Deny" button click
        // function denyNotifications() {
        //     // Save the user's choice to local storage
        //     localStorage.setItem('apluPushOptIn', 'denied');
        //     // Close the opt-in form
        //     closeOptInForm();
        //     console.log("User denied notifications.");
        // }

        // // Function to close the opt-in form
        // function closeOptInForm() {
        //     var optInContainer = document.getElementById('apluPushOptIn');
        //     if (optInContainer) {
        //         // Add fade-out animation
        //         optInContainer.style.transition = 'opacity 0.3s ease';
        //         optInContainer.style.opacity = '0';
        //         setTimeout(function() {
        //             optInContainer.remove();
        //         }, 300);
        //     }
        // }

        // // Function to show the opt-in form if the user has not yet interacted
        // function showOptInForm() {
        //     var optInStatus = checkOptInStatus();
        //     if (!optInStatus) {
        //         // Show the form after 2 seconds
        //         setTimeout(function() {
        //             createOptInForm();
        //         }, 2000);
        //     }
        // }

        // // Run the script after the page has loaded
        // window.addEventListener('DOMContentLoaded', function() {
        //     showOptInForm();
        // });
    </script>

<script src='http://localhost:8000/widget.js'></script>

<script>
    let apluPush = new ApluPush(
        "We want to notify you about the latest updates.", // Heading
        "You can unsubscribe anytime later.", // Subheading
        "Yes", // Yes button text
        "Later", // No Button Text 
        "http://localhost:8000/images/push/icons/alarm-1.png", // Bell Icon
        "Please click 'Allow' when asked about notifications to subscribe to updates."
    );
    apluPush.init(); 
</script>
                            
    
@endpush
