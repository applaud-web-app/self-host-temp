const SW_IDENTIFIER = 'my-custom-sw-v1';

// 0) Immediate SW activation
self.addEventListener('install', (event) => self.skipWaiting());
self.addEventListener('activate', (event) => {
  event.waitUntil(Promise.all([
    storeSWIdentifier(SW_IDENTIFIER),
    self.clients.claim(),
    flushAnalytics() // flush anything left from a previous SW spin
  ]));
});

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
const ANALYTICS_ENDPOINT = "http://localhost:8000/api/push/analytics";
const SUBSCRIBE_ENDPOINT = "http://localhost:8000/api/push/subscribe";
const DEFAULT_ICON       = '/favicon.ico';

// ===== Analytics batching (size 10 or 2s) =====
const MAX_BATCH_SIZE = 10;   // your requirement
const BATCH_DELAY    = 2000; // 2 seconds
const MAX_QUEUE      = 1000; // safety cap

let analyticsQueue = [];
let flushTimer = null;
let flushInFlight = null;

// Restore any saved queue at startup so we don't lose events
loadQueueFromDB().then(q => { if (Array.isArray(q)) analyticsQueue = q; }).catch(() => {});

function isCorrectServiceWorkerActive() {
  return getSWIdentifierFromDB().then(activeSW => activeSW === SW_IDENTIFIER);
}

function scheduleFlush() {
  if (flushTimer) return;
  flushTimer = setTimeout(() => {
    flushTimer = null;
    flushAnalytics();
  }, BATCH_DELAY);
}

function enqueueAnalytics(eventType, messageId) {
  analyticsQueue.push({
    event: eventType,
    message_id: messageId || '',
    ts: Date.now()
  });

  // Bound growth & persist
  if (analyticsQueue.length > MAX_QUEUE) {
    analyticsQueue.splice(0, analyticsQueue.length - MAX_QUEUE);
  }
  saveQueueToDB(analyticsQueue).catch(() => {});

  // Flush immediately if new size hit the batch size else in 2s
  if (analyticsQueue.length >= MAX_BATCH_SIZE) {
    return flushAnalytics();
  } else {
    scheduleFlush();
    return Promise.resolve();
  }
}

async function flushAnalytics() {
  if (flushInFlight) return flushInFlight;
  if (!analyticsQueue.length) return;

  // Take up to MAX_BATCH_SIZE; rest stays queued
  const batch = analyticsQueue.splice(0, MAX_BATCH_SIZE);

  // Only the right SW should send
  const correct = await isCorrectServiceWorkerActive().catch(() => false);
  if (!correct) {
    analyticsQueue = batch.concat(analyticsQueue).slice(0, MAX_QUEUE);
    await saveQueueToDB(analyticsQueue);
    return;
  }

  const events = batch.map(e => ({ ...e }));
  const payload = { batch: true, events };

  flushInFlight = fetch(ANALYTICS_ENDPOINT, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  })
  .catch(err => {
    // Re-queue batch on failure
    analyticsQueue = events.concat(analyticsQueue).slice(0, MAX_QUEUE);
    console.error('Analytics batch error:', err);
  })
  .finally(async () => {
    flushInFlight = null;
    await saveQueueToDB(analyticsQueue);
    if (analyticsQueue.length >= MAX_BATCH_SIZE) {
      // drain quickly
      flushAnalytics();
    } else if (analyticsQueue.length) {
      scheduleFlush();
    }
  });

  return flushInFlight;
}

// Public helper (keeps your call sites unchanged)
async function sendAnalytics(eventType, messageId) {
  return enqueueAnalytics(eventType, messageId);
}

// ===== Messaging handlers =====
// Note: onBackgroundMessage is NOT extendable; keep it for display only (no analytics)
messaging.onBackgroundMessage((payload) => {
  const d = payload.data || {};
  let actions = [];
  try { actions = JSON.parse(d.actions || '[]'); } catch (e) { console.warn('Invalid actions JSON:', e); }

  const title = d.title || 'Notification';
  const options = {
    body:  d.body   || '',
    icon:  d.icon   || DEFAULT_ICON,
    image: d.image  || undefined,
    data: {
      click_action: d.click_action || payload.fcmOptions?.link || '/',
      message_id: d.message_id || '',
      actions: actions
    },
    actions: actions.map(a => ({ action: a.action, title: a.title }))
  };

  return self.registration.showNotification(title, options);
});

// Fallback / primary analytics hook for ALL pushes (extendable)
self.addEventListener('push', (event) => {
  let payload = {};
  try { payload = event.data.json(); } catch {}

  // Always compute 'd' so we can log analytics even if FCM rendered it
  const d = (payload && (payload.data || payload)) || {};
  const messageId = d.message_id || '';

  // If payload.data exists, FCM likely displayed the notification already.
  const shouldShowNotification = !(payload && payload.data);

  let actions = [];
  try { actions = JSON.parse(d.actions || '[]'); } catch (e) { /* ignore */ }

  const title = d.title || 'Notification';
  const options = {
    body:  d.body   || '',
    icon:  d.icon   || DEFAULT_ICON,
    image: d.image  || undefined,
    data: {
      click_action: d.click_action || '/',
      message_id: messageId,
      actions: actions
    },
    actions: actions.map(a => ({ action: a.action, title: a.title }))
  };

  const tasks = [ sendAnalytics('received', messageId), flushAnalytics() ];
  if (shouldShowNotification) {
    tasks.push(self.registration.showNotification(title, options));
  }

  event.waitUntil(Promise.all(tasks));
});

// Clicks
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const data = event.notification.data || {};
  const messageId = data.message_id || '';

  let url = data.click_action || '/';
  if (event.action) {
    const match = (data.actions || []).find(a => a.action === event.action);
    if (match && match.url) url = match.url;
  }

  const queued = sendAnalytics('click', messageId);
  const openTab = clients.openWindow(url);
  event.waitUntil(Promise.all([queued, flushAnalytics(), openTab]));
});

// Dismissals
self.addEventListener('notificationclose', (event) => {
  const data = event.notification.data || {};
  const messageId = data.message_id || '';
  const queued = sendAnalytics('close', messageId);
  event.waitUntil(Promise.all([queued, flushAnalytics()]));
});

// Optional: allow a page to tell the SW to flush now
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'FLUSH_ANALYTICS') {
    event.waitUntil(flushAnalytics());
  }
});

// 8) Subscription change
self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil(
    event.oldSubscription && event.oldSubscription.options
      ? self.registration.pushManager
          .subscribe(event.oldSubscription.options)
          .then(sub => fetch(SUBSCRIBE_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(sub.toJSON())
          }))
      : Promise.resolve()
  );
});

// ===== IndexedDB helpers (fixed transactions) =====
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
    request.onerror  = () => reject('Failed to open IndexedDB');
  });
}

function storeSWIdentifier(identifier) {
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('swData', 'readwrite');
    tx.oncomplete = () => resolve();
    tx.onerror    = () => reject('Failed to store SW identifier');
    tx.objectStore('swData').put({ key: 'activeSW', value: identifier });
  }));
}

function getSWIdentifierFromDB() {
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('swData', 'readonly');
    const req = tx.objectStore('swData').get('activeSW');
    req.onsuccess = () => resolve(req.result ? req.result.value : null);
    req.onerror   = () => reject('Failed to retrieve SW identifier');
  }));
}

function getParentOriginFromDB() {
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('swData', 'readonly');
    const req = tx.objectStore('swData').get('parentOrigin');
    req.onsuccess = () => resolve(req.result ? req.result.value : null);
    req.onerror   = () => reject('Failed to retrieve parentOrigin');
  }));
}

// Persist analytics queue so SW restarts don't lose events
function saveQueueToDB(queue) {
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('swData', 'readwrite');
    tx.oncomplete = () => resolve();
    tx.onerror    = () => reject('Failed to persist analytics queue');
    tx.objectStore('swData').put({ key: 'analyticsQueue', value: queue });
  }));
}

function loadQueueFromDB() {
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('swData', 'readonly');
    const req = tx.objectStore('swData').get('analyticsQueue');
    req.onsuccess = () => resolve(req.result ? req.result.value : []);
    req.onerror   = () => reject('Failed to load analytics queue');
  }));
}