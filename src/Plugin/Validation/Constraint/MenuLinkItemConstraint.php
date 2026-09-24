<?php

namespace Drupal\stanford_profile_helper\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint as ConstraintAttribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Checks that a menu link item URL is not absolute.
 */
#[ConstraintAttribute(
  id: 'menu_link_item_url_constraint',
  label: new TranslatableMarkup('Menu Link Item', [], ['context' => 'Validation']),
  type: 'string'
)]
class MenuLinkItemConstraint extends Constraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $absoluteLink = 'The link URL must not be an absolute URL. Please use relative links that start with "/" for local destinations.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
