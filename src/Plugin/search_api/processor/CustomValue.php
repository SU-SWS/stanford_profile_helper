<?php

namespace Drupal\stanford_profile_helper\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\processor\CustomValue as SearchApiCustomValue;
use Drupal\stanford_profile_helper\Plugin\search_api\processor\Property\CustomValueProperty;

/**
 * Extends original custom value search api field to support token_or module.
 */
class CustomValue extends SearchApiCustomValue {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Custom value'),
        'description' => $this->t('Index a custom value with replacement tokens.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['custom_value'] = new CustomValueProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get all of the "custom_value" fields on this item.
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, 'custom_value');
    // If the indexed item is an entity, we can pass that as data to the token
    // service. Otherwise, only global tokens are available.
    $entity = $item->getOriginalObject()->getValue();
    if ($entity instanceof EntityInterface) {
      $data = [$entity->getEntityTypeId() => $entity];
    }
    else {
      $data = [];
    }

    $token = $this->getToken();
    foreach ($fields as $field) {
      $config = $field->getConfiguration();
      if (empty($config['value'])) {
        continue;
      }

      $field_value = trim($token->replace($config['value'], $data, ['clear' => TRUE]));
      if ($field_value !== '') {
        $field->addValue($field_value);
      }
    }
  }

}
