// 0) Immediate SW activation
self.addEventListener('install', event => {
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(self.clients.claim());
});

// 1) Import Firebase compat libraries
importScripts(
  'https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js',
  'https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js'
);

// 2) Initialize Firebase App
firebase.initializeApp({
  apiKey:            "{{ $config['apiKey'] }}",
  authDomain:        "{{ $config['authDomain'] }}",
  projectId:         "{{ $config['projectId'] }}",
  storageBucket:     "{{ $config['storageBucket'] }}",
  messagingSenderId: "{{ $config['messagingSenderId'] }}",
  appId:             "{{ $config['appId'] }}",
  measurementId:     "{{ $config['measurementId'] }}"
});

const messaging = firebase.messaging();
const ANALYTICS_ENDPOINT = "{{ url('/api/push/analytics') }}"; // point to your analytics route

// 3) Analytics helper
async function sendAnalytics(eventType, data = {}) {
  try {
    await fetch(ANALYTICS_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        event:  eventType,
        domain: "{{ $domain }}",
        data
      })
    });
  } catch (e) {
    console.error('Analytics error:', e);
  }
}

// 4) Background Firebase messages
messaging.setBackgroundMessageHandler(payload => {
  // Report receipt
  sendAnalytics('received', payload);

  const notif = payload.notification || {};
  const options = {
    body: notif.body,
    icon: notif.icon || '/favicon.ico',
    image: notif.image,
    data: {
      click_action: notif.click_action || payload.fcmOptions?.link || '/',
      raw: payload
    }
  };

  return self.registration.showNotification(notif.title || 'Notification', options);
});

// 5) Fallback: raw push event
self.addEventListener('push', event => {
  let payload = {};
  try { payload = event.data.json(); } catch{}
  sendAnalytics('push_event', payload);
  // optionally show a default notification here if you like
});

// 6) Handle notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const clickData = event.notification.data || {};
  sendAnalytics('click', clickData.raw || clickData);

  // navigate to click_action URL
  const url = clickData.click_action || '/';
  event.waitUntil(clients.openWindow(url));
});

// 7) Handle dismissals
self.addEventListener('notificationclose', event => {
  const closeData = event.notification.data || {};
  sendAnalytics('close', closeData.raw || closeData);
});

// 8) Handle subscription changes
self.addEventListener('pushsubscriptionchange', event => {
  sendAnalytics('subscription_change', {});
  event.waitUntil(
    self.registration.pushManager.subscribe(event.oldSubscription.options)
      .then(sub => {
        sendAnalytics('subscription_success', sub.toJSON());
        // TODO: POST the new subscription back to your /push/subscribe endpoint
      })
      .catch(err => {
        sendAnalytics('subscription_error', { message: err.message });
      })
  );
});