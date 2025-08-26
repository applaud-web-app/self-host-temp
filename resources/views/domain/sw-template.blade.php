const SW_IDENTIFIER = 'my-custom-sw-v1';  

// 0) Immediate SW activation
self.addEventListener('install', event => self.skipWaiting());
self.addEventListener('activate', event => {
  storeSWIdentifier(SW_IDENTIFIER);
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
const ANALYTICS_ENDPOINT = "{{ route('api.analytics') }}";
const SUBSCRIBE_ENDPOINT = "{{ route('api.subscribe') }}";
const DEFAULT_ICON       = '/favicon.ico';

function isCorrectServiceWorkerActive() {
  return getSWIdentifierFromDB().then(activeSW => activeSW === SW_IDENTIFIER); 
}

// 3) Analytics helper
async function sendAnalytics(eventType, messageId) {
  
  const correct = await isCorrectServiceWorkerActive();
  if (!correct) {
    console.log('Not the correct Service Worker. Skipping analytics.');
    return;
  }

  // Default domain
  let domain = self.location.hostname;

  // Try to read parentOrigin from IDB and use its hostname
  try {
    const parentOrigin = await getParentOriginFromDB();
    if (parentOrigin && typeof parentOrigin === 'string') {
      try {
        // If it's a full origin like "https://example.com", grab hostname
        domain = new URL(parentOrigin).hostname || parentOrigin;
      } catch {
        // If it's already just a hostname, use as-is
        domain = parentOrigin;
      }
    }
  } catch (e) {
    // Keep fallback
    console.warn('parentOrigin not available; using self.location.hostname');
  }
  
  return fetch(ANALYTICS_ENDPOINT, {
    method: "POST",
    credentials: "same-origin",         
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      message_id: messageId,
      event: eventType,
      domain
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
      self.registration.showNotification(title, options)
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

// IndexedDB helper functions
function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('serviceWorkerDB', 1);
    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains('swData')) {
        db.createObjectStore('swData', { keyPath: 'key' });
      }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject('Failed to open IndexedDB');
  });
}

function storeSWIdentifier(identifier) {
  return openDB().then(db => {
    const transaction = db.transaction('swData', 'readwrite');
    const store = transaction.objectStore('swData');
    store.put({ key: 'activeSW', value: identifier });
    return transaction.complete;
  });
}

function getSWIdentifierFromDB() {
  return openDB().then(db => {
    return new Promise((resolve, reject) => {
      const transaction = db.transaction('swData', 'readonly');
      const store = transaction.objectStore('swData');
      const request = store.get('activeSW');
      request.onsuccess = () => resolve(request.result ? request.result.value : null);
      request.onerror = () => reject('Failed to retrieve SW identifier');
    });
  });
}

// Add alongside your other IndexedDB helpers:
function getParentOriginFromDB() {
  return openDB().then(db => {
    return new Promise((resolve, reject) => {
      const tx = db.transaction('swData', 'readonly');
      const store = tx.objectStore('swData');
      const req = store.get('parentOrigin');
      req.onsuccess = () => {
        const val = req.result ? req.result.value : null;
        resolve(val);
      };
      req.onerror = () => reject('Failed to retrieve parentOrigin');
    });
  });
}