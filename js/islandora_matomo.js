/**
 * @file - islandora_matomo.js
 */

(function ($) {
Drupal.behaviors.islandora_matomo = {
  attach: function (context, settings) {
    // Since any of these would require the _paq library is defined -- and that
    // depends on whether or not the user is configured to track Matomo events
    // i.e. "do not add tracking for Admin role", this logic can wrap any
    // further logic.
    if (typeof _paq !== 'undefined') {
      var pathname = window.location.pathname;
      console.log("path = " + pathname);
      pathname = pathname.substring(1, pathname.length);
      console.log("path = " + pathname);
      $('audio').each( function() {
          $(this, context).once('islandora_matomo').on("play", function() {
            // Track the event with Matomo.
            _paq.push(['trackEvent', 'MediaEvents', 'Play audio', pathname]);
            console.log("only fires once per play");
          });
      });
      $('video').each( function() {
          $(this, context).once('islandora_matomo').on("play", function() {
            // Track the event with Matomo.
            _paq.push(['trackEvent', 'MediaEvents', 'Play video', pathname]);
            console.log("only fires once per play");
          });
      });
    }
  }
};

})(jQuery, Drupal);
