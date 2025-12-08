((Drupal, drupalSettings) => {
  document.addEventListener('DOMContentLoaded', function (event) {
    anchors.add('#page-content h2:not(.visually-hidden)');
    let navMarkup = '';
    const headings = document.querySelectorAll('#page-content h2:not(.visually-hidden)');
    for (let i = 0; i < headings.length; i++) {
      // Exclude links in the page banner since that is above the nav.
      if (headings[i].closest('.node-stanford-page-su-page-banner')) {
        continue;
      }
      const id = headings[i].getAttribute('id');
      if (!id) {
        continue;
      }
      navMarkup += `<li><a href="#${id}">${headings[i].textContent}</a></li>`;
    }

    document.getElementsByClassName('anchor-link-nav')[0].innerHTML = `<nav aria-label="On this page"><ul>${navMarkup}</ul></nav>`;
  });
})(Drupal, drupalSettings);
