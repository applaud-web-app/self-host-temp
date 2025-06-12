@extends('layouts.master')

@section('content')
<section class="content-body">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center text-head">
            <h2 class="mb-3">Firebase Push Notification Setup</h2>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card ">
                    <div class="card-body">
                        {{-- Intro --}}
                        <p>
                            Follow the steps below to integrate Firebase Push Notifications into your application.
                        </p>

                        {{-- Step 1 --}}
                        <div class="mb-4">
                            <h4 class="mb-2"><i class="fas fa-fire me-2 text-danger"></i>Step 1: Create a Firebase Project</h4>
                            <p>
                                Visit <a href="https://console.firebase.google.com" target="_blank">Firebase Console</a>,
                                sign in, and click “<strong>Add Project</strong>”. Follow the setup wizard.
                            </p>
                        </div>

                        {{-- Step 2 --}}
                        <div class="mb-4">
                            <h4 class="mb-2"><i class="fas fa-code me-2 text-primary"></i>Step 2: Add a Web App</h4>
                            <p>
                                Navigate to <strong>Project Settings → General</strong>. Under “Your apps”, click the web icon (</>) and register your app.
                                Firebase will give you a configuration script.
                            </p>
                        </div>

                        {{-- Step 3 --}}
                        <div class="mb-4">
                            <h4 class="mb-2"><i class="fas fa-key me-2 text-success"></i>Step 3: Enable Cloud Messaging</h4>
                            <p>
                                In the left sidebar, go to <strong>Build → Cloud Messaging</strong>. Generate a VAPID key pair and save the <strong>public key</strong>.
                            </p>
                        </div>

                        {{-- Step 4 --}}
                        <div class="mb-4">
                            <h4 class="mb-2"><i class="fas fa-file-code me-2 text-info"></i>Step 4: Add Firebase Scripts</h4>
                            <p>Add the following to your Blade layout (typically in the `<head>` or before closing `</body>`):</p>
                            <pre class="bg-light p-3"><code>&lt;script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"&gt;&lt;/script&gt;
&lt;script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"&gt;&lt;/script&gt;</code></pre>
                        </div>

                        {{-- Step 5 --}}
                        <div class="mb-4">
                            <h4 class="mb-2"><i class="fas fa-cogs me-2 text-warning"></i>Step 5: Initialize Firebase</h4>
                            <p>Paste your config and initialize messaging:</p>
                            <pre class="bg-light p-3"><code>const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "your-project.firebaseapp.com",
  projectId: "your-project-id",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID",
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();</code></pre>
                        </div>

                        {{-- Step 6 --}}
                        <div class="mb-4">
                            <h4 class="mb-2"><i class="fas fa-bell me-2 text-danger"></i>Step 6: Request Notification Permission</h4>
                            <pre class="bg-light p-3"><code>messaging.requestPermission()
  .then(() => messaging.getToken({ vapidKey: 'YOUR_PUBLIC_VAPID_KEY' }))
  .then(token => {
    console.log('FCM Token:', token);
    // Send token to backend
  })
  .catch(err => console.error('Permission error:', err));</code></pre>
                        </div>

                        {{-- Step 7 --}}
                        <div class="mb-4">
                            <h4 class="mb-2"><i class="fas fa-server me-2 text-secondary"></i>Step 7: Create Service Worker</h4>
                            <p>Save this file as <code>public/firebase-messaging-sw.js</code>:</p>
                            <pre class="bg-light p-3"><code>importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "...",
  messagingSenderId: "...",
  projectId: "...",
  appId: "..."
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/icon.png'
  };
  self.registration.showNotification(notificationTitle, notificationOptions);
});</code></pre>
                        </div>

                        {{-- Step 8 --}}
                        <div class="mb-4">
                            <h4 class="mb-2"><i class="fas fa-paper-plane me-2 text-primary"></i>Step 8: Send Push Notification from Laravel</h4>
                            <p>Use Laravel's HTTP client to push messages:</p>
                            <pre class="bg-light p-3"><code>use Illuminate\Support\Facades\Http;

Http::withHeaders([
    'Authorization' => 'key=YOUR_SERVER_KEY',
    'Content-Type' => 'application/json',
])->post('https://fcm.googleapis.com/fcm/send', [
    'to' => $user->fcm_token,
    'notification' => [
        'title' => 'Test Notification',
        'body' => 'This is a test message!',
    ],
]);</code></pre>
                        </div>

                     
                       
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
