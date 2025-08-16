class ApluPush {
    constructor(heading, subheading, yesText, laterText, bellIcon, popupText, btnColor = '#f93a0b', showPoweredBy = true) {
        this.heading = heading;
        this.subheading = subheading;
        this.yesText = yesText;
        this.laterText = laterText;
        this.bellIcon = bellIcon;
        this.popupText = popupText;
        this.btnColor = btnColor;
        this.showPoweredBy = showPoweredBy;

        this.showApluPushPoweredByRibbon();
    }

    init() {
        const optInStatus = localStorage.getItem('apluPushOptIn');
        if (!optInStatus) {
            setTimeout(() => {
                this.createOptInForm();
            }, 2000);
        }else{
            this.removePoweredByRibbon();
        }
    }

    createOptInForm() {
        const optInContainer = document.createElement('div');
        optInContainer.id = 'apluPushOptIn';
        optInContainer.style.position = 'fixed';
        optInContainer.style.top = '2%';
        optInContainer.style.left = '50%';
        optInContainer.style.transform = 'translateX(-50%)';
        optInContainer.style.padding = '18px 20px';
        optInContainer.style.backgroundColor = '#ffffff';
        optInContainer.style.borderRadius = '12px';
        optInContainer.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.15)';
        optInContainer.style.zIndex = '9999';
        optInContainer.style.maxWidth = '450px';
        optInContainer.style.width = '90%';
        optInContainer.style.textAlign = 'center';
        optInContainer.style.fontFamily = 'Arial, sans-serif';
        optInContainer.style.border = '1px solid #e0e0e0';
        optInContainer.style.display = 'flex';
        optInContainer.style.flexDirection = 'column';
        optInContainer.style.justifyContent = 'space-between';
        optInContainer.style.alignItems = 'center';

        // Allow button style with dynamic background color
        const allowBtnStyle = `
            padding: 10px 20px;
            background-color: ${this.btnColor};
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            flex: 1;
        `;

        optInContainer.innerHTML = `
            <div style="display: flex; align-items: center; margin-bottom: 18px; width: 100%;">
                <img src="${this.bellIcon}" alt="Notification Icon" style="width: 50px; margin-right: 15px;" />
                <div style="flex: 1; text-align: left;">
                    <h2 style="margin: 0; color: #333; font-size: 22px;">${this.heading}</h2>
                    <p style="color: #666; margin-top: 5px; font-size: 14px; margin-bottom: 0px;">
                        ${this.subheading}
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 10px; width: 100%; justify-content: space-between;">
                <button id="allowButton" style="${allowBtnStyle}">
                    ${this.yesText}
                </button>
                <button id="denyButton" style="
                    padding: 10px 20px;
                    background-color: #f1f1f1;
                    color: #333;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: bold;
                    transition: background-color 0.3s;
                    flex: 1;
                ">
                    ${this.laterText}
                </button>
            </div>
            ${this.showPoweredBy ? `
                <div style="margin-top: 5px; color: #999; font-size: 12px;">
                    <p style="margin-bottom: 0px;">Powered by <a href="https://aplu.io" target="_blank" style="color: #f94d0f; text-decoration: none;">aplu.io</a></p>
                </div>` : ''}
        `;

        document.body.appendChild(optInContainer);

        document.getElementById('allowButton').addEventListener('click', () => {
            this.allowNotifications();
        });
        document.getElementById('denyButton').addEventListener('click', () => {
            this.denyNotifications();
        });
    }

    allowNotifications() {
        localStorage.setItem('apluPushOptIn', 'allowed');
        const parentOrigin = new URL(window.location.origin).hostname;
        const url = `http://localhost:8000/api/permission.html?parentOrigin=${encodeURIComponent(parentOrigin)}&popupText=${encodeURIComponent(this.popupText)}`;
        window.open(url, "SubscriptionWindow", `width=600,height=400`);
        this.closeOptInForm();
        this.removePoweredByRibbon();
    }

    denyNotifications() {
        localStorage.setItem('apluPushOptIn', 'denied');
        this.closeOptInForm();
        this.removePoweredByRibbon();
    }

    closeOptInForm() {
        const optInContainer = document.getElementById('apluPushOptIn');
        if (optInContainer) {
            optInContainer.style.transition = 'opacity 0.3s ease';
            optInContainer.style.opacity = '0';
            setTimeout(() => {
                optInContainer.remove();
            }, 300);
        }
    }
    
    showApluPushPoweredByRibbon = () => {
        // Check if 'apluPushOptIn' is not set in localStorage
        const optInStatus = localStorage.getItem('apluPushOptIn');
        if (!optInStatus) {
            const ribbon = document.createElement('div');
            ribbon.id = 'apluPushPoweredBy';
            ribbon.classList.add('ribbon-pop');
            ribbon.innerHTML = '<a href="https://aplu.io" target="_blank" style="color:#fff !important;">Notifications Powered By <b>Aplu</b></a>';

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
    };

    removePoweredByRibbon() {
        const ribbon = document.querySelector('#apluPushPoweredBy');
        if (ribbon) {
            ribbon.remove();
        }
    }

}