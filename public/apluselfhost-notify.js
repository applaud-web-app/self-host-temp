// public/js/apluselfhost-notify.js
;(async () => {
  'use strict';

  // 1) Utility to load external JS
  async function loadScript(url) {
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = url;
      s.onload = () => resolve();
      s.onerror = () => reject(new Error(`Failed to load ${url}`));
      document.head.appendChild(s);
    });
  }

  try {
    // 2) Load Firebase App & Messaging (v9 compat)
    await loadScript('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
    await loadScript('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');
  } catch (e) {
    console.error('[PushNotify] Firebase SDK load error:', e);
    return;
  }

  // 3) Read config + VAPID from meta tags
  const cfgMeta   = document.querySelector('meta[name="firebase-config"]');
  const vapidMeta = document.querySelector('meta[name="firebase-vapid-key"]');

  if (!cfgMeta) {
    console.error('[PushNotify] <meta name="firebase-config"> not found');
    return;
  }
  if (!vapidMeta) {
    console.error('[PushNotify] <meta name="firebase-vapid-key"> not found');
    return;
  }

  let firebaseConfig, vapidKey;
  try {
    firebaseConfig = JSON.parse(cfgMeta.getAttribute('content'));
    vapidKey       = vapidMeta.getAttribute('content');
  } catch (err) {
    console.error('[PushNotify] Invalid JSON in meta tag:', err);
    return;
  }

  // 4) Initialize Firebase & Messaging
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  // 5) Subscribe for push & send token to server
  async function subscribeUser() {
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
      throw new Error(`Permission ${permission}`);
    }

    const token = await messaging.getToken({ vapidKey });
    // send token & domain to your subscribe endpoint
    await fetch('/push/subscribe', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        token,
        domain: window.location.hostname
      })
    });

    console.log('[PushNotify] Subscribed with token:', token);
    return token;
  }

  subscribeUser().catch(err => {
    console.error('[PushNotify] Subscription failed:', err);
  });

  // 6) Handle messages while page is in focus
  messaging.onMessage(payload => {
    console.log('[PushNotify] Message received:', payload);
    const notif = payload.notification || {};
    new Notification(notif.title || '', {
      body: notif.body,
      icon: notif.icon,
      image: notif.image,
      data: payload.data
    });
  });

  // 7) Optionally expose a manual refresh for the token
  window.pushNotifyRefreshToken = async () => {
    try {
      await messaging.deleteToken();
      console.log('[PushNotify] Old token deleted');
      const newToken = await subscribeUser();
      console.log('[PushNotify] Refreshed token:', newToken);
      return newToken;
    } catch (e) {
      console.error('[PushNotify] Refresh failed:', e);
    }
  };
})();
