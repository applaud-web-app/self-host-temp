<!DOCTYPE html>
<html ⚡>
<head>
    <meta charset="utf-8">
    <title>Permission Page</title>
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-iframe" src="https://cdn.ampproject.org/v0/amp-iframe-0.1.js"></script>
    <style amp-custom>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            background-color: #fafafa;
        }
        p {
            color: #666;
            font-size: 1.2em;
        }
        #instructionsApluPush {
            display: none;
            background-color: #ffeb3b;
            color: #000;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
    <script src='{{route("api.push.notify")}}'></script>
</head>
<body>
    <div>
        <p id="defaultApluPushText">Please click 'Allow' when asked about notifications to subscribe to updates.</p>
        <div id="instructionsApluPush">
            <amp-img src="{{asset('permission-dialog-block.jpg')}}" width="300" height="200" layout="responsive"></amp-img>
        </div>
    </div>
    <script>
        var isThisWindowPopup = window.opener && window.opener !== window || !window.menubar.visible || false;
    
        async function registerServiceWorker() {
            if ("serviceWorker" in navigator) {
                try {
                    const registration = await navigator.serviceWorker.register(location.origin + "/apluselfhost-messaging-sw.js", {
                        scope: location.origin
                    });
                    return registration;
                } catch (error) {
                    console.error(`Registration failed with ${error}`);
                    throw error;
                }
            }
        }
    
        async function requestPermission() {
            if (navigator.userAgent.match(/iPhone/i)) {
                return "not-granted";
            }
            const permission = await Notification.requestPermission();
            return permission;
        }
    
        (async function () {
            try {
                const sw = await registerServiceWorker();
                const permission = await requestPermission();
    
                if (permission === 'granted') {
                    const waitForToken = () => {
                        const checkToken = () => {
                            if (localStorage.getItem('push_token')) {
                                if (window.opener) {
                                    window.opener.postMessage({ type: 'hideButton' }, '*');
                                }
                                isThisWindowPopup ? window.close() : history.back();
                            } else {
                                setTimeout(checkToken, 500);
                            }
                        };
                        setTimeout(() => {
                            checkToken();
                            setTimeout(() => isThisWindowPopup ? window.close() : history.back(), 15000);
                        }, 1000);
                    };
                    waitForToken();
    
                    if (window.opener) {
                        window.opener.postMessage({ type: 'hideButton' }, '*');
                    }
                } else if (permission === 'denied') {
                    document.getElementById('instructionsApluPush').style.display = 'block';
                    document.getElementById('defaultApluPushText').style.display = 'none';
                } else {
                    window.close();
                }
            } catch (error) {
                console.error(`Error occurred: ${error}`);
                if (!isThisWindowPopup) {
                    history.back();
                } else {
                    window.close();
                }
            }
        })();
    </script>
</body>
</html>