// Function to assign ad refresh rates according to the interval set in configurations.

(function ($, Drupal) {
  Drupal.behaviors.sgc_ad_refresh = {
    attach: function (context, settings) {
      if (
        // Only run this once.
        context === document
      ) {
        // Get an object of the ad refresh configuration from Drupal settings.
        try {
          const config = JSON.parse(drupalSettings.sgc_ad_refresh);
        } catch (err) {
          console.error('Error parsing SGC Ad Refresh settings:', err);
          return;
        }        
        // Get an array of the keys from the object, which are the names of the ad slots.
        const refreshAdSlots = Object.keys(config);

        refreshAdSlots.forEach((ad) => {
          // Check if the ad status is active, and if the ad is in googletag.slots
          if (config[ad].status == "1" && googletag.slots[ad]) {
            // Assign an setInterval function to each ad slot.
            setInterval(function () {
              // Check if the window is active and ad exists on page.
              if (!document.hidden) {
                // Set a refresh command on each ad slot located in googletag.slots
                googletag
                  .pubads()
                  .refresh([googletag.slots[ad]], { changeCorrelator: false });
              }
              // Get the assigned interval value from config and convert to seconds.
            }, config[ad].interval * 1000);
          }
        });
      }
    },
  };
})(Drupal);
