class ApluPushAMP {
    constructor(popupText, showPoweredBy = true) {
        this.popupText = popupText;
        this.showPoweredBy = showPoweredBy;
    }

    init() {
        // Find the AMP button and attach click event
        const ampButtons = document.querySelectorAll('.apluPushAmpBtn');
        ampButtons.forEach(function(ampButton) {
            ampButton.addEventListener('click', () => {
                this.allowNotifications();
            });
        });
    }

    allowNotifications() {
        // Open the subscription window directly
        const parentOrigin = new URL(window.location.origin).hostname;
        const url = `https://${parentOrigin}/permission.html?parentOrigin=${encodeURIComponent(parentOrigin)}&popupText=${encodeURIComponent(this.popupText)}`;
        
        // Open the subscription window
        window.open(url, "SubscriptionWindow", `width=500,height=500`);
        
        // Hide the AMP button
        const ampButtons = document.querySelectorAll('.apluPushAmpBtn');
        ampButtons.forEach(function(ampButton) {
            ampButton.style.display = 'none';
        });

        
        console.log("Opening subscription window for AMP");
    }
}

document.addEventListener('DOMContentLoaded', function() {
    let apluPush = new ApluPushAMP();
    apluPush.init();
});