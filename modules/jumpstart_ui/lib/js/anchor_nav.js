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
      .attr('aria-controls', 'overflow-container')
      .attr('aria-label', 'See More');

    const $overflowItemsContainer = $('<ul>').addClass('overflow-items hidden').attr('id', 'overflow-container');

    function manageOverflow(vertical = false) {
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

      if(!vertical) {
        // remove 'See More' on mobile for horizontal
        var width = $(window).width();
        var maxMobileWidth = 1185;
        if(width < maxMobileWidth) {
          $expandButton.html('On This Page<i class="fa-solid fa-chevron-down"></i>');
          $expandButton.attr('aria-label', 'On This Page');
        } else {
          $expandButton.html('See More<i class="fa-solid fa-chevron-down"></i>');
          $expandButton.attr('aria-label', 'See More');
        }

        // Check if the list overflows its container
        if ($list[0].scrollWidth > $container[0].clientWidth - 220 || width < maxMobileWidth) {

          $listItems.get().reverse().map(item => {
            const $item = $(item);

            if ($list[0].scrollWidth > $container[0].clientWidth - 220 || width < maxMobileWidth) {
              $overflowItemsContainer.prepend($item.clone().addClass('overflow-item'));
              $item.addClass('hidden');
            }
          });

          $nav.append($expandButton).append($overflowItemsContainer);
        }
      } else {
        // show first 7 links on vertical
        if($listItems.length > 7) {

          let i = 0;
          $listItems.get().reverse().map(item => {
            const $item = $(item);
  
            if (i < $listItems.length - 7) {
              $overflowItemsContainer.prepend($item.clone().addClass('overflow-item'));
              $item.remove();
              i++;
            }
          });
  
          $nav.append($expandButton).append($overflowItemsContainer);
          $overflowItemsContainer.addClass('vertical');
        }
      }



    }

    if ($container.hasClass('orientation-horizontal')) {
      // Initial check and on window resize
      manageOverflow(false);
      window.addEventListener('resize', manageOverflow);

      window.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
          if ($expandButton.attr('aria-expanded') === 'true') {
            $expandButton.click();
            $expandButton.focus();
          }
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

      // Event listener for clicks anywhere on the document
      document.addEventListener('click', (event) => {
        // Check if the clicked element is outside the collapsible area or the toggle button itself (including the chevron icon)
        const isOutsideClick = !$overflowItemsContainer[0].contains(event.target) && event.target !== $expandButton[0] && event.target.parentNode !== $expandButton[0];

        if (isOutsideClick && $expandButton.attr('aria-expanded') === 'true') {
          $expandButton.click();
        }
      });
    }
    
    if ($container.hasClass('orientation-vertical')) {
      // Initial check and on window resize
      manageOverflow(true);
    }


  });
})(jQuery);
