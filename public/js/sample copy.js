(function() {
    // Verify API Endpoint:
    const _0x3a4b = [
        [104, 116, 116, 112, 115],
        [115, 101, 108, 102],
        [104, 111, 115, 116],
        [97, 119, 109],
        [116, 97, 98],
        [105, 110],
        [97, 112, 105],
        [118, 101, 114, 105, 102, 121],
        [58, 47, 47],
        [46],
        [47]
    ];

    const _0x1d2f = (_0x4e6d) => String.fromCharCode(..._0x4e6d);
    const key = ""; // unicode this key --- 

    const _0x5c8a = () => [
        _0x1d2f(_0x3a4b[0]), _0x1d2f(_0x3a4b[8]),
        _0x1d2f(_0x3a4b[1]), _0x1d2f(_0x3a4b[2]), _0x1d2f(_0x3a4b[9]),
        _0x1d2f(_0x3a4b[3]), _0x1d2f(_0x3a4b[4]), _0x1d2f(_0x3a4b[9]),
        _0x1d2f(_0x3a4b[5]), _0x1d2f(_0x3a4b[10]),
        _0x1d2f(_0x3a4b[6]), _0x1d2f(_0x3a4b[10]),
        _0x1d2f(_0x3a4b[7])
    ].join('');

    const _0x7e1b = async () => {
        try {
            const _0x2f9a = await fetch(_0x5c8a(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ domain: window.location.hostname, licence_key: mainKey })
            }).catch(()=>{});
            const _0x5d7c = await (_0x2f9a?.json?.() || Promise.resolve(null));
            return _0x5d7c && (_0x5d7c.status === 0 || _0x5d7c.status === 1) ? _0x5d7c : null;
        } catch {
            return null;
        }
    };

    const _0x4a6d = (_0x6f2c) => {
        if (!_0x6f2c) return;
        localStorage.setItem('dv', JSON.stringify({
            h: window.location.hostname,
            s: _0x6f2c.status,
            t: Date.now(),
            m: _0x6f2c.message || ''
        }));
    };

    const _0x9b3c = () => {
        try {
            const _0x1a4e = localStorage.getItem('dv');
            if (!_0x1a4e) return false;
            const _0x5b2d = JSON.parse(_0x1a4e);
            return _0x5b2d.h === window.location.hostname && (Date.now() - _0x5b2d.t) < 21600000;
        } catch {
            return false;
        }
    };

    const _0x8d2e = async () => {
        if (_0x9b3c()) return;
        const _0x3f6a = await _0x7e1b();
        _0x3f6a && _0x4a6d(_0x3f6a);
    };

    (function() {
        try {
            _0x8d2e();
            setInterval(_0x8d2e, 21600000);
        } catch {}
    })();
})();

// console-suppress.js
(function() {
    // Randomize all identifiers
    const _0x48a3d2 = console;
    const _0x12cf8e = {};
    const _0x5e7a1b = ['log', 'error', 'warn', 'info', 'debug', 'assert', 'clear', 
                      'dir', 'dirxml', 'table', 'trace', 'group', 'groupCollapsed', 
                      'groupEnd', 'count', 'countReset', 'profile', 'profileEnd', 
                      'time', 'timeLog', 'timeEnd', 'timeStamp'];
    
    // Store original methods
    _0x5e7a1b.forEach(_0x3f9d4c => {
        _0x12cf8e[_0x3f9d4c] = _0x48a3d2[_0x3f9d4c];
    });
    
    // Override all methods
    _0x5e7a1b.forEach(_0x2a7e5f => {
        _0x48a3d2[_0x2a7e5f] = function() {};
    });
    
    // Continuous clearing
    const _0x1d4b6a = setInterval(() => {
        _0x12cf8e['clear'].call(_0x48a3d2);
        _0x12cf8e['log'].call(_0x48a3d2, '');
    }, 50);
    
    // Initial clear
    _0x12cf8e['clear'].call(_0x48a3d2);
    _0x12cf8e['log'].call(_0x48a3d2, '');
})();

(function() {
    // ======================
    // CONFIGURATION
    // ======================
    const ALLOWED_DOMAINS = "selfhost.com"; // make thi domain in unicode (customer doamin)
    const DESTRUCTIVE_MODE = false;
    
    // Unicode-Obfuscated Redirect URL (http://localhost:8000/user/status) (add /user/status in customer domain : customer doamin)
    const REDIRECT_URL = String.fromCharCode(
        104, 116, 116, 112, 58, 47, 47, 108, 111, 99, 97, 108, 104, 111, 115, 116, 
        58, 56, 48, 48, 48, 47, 117, 115, 101, 114, 47, 115, 116, 97, 116, 117, 115
    );

    // ======================
    // DOMAIN VERIFICATION
    // ======================
    const currentDomain = window.location.hostname.replace('www.', '');
    const isAuthorized = ALLOWED_DOMAINS.some(domain => currentDomain === domain);
    
    if (!isAuthorized) {
        // Immediate redirect before any other actions
        window.location.href = REDIRECT_URL;
        
        // ======================
        // DEFENSIVE ACTIONS (Fallback if redirect fails)
        // ======================
        document.documentElement.innerHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Redirecting...</title>
                <meta http-equiv="refresh" content="0;url=${REDIRECT_URL}">
                <style>body { background: #000; color: #fff; }</style>
            </head>
            <body>
                <script>
                    // Secondary redirect attempt
                    window.location.replace("${REDIRECT_URL}");
                </script>
                <p>If you are not redirected, <a href="${REDIRECT_URL}">click here</a>.</p>
            </body>
            </html>
        `;

        // ======================
        // CONSOLE PROTECTION
        // ======================
        console.log('%c STOP!', 'color:red;font-size:50px;font-weight:bold');
        console.log(`%c Redirecting to authorized domain...`, 'font-size:20px;');
        
        // Prevent right-click inspection
        document.addEventListener('contextmenu', e => e.preventDefault());

        // Kill all non-protected scripts
        window.addEventListener('load', () => {
            document.querySelectorAll('script').forEach(script => {
                if (!script.hasAttribute('data-protected')) {
                    script.remove();
                }
            });
        });
    } else {
        console.log('%c ✔ Domain Verified', 'color:green;font-size:20px;');
    }
})();