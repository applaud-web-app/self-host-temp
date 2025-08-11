<!DOCTYPE html>
<html>
<head>
    <title>Permission Page</title>
    <style>
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
            <img src="{{asset('permission-dialog-block.jpg')}}" width="300px" />
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
                    // If permission granted, wait for 2-3 seconds and then close the window or return to the previous window
                    const waitForToken = () => {
                        const checkToken = () => {
                            if (localStorage.getItem('push_token')) {
                                if (window.opener) {
                                    var elements = window.opener.document.querySelectorAll('.apluPushAmpBtn');
                                    elements.forEach(function(element) {
                                        element.style.display = 'none';
                                    });

                                }
                                isThisWindowPopup ? window.close() : history.back();
                            } else {
                                setTimeout(checkToken, 500);
                            }
                        };
                        // Start checking after 1 second, timeout after 15 seconds
                        setTimeout(() => {
                            checkToken();
                            setTimeout(() => isThisWindowPopup ? window.close() : history.back(), 15000);
                        }, 1000);
                    };
                    waitForToken();
    
                    // You can also communicate with the parent page (if needed) to hide the button
                    if (window.opener) {
                        var elements = window.opener.document.querySelectorAll('.apluPushAmpBtn');
                        for (var i = 0; i < elements.length; i++) {
                            elements[i].style.display = 'none';
                        }
                    }
                } else if (permission === 'denied') {
                    // If permission denied, show instructionsApluPush on how to unblock notifications
                    document.getElementById('instructionsApluPush').style.display = 'block';
                    document.getElementById('defaultApluPushText').style.display = 'none';
                } else {
                    // Handle if the user cancels the request
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