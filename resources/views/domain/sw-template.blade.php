// 0) Immediate SW activation
self.addEventListener('install', event => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));

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
const ANALYTICS_ENDPOINT = "{{ route('api.analytics') }}";
const SUBSCRIBE_ENDPOINT = "{{ route('api.subscribe') }}";
const DEFAULT_ICON       = '/favicon.ico';

// 3) Analytics helper
async function sendAnalytics(eventType, messageId) {
  try {
    await fetch(ANALYTICS_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message_id: messageId,
        event: eventType
      })
    });
  } catch (e) {
    console.error('Analytics error:', e);
  }
}

// 4) Background Firebase messages
messaging.onBackgroundMessage(payload => {

  const d = payload.data || {};
  const messageId = d.message_id || '';
  
  // always ping "received" first
  sendAnalytics('received', messageId);

  const title = d.title || 'Notification';
  const options = {
    body:    d.body          || '',
    icon:    d.icon          || DEFAULT_ICON,
    image:   d.image         || undefined,
    data: {
      click_action: d.click_action || payload.fcmOptions?.link || '/',
      message_id: messageId  || ''
    }
  };

  return self.registration.showNotification(title, options);
});

// 5) Fallback for raw push events
self.addEventListener('push', event => {
  let payload = {};
  try {
    payload = event.data.json();
  } catch {}

  // If it has a .data block, Firebase already showed it for us
  if (payload.data) {
    return;
  }

  const d = payload.data || payload;
  const messageId = d.message_id || '';

  // ping raw push arrival
  sendAnalytics('received', messageId);

  const title = d.title || 'Notification';
  const options = {
    body:    d.body          || '',
    icon:    d.icon          || DEFAULT_ICON,
    image:   d.image         || undefined,
    data: {
      click_action: d.click_action || '/',
      message_id:   d.message_id   || ''
    }
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// 6) Notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const md = event.notification.data?.message_id || '';
  sendAnalytics('click', md);
  const url = event.notification.data?.click_action || '/';
  event.waitUntil(clients.openWindow(url));
});

// 7) Handle dismissals
self.addEventListener('notificationclose', event => {
  const md = event.notification.data?.message_id || '';
  sendAnalytics('close', md);
});

// 8) Subscription change
self.addEventListener('pushsubscriptionchange', event => {
  event.waitUntil(
    event.oldSubscription && event.oldSubscription.options
      ? self.registration.pushManager
          .subscribe(event.oldSubscription.options)
          .then(sub => {
            return fetch(SUBSCRIBE_ENDPOINT, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(sub.toJSON())
            });
          })
      : Promise.resolve()
  );
});