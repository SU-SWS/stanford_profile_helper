<?php

namespace Drupal\stanford_profile_helper\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "link_field_item_constaint",
 *   label = @Translation("Link Field Item", context = "Validation")
 * )
 */
class LinkFieldItemConstraint extends Constraint {

  public $absoluteLink = 'Please use relative links that start with "/" for paths on this site.';

}
