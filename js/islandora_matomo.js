/**
 * @file - islandora_matomo.js
 */

(function ($) {
Drupal.behaviors.islandora_matomo = {
  attach: function (context, settings) {
    $('audio').each( function() {
        console.log("hello world of audio");
        $(this, context).once('islandora_matomo').on("play", function() {
          console.log("only fires once per play");
        });
    });
    $('video').each( function() {
        console.log("hello world of video");
        console.log('video found on page');
        $(this, context).once('islandora_matomo').on("play", function() {
          console.log("only fires once per play");
          console.log('The Boolean paused property is now false. Either the ' +
            'play() method was called or the autoplay attribute was toggled.');
        });
    });
  }
};

})(jQuery, Drupal);
