<?php

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\stanford_profile_helper\Form\LayoutLibraryIconForm;
use Drupal\stanford_profile_helper\StanfordLayoutListBuilder;

class LayoutLibraryHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  function entityTypeAlter(array &$entity_types) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $layout */
    $layout = $entity_types['layout'];
    $layout->setLinkTemplate('edit-icon-form', $entity_types['layout']->getLinkTemplate('edit-form') . '/edit-icon');
    $layout->setFormClass('edit-icon', LayoutLibraryIconForm::class);
    $layout->setListBuilderClass(StanfordLayoutListBuilder::class);
  }

}
