(($) => {
  document.addEventListener('DOMContentLoaded', function (event) {
    anchors.add('#page-content h2:not(.visually-hidden)');
    let navMarkup = '';

    const $headings = $('#page-content h2:not(.visually-hidden)[id]')
      .filter((i, item) => $(item).closest('.node-stanford-page-su-page-banner').length === 0);

    // If no links exist, don't add the markup.
    if ($headings.length === 0) {
      return;
    }

    $headings.map((i, heading) => {
      const $heading = $(heading);
      const id = $heading.attr('id');
      navMarkup += `<li><a href="#${id}">${$heading.text().trim()}</a></li>`;
    });

    $('.anchor-link-nav').html(`<nav aria-label="On this page"><ul>${navMarkup}</ul></nav>`);

  });
})(jQuery);
