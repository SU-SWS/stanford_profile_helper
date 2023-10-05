
(($, Drupal ) => {
  Drupal.behaviors.stanfordPerson = {
    attach: function attach(context, settings) {
      // Looking at the h3 in the grid and hiding duplicates
      var headingLabels = $('.stanford-people-grid--filters h3');
      headingLabels.each(function (i) {
        var headingLabelsCurrent = $(this).text();
        var prev = headingLabels.eq(i - 1).text();

        if (headingLabelsCurrent === prev && i > 0) {
          $(this).hide();
        }

      });
    },
  };
})(jQuery, Drupal);
