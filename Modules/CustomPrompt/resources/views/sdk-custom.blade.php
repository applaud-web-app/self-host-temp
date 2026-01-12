@php
  $cp = $customPrompt ? [
    'status'                 => (int)($customPrompt->status ?? 1),
    'title'                  => $customPrompt->title ?? 'We want to notify you about the latest updates.',
    'description'            => $customPrompt->description ?? 'You can unsubscribe anytime later.',
    'icon'                   => $customPrompt->icon ?? asset('images/push/icons/alarm-1.png'),
    'allow_btn_text'         => $customPrompt->allow_btn_text ?? 'Allow',
    'allow_btn_color'        => $customPrompt->allow_btn_color ?? '#00c220',
    'allow_btn_text_color'   => $customPrompt->allow_btn_text_color ?? '#ffffff',
    'deny_btn_text'          => $customPrompt->deny_btn_text ?? 'Deny',
    'deny_btn_color'         => $customPrompt->deny_btn_color ?? '#ff0000',
    'deny_btn_text_color'    => $customPrompt->deny_btn_text_color ?? '#ffffff',
    'enable_desktop'         => (bool)($customPrompt->enable_desktop ?? true),
    'enable_mobile'          => (bool)($customPrompt->enable_mobile ?? true),
    'delay'                  => (int)($customPrompt->delay ?? 0),  
    'reappear'               => (int)($customPrompt->reappear ?? 0),
    'enable_allow_only'      => (bool)($customPrompt->enable_allow_only ?? false),
    'deny_text_allow_only'   => $customPrompt->deny_text_allow_only ?? '',
    'prompt_location_mobile' => $customPrompt->prompt_location_mobile ?? 'bottom',
  ] : null;
@endphp

;(async () => {
  'use strict';

  // CONFIG
  const SW_PATH       = '/apluselfhost-messaging-sw.js';
  const SUB_URL       = '{{route('api.subscribe')}}';
  const UNSUB_URL     = '{{route('api.unsubscribe')}}';
  const TOKEN_LS_KEY  = 'push_token';
  const customPrompt = @json($cp);
  const CP = (typeof customPrompt !== 'undefined') ? customPrompt : null;

  // 👉 Ribbon config
  const RIBBON_ID     = 'mainApluPushPoweredBy';

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
  firebase.initializeApp(@json($config->web_app_config));
  const messaging = firebase.messaging();
  let analytics = null;
  if (firebase.analytics) {
      analytics = firebase.analytics();
  }

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

  // 👉 Helpers for ribbon + state
  const isSubscribed = () => Boolean(localStorage.getItem(TOKEN_LS_KEY));

  function showApluPushPoweredByRibbon() {
    if (isSubscribed()) {
      removePoweredByRibbon();
      return;
    }
    if (document.getElementById(RIBBON_ID)) return; // already present

    const ribbon = document.createElement('div');
    ribbon.id = RIBBON_ID;
    ribbon.classList.add('ribbon-pop-main');
    ribbon.innerHTML =
      '<a href="https://aplu.io" target="_blank" style="color:#fff !important;">Notifications Powered By <b>Aplu</b></a>';

    Object.assign(ribbon.style, {
      background: '#000000ad',
      padding: '5px 10px',
      color: 'white',
      position: 'fixed',
      fontSize: '12px',
      top: '0px',
      right: '0px',
      zIndex: '1111111111',
      fontFamily: 'monospace'
    });

    document.body.appendChild(ribbon);
  }

  function removePoweredByRibbon() {
    const ribbon = document.getElementById(RIBBON_ID);
    if (ribbon) ribbon.remove();
  }

  const onDomReady = (fn) => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  };

 // ===== CUSTOM PROMPT ADD-ON START =====
const isMobile = () => /Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(navigator.userAgent);
let reappearShown = false;

function isCpActive() {
  if (!CP || (CP.status|0) !== 1) return false;
  const onDesktop = !isMobile();
  if ((onDesktop && !CP.enable_desktop) || (!onDesktop && !CP.enable_mobile)) return false;
  if (Notification.permission !== 'default') return false;
  if (isSubscribed()) return false; 
  if (reappearShown) return false; 
  return true;
}

function startReappearTimer() {
  const secs = Number(CP?.reappear || 0);
  if (secs > 0) {
    setTimeout(  () => {
      if (isCpActive()) {
        reappearShown = true;
        showCustomPrompt();
      }
    }, secs * 1000);
  }
}

// small entrance animation
function animateIn(el) {
  el.style.opacity = '0';
  el.style.transform = 'translateY(-10px)';
  el.style.transition = 'opacity 250ms ease, transform 250ms ease';
  requestAnimationFrame(() => {
    el.style.opacity = '1';
    el.style.transform = 'translateY(0)';
  });
}

let cpEl = null;
function buildCustomPrompt() {
  if (cpEl) return cpEl;

  const onDesktop = !isMobile();

  // OUTER WRAP
  const wrap = document.createElement('div');
  wrap.id = 'aplu-cp-wrap';
  Object.assign(wrap.style, {
    position: 'fixed',
    zIndex: '2147483646',
    pointerEvents: 'none',
    left: '0',
    right: '0',
    display: 'flex',
    justifyContent: 'center'
  });

  // Positioning
  if (onDesktop) {
    Object.assign(wrap.style, { top: '2%' });
  } else {
    const loc = (CP.prompt_location_mobile || 'bottom');
    if (loc === 'top')   Object.assign(wrap.style, { top: '12px' });
    if (loc === 'center')Object.assign(wrap.style, { top: 'calc(50% - 110px)' });
    if (loc === 'bottom')Object.assign(wrap.style, { bottom: '12px' });
  }

  // CARD
  const card = document.createElement('div');
  Object.assign(card.style, {
    maxWidth: '450px',
    width: '90%',
    padding: '18px 18px 14px',
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'space-between',
    alignItems: 'center',
    textAlign: 'center',
    pointerEvents: 'auto',
    backgroundColor: '#ffffff',
    color: '#333',
    borderRadius: '12px',
    boxShadow: '0 4px 20px rgba(0,0,0,0.15)',
    border: '1px solid #e0e0e0',
    fontFamily: 'Arial, sans-serif'
  });

  // HEADER
  const headerRow = document.createElement('div');
  Object.assign(headerRow.style, {
    display: 'flex',
    marginBottom: '10px',
    width: '100%'
  });

  const img = document.createElement('img');
  img.src = CP.icon || '/favicon.ico';
  img.alt = 'Notification Icon';
  Object.assign(img.style, { width: '40px', height: '40px', marginRight: '10px', borderRadius: '8px' });

  const textCol = document.createElement('div');
  Object.assign(textCol.style, { flex: '1', textAlign: 'left' });

  const h2 = document.createElement('h2');
  h2.textContent = CP.title || 'We want to notify you about the latest updates.';
  Object.assign(h2.style, { margin: '0', color: '#333', fontSize: '18px' });

  const p = document.createElement('p');
  p.textContent = CP.description || 'You can unsubscribe anytime later.';
  Object.assign(p.style, { color: '#666', marginTop: '0px', fontSize: '14px', marginBottom: '0' });

  textCol.appendChild(h2);
  textCol.appendChild(p);
  headerRow.appendChild(img);
  headerRow.appendChild(textCol);

  // ACTIONS
  const actions = document.createElement('div');
  Object.assign(actions.style, {
    display: 'flex',
    gap: '10px',
    width: '100%',
    justifyContent: 'space-between'
  });

  const allowBtn = document.createElement('button');
  allowBtn.id = 'apluAllowBtn';
  allowBtn.textContent = CP.allow_btn_text || 'Allow';
  Object.assign(allowBtn.style, {
    backgroundColor: CP.allow_btn_color || '#f93a0b',
    color: CP.allow_btn_text_color || '#ffffff',
    border: 'none',
    borderRadius: '6px',
    cursor: 'pointer',
    transition: 'background-color 0.3s',
    flex: '1',
    height: '32px'
  });

  const denyBtn = document.createElement('button');
  denyBtn.id = 'apluDenyBtn';
  denyBtn.textContent = CP.deny_btn_text || 'Later';
  Object.assign(denyBtn.style, {
    backgroundColor: CP.deny_btn_color || '#f1f1f1',
    color: CP.deny_btn_text_color || '#333',
    border: 'none',
    borderRadius: '6px',
    cursor: 'pointer',
    transition: 'background-color 0.3s',
    flex: '1',
    display: CP.enable_allow_only ? 'none' : 'inline-block',
    height: '32px'
  });

  // Powered by
  const powered = document.createElement('div');
  powered.innerHTML = `Powered by <a href="https://aplu.io" target="_blank" style="color:#f94d0f; text-decoration:none;">aplu.io</a>`;
  Object.assign(powered.style, { marginTop: '10px', color: '#999', fontSize: '12px' });

  // Actions wiring
  allowBtn.addEventListener('click', async () => {
    hideCustomPrompt();
    reappearShown = false;
    try {
      await subscribe();
      removePoweredByRibbon();
    } catch (e) {
      console.error('[Push] subscribe failed', e);
      showApluPushPoweredByRibbon();
    }
  });

  denyBtn.addEventListener('click', () => {
    hideCustomPrompt();
    startReappearTimer(); // ADD THIS LINE
    showApluPushPoweredByRibbon();
  });

  if (!CP.enable_allow_only) actions.appendChild(denyBtn);
  actions.appendChild(allowBtn);

  // Compose
  card.appendChild(headerRow);
  card.appendChild(actions);
  card.appendChild(powered);

  wrap.appendChild(card);
  document.body.appendChild(wrap);

  // Animate in
  animateIn(card);

  cpEl = wrap;
  return wrap;
}

function showCustomPrompt() {
  if (!cpEl) buildCustomPrompt();
  cpEl.hidden = false;
  cpEl.style.display = 'flex';         // ← ensure it's rendered
  cpEl.style.transition = 'opacity 200ms ease';
  requestAnimationFrame(() => { cpEl.style.opacity = '1'; });

  // optional: re-animate the card
  const card = cpEl.firstElementChild;
  if (card) {
    card.style.opacity = '0';
    card.style.transform = 'translateY(-10px)';
    card.style.transition = 'opacity 250ms ease, transform 250ms ease';
    requestAnimationFrame(() => {
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    });
  }
}

function hideCustomPrompt() {
  if (!cpEl) return;
  cpEl.style.transition = 'opacity 200ms ease';
  cpEl.style.opacity = '0';
  setTimeout(() => {
    cpEl.hidden = true;
    cpEl.style.display = 'none';       // ← collapse the wrapper
  }, 200);
}
// ===== END Custom Prompt add-on =====

  // 6) subscribe (or update) function
  async function subscribe() {
    // ask for notification permission
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') {
      console.warn('[Push] Notification permission not granted');
      showApluPushPoweredByRibbon(); // NEW
      return null;
    }

    // prepare getToken options
    const opts = { vapidKey: "{{ $config->vapid_public_key }}" };
    if (swReg) opts.serviceWorkerRegistration = swReg;

    // 6a) fetch a new FCM token
    const newToken = await messaging.getToken(opts);
    if (!newToken) throw new Error('No FCM token retrieved');

    // 6b) old token for update
    const oldToken = localStorage.getItem(TOKEN_LS_KEY) || null;

    // If it hasn’t changed, skip the API call entirely
    if (oldToken && oldToken === newToken) {
      console.log('[Push] Token unchanged, skipping subscribe');
      removePoweredByRibbon();
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
      p256dh,
      parent_origin: new URLSearchParams(window.location.search).get('parentOrigin') || location.hostname
    });

    // 6e) store locally so next time we update instead of insert
    localStorage.setItem(TOKEN_LS_KEY, newToken);
    removePoweredByRibbon();

    // ✅ ANALYTICS
    if (analytics) {
      analytics.setUserProperties({
        subscriber_id: localStorage.getItem('aplu_subscriber_id') || newToken
      });

      analytics.logEvent('push_subscribed', {
        domain: location.hostname
      });
    }

    console.log('[Push] Subscribed/Updated token', newToken);
    return newToken;
  }

  // 7) initial subscribe on page load
  onDomReady(() => {
    const delayMs = Math.max(0, Number(CP?.delay || 0) * 1000);

    if (isCpActive()) {
        showApluPushPoweredByRibbon();
        setTimeout(() => {
            showCustomPrompt();
        }, delayMs);
      return;
    }

    // Default behavior (unchanged)
    showApluPushPoweredByRibbon(); // show immediately if not subscribed/opted-in
    subscribe().catch(e => {
      console.error('[Push] subscribe error', e);
      showApluPushPoweredByRibbon(); // keep ribbon visible on error
    });
  });


  // 8) handle token refresh (v9 compat)
  if (typeof messaging.onTokenRefresh === 'function') {
    messaging.onTokenRefresh(async () => {
      try {
        await messaging.deleteToken();
        const token = await subscribe();
        if (token) {
          removePoweredByRibbon(); 
        } else {
          showApluPushPoweredByRibbon();
        }
        console.log('[Push] Token refreshed and updated');
      } catch (err) {
        console.error('[Push] Token refresh error', err);
        showApluPushPoweredByRibbon();
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

    try {
      const res = await fetch('{{route('api.analytics')}}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          analytics: [
            { message_id: messageId, event: 'received', timestamp: Date.now() }
          ]
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

        try {
          const res = await fetch('{{route('api.analytics')}}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              analytics: [
                { message_id: messageId, event: 'click', timestamp: Date.now() }
              ]
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