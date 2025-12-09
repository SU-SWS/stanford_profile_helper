(($) => {
  document.addEventListener('DOMContentLoaded', () => {
    anchors.add('#page-content h2:not(.visually-hidden):not(.no-anchor)');

    const $headings = $('#page-content h2:not(.visually-hidden):not(.no-anchor)[id]')
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
    const $nav = $('<nav>').attr('aria-label', 'On this page').append($list);

    $container.append($nav);
    const $expandButton = $('<button>').html('See More<i class="fa-solid fa-chevron-down"></i>')
      .attr('aria-expanded', 'false')
      .attr('aria-controls', 'overflow-container');

    const $overflowItemsContainer = $('<ul>').addClass('overflow-items hidden').attr('id', 'overflow-container');

    function manageOverflow() {
      $('button', $nav).remove();
      $('.overflow-items', $nav).remove();

      $overflowItemsContainer.empty()

      // Toggle overflow items visibility on expand button click
      $expandButton.on('click', () => {
        $expandButton.attr('aria-expanded', (i, currentValue) => currentValue === 'true' ? 'false' : 'true');
        $overflowItemsContainer.toggleClass('hidden');
      });

      const $listItems = $('li', $list);
      // Reset all list items to visible
      $listItems.removeClass('hidden');

      // Check if the list overflows its container
      if ($list[0].scrollWidth > $container[0].clientWidth - 150) {

        $listItems.get().reverse().map(item => {
          const $item = $(item);

          if ($list[0].scrollWidth > $container[0].clientWidth - 150) {
            $overflowItemsContainer.prepend($item.clone().addClass('overflow-item'));
            $item.addClass('hidden');
          }
        });

        $nav.append($expandButton).append($overflowItemsContainer);
      }
    }

    if ($container.hasClass('orientation-horizontal')) {
      // Initial check and on window resize
      manageOverflow();
      window.addEventListener('resize', manageOverflow);

      window.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
          if ($expandButton.attr('aria-expanded') === 'true') {
            $expandButton.click();
            $expandButton.focus();
          }
        }
      });

      // Event listener for clicks/focus anywhere on the document
      document.addEventListener('click', (event) => {
        // Check if the clicked element is outside the collapsible area or the toggle button itself
        const isOutsideClick = !$overflowItemsContainer[0].contains(event.target) && event.target !== $expandButton[0];

        if (isOutsideClick && $expandButton.attr('aria-expanded') === 'true') {
          $expandButton.click();
        }
      });

      // Handle focus events to close when focus moves outside
      document.addEventListener('focusin', (event) => {
        // Check if the newly focused element is outside the container and button
        const isOutsideFocus = !$overflowItemsContainer[0].contains(event.target) && event.target !== $expandButton[0];

        if (isOutsideFocus && $expandButton.attr('aria-expanded') === 'true') {
          $expandButton.click();
        }
      });
    }
  });
})(jQuery);
