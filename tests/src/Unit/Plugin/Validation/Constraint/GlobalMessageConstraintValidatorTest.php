<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\Plugin\Validation\Constraint;

use Drupal\stanford_profile_helper\Plugin\Validation\Constraint\GlobalMessageConstraint;
use Drupal\stanford_profile_helper\Plugin\Validation\Constraint\GlobalMessageConstraintValidator;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class GlobalMessageConstraintValidatorTest.
 *
 * @group stanford_profile_helper
 * @coversDefaultClass \Drupal\stanford_profile_helper\Plugin\Validation\Constraint\GlobalMessageConstraintValidator
 */
class GlobalMessageConstraintValidatorTest extends UnitTestCase {

  /**
   * Has the field value already been returned via the mock field list.
   *
   * @var bool
   */
  protected $fieldValueReturned = FALSE;

  /**
   * All fields are populated.
   */
  public function testNoErrorValidation() {
    $validator = new TestGlobalMessageConstraintValidator();
    $validator->initialize($this->getContext());

    $field_value = $this->createMock(FieldItemListInterface::class);
    $field_value->method('count')->willReturn(1);

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_value);

    $field_value->method('getEntity')->willReturn($entity);

    $constraint = new GlobalMessageConstraint();
    $validator->validate($field_value, $constraint);
    $this->assertFalse($validator->hasErrors());
  }

  /**
   * None of the field are populated.
   */
  public function testEmptyFieldsValidation() {
    $validator = new TestGlobalMessageConstraintValidator();
    $validator->initialize($this->getContext());

    $field_value = $this->createMock(FieldItemListInterface::class);
    $field_value->method('count')->willReturn(NULL);

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_value);

    $field_value->method('getEntity')->willReturn($entity);

    $constraint = new GlobalMessageConstraint();
    $validator->validate($field_value, $constraint);
    $this->assertTrue($validator->hasErrors());
  }

  /**
   * At least 1 field is not populated while the others are.
   */
  public function testErrorValidation() {
    $validator = new TestGlobalMessageConstraintValidator();
    $validator->initialize($this->getContext());

    $field_value = $this->createMock(FieldItemListInterface::class);
    $field_value->method('count')
      ->will($this->returnCallback([$this, 'getFieldCallback']));;

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($field_value);

    $field_value->method('getEntity')->willReturn($entity);

    $constraint = new GlobalMessageConstraint();
    $validator->validate($field_value, $constraint);
    $this->assertFalse($validator->hasErrors());
  }

  /**
   * Field count callback from the mock object.
   *
   * @return int
   *   Field list count.
   */
  public function getFieldCallback() {
    $value = $this->fieldValueReturned ? 0 : 1;
    $this->fieldValueReturned = TRUE;
    return $value;
  }

  /**
   * Build a context object for the validator.
   *
   * @return \Symfony\Component\Validator\Context\ExecutionContext
   */
  protected function getContext() {
    $validator = $this->createMock(ValidatorInterface::class);
    $translator = $this->createMock(TranslatorInterface::class);
    return new ExecutionContext($validator, '', $translator);
  }

}

class TestGlobalMessageConstraintValidator extends GlobalMessageConstraintValidator {

  /**
   * If the violation has errors.
   *
   * @return bool
   *   Violations exist.
   */
  public function hasErrors() {
    return $this->context->getViolations()->count() > 0;
  }

}
