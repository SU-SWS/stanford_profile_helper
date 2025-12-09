(($) => {
  document.addEventListener('DOMContentLoaded', () => {
    anchors.add('#page-content h2:not(.visually-hidden)');

    const $headings = $('#page-content h2:not(.visually-hidden)[id]')
      .filter((i, item) => $(item).closest('.node-stanford-page-su-page-banner').length === 0);

    // If no links exist, don't add the markup.
    if ($headings.length === 0) {
      return;
    }

    const $list = $('<ul>');

    $headings.map((i, heading) => {
      const $heading = $(heading);
      const id = $heading.attr('id');

      $list.append($('<li>').append($('<a>').attr('href', `#${id}`).text($heading.text().trim())));
    });

    const $container = $('.anchor-link-nav');

    $container.append($('<nav>').attr('aria-label', 'On this page').append($list));

    function manageOverflow() {
      $('button', $container).remove();
      $('.overflow-items', $container).remove();

      const $expandButton = $('<button>').text('Expand')
        .attr('aria-expanded', 'false')
        .attr('aria-controls', 'overflow-container');
      const $overflowItemsContainer = $('<ul>').addClass('overflow-items hidden').attr('id', 'overflow-container');

      // Toggle overflow items visibility on expand button click
      $expandButton.on('click', () => {
        $expandButton.attr('aria-expanded', (i, currentValue) => currentValue === 'true' ? 'false' : 'true');
        $overflowItemsContainer.toggleClass('hidden');
      });

      // Clear previous overflow items
      $overflowItemsContainer.empty();
      $overflowItemsContainer.removeClass('visible');
      $expandButton.removeClass('visible');

      const $listItems = $('li', $list);
      // Reset all list items to visible
      $listItems.removeClass('hidden');

      // Check if the list overflows its container
      if ($list[0].scrollWidth > $container[0].clientWidth - 150) {

        $listItems.get().reverse().map(item => {
          const $item = $(item);

          if ($list[0].scrollWidth > $container[0].clientWidth - 150) {
            $overflowItemsContainer.prepend($item.clone());
            $item.addClass('hidden');
          }
        });
        $container.append($expandButton).append($overflowItemsContainer);
      }
    }

    if ($container.hasClass('orientation-horizontal')) {
      // Initial check and on window resize
      manageOverflow();
      window.addEventListener('resize', manageOverflow);
    }
  });
})(jQuery);
