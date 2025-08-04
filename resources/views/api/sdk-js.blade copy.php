// push-notify.js (stateless, no CSRF/session)
;(async () => {
  'use strict';

  // CONFIG
  const SW_PATH       = '/apluselfhost-messaging-sw.js';
  const SUB_URL       = '{{route('api.subscribe')}}';
  const UNSUB_URL     = '{{route('api.unsubscribe')}}';
  const TOKEN_LS_KEY  = 'push_token';

  // 1) dynamic script loader
  const load = src => new Promise((res, rej) => {
    const s = document.createElement('script');
    s.src = src;
    s.onload = () => res();
    s.onerror = () => rej(new Error(`Failed to load ${src}`));
    document.head.appendChild(s);
  });

  // 2) load Firebase compat libs _in sequence_
  await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
  await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

  // 3) init Firebase
  firebase.initializeApp(@json($cfg));
  const messaging = firebase.messaging();

  // 4) register your SW once
  let swReg = null;
  if ('serviceWorker' in navigator) {
    try {
      swReg = await navigator.serviceWorker.register(SW_PATH);
      console.log('[Push] SW registered at', SW_PATH);
    } catch (err) {
      console.error('[Push] SW registration failed', err);
    }
  }

  // 5) helper for POSTing JSON and error checking
  const apiPost = async (url, payload) => {
    const res = await fetch(url, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });
    if (!res.ok) throw new Error(`API ${url} returned ${res.status}`);
    return res.json();
  };

  // 6) subscribe (or update) function
  async function subscribe() {
    // ask for notification permission
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') {
      console.warn('[Push] Notification permission not granted');
      return null;
    }

    // prepare getToken options
    const opts = { vapidKey: "{{ $vapid }}" };
    if (swReg) opts.serviceWorkerRegistration = swReg;

    // 6a) fetch a new FCM token
    const newToken = await messaging.getToken(opts);
    if (!newToken) throw new Error('No FCM token retrieved');

    // 6b) old token for update
    const oldToken = localStorage.getItem(TOKEN_LS_KEY) || null;

    // If it hasn’t changed, skip the API call entirely
    if (oldToken && oldToken === newToken) {
      console.log('[Push] Token unchanged, skipping subscribe');
      return newToken;
    }

    // 6c) pull raw PushSubscription for endpoint + keys
    let endpoint = '', auth = '', p256dh = '';
    if (swReg) {
      const pushSub = await swReg.pushManager.getSubscription();
      if (pushSub) {
        const subJson = pushSub.toJSON();
        endpoint = subJson.endpoint;
        auth     = (subJson.keys && subJson.keys.auth)   || '';
        p256dh   = (subJson.keys && subJson.keys.p256dh) || '';
      }
    }

    // 6d) POST to your backend
    await apiPost(SUB_URL, {
      token:     newToken,
      old_token: oldToken,
      domain:    location.hostname,
      url:       location.href,
      endpoint,
      auth,
      p256dh
    });

    // 6e) store locally so next time we update instead of insert
    localStorage.setItem(TOKEN_LS_KEY, newToken);
    console.log('[Push] Subscribed/Updated token', newToken);
    return newToken;
  }

  // 7) initial subscribe on page load
  subscribe().catch(e => console.error('[Push] subscribe error', e));

  // 8) handle token refresh (v9 compat)
  if (typeof messaging.onTokenRefresh === 'function') {
    messaging.onTokenRefresh(async () => {
      try {
        await messaging.deleteToken();
        await subscribe();
        console.log('[Push] Token refreshed and updated');
      } catch (err) {
        console.error('[Push] Token refresh error', err);
      }
    });
  }

  // 9) unsubscribe helper
  window.pushNotifyUnsubscribe = async () => {
    const token = localStorage.getItem(TOKEN_LS_KEY);
    if (!token) throw new Error('No token present to unsubscribe');

    // remove from FCM & local storage
    await messaging.deleteToken();
    localStorage.removeItem(TOKEN_LS_KEY);

    // notify backend and return the new count
    const { unsub_count } = await apiPost(UNSUB_URL, { token });
    console.log('[Push] Unsubscribed, count:', unsub_count);
    return unsub_count;
  };

  // 10) handle in-page notifications
  messaging.onMessage(async payload => {
    console.log('[Push] Foreground message received:', payload);

    const d = payload.data || {};
    const title = d.title || 'Notification';
    const messageId = d.message_id || '';
    const clickAction = d.click_action || '/';

    const options = {
      body: d.body || '',
      icon: d.icon || '/favicon.ico',
      image: d.image,
      data: {
        click_action: clickAction,
        message_id: messageId,
        actions: []
      }
    };

    // Parse actions
    try {
      const actions = JSON.parse(d.actions || '[]');
      options.data.actions = actions;
    } catch (e) {
      console.warn('[Push] Invalid actions JSON in foreground:', e);
    }

    // 👉 Send "received" analytics
    console.log('[Push] Sending received analytics:', {
      message_id: messageId,
      event: 'received',
      domain: location.hostname
    });

    try {
      const res = await fetch('{{route('api.analytics')}}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message_id: messageId,
          event: 'received',
          domain: location.hostname
        })
      });

      if (!res.ok) {
        const text = await res.text();
        console.warn('[Push] Analytics response error:', res.status, text);
      } else {
        console.log('[Push] Received analytics sent successfully');
      }
    } catch (err) {
      console.error('[Push] Foreground received analytics failed:', err);
    }

    // 👉 Show browser notification
    if (Notification.permission === 'granted') {
      console.log('[Push] Showing foreground notification:', { title, options });

      const notif = new Notification(title, options);

      notif.onclick = async function () {
        console.log('[Push] Foreground notification clicked:', clickAction);

        // 👉 Send "click" analytics
        console.log('[Push] Sending click analytics:', {
          message_id: messageId,
          event: 'click',
          domain: location.hostname
        });

        try {
          const res = await fetch('{{route('api.analytics')}}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              message_id: messageId,
              event: 'click',
              domain: location.hostname
            })
          });

          if (!res.ok) {
            const text = await res.text();
            console.warn('[Push] Click analytics response error:', res.status, text);
          } else {
            console.log('[Push] Click analytics sent successfully');
          }
        } catch (err) {
          console.error('[Push] Foreground click analytics failed:', err);
        }

        // Open target URL
        window.open(clickAction, '_blank');
      };
    } else {
      console.warn('[Push] Foreground notification skipped — permission not granted');
    }
  });


  // 11) expose manual refresh if needed
  window.pushNotifyRefreshToken = subscribe;

})();
