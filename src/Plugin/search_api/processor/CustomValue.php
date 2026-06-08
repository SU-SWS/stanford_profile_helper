<?php

namespace Drupal\stanford_profile_helper\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\processor\CustomValue as SearchApiCustomValue;
use Drupal\search_api\Plugin\search_api\processor\Property\CustomValueProperty;

/**
 * Extends original custom value search api field to support token_or module.
 */
class CustomValue extends SearchApiCustomValue {

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get all of the "custom_value" fields on this item.
    $fields_helper = $this->getFieldsHelper();
    $item_fields = $item->getFields(FALSE);
    $fields = $fields_helper->filterForPropertyPath($item_fields, NULL, $this->getPropertyPath());
    // Add datasource-specific fields.
    $fields += $fields_helper->filterForPropertyPath(
      $item_fields,
      $item->getDatasourceId(),
      $this->getPropertyPath($item->getDatasource())
    );
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
      if (
        empty($config['value'])
        || !($field->getDataDefinition() instanceof CustomValueProperty)
      ) {
        // Avoid adding the same field value twice in the event of a property
        // path collision within the datasource.
        continue;
      }
      // Check if there are any tokens to replace.
      $field_value = $config['value'];
      if (preg_match_all('/\[[-\w]++(?::[-\w]++)++(?:\|[-\w]++(?::[-\w]++)++)*]/', $field_value, $matches)) {
        $field_value = $token->replacePlain($field_value, $data);
        // Make sure there are no left-over tokens.
        $field_value = str_replace($matches[0], '', $field_value);
        $field_value = trim($field_value);
      }
      if ($field_value !== '') {
        $field->addValue($field_value);
      }
    }
  }

}
