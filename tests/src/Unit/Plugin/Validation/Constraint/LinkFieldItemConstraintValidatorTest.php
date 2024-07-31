<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Validation\ExecutionContext;
use Drupal\Core\Validation\TranslatorInterface;
use Drupal\stanford_profile_helper\Plugin\Validation\Constraint\LinkFieldItemConstraintValidator;
use Drupal\stanford_profile_helper\Plugin\Validation\Constraint\MenuLinkItemConstraint;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @coversDefaultClass \Drupal\stanford_profile_helper\Plugin\Validation\Constraint\LinkFieldItemConstraintValidator
 */
class LinkFieldItemConstraintValidatorTest extends UnitTestCase {

  /**
   * Data provider for validator.
   *
   * @return array[]
   */
  public function dataProvider() {
    return [
      ['http://localhost', 'http://localhost/foo/bar', TRUE],
      ['http://localhost', '/foo/bar', FALSE],
      ['http://localhost', 'http://hostlocal/foo/bar', FALSE],
    ];
  }

  /**
   * Tests the validate method.
   *
   * @dataProvider dataProvider
   */
  public function testValidation($currentDomain, $linkUrl, $shouldHaveViolations) {
    // Create mocks for the services and dependencies.
    $request_stack = $this->createMock(RequestStack::class);

    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item = $this->createMock(FieldItemInterface::class);
    $request = $this->createMock(Request::class);

    // Configure the request mock to return a specific scheme and host.
    $request->method('getSchemeAndHttpHost')->willReturn($currentDomain);
    $request_stack->method('getCurrentRequest')->willReturn($request);

    // Configure the field item list mock to return a field item with a specific URI.
    $field_item->method('get')->willReturnSelf();
    $field_item->method('getString')->willReturn($linkUrl);
    $field_item_list->method('get')->with(0)->willReturn($field_item);

    $container = new Container();
    $container->set('request_stack', $request_stack);

    $validator = $this->createMock(ValidatorInterface::class);
    $translator = $this->createMock(TranslatorInterface::class);
    $translator->method('trans')->willReturnCallback(fn($message) => $message);
    $context = new ExecutionContext($validator, NULL, $translator);

    // Instantiate the validator and set the context.
    $validator = TestLinkValidator::create($container);
    $validator->initialize($context);

    $constraint = new MenuLinkItemConstraint();
    $context->setConstraint($constraint);

    // Call the validate method.
    $validator->validate($field_item_list, $constraint);
    if ($shouldHaveViolations) {
      $this->assertTrue($validator->hasViolation());
    }
    else {
      $this->assertFalse($validator->hasViolation());
    }
  }

}

class TestLinkValidator extends LinkFieldItemConstraintValidator {

  public function hasViolation() {
    return count($this->context->getViolations()) > 0;
  }

}
