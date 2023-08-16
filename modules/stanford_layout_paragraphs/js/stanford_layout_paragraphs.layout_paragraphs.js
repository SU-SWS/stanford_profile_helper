(($, Drupal ) => {
  Drupal.behaviors.stanfordHelperLayoutParagraphs = {
    attach: function attach(context, settings) {
      if (typeof CKEDITOR !== 'undefined') {
        Object.keys(CKEDITOR.instances).forEach(instanceId => {
          CKEDITOR.instances[instanceId].on('unlockSnapshot', snapshot => {
            snapshot.editor.fire('change');
          })
        })
      }

      // For some reason in the edit view, the two classes don't get added via
      // the template when in editing mode.
      $('.ds-entity--stanford-event .su-event-list-item', context).addClass(['su-card', 'su-event-card']);
    },
  };
})(jQuery, Drupal);
