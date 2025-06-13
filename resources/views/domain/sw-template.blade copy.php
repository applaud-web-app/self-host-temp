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
async function sendAnalytics(eventType, data = {}) {
  try {
    await fetch(ANALYTICS_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        event:  eventType,
        domain: "testdevansh.awmtab.in",
        data
      })
    });
  } catch (e) {
    console.error('Analytics error:', e);
  }
}

// 4) Background Firebase messages
messaging.onBackgroundMessage(payload => {
  sendAnalytics('received', payload);

  const d = payload.data || {};

  const title = d.title || 'Notification';
  const options = {
    body:    d.body          || '',
    icon:    d.icon          || DEFAULT_ICON,
    image:   d.image         || undefined,
    data: {
      click_action: d.click_action || payload.fcmOptions?.link || '/',
      message_id:   d.message_id   || '',
      raw:          payload
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

  sendAnalytics('push_event', payload);

  const d = payload.data || payload;
  const title = d.title || 'Notification';
  const options = {
    body:    d.body          || '',
    icon:    d.icon          || DEFAULT_ICON,
    image:   d.image         || undefined,
    data: {
      click_action: d.click_action || '/',
      message_id:   d.message_id   || '',
      raw:          payload
    }
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// 6) Notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const clickData = event.notification.data || {};
  sendAnalytics('click', clickData.raw || clickData);
  const url = clickData.click_action || '/';
  event.waitUntil(clients.openWindow(url));
});

// 7) Handle dismissals
self.addEventListener('notificationclose', event => {
  const closeData = event.notification.data || {};
  sendAnalytics('close', closeData.raw || closeData);
});

// 8) Subscription change
self.addEventListener('pushsubscriptionchange', event => {
  sendAnalytics('subscription_change', {});
  event.waitUntil(
    event.oldSubscription && event.oldSubscription.options
      ? self.registration.pushManager
          .subscribe(event.oldSubscription.options)
          .then(sub => {
            sendAnalytics('subscription_success', sub.toJSON());
            return fetch(SUBSCRIBE_ENDPOINT, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(sub.toJSON())
            });
          })
      : Promise.resolve()
  );
});