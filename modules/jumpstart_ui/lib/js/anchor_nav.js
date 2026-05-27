(($) => {
  document.addEventListener('DOMContentLoaded', () => {
    anchors.add('#page-content h2:not(.visually-hidden):not(.no-anchor)');

    const $headings = $('#page-content h2:not(.visually-hidden):not(.no-anchor)[id]')
      .filter((i, item) => $(item).closest('.node-stanford-page-su-page-banner').length === 0);

    // If no links exist, don't add the markup.
    if ($headings.length === 0) {
      return;
    }

    const $container = $('.anchor-link-nav');
    $container.addClass('has-headings');

    const $list = $('<ul>');

    $headings.map((i, heading) => {
      const $heading = $(heading);
      const id = $heading.attr('id');

      $list.append($('<li>').append($('<a>').attr('href', `#${id}`).addClass(`anchor-link`).attr('aria-label', `Jump to: ${$heading.text().trim()}`).text($heading.text().trim())));    });

    const $nav = $('<nav>').attr('aria-label', 'On this page Navigation').append($list);
    $container.append($nav);
    const $span = $('<span id="expand-text">See More<span>');
    const $chevron = $('<i class="fa-solid fa-chevron-down"></i>');
    const $expandButton = $('<button>').append($span).append($chevron)
      .attr('aria-expanded', 'false')
      .attr('aria-controls', 'overflow-container')
      .attr('aria-label', 'See More');

    const $overflowItemsContainer = $('<ul>').addClass('overflow-items hidden').attr('id', 'overflow-container');

    const setExpanded = (expanded) => {
      $expandButton.attr('aria-expanded', expanded ? 'true' : 'false');

      if (expanded) {
        $overflowItemsContainer.attr('open', 'true');   // mirrors main-nav pattern
      } else {
        $overflowItemsContainer.removeAttr('open');
      }
    };

    const isExpanded = () => $expandButton.attr('aria-expanded') === 'true';

    // FIX: Central close helper used by all dismiss paths so behaviour is
    // consistent (click, Escape, focusout, VO cursor-leave).
    const closeOverflow = () => {
      if (isExpanded()) {
        $expandButton.click(); // reuse existing toggle logic (label update etc.)
      }
    };

    const relabelExpandButton = (text = 'See More') => {
      // relabel the button if the text is different
      const $btnText = $expandButton.children('#expand-text');
      if (!$btnText.text().includes(text)) {
        $btnText.text(text);
        $expandButton.attr('aria-label', text);
      }
    }

    const maxMobileWidth = 1185; // 1200px -15
    const maxMobileVerticalWidth = 977; // 992px -15

    const manageOverflow = (vertical = false) => {
      $('button', $nav).remove();
      $('.overflow-items', $nav).remove();

      $overflowItemsContainer.empty();
      const width = $(window).width();

      // Toggle overflow items visibility on expand button click
      $expandButton.on('click', () => {
        const startsExpanded = isExpanded();

        setExpanded(!startsExpanded);
        $overflowItemsContainer.toggleClass('hidden');

        if (vertical) {
          if (width >= maxMobileVerticalWidth) {
            relabelExpandButton(startsExpanded ? 'Show More' : 'Show Less');
            //when overflow was just opened
            if (!startsExpanded) {
              // move tab focus to first overflow link for tabbing accessibility
              $('#overflow-container li:first-child a')[0].focus();
            }
          } else {
            relabelExpandButton('On This Page');
          }
        } else {
          if (width >= maxMobileWidth) {
            relabelExpandButton(startsExpanded ? 'See More' : 'See Less');
          } else {
            relabelExpandButton('On This Page');
          }
        }
      });

      const $listItems = $('li', $list);
      // Reset all list items to visible
      $listItems.removeClass('hidden');

      //horizontal layout or mobile vertical layout
      if (!vertical || width < maxMobileVerticalWidth) {
        // remove 'See More' on mobile for horizontal
        if (width < maxMobileWidth) {
          relabelExpandButton('On This Page');
        } else {
          relabelExpandButton('See More');
        }

        const maxAnchorWidth = $($container[0]).css('max-width').split('px')[0]; // clientWidth doesn't work when width is unset
        const maxRegionWidth = $('.main-region .node-stanford-page-body, .main-region .su-page-components')[0]?.clientWidth || 1000; // width of the main text area
        const maxWidth = Math.min(maxAnchorWidth, maxRegionWidth); // limit the horizontal nav to whichever is smallest

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
        if ($listItems.length > 7) {

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
        if (isExpanded()) {
          const width = $(window).width();
          if ($container.hasClass('orientation-horizontal') || width < maxMobileVerticalWidth) {
            $expandButton.click();
            $expandButton.focus();
          }
        }
      }
    });

    // FIX: Close the overflow menu when VoiceOver navigates away with
    // Ctrl+Option+Arrow keys.

    $overflowItemsContainer[0].addEventListener('focusout', (event) => {
      setTimeout(() => {
        const width = $(window).width();
        const shouldManage =
          $container.hasClass('orientation-horizontal') || width < maxMobileVerticalWidth;

        if (!shouldManage || !isExpanded()) return;

        const focusedEl = event.relatedTarget || document.activeElement;

        // If the newly-focused element is still inside the overflow list or is
        // the expand button itself, the user is still within our widget — leave
        // the menu open.
        const stillInside =
          $overflowItemsContainer[0].contains(focusedEl) ||
          focusedEl === $expandButton[0];

        if (!stillInside) {
          closeOverflow();
        }
      }, 0);
    });

    // Handle focus events to close when focus moves outside.
    // NOTE: This handles Tab-key navigation (standard keyboard users and some AT).
    // VoiceOver Ctrl+Option+Arrow is handled separately by the focusout listener
    // above, because focusin does not fire for VO virtual-cursor movement.
    document.addEventListener('focusin', (event) => {
      // Check if the newly focused element is outside the container and button
      const isOutsideFocus = !$overflowItemsContainer[0].contains(event.target) && event.target !== $expandButton[0];

      if (isOutsideFocus && isExpanded()) {
        const width = $(window).width();
        if ($container.hasClass('orientation-horizontal') || width < maxMobileVerticalWidth) {
          $expandButton.click();
        }
      }
    });

    // Event listener for clicks anywhere on the document
    document.addEventListener('click', (event) => {
      // Check if the clicked element is outside the collapsible area or the toggle button itself (including the chevron icon)
      const isOutsideClick = !$overflowItemsContainer[0].contains(event.target) && event.target !== $expandButton[0] && event.target.parentNode !== $expandButton[0];
      const width = $(window).width();
      if ($container.hasClass('orientation-horizontal') || width < maxMobileVerticalWidth) {

        if (isOutsideClick && isExpanded()) {
          $expandButton.click();
        }
        if (event.target && event.target.classList.contains('anchor-link')) {
          // clicked an anchor link in the overflow dropdown menu
          if (isExpanded()) {
            $expandButton.click();
          }
        }
      }
    });
  });
})(jQuery);
