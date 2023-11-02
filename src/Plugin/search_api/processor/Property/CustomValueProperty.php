<?php

namespace Drupal\stanford_profile_helper\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Plugin\search_api\processor\Property\CustomValueProperty as SearchApiCustomValueProperty;

/**
 * Increases max length from the contrib module.
 */
class CustomValueProperty extends SearchApiCustomValueProperty {

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($field, $form, $form_state);
    $form['value']['#maxlength'] = 1000;
    return $form;
  }

}
