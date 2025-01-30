(($, Drupal, once) => {
  Drupal.behaviors.jumpstartUiAccordion = {
    attach: function attach(context, settings) {
      $(once('accordion', '.jumpstart-accordion')).each(function () {
        const $button = $('button', this);
        const $contents = $('.accordion__contents', this);
        const uniqueId = $button.text()
          .toLowerCase()
          .trim()
          .replaceAll(/[^a-z0-9]/g, '-')

        $contents.addClass('hidden')
          .attr('id', `${uniqueId}-contents`)
          .attr('aria-labelledby', `${uniqueId}-button`)
          .attr('role', 'region');

        $button.attr('id', `${uniqueId}-button`)
          .attr('aria-controls', `${uniqueId}-contents`)
          .attr('aria-expanded', false)
          .click(function () {
            $contents.toggleClass('hidden');
            $button.attr('aria-expanded', $button.attr('aria-expanded') === 'false')
          });
      });
    },
  };
})(jQuery, Drupal, once);
