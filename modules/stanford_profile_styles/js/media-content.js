(function ($, Drupal, once) {
  'use strict';
  Drupal.behaviors.stanfordMediaContent = {
    attach: function (context, settings) {
      $(once('media-date', '.node-stanford-media-su-media-date .su-media-date, .node-stanford-media-su-media-duration', context)).wrapAll('<div class="date-duration-wrapper"></div>')
      $(once('media-date', '.node-stanford-media-su-media-season, .node-stanford-media-su-media-episode', context)).wrapAll('<div class="season-episode-wrapper"></div>')
    },
  };

})(jQuery, Drupal, once);
