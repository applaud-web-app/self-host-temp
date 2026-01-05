const SW_IDENTIFIER = 'aplu-selfhost-sw-v1';

/* ---------------- Immediate SW activation ---------------- */
self.addEventListener('install', (event) => self.skipWaiting());
self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    await storeSWIdentifier(SW_IDENTIFIER);
    await self.clients.claim();
    // On activate, try to flush anything left in the durable queue
    await flushAnalyticsQueue({ reason: 'activate' });
  })());
});

/* ---------------- Firebase compat libs ---------------- */
importScripts(
  'https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js',
  'https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js'
);

/* ---------------- Firebase init ---------------- */
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

/* ---------------- Endpoints & constants ---------------- */
const ANALYTICS_ENDPOINT = "{{ route('api.analytics') }}"; // expects { analytics: [...] }
const SUBSCRIBE_ENDPOINT = "{{ route('api.subscribe') }}";
const DEFAULT_ICON       = '/favicon.ico';

/* ---------------- Batching config ---------------- */
const BATCH_INTERVAL_MS       = 2000; // 2s window
const BATCH_MAX_SIZE          = 10;   // flush immediately at/over this
const BATCH_MAX_TAKE          = 500;  // send at most this many per POST (safety cap)
const RETRY_ATTEMPTS          = 3;    // per POST
const RETRY_BASE_DELAY_MS     = 1000; // 1s -> 2s -> 4s

/* ---------------- Runtime state ---------------- */
let flushTimerId = null;
let isFlushing   = false;

/* ---------------- Ensure we're the right SW ---------------- */
function isCorrectServiceWorkerActive() {
  return getSWIdentifierFromDB().then(activeSW => activeSW === SW_IDENTIFIER);
}

/* ====================================================================== */
/* =========================== Analytics API ============================ */
/* ====================================================================== */

/** Enqueue analytics event (durable) and schedule flush */
async function sendAnalytics(eventType, messageId) {
  const correct = await isCorrectServiceWorkerActive();
  if (!correct) return;

  // Persist event immediately (no domain)
  await enqueueAnalyticsEvent({
    message_id: messageId || '',
    event: eventType,
    ts: Date.now()
  });

  // Trigger flush policy
  const qLen = await getAnalyticsQueueLength();
  if (qLen >= BATCH_MAX_SIZE) {
    scheduleImmediateFlush('threshold');
  } else {
    scheduleTimedFlush();
  }

  // Optional resilience
  if (self.registration && self.registration.sync && 'sync' in self.registration) {
    try { await self.registration.sync.register('analytics-sync'); } catch (_) {}
  }
}

/* ---------------- Firebase background messages ---------------- */
messaging.onBackgroundMessage((payload) => {
  const d = payload.data || {};
  const messageId = d.message_id || '';

  let actions = [];
  try { actions = JSON.parse(d.actions || '[]'); }
  catch (e) { /* ignore invalid JSON */ }

  const title = d.title?.trim();
  const body  = d.body?.trim();

  // Only show notification if title or body is non-empty
  if (!title && !body) return Promise.resolve();

  const options = {
    body:  body || '',
    icon:  d.icon || DEFAULT_ICON,
    image: d.image || undefined,
    data: {
      click_action: d.click_action || payload.fcmOptions?.link || '/',
      message_id: messageId,
      actions: actions
    },
    actions: actions.map(a => ({ action: a.action, title: a.title }))
  };

  // Only enqueue "received" analytics if notification will be shown
  const p1 = sendAnalytics('received', messageId);

  return Promise.all([p1]).then(() => self.registration.showNotification(title || 'Notification', options));
});

/* ---------------- Notification click/close ---------------- */
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const data = event.notification.data || {};
  const messageId = data.message_id || '';
  let url = data.click_action || '/';

  if (event.action) {
    const match = (data.actions || []).find(a => a.action === event.action);
    if (match?.url) url = match.url;
  }

  event.waitUntil(Promise.all([
    sendAnalytics('click', messageId),
    flushAnalyticsQueue({ reason: 'notificationclick' }),
    clients.openWindow(url)
  ]));
});

/* ---------------- Subscription change ---------------- */
self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil(
    (async () => {
      if (event.oldSubscription?.options) {
        const sub = await self.registration.pushManager.subscribe(event.oldSubscription.options);
        await fetch(SUBSCRIBE_ENDPOINT, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(sub.toJSON())
        }).catch(() => {});
      }
      await flushAnalyticsQueue({ reason: 'pushsubscriptionchange' });
    })()
  );
});

/* ---------------- Background Sync ---------------- */
self.addEventListener('sync', (event) => {
  if (event.tag === 'analytics-sync') {
    event.waitUntil(flushAnalyticsQueue({ reason: 'background-sync' }));
  }
});

/* ---------------- Optional: flush when page hides ---------------- */
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'PAGE_HIDDEN') {
    event.waitUntil(flushAnalyticsQueue({ reason: 'page-hidden' }));
  }
});

/* ====================================================================== */
/* ===================== Durable queue (IndexedDB) ====================== */
/* ====================================================================== */

function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('serviceWorkerDB', 2);
    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains('swData')) {
        db.createObjectStore('swData', { keyPath: 'key' });
      }
      if (!db.objectStoreNames.contains('analyticsQueue')) {
        const s = db.createObjectStore('analyticsQueue', { keyPath: 'id', autoIncrement: true });
        s.createIndex('by_ts', 'ts', { unique: false });
      }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror   = () => reject('Failed to open IndexedDB');
  });
}

/* ---- swData helpers ---- */
function storeSWIdentifier(identifier) {
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('swData', 'readwrite');
    tx.objectStore('swData').put({ key: 'activeSW', value: identifier });
    tx.oncomplete = () => resolve();
    tx.onerror    = () => reject('Failed to store SW identifier');
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

/* ---- analyticsQueue helpers ---- */
function enqueueAnalyticsEvent(evt) {
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('analyticsQueue', 'readwrite');
    tx.objectStore('analyticsQueue').add(evt);
    tx.oncomplete = () => resolve();
    tx.onerror    = () => reject('Failed to enqueue analytics event');
  }));
}

function getAnalyticsQueueLength() {
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('analyticsQueue', 'readonly');
    const req = tx.objectStore('analyticsQueue').count();
    req.onsuccess = () => resolve(req.result || 0);
    req.onerror   = () => reject('Failed to count analytics queue');
  }));
}

function readNextAnalyticsBatch(limit = BATCH_MAX_TAKE) {
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('analyticsQueue', 'readonly');
    const idx = tx.objectStore('analyticsQueue').index('by_ts');
    const items = [];
    idx.openCursor().onsuccess = (e) => {
      const cursor = e.target.result;
      if (!cursor || items.length >= limit) return resolve(items);
      items.push({ id: cursor.primaryKey, ...cursor.value });
      cursor.continue();
    };
    tx.onerror = () => reject('Failed to read analytics batch');
  }));
}

function deleteAnalyticsItems(ids) {
  if (!ids?.length) return Promise.resolve();
  return openDB().then(db => new Promise((resolve, reject) => {
    const tx = db.transaction('analyticsQueue', 'readwrite');
    const store = tx.objectStore('analyticsQueue');
    ids.forEach(id => store.delete(id));
    tx.oncomplete = () => resolve();
    tx.onerror    = () => reject('Failed to delete analytics items');
  }));
}

/* ====================================================================== */
/* ======================= Flush & retry logic ========================== */
/* ====================================================================== */

function scheduleTimedFlush() {
  if (flushTimerId) return;
  flushTimerId = setTimeout(() => {
    flushTimerId = null;
    flushAnalyticsQueue({ reason: 'timer' });
  }, BATCH_INTERVAL_MS);
}

function scheduleImmediateFlush(reason = 'threshold') {
  if (flushTimerId) {
    clearTimeout(flushTimerId);
    flushTimerId = null;
  }
  return flushAnalyticsQueue({ reason });
}

async function flushAnalyticsQueue({ reason = 'manual' } = {}) {
  if (isFlushing) return;
  isFlushing = true;

  try {
    while (true) {
      const batch = await readNextAnalyticsBatch(BATCH_MAX_TAKE);
      if (!batch.length) break;

      const payload = {
        analytics: batch.map(({ message_id, event, ts }) => ({
          message_id,
          event,
          timestamp: ts
        }))
      };

      const ok = await postBatchedAnalyticsWithRetry(payload, batch.length);
      if (ok) {
        await deleteAnalyticsItems(batch.map(b => b.id));
      } else {
        // give up for now; keep items in queue for next attempt
        break;
      }

      if (batch.length < BATCH_MAX_TAKE) break;
    }
  } catch (err) {
    // keep items; they remain in the queue
    console.error('flushAnalyticsQueue error:', err);
  } finally {
    isFlushing = false;
  }
}

function delay(ms) { return new Promise(res => setTimeout(res, ms)); }

async function postBatchedAnalyticsWithRetry(payload, reportedSize) {
  for (let attempt = 0; attempt < RETRY_ATTEMPTS; attempt++) {
    try {
      const res = await fetch(ANALYTICS_ENDPOINT, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-Batch-Size': String(reportedSize)
        },
        body: JSON.stringify(payload)
      });
      if (res.ok) return true;
    } catch (_) { /* network error */ }

    if (attempt < RETRY_ATTEMPTS - 1) {
      await delay(RETRY_BASE_DELAY_MS * Math.pow(2, attempt)); // 1s, 2s, 4s
    }
  }
  return false;
}