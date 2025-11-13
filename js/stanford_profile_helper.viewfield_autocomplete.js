(function ($) {
  'use strict';
  Drupal.behaviors.stanfordProfileHelperViewFieldAutcomplete = {
    attach: function (context) {
      const viewField = $('.viewfield-autocomplete', context);

      const targetField = $('select[name$="[target_id]"]', viewField);
      const displayField = $('select[name$="[display_id]"]', viewField);
      const argField = $('input[name$="[arguments]"]', viewField);

      // Change the autocomplete path based on the values of the other fields.
      // Clear autocomplete cache for the element.
      const updatePath = function () {
        const path = argField.attr('data-autocomplete-path')
          .replace(/autocomplete\/\w+/, 'autocomplete/' + targetField.val())
          .replace(/\w+$/, displayField.val());

        argField.attr('data-autocomplete-path', path);
        const elementId = argField.attr('id');
        Drupal.autocomplete.cache[elementId] = {};
      };

      targetField.on('change', updatePath);
      displayField.on('change', updatePath);
    },
  };

})(jQuery);
