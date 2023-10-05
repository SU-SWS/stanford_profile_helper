

(($, Drupal ) => {
  Drupal.behaviors.stanfordPublication = {
    attach: function attach(context, settings) {
      const menuBlock = $('.stanford-publication-topics', context);

      // Only open and close filter menu when on responsive sizes.
      const mediaQuery = window.matchMedia('(max-width: 991px)');

      function handleMobileSize(e) {
        // Check if the media query is true
        if (e.matches) {
          menuBlock.find('h2').unwrap('button')
            .wrap($('<button/>'));

          const button = menuBlock.find('button');
          button.attr('aria-expanded', false);

          button.click(() => {
            menuBlock.toggleClass('show');

            button.attr('aria-expanded', false);
            if (menuBlock.hasClass('show')) {
              button.attr('aria-expanded', true);
            }
          });
        }
        else {
          menuBlock.find('h2').unwrap('button').removeClass('show');
        }
      }

      if(menuBlock.length > 0) {
        // Listen and change when window does.
        mediaQuery.addListener(handleMobileSize);
        handleMobileSize(mediaQuery);
      }
    },
  };
})(jQuery, Drupal);
