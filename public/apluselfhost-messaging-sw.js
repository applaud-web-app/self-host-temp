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
  apiKey:            "AIzaSyCx9ie7q1flTUAjtBWKn9uZUpRpJdJvCtE",
  authDomain:        "learn-react-78ccf.firebaseapp.com",
  projectId:         "learn-react-78ccf",
  storageBucket:     "learn-react-78ccf.firebasestorage.app",
  messagingSenderId: "943408743701",
  appId:             "1:943408743701:web:acbac44c9c8e4eb8a9951c",
  measurementId:     "943408743701"
});

const messaging = firebase.messaging();
const ANALYTICS_ENDPOINT = "{{route('api.push.notify')}}";
const SUBSCRIBE_ENDPOINT = "{{route('api.subscribe')}}";
const DEFAULT_ICON       = '/favicon.ico';

// 3) Analytics helper
function sendAnalytics(eventType, messageId) {
  return fetch(ANALYTICS_ENDPOINT, {
    method: "POST",
    credentials: "same-origin",          // keep cookies (e.g. Sanctum) if needed
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      message_id: messageId,
      event: eventType,
      domain: self.location.hostname
    })
  }).catch(err => {
    console.error('Analytics error:', err);
    return null; 
  });
}

// 4) Background Firebase messages
messaging.onBackgroundMessage(payload => {

  const d = payload.data || {};
  const messageId = d.message_id || '';
  
  // always ping "received" first
  sendAnalytics('received', messageId);

  let actions = [];
  try {
    actions = JSON.parse(d.actions || '[]');
  } catch (e) {
    console.warn('Invalid actions JSON:', e);
  }

  const title = d.title || 'Notification';
  const options = {
    body:    d.body          || '',
    icon:    d.icon          || DEFAULT_ICON,
    image:   d.image         || undefined,
    data: {
      click_action: d.click_action || payload.fcmOptions?.link || '/',
      message_id: messageId,
      actions: actions
    },
    actions: actions.map(a => ({
      action: a.action,
      title:  a.title
    }))
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
  if (payload.data) return;

  const d = payload.data || payload;
  const messageId = d.message_id || '';

  let actions = [];
  try {
    actions = JSON.parse(d.actions || '[]');
  } catch (e) {
    console.warn('Invalid actions JSON in raw push:', e);
  }

  const title = d.title || 'Notification';
  const options = {
    body:    d.body          || '',
    icon:    d.icon          || DEFAULT_ICON,
    image:   d.image         || undefined,
    data: {
      click_action: d.click_action || '/',
      message_id: messageId,
      actions: actions
    },
    actions: actions.map(a => ({
      action: a.action,
      title: a.title
    }))
  };

  // Single waitUntil with both analytics + showNotification
  event.waitUntil(
    Promise.all([
      sendAnalytics('received', messageId),
      self.registration.showNotification(title, opts)
    ])
  );
});

// 6) Notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();

  const data = event.notification.data || {};
  const messageId = data.message_id || '';

  let url = data.click_action || '/';

  if (event.action) {
    const match = (data.actions || []).find(a => a.action === event.action);
    if (match && match.url) {
      url = match.url;
    }
  }

  const analytics  = sendAnalytics('click', messageId);
  const openTab = clients.openWindow(url);
  event.waitUntil(Promise.all([analytics, openTab]));
});

// 7) Handle dismissals
self.addEventListener('notificationclose', event => {
  const data = event.notification.data || {};
  const messageId = data.message_id || '';
  event.waitUntil(sendAnalytics('close', messageId));
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