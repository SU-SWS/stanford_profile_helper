<?php

namespace Drupal\stanford_profile_helper\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint as ConstraintAttribute;
use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 */
#[ConstraintAttribute(
  id: 'global_message_constraint',
  label: new TranslatableMarkup('Menu Link Item', [], ['context' => 'Validation']),
  type: 'string'
)]
class MenuLinkItemConstraint extends Constraint {

  public $absoluteLink = 'The link URL must not be an absolute URL. Please use relative links that start with "/" for local destinations.';

}
