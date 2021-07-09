/**
 * @file - islandora_matomo.js
 */

(function ($) {
Drupal.behaviors.islandora_matomo = {
  attach: function (context, settings) {
    
    var video = document.getElementsByTagName('video')[0];
    if (video) {        
      video.addEventListener('play', (event) => {
        console.log('video found on page');
        console.log('The Boolean paused property is now false. Either the ' +
        'play() method was called or the autoplay attribute was toggled.');
      }, {once: true});
    }
  }
};

})(jQuery, Drupal);
