(($, Drupal, once) => {
  Drupal.behaviors.stanfordHelperLayoutParagraphs = {
    attach: function attach(context, settings) {
      if (typeof CKEDITOR !== 'undefined') {
        Object.keys(CKEDITOR.instances).forEach(instanceId => {
          CKEDITOR.instances[instanceId].on('unlockSnapshot', snapshot => {
            snapshot.editor.fire('change');
          });
        });
      }

      // For some reason in the edit view, the two classes don't get added via
      // the template when in editing mode.
      $('.ds-entity--stanford-event .su-event-list-item', context).addClass(['su-card', 'su-event-card']);

      if ($('.paragraph--type--stanford-filtered-lists').length > 0) {
        $('#edit-layout-selection').val('stanford_basic_page_full').attr('disabled', true);
        $(once('layout-selection-locked', '#edit-layout-selection--description')).text('Layout selection is disabled when a "filtered list" is used on the page.')
      }
    },
  };
})(jQuery, Drupal, once);
