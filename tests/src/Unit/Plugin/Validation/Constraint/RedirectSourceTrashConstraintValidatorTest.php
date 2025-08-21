<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\Plugin\Validation\Constraint;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\stanford_profile_helper\Plugin\Validation\Constraint\RedirectSourceTrashConstraint;
use Drupal\stanford_profile_helper\Plugin\Validation\Constraint\RedirectSourceTrashConstraintValidator;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class RedirectSourceTrashConstraintValidatorTest.
 */
class RedirectSourceTrashConstraintValidatorTest extends UnitTestCase {

  /**
   * Has the field value already been returned via the mock?
   *
   * @var bool
   */
  protected $fieldValueReturned = FALSE;

  /**
   * All fields are populated.
   */
  public function testNoErrorValidation() {
    $deleted_count = 0;

    $select = $this->createMock(SelectInterface::class);
    $queryResult = $this->createMock(StatementInterface::class);

    $database = $this->createMock(Connection::class);
    $database->method('select')->willReturn($select);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('countQuery')->willReturnSelf();
    $select->method('execute')->willReturn($queryResult);
    $queryResult->method('fetchField')->willReturnReference($deleted_count);

    $container = new ContainerBuilder();
    $container->set('database', $database);

    $property = $this->createMock(TypedDataInterface::class);
    $property->method('getString')->willReturn('/foo/bar');

    $field_value = $this->createMock(FieldItemInterface::class);
    $field_value->method('get')->willReturn($property);

    $field_value_list = $this->createMock(FieldItemListInterface::class);
    $field_value_list->method('get')->willReturn($field_value);

    TestRedirectSourceTrashConstraintValidator::create($container);
    $validator = new TestRedirectSourceTrashConstraintValidator($database);
    $validator->initialize($this->getContext());

    $validator->validate($field_value_list, new RedirectSourceTrashConstraint());
    $this->assertFalse($validator->hasErrors());

    $deleted_count = 1;
    $validator->validate($field_value_list, new RedirectSourceTrashConstraint());
    $this->assertTrue($validator->hasErrors());

    $this->expectException(\InvalidArgumentException::class);
    $validator->validate(null, new RedirectSourceTrashConstraint());
  }

  protected function getContext() {
    $validator = $this->createMock(ValidatorInterface::class);
    $translator = $this->createMock(TranslatorInterface::class);
    return new ExecutionContext($validator, '', $translator);
  }

}

/**
 * Testable validator.
 */
class TestRedirectSourceTrashConstraintValidator extends RedirectSourceTrashConstraintValidator {

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
