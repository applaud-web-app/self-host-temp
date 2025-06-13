{{-- resources/views/settings/firebase-setup.blade.php --}}
@extends('layouts.master')

@section('content')
<section class="content-body">
  <div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center text-head mb-3">
      <h2>Firebase Push Notification Setup</h2>
    </div>

    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-body">
            {{-- Intro --}}
            <p>
              Follow these steps to integrate Firebase Push Notifications into your application.
            </p>

            {{-- Step 1 --}}
            <div class="mb-4">
              <h4 class="mb-2">
                <i class="fas fa-fire me-2 text-danger"></i>
                Step 1: Create a Firebase Project
              </h4>
              <p>
                Go to the <a href="https://console.firebase.google.com" target="_blank">Firebase Console</a>, sign in, and click 
                <strong>Add Project</strong>. Follow the wizard to create your new project.
              </p>
            </div>

            <hr style="border-top:1px dashed #ccc;">

            {{-- Step 2 --}}
            <div class="mb-4">
              <h4 class="mb-2">
                <i class="fas fa-code me-2 text-primary"></i>
                Step 2: Add a Web App
              </h4>
              <p>
                In your Firebase project, open <strong>Settings → General</strong>. Under “Your apps”, click the web icon (<code>&lt;/&gt;</code>) 
                and register a new app. Copy the config snippet that Firebase provides.
              </p>
            </div>

            <hr style="border-top:1px dashed #ccc;">

            {{-- Step 3 --}}
            <div class="mb-4">
              <h4 class="mb-2">
                <i class="fas fa-key me-2 text-success"></i>
                Step 3: Enable Cloud Messaging
              </h4>
              <p>
                In the left sidebar, choose <strong>Build → Cloud Messaging</strong>. Generate a VAPID key pair and copy the 
                <strong>public key</strong>—you’ll need it on the client.
              </p>
            </div>

            <hr style="border-top:1px dashed #ccc;">

            {{-- Step 4 --}}
            <div class="mb-4">
              <h4 class="mb-2">
                <i class="fas fa-file-code me-2 text-info"></i>
                Step 4: Add Firebase Scripts
              </h4>
              <p>
                Include these before your closing <code>&lt;/body&gt;</code> tag (or in your main Blade layout):
              </p>
              <pre class="bg-light p-3"><code>&lt;script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"&gt;&lt;/script&gt;
&lt;script src="https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"&gt;&lt;/script&gt;</code></pre>
            </div>

            <hr style="border-top:1px dashed #ccc;">

            {{-- Step 5 --}}
            <div class="mb-4">
              <h4 class="mb-2">
                <i class="fas fa-cogs me-2 text-warning"></i>
                Step 5: Initialize Firebase
              </h4>
              <p>Paste your config from Step 2 and initialize messaging:</p>
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

            <hr style="border-top:1px dashed #ccc;">

            {{-- Step 6 --}}
            <div class="mb-4">
              <h4 class="mb-2">
                <i class="fas fa-bell me-2 text-danger"></i>
                Step 6: Request Notification Permission
              </h4>
              <p>Ask the user and retrieve their FCM token:</p>
              <pre class="bg-light p-3"><code>messaging.requestPermission()
  .then(() => messaging.getToken({ vapidKey: 'YOUR_PUBLIC_VAPID_KEY' }))
  .then(token => {
    console.log('FCM Token:', token);
    // Send this token to your backend to save it
  })
  .catch(err => console.error('Permission error:', err));</code></pre>
            </div>

            <hr style="border-top:1px dashed #ccc;">

            {{-- Step 7 --}}
            <div class="mb-4">
              <h4 class="mb-2">
                <i class="fas fa-server me-2 text-secondary"></i>
                Step 7: Create Service Worker
              </h4>
              <p>Save this as <code>public/firebase-messaging-sw.js</code>:</p>
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

            <hr style="border-top:1px dashed #ccc;">

            {{-- Step 8 --}}
            <div class="mb-4">
              <h4 class="mb-2">
                <i class="fas fa-paper-plane me-2 text-primary"></i>
                Step 8: Send Push from Laravel
              </h4>
              <p>Use Laravel’s HTTP client to send notifications:</p>
              <pre class="bg-light p-3"><code>use Illuminate\Support\Facades\Http;

Http::withHeaders([
    'Authorization' => 'key=YOUR_SERVER_KEY',
    'Content-Type'  => 'application/json',
])->post('https://fcm.googleapis.com/fcm/send', [
    'to'           => $user->fcm_token,
    'notification' => [
        'title' => 'Test Notification',
        'body'  => 'This is a test message!',
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
