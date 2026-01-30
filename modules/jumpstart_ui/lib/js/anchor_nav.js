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

      $list.append($('<li>').append($('<a>').attr('href', `#${id}`).addClass(`anchor-link`).text($heading.text().trim())));
    });

    const $container = $('.anchor-link-nav');
    
    // Add a skip link before the nav for keyboard users
    const $skipLink = $('<a>').attr('href', '#main-content').addClass('visually-hidden focusable su-skipnav su-skipnav--secondary').text('Skip to main content');
    $container.append($skipLink);

    const $nav = $('<nav>').attr('aria-label', 'On this page Navigation').append($list);
    $container.append($nav);
    const $expandButton = $('<button>').html('See More<i class="fa-solid fa-chevron-down"></i>')
      .attr('aria-expanded', 'false')
      .attr('aria-controls', 'overflow-container')
      .attr('aria-label', 'See More');

    const $overflowItemsContainer = $('<ul>').addClass('overflow-items hidden').attr('id', 'overflow-container');

    function relabelExpandButton(text = 'See More') {
      // relabel the button if the text is different
      if(!$expandButton.text().includes(text)) {
        $expandButton.html(text + '<i class="fa-solid fa-chevron-down"></i>');
        $expandButton.attr('aria-label', text);
      }
    }
    const maxMobileWidth = 1185; // 1200px -15
    const maxMobileVerticalWidth = 977; // 992px -15

    function manageOverflow(vertical = false) {
      $('button', $nav).remove();
      $('.overflow-items', $nav).remove();

      $overflowItemsContainer.empty()
      const width = $(window).width();

      // Toggle overflow items visibility on expand button click
      $expandButton.on('click', () => {
        $expandButton.attr('aria-expanded', (i, currentValue) => currentValue === 'true' ? 'false' : 'true');
        $overflowItemsContainer.toggleClass('hidden');

        if(vertical) {
          if(width >= maxMobileVerticalWidth) {
            relabelExpandButton($expandButton.attr('aria-expanded') === 'true' ? 'Show Less' : 'Show More');
            //when overflow was just opened
            if($expandButton.attr('aria-expanded') === 'true') {
              // move tab focus to first overflow link for tabbing accessibility
              $('#overflow-container li:first-child a')[0].focus();
            }
          } else {
            relabelExpandButton('On This Page');
          }
        }
      });

      const $listItems = $('li', $list);
      // Reset all list items to visible
      $listItems.removeClass('hidden');

      //horizontal layout or mobile vertical layout
      if(!vertical || width < maxMobileVerticalWidth) {
        // remove 'See More' on mobile for horizontal
        if(width < maxMobileWidth) {
          relabelExpandButton('On This Page');
        } else {
          relabelExpandButton('See More');
        }
        
        const maxWidth = $($container[0]).css('max-width').split('px')[0]; // clientWidth doesn't work when width is unset

        // Check if the list overflows its container
        if ($list[0].scrollWidth > maxWidth - 220 || width < maxMobileWidth) {
          $listItems.get().reverse().map(item => {
            const $item = $(item);

            if ($list[0].scrollWidth > maxWidth - 220 || width < maxMobileWidth) {
              $overflowItemsContainer.prepend($item.clone().addClass('overflow-item'));
              $item.addClass('hidden');
            }
          });

          $nav.append($expandButton).append($overflowItemsContainer);
        }
      } else {
        // show first 7 links on vertical (non-mobile)
        if($listItems.length > 7) {

          let i = 0;
          $listItems.get().reverse().map(item => {
            const $item = $(item);
  
            if (i < $listItems.length - 7) {
              $overflowItemsContainer.prepend($item.clone().addClass('overflow-item'));
              $item.addClass('hidden');
              i++;
            }
          });
  
          relabelExpandButton('Show More');
          $nav.append($overflowItemsContainer).append($expandButton);
        }
      }

    }

    if ($container.hasClass('orientation-horizontal')) {
      // Initial check and on window resize
      manageOverflow(false);
      window.addEventListener('resize', manageOverflow.bind(this, false));
    }
    
    if ($container.hasClass('orientation-vertical')) {
      // Initial check and on window resize
      manageOverflow(true);
      window.addEventListener('resize', manageOverflow.bind(this, true));
    }

    window.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        if ($expandButton.attr('aria-expanded') === 'true') {
          const width = $(window).width();
          if($container.hasClass('orientation-horizontal') || width < maxMobileVerticalWidth) {
            $expandButton.click();
            $expandButton.focus();
          }
        }
      }
    });

    // Handle focus events to close when focus moves outside
    document.addEventListener('focusin', (event) => {
      // Check if the newly focused element is outside the container and button
      const isOutsideFocus = !$overflowItemsContainer[0].contains(event.target) && event.target !== $expandButton[0];

      if (isOutsideFocus && $expandButton.attr('aria-expanded') === 'true') {
        const width = $(window).width();
        if($container.hasClass('orientation-horizontal') || width < maxMobileVerticalWidth) { 
          $expandButton.click();
        }
      }
    });

    // Event listener for clicks anywhere on the document
    document.addEventListener('click', (event) => {
      // Check if the clicked element is outside the collapsible area or the toggle button itself (including the chevron icon)
      const isOutsideClick = !$overflowItemsContainer[0].contains(event.target) && event.target !== $expandButton[0] && event.target.parentNode !== $expandButton[0];
      const width = $(window).width();
      if($container.hasClass('orientation-horizontal') || width < maxMobileVerticalWidth) {
          
        if (isOutsideClick && $expandButton.attr('aria-expanded') === 'true') {
          $expandButton.click();
        }
        if (event.target && event.target.classList.contains('anchor-link')) {
          // clicked an anchor link in the overflow dropdown menu
          if ($expandButton.attr('aria-expanded') === 'true') {
            $expandButton.click();
          }
        }
      }
    });
  });
})(jQuery);
