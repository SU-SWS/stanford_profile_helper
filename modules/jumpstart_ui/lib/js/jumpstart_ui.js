

(($, Drupal ) => {
  Drupal.behaviors.jumpstartUi = {
    attach: function attach(context, settings) {
      $('figure .media-entity-wrapper.video', context).each((i, video) => {
        $(video).closest('figure').css('width', '100%');
      });
    },
  };
})(jQuery, Drupal);
