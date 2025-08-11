class ApluPushAMP {
  constructor(popupText, showPoweredBy = true) {
    this.popupText = popupText;
    this.showPoweredBy = showPoweredBy;
  }

  init() {
    // AMP-specific initialization
    if (window.AMP) {
      this.setupAMPListeners();
    } else {
      // Fallback for non-AMP pages
      const ampButtons = document.querySelectorAll('.apluPushAmpBtn');
      ampButtons.forEach((ampButton) => {
        ampButton.addEventListener('click', () => {
          this.allowNotifications();
        });
      });
    }
  }

  setupAMPListeners() {
    // AMP-specific logic
    document.addEventListener('subscriptionWindowOpen', () => {
      this.allowNotifications();
    });
  }

  allowNotifications() {
    // In AMP, the lightbox is already handling the iframe display
    // So we just need to hide the button
    const ampButtons = document.querySelectorAll('.apluPushAmpBtn');
    ampButtons.forEach((ampButton) => {
      ampButton.style.display = 'none';
    });
    
    console.log("AMP subscription flow initiated");
  }
}

document.addEventListener('DOMContentLoaded', function() {
  let apluPush = new ApluPushAMP();
  apluPush.init();
});