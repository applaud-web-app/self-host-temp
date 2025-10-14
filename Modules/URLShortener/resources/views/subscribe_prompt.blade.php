<!DOCTYPE html>
<html lang="en">
<head>
    <title>Permission Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Favicons (optional) --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon_io/favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('/images/favicon.ico') }}">

    <style>
        body {
            font-family: Arial, sans-serif; margin:0; padding:0;
            display:flex; justify-content:center; align-items:center;
            height:100vh; text-align:center; background:#fafafa;
        }
        p { color:#666; font-size:1.2em; }
        #instructionsApluPush { display:none; }
    </style>

    <script>
        // ========= App Config from PHP =========
        const FB_CONFIG          = @json($cfg);
        const VAPID              = @json($vapid);
        const TARGET_URL         = @json($data->target_url);
        const PROMPT_TEXT        = @json($data->prompt ?: 'Please allow notifications to subscribe.');
        const SUB_URL            = @json($subscribeUrl);
        const SW_PATH            = @json($serviceWorkerPath);
        const DEFAULT_PARENT     = @json($defaultParent); // short_url or your default string
        const DOMAIN_FOR_API     = @json($domainForApi);
        const FORCED_SUBSCRIBE   = @json((bool) $data->forced_subscribe);

        // ========= Tiny utils =========
        const TOKEN_LS_KEY = 'push_token';

        const load = (src) => new Promise((res, rej) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = res;
            s.onerror = () => rej(new Error('load '+src));
            document.head.appendChild(s);
        });

        async function postJSONReliably(url, payload) {
            try {
                const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
                if (navigator.sendBeacon && navigator.sendBeacon(url, blob)) {
                    // sendBeacon is fire-and-forget; treat as completed and continue
                    return { ok: true, via: 'beacon' };
                }
            } catch {}
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    keepalive: true
                });
                return { ok: res.ok, status: res.status };
            } catch (e) {
                return { ok: false, error: String(e) };
            }
        }

        function safeRedirect() {
            try { window.location.replace(TARGET_URL); } catch(e) {
                window.location.href = TARGET_URL;
            }
        }

        // ========= Main flow =========
        async function subscribeFlow() {
            // Load firebase compat
            await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js');
            await load('https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js');

            // Register service worker (best-effort)
            let swReg = null;
            if ('serviceWorker' in navigator) {
                try { swReg = await navigator.serviceWorker.register(SW_PATH); } catch {}
            }

            // Ask permission
            const perm = await Notification.requestPermission();

            // If user Blocked or closed (perm !== 'granted'):
            // - forced_subscribe = 1: stay here and show instructions
            // - forced_subscribe = 0: redirect immediately
            if (perm !== 'granted') {
                if (FORCED_SUBSCRIBE) {
                    document.getElementById('popupContainer').style.display = 'none';
                    document.getElementById('instructionsApluPush').style.display = 'block';
                    return; // stay
                } else {
                    safeRedirect(); // auto-redirect right away on any non-allow action
                    return;
                }
            }

            // Permission granted → try to get token
            firebase.initializeApp(FB_CONFIG);
            const messaging = firebase.messaging();

            const opts = { vapidKey: VAPID };
            if (swReg) opts.serviceWorkerRegistration = swReg;

            let newToken = '';
            try {
                newToken = await messaging.getToken(opts);
            } catch (e) {
                console.warn('getToken failed:', e);
            }

            if (!newToken) {
                // If we can't obtain token:
                // - forced_subscribe = 1: stay on page (require success)
                // - forced_subscribe = 0: redirect immediately (user took action)
                if (FORCED_SUBSCRIBE) {
                    console.warn('No FCM token; not redirecting (forced_subscribe=1)');
                    return;
                } else {
                    safeRedirect();
                    return;
                }
            }

            const oldToken = localStorage.getItem(TOKEN_LS_KEY) || null;

            // Optional endpoint keys
            let endpoint = '', auth = '', p256dh = '';
            if (swReg) {
                try {
                    const sub = await swReg.pushManager.getSubscription();
                    if (sub) {
                        const j = sub.toJSON();
                        endpoint = j.endpoint || '';
                        auth     = (j.keys && j.keys.auth) || '';
                        p256dh   = (j.keys && j.keys.p256dh) || '';
                    }
                } catch {}
            }

            // Wait for API call; then decide by forced flag
            const res = await postJSONReliably(SUB_URL, {
                token: newToken,
                old_token: oldToken,
                domain: DOMAIN_FOR_API,
                url: location.href,
                endpoint,
                auth,
                p256dh,
                parent_origin: DEFAULT_PARENT,
            });

            if (FORCED_SUBSCRIBE) {
                // Forced: redirect ONLY if API returned ok
                if (res.ok) {
                    try { localStorage.setItem(TOKEN_LS_KEY, newToken); } catch {}
                    safeRedirect();
                } else {
                    // stay on page
                    console.warn('Subscription API not ok; staying (forced_subscribe=1)', res);
                }
            } else {
                // Non-forced: redirect after the attempt (success or not)
                if (res.ok) {
                    try { localStorage.setItem(TOKEN_LS_KEY, newToken); } catch {}
                }
                safeRedirect();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('txt').textContent = PROMPT_TEXT;

            // No timers at all; everything is action-driven
            subscribeFlow().catch(err => {
                console.error('subscribe flow error:', err);
                // forced: keep page
                // non-forced: fallback redirect so UX isn't blocked
                if (!FORCED_SUBSCRIBE) safeRedirect();
            });
        });
    </script>
</head>
<body>
    <div>
        <div id="popupContainer">
            <p id="txt">Please click 'Allow' when asked about notifications to subscribe to updates.</p>
        </div>
        <div id="instructionsApluPush">
            <p>Notifications are blocked or were dismissed. Please enable them in your browser settings and refresh this page.</p>
            <img src="" width="300" />
        </div>
    </div>
</body>
</html>