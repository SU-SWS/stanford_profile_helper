<?php

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;
use Drupal\stanford_profile_helper\Form\LayoutLibraryIconForm;

class LayoutLibraryHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  function entityTypeAlter(array &$entity_types) {
    $entity_types['layout']->setLinkTemplate('edit-icon-form', $entity_types['layout']->getLinkTemplate('edit-form') . '/edit-icon');
    $entity_types['layout']->setFormClass('edit-icon', LayoutLibraryIconForm::class);
  }

  /**
   * Implements hook_entity_operation_alter().
   */
  #[Hook('entity_operation_alter')]
  public function entityOperationAlter(array &$operations, ConfigEntityInterface $entity) {
    if ($entity->getEntityTypeId() == 'layout') {
      $operations['icon'] = [
        'title' => t('Edit Icon'),
        'weight' => 50,
        'url' => Url::fromRoute('entity.layout.edit_icon_form', [
          'node_type' => $entity->get('targetBundle'),
          'layout' => $entity->id(),
        ]),
      ];
    }
  }

}
