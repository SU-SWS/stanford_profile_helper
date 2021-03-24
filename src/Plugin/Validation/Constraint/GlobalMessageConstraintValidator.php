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
  public function validate($value, Constraint $constraint) {

    $is_valid = FALSE;

    $resource_fields = [
        'su_global_msg_label',
        'su_global_msg_header',
        'su_global_msg_message',
        'su_global_msg_link',
    ];

    if ($value->getEntity()->hasField('su_global_msg_enabled') &&
    $value->getEntity()->get('su_global_msg_enabled')->getValue()[0]['value']) {

        foreach($resource_fields as $field) {
            if (!empty($value->getEntity()->get($field)->getValue())) {
                $is_valid = TRUE;
            }
        }

        if (!$is_valid) {
            $this->context->addViolation($constraint->fieldsNotPopulated);
        }
        
    }
  }

}
