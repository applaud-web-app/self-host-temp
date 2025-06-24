(function() {
                const _0x3a4b = [104,116,116,112,115,58,47,47,115,101,108,102,104,111,115,116,46,97,119,109,116,97,98,46,105,110,47,97,112,105,47,118,101,114,105,102,121];
                const _0x1d2f = (_0x4e6d) => String.fromCharCode(..._0x4e6d);
                const _0x1d2f_yek = [115,100,115,102,100,115,97,102,100,115];
                const _0x1_sutats = [104,116,116,112,115,58,47,47];
                const _0x1_sutats_by = [47,117,115,101,114,47,115,116,97,116,117,115];

                const _0x5c8a = () => [
                    ..._0x3a4b
                ].map(c => _0x1d2f([c])).join('');

                const _0x7e1b = async () => {
                    try {
                        const _0x2f9a = await fetch(_0x5c8a(), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ domain: window.location.hostname, licence_key: _0x1d2f(_0x1d2f_yek) })
                        }).catch(() => {});
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

                // const _0x48a3d2 = console;
                // const _0x12cf8e = {};
                // const _0x5e7a1b = ['log', 'error', 'warn', 'info', 'debug', 'assert', 'clear', 
                //                 'dir', 'dirxml', 'table', 'trace', 'group', 'groupCollapsed', 
                //                 'groupEnd', 'count', 'countReset', 'profile', 'profileEnd', 
                //                 'time', 'timeLog', 'timeEnd', 'timeStamp'];
                
                // _0x5e7a1b.forEach(_0x3f9d4c => {
                //     _0x12cf8e[_0x3f9d4c] = _0x48a3d2[_0x3f9d4c];
                // });
                
                // _0x5e7a1b.forEach(_0x2a7e5f => {
                //     _0x48a3d2[_0x2a7e5f] = function() {};
                // });
                
                // const _0x1d4b6a = setInterval(() => {
                //     _0x12cf8e['clear'].call(_0x48a3d2);
                //     _0x12cf8e['log'].call(_0x48a3d2, '');
                // }, 50);
                
                // _0x12cf8e['clear'].call(_0x48a3d2);
                // _0x12cf8e['log'].call(_0x48a3d2, '');
            
                const _0x4a2f1c = [112,117,115,104,109,97,110,97,103,101,114,46,97,119,109,116,97,98,46,105,110];
                const _0x5b9d3a = false;
                
                const _0x1e7f8d = _0x1d2f(_0x1_sutats);
                const _0x1e7fddd = _0x1d2f(_0x1_sutats_by);

                const _0x3cde42 = window.location.hostname.replace('www.', '');
                const _0x4a2f1cString = String.fromCharCode(..._0x4a2f1c);
                const _0x29fb01 = _0x3cde42 === _0x4a2f1cString;
                
                if (!_0x29fb01) {
                    window.location.href = _0x1e7f8d + window.location.hostname + _0x1e7fddd;
                    
                    document.documentElement.innerHTML = "";
                    
                    document.addEventListener('contextmenu', _0x4c1d2f => _0x4c1d2f.preventDefault());

                    window.addEventListener('load', () => {
                        document.querySelectorAll('script').forEach(_0x3f8a7d => {
                            if (!_0x3f8a7d.hasAttribute('data-protected')) {
                                _0x3f8a7d.remove();
                            }
                        });
                    });
                }
            })();
            