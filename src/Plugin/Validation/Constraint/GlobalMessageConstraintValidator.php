<?php

namespace Drupal\stanford_profile_helper\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInteger constraint.
 */
class GlobalMessageConstraintValidator extends ConstraintValidator
{

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    $resource_fields = [
        'su_global_msg_label' => 0,
        'su_global_msg_header' => 0,
        'su_global_msg_message' => 0,
        'su_global_msg_link' => 0,
    ];

    foreach (array_keys($resource_fields) as $resource_field) {
        if ($value->getEntity()->hasField($resource_field)) {
        $resource_fields[$resource_field] = $value->getEntity()
            ->get($resource_field)
            ->count();
        }
    }

    if (array_filter($resource_fields) && count(array_filter($resource_fields)) != count($resource_fields)) {
        $this->context->addViolation($constraint->fieldsNotPopulated);
    }
  }

}
