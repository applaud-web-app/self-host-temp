// Script that can be included as a widget (like the aplupush example)

class ApluPush {
    constructor(heading, subheading, yesText, laterText, bellIcon, popupText, showPoweredBy = true) {
        // The constructor now accepts an additional parameter 'showPoweredBy'
        this.heading = heading;
        this.subheading = subheading;
        this.yesText = yesText;
        this.laterText = laterText;
        this.bellIcon = bellIcon;
        this.popupText = popupText;
        this.showPoweredBy = showPoweredBy; // Defaults to true if not provided
    }

    init() {
        // Check if the user has interacted with the opt-in
        const optInStatus = localStorage.getItem('apluPushOptIn');
        if (!optInStatus) {
            // Show opt-in prompt after 2 seconds
            setTimeout(() => {
                this.createOptInForm();
            }, 2000);
        }
    }

    // Create the opt-in form
    createOptInForm() {
        // Create opt-in container
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

        // Add content to the container
        optInContainer.innerHTML = `
            <div style="display: flex; align-items: center; margin-bottom: 18px;width:100%;">
                <img src="${this.bellIcon}" alt="Notification Icon" style="width: 50px; margin-right: 15px;" />
                <div style="flex: 1; text-align: left;">
                    <h2 style="margin: 0; color: #333; font-size: 22px;">${this.heading}</h2>
                    <p style="color: #666;margin-top: 5px;font-size: 14px;margin-bottom: 0px;">
                        ${this.subheading}
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 10px; width: 100%; justify-content: space-between;">
                <button id="allowButton" style="padding: 10px 20px; background-color: rgb(249 58 11); 
                    color: white; border: none; border-radius: 6px; cursor: pointer; 
                    font-weight: bold; transition: background-color 0.3s; flex: 1;">
                    ${this.yesText}
                </button>
                <button id="denyButton" style="padding: 10px 20px; background-color: #f1f1f1; 
                    color: #333; border: none; border-radius: 6px; cursor: pointer; 
                    font-weight: bold; transition: background-color 0.3s; flex: 1;">
                    ${this.laterText}
                </button>
            </div>
            ${this.showPoweredBy ? `
                <div style="margin-top: 5px; color: #999; font-size: 12px;">
                    <p style="margin-bottom: 0px;">Powered by <a href="https://aplu.io" target="_blank" style="color: #f94d0f; text-decoration: none;">aplu.io</a></p>
                </div>` : ''}
        `;

        // Append the opt-in form to the body
        document.body.appendChild(optInContainer);

        // Event listeners for buttons
        document.getElementById('allowButton').addEventListener('click', () => {
            this.allowNotifications();
        });
        document.getElementById('denyButton').addEventListener('click', () => {
            this.denyNotifications();
        });
    }

    // Handle "Allow" button click
    allowNotifications() {
        // Save the user's choice to local storage
        localStorage.setItem('apluPushOptIn', 'allowed');

        // Open the URL for subscription or handle logic
        const parentOrigin = new URL(window.location.origin).hostname;
        const url = `https://host.awmtab.in/permission.html?parentOrigin=${encodeURIComponent(parentOrigin)}&popupText=${encodeURIComponent(this.popupText)}`;
        window.open(url, "SubscriptionWindow", `width=600,height=400`);

        // Close the opt-in form
        this.closeOptInForm();
    }

    // Handle "Deny" button click
    denyNotifications() {
        // Save the user's choice to local storage
        localStorage.setItem('apluPushOptIn', 'denied');

        // Close the opt-in form
        this.closeOptInForm();
        console.log("User denied notifications.");
    }

    // Function to close the opt-in form
    closeOptInForm() {
        var optInContainer = document.getElementById('apluPushOptIn');
        if (optInContainer) {
            // Add fade-out animation
            optInContainer.style.transition = 'opacity 0.3s ease';
            optInContainer.style.opacity = '0';
            setTimeout(function() {
                optInContainer.remove();
            }, 300);
        }
    }
}