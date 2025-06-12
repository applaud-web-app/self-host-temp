// dynamic push-notify.js (stateless, no CSRF/session)
;(async()=>{
  'use strict';

  // 1) tiny helper to load a script by URL
  async function load(src) {
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = src;
      s.onload = resolve;
      s.onerror = ()=>reject(new Error(`Failed to load ${src}`));
      document.head.appendChild(s);
    });
  }

  // 2) load Firebase compat libs
  await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
  await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

  // 3) injected values
  const firebaseConfig = @json($cfg);
  const vapidKey       = "{{ $vapid }}";
  const SW_PATH        = "/aplupush-messaging-sw.js";
  const SUBSCRIBE_URL  = "/api/push/subscribe"; // your stateless endpoint

  // 4) init Firebase & Messaging
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  // 5) register *your* SW and tell FCM about it
  let swRegistration = null;
  if ('serviceWorker' in navigator) {
    try {
      swRegistration = await navigator.serviceWorker.register(SW_PATH);
      messaging.useServiceWorker(swRegistration);
      console.log('[Push] SW registered at', SW_PATH);
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

    const token = await messaging.getToken({ vapidKey });
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

  // 8) auto-refresh token when it changes
  messaging.onTokenRefresh?.(async () => {
    try {
      await messaging.deleteToken();
      const newToken = await subscribe();
      console.log('[Push] Token refreshed:', newToken);
    } catch (err) {
      console.error('[Push] token refresh error', err);
    }
  });

  // 9) handle foreground (in-page) messages
  messaging.onMessage(payload => {
    const n = payload.notification || {};
    // if SW supports showNotification, use it; otherwise fallback to window.Notification
    const show = swRegistration?.showNotification?.bind(swRegistration)
      || (title, opts)=>new Notification(title, opts);

    show(n.title || '', {
      body:  n.body,
      icon:  n.icon,
      image: n.image,
      data:  payload.data
    });
  });

  // 10) expose a manual refresh helper
  window.pushNotifyRefreshToken = async () => {
    await messaging.deleteToken();
    return subscribe();
  };
})();
