<?php

namespace Drupal\stanford_profile_helper\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint as ConstraintAttribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Plugin implementation of the 'global_message_constraint'.
 */
#[ConstraintAttribute(
  id: 'global_message_constraint',
  label: new TranslatableMarkup('Global message constraint', [], ['context' => 'Validation'])
)]
class GlobalMessageConstraint extends Constraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $fieldsNotPopulated = 'To enable a global message, at least one field must have a value: Label, Headline, Message, Action Link.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
