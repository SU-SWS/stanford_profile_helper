
(($ ) => {
  Drupal.behaviors.newsList = {
    attach: function attach(context, settings) {
      // Remove the alt text on list image.
      $('.su-news-article img', context).attr('alt', '');
    },
  };
})(jQuery);
