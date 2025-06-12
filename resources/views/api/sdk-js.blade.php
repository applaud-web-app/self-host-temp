// push-notify.js (stateless, no CSRF/session)
;(async function(){
  'use strict';

  // 1) tiny helper to load a script by URL
  async function load(src) {
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = src;
      s.onload = resolve;
      s.onerror = () => reject(new Error(`Failed to load ${src}`));
      document.head.appendChild(s);
    });
  }

  // 2) load Firebase compat libraries
  await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
  await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

  // 3) injected values
  const firebaseConfig = @json($cfg);
  const vapidKey       = "{{ $vapid }}";
  const SW_PATH        = "/apluselfhost-messaging-sw.js";  // your actual SW filename
  const SUBSCRIBE_URL  = "/api/push/subscribe";

  // 4) init Firebase & Messaging
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  // 5) register *your* SW
  let swRegistration = null;
  if ('serviceWorker' in navigator) {
    try {
      swRegistration = await navigator.serviceWorker.register(SW_PATH);
      console.log('[Push] SW registered at', SW_PATH);
      // no more useServiceWorker in v9, we'll pass the registration to getToken
    } catch (err) {
      console.error('[Push] SW registration failed', err);
    }
  } else {
    console.warn('[Push] Service workers not supported.');
  }

  // 6) subscribe helper: request permission, get token, POST to your API
  async function subscribe() {
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') {
      throw new Error(`Notification permission ${perm}`);
    }

    // pass your SW registration so Firebase won't try the default SW
    const tokenOpts = { vapidKey };
    if (swRegistration) tokenOpts.serviceWorkerRegistration = swRegistration;

    const token = await messaging.getToken(tokenOpts);
    if (!token) {
      throw new Error('No FCM token retrieved');
    }

    await fetch(SUBSCRIBE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        token,
        domain: location.hostname
      })
    });

    console.log('[Push] Subscribed:', token);
    return token;
  }

  // 7) initial subscribe
  subscribe().catch(e => console.error('[Push] subscribe error', e));

  // 8) auto-refresh token when it changes (compat only)
  if (typeof messaging.onTokenRefresh === 'function') {
    messaging.onTokenRefresh(async function() {
      try {
        await messaging.deleteToken();
        const newToken = await subscribe();
        console.log('[Push] Token refreshed:', newToken);
      } catch (err) {
        console.error('[Push] token refresh error', err);
      }
    });
  }

  // 9) handle foreground messages
  messaging.onMessage(function(payload) {
    const n = payload.notification || {};
    if (swRegistration && typeof swRegistration.showNotification === 'function') {
      swRegistration.showNotification(n.title || '', {
        body:  n.body,
        icon:  n.icon,
        image: n.image,
        data:  payload.data
      });
    } else {
      new Notification(n.title || '', {
        body:  n.body,
        icon:  n.icon,
        image: n.image,
        data:  payload.data
      });
    }
  });

  // 10) expose a manual refresh helper
  window.pushNotifyRefreshToken = async function() {
    await messaging.deleteToken();
    return subscribe();
  };

})();