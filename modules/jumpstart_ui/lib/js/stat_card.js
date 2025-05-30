

(($, Drupal, once ) => {
  Drupal.behaviors.jumpstartUiStatCard = {
    attach: function attach(context, settings) {
      const isReduced = window.matchMedia(`(prefers-reduced-motion: reduce)`) === true || window.matchMedia(`(prefers-reduced-motion: reduce)`).matches === true;
      if (!!isReduced) {
        // User prefers reduced motion. Don't add animation.
        return;
      }

      $(once('stat-card-counter', '.stat-card-stat', context)).each(function () {
        const stat = $(this);
        const statText = stat.text().replaceAll(/[\n ]+/g, '');

        // The stat starts with numbers.
        if (/^[0-9+]/.test(statText)) {
          const matches = statText.match(/^([0-9.]+)(.*)/);
          const xpath = `//div[text()='${statText}']`;
          const matchingElement = document.evaluate(xpath, stat[0], null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
          $(matchingElement).html(`<count-up data-duration="2" data-suffix="${matches[2]}">${matches[1]}</count-up>`);
        }

        // The stat starts with dollar sign and then numbers.
        if (/^\$[0-9+]/.test(statText)) {
          const matches = statText.match(/^\$([0-9.]+)(.*)/);
          const xpath = `//div[text()='${statText}']`;
          const matchingElement = document.evaluate(xpath, stat[0], null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
          $(matchingElement).html(`<count-up data-duration="2" data-prefix="$" data-suffix="${matches[2]}">${matches[1]}</count-up>`);
        }
      });
    },
  };
})(jQuery, Drupal, once);
