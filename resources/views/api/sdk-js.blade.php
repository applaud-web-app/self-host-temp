// dynamic push-notify.js (no session/CSRF)
;(async()=>{
  'use strict';

  // helper to load scripts
  async function load(src) {
    return new Promise((ok, no) => {
      const s=document.createElement('script');
      s.src=src; s.onload=ok; s.onerror=_=>no(new Error(`Failed to load ${src}`));
      document.head.appendChild(s);
    });
  }

  // 1) load Firebase compat libs
  await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
  await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

  // 2) injected config
  const firebaseConfig = @json($cfg);
  const vapidKey       = "{{ $vapid }}";

  // 3) init
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  // 4) subscribe and POST to stateless API
  async function subscribe() {
    const perm = await Notification.requestPermission();
    if(perm!=='granted') throw new Error(`Permission ${perm}`);
    const token = await messaging.getToken({vapidKey});
    await fetch('/api/push/subscribe', {
      method: 'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        token,
        domain: location.hostname
      })
    });
    console.log('[Push] Subscribed', token);
    return token;
  }

  subscribe().catch(e=>console.error('[Push] subscribe error',e));

  // 5) handle in-page messages
  messaging.onMessage(payload=>{
    const n=payload.notification||{};
    new Notification(n.title||'',{
      body:n.body, icon:n.icon, image:n.image, data:payload.data
    });
  });

  // 6) expose token refresh
  window.pushNotifyRefreshToken = async ()=>{
    await messaging.deleteToken();
    return subscribe();
  };
})();