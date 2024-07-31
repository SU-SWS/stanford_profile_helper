<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\Plugin\Validation\Constraint;

use Drupal\stanford_profile_helper\Plugin\Validation\Constraint\LinkFieldItemConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraint;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\stanford_profile_helper\Plugin\Validation\Constraint\LinkFieldItemConstraintValidator
 */
class LinkFieldItemConstraintValidatorTest extends TestCase {

  /**
   * Tests the create method.
   *
   * @covers ::create
   */
  public function testCreate() {
    // Create mocks for the services.
    $container = $this->createMock(ContainerInterface::class);
    $request_stack = $this->createMock(RequestStack::class);
    $alias_manager = $this->createMock(AliasManagerInterface::class);

    // Configure the container mock to return the service mocks.
    $container->expects($this->exactly(2))
      ->method('get')
      ->withConsecutive(['request_stack'], ['path_alias.manager'])
      ->willReturnOnConsecutiveCalls($request_stack, $alias_manager);

    // Call the create method.
    $validator = LinkFieldItemConstraintValidator::create($container);

    // Assert the returned object is an instance of LinkFieldItemConstraintValidator.
    $this->assertInstanceOf(LinkFieldItemConstraintValidator::class, $validator);

    // Assert the properties are correctly set.
    $this->assertSame($request_stack, $validator->request);
    $this->assertSame($alias_manager, $validator->aliasManager);
  }

  /**
   * Tests the validate method.
   *
   * @covers ::validate
   */
  public function testValidate() {
    // Create mocks for the services and dependencies.
    $request_stack = $this->createMock(RequestStack::class);
    $alias_manager = $this->createMock(AliasManagerInterface::class);
    $constraint = $this->createMock(Constraint::class);
    $context = $this->createMock(ExecutionContextInterface::class);
    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item = $this->createMock(FieldItemInterface::class);
    $request = $this->createMock(Request::class);

    // Configure the request mock to return a specific scheme and host.
    $request->method('getSchemeAndHttpHost')->willReturn('http://example.com');
    $request_stack->method('getCurrentRequest')->willReturn($request);

    // Configure the field item list mock to return a field item with a specific URI.
    $field_item->method('get')->with('uri')->willReturn('http://example.com/some/path');
    $field_item_list->method('get')->with(0)->willReturn($field_item);

    // Instantiate the validator and set the context.
    $validator = new LinkFieldItemConstraintValidator($request_stack, $alias_manager);
    $validator->initialize($context);

    // Configure the constraint mock to have a specific violation message.
    $constraint->absoluteLink = 'Absolute links are not allowed.';

    // Expect the addViolation method to be called.
    $context->expects($this->once())
      ->method('addViolation')
      ->with('Absolute links are not allowed.');

    // Call the validate method.
    $validator->validate($field_item_list, $constraint);
  }
}
