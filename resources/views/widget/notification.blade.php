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
    <script src='{{route('api.push.notify')}}'></script>
</head>

<body>
    <div id="popupContainer">
        <p></p>
    </div>
    <div id="instructionsApluPush">
        <img src="{{ asset('images/block.png') }}" width="300" />
    </div>
    <script>
        var text = new URL(location.href).searchParams.get('popupText')
        document.querySelector('p').innerHTML = text;
        var isThisWindowPopup = window.opener && window.opener !== window || !window.menubar.visible || false;

        async function registerServiceWorker() {
            if ("serviceWorker" in navigator) {
                try {
                    const registration = await navigator.serviceWorker.register(location.origin + "/apluselfhost-messaging-sw.js", {
                        scope: location.origin
                    });
                    if (registration.installing) {
                        console.log("Service worker installing");
                    } else if (registration.waiting) {
                        console.log("Service worker installed");
                    } else if (registration.active) {
                        console.log("Service worker active");
                    }
                    return registration;
                } catch (error) {
                    console.error(`Registration failed with ${error}`);
                    throw error;
                }
            }
        }

        async function waitForXSeconds(seconds) {
            return new Promise((resolve) => {
                setTimeout(() => {
                    resolve();
                }, seconds * 1000);
            }).catch(() => {
                console.log("Error in waitForXSeconds");
            });
        }

        async function requestPermission() {
            return new Promise(async (resolve, reject) => {
                setTimeout(() => {
                    resolve("not-granted")
                }, 15000)

                if (navigator.userAgent.match(/iPhone/i)) {
                    resolve("not-granted")
                }

                resolve(await Notification.requestPermission());
            })
        }

        (async function () {
            try {
                const sw = await registerServiceWorker();
                const permission = await requestPermission();
                if (permission !== 'granted') {
                    // console.log('Permission not granted. Please try again.');
                    document.getElementById("instructionsApluPush").style.display = "block";
                    document.getElementById("popupContainer").style.display = "none";
                    if (!isThisWindowPopup) {
                        // history.back();
                    } else {
                        // window.close();
                    }
                    return;
                }
                let swActive = false;
                while (!swActive) {
                    await waitForXSeconds(0.1);
                    swActive = sw.active;
                }
                sw.active.postMessage({
                    command: "amp-web-push-subscribe",
                    url: new URL(location.href).searchParams.get('parentOrigin')
                });
                if (!isThisWindowPopup) {
                    // history.back();
                } else {
                    // window.close();
                }
            } catch (error) {
                console.error(`Error occurred: ${error}`);
                if (!isThisWindowPopup) {
                    // history.back();
                } else {
                    // window.close();
                }
            }
        })();
    </script>
</body>

</html>