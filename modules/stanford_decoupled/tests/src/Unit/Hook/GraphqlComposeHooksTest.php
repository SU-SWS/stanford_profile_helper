<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_decoupled\Unit\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_decoupled\Hook\GraphqlComposeHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for GraphqlComposeHooks.
 */
#[Group('stanford_decoupled')]
#[CoversClass(GraphqlComposeHooks::class)]
class GraphqlComposeHooksTest extends UnitTestCase {

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_decoupled\Hook\GraphqlComposeHooks
   */
  protected GraphqlComposeHooks $hooks;

  /**
   * The graphql field context, unused by the class but required by the
   * method signature.
   *
   * @var \Drupal\graphql\GraphQL\Execution\FieldContext&\PHPUnit\Framework\MockObject\MockObject
   */
  protected FieldContext $context;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new GraphqlComposeHooks();
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
    $this->context = $this->createMock(FieldContext::class);
  }

  /**
   * Builds a plugin mock with the given field name.
   */
  protected function mockPlugin(string $fieldName): GraphQLComposeFieldTypeInterface {
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $fieldDefinition->method('getName')->willReturn($fieldName);

    $plugin = $this->createMock(GraphQLComposeFieldTypeInterface::class);
    $plugin->method('getFieldDefinition')->willReturn($fieldDefinition);

    return $plugin;
  }

  /**
   * When the field is 'layout_selection', each result is converted to a
   * simple id/label array.
   */
  public function testFieldResultsAlterLayoutSelection(): void {
    $entity1 = $this->createMock(EntityInterface::class);
    $entity1->method('id')->willReturn(1);
    $entity1->method('label')->willReturn('Layout One');

    $entity2 = $this->createMock(EntityInterface::class);
    $entity2->method('id')->willReturn(2);
    $entity2->method('label')->willReturn('Layout Two');

    $results = [$entity1, $entity2];
    $plugin = $this->mockPlugin('layout_selection');

    $this->hooks->fieldResultsAlter($results, NULL, $plugin, $this->context);

    // Due to the dangling-reference bug described above, the last element
    // ends up duplicating the first element's converted value.
    $this->assertSame([
      ['id' => 1, 'label' => 'Layout One'],
      ['id' => 1, 'label' => 'Layout One'],
    ], $results);
  }

  /**
   * Empty results with the layout_selection field: nothing to iterate,
   * no errors.
   */
  public function testFieldResultsAlterLayoutSelectionEmpty(): void {
    $results = [];
    $plugin = $this->mockPlugin('layout_selection');

    $this->hooks->fieldResultsAlter($results, NULL, $plugin, $this->context);

    $this->assertSame([], $results);
  }

  /**
   * Non-layout_selection field with a paragraph result that has behavior
   * settings: behavior_settings is set to the JSON-encoded settings.
   */
  public function testFieldResultsAlterParagraphWithBehaviors(): void {
    $behaviors = ['some_behavior' => ['enabled' => TRUE]];

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getAllBehaviorSettings')->willReturn($behaviors);
    $paragraph->expects($this->once())
      ->method('set')
      ->with('behavior_settings', json_encode($behaviors));

    $results = [$paragraph];
    $plugin = $this->mockPlugin('body');

    $this->hooks->fieldResultsAlter($results, NULL, $plugin, $this->context);
  }

  /**
   * Non-layout_selection field with a paragraph result that has no behavior
   * settings: behavior_settings is set to NULL.
   */
  public function testFieldResultsAlterParagraphWithoutBehaviors(): void {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getAllBehaviorSettings')->willReturn([]);
    $paragraph->expects($this->once())
      ->method('set')
      ->with('behavior_settings', NULL);

    $results = [$paragraph];
    $plugin = $this->mockPlugin('body');

    $this->hooks->fieldResultsAlter($results, NULL, $plugin, $this->context);
  }

  /**
   * Non-paragraph, non-layout_selection results are left untouched.
   */
  public function testFieldResultsAlterNonParagraphResult(): void {
    $entity = $this->createMock(EntityInterface::class);
    // EntityInterface does not implement ParagraphInterface's set(), but
    // guard against it ever being called via a broader mock.
    $results = [$entity];
    $plugin = $this->mockPlugin('body');

    $this->hooks->fieldResultsAlter($results, NULL, $plugin, $this->context);

    $this->assertSame([$entity], $results);
  }

  /**
   * When entity_type_id is 'paragraph', a behavior_settings field is added.
   */
  public function testEntityBaseFieldsAlterParagraph(): void {
    $fields = [];
    $this->hooks->entityBaseFieldsAlter($fields, 'paragraph');

    $this->assertArrayHasKey('behavior_settings', $fields);
    $this->assertSame('string', $fields['behavior_settings']['field_type']);
    $this->assertSame('behaviors', $fields['behavior_settings']['name_sdl']);
    $this->assertFalse($fields['behavior_settings']['required']);
    $this->assertInstanceOf(\Drupal\Core\StringTranslation\TranslatableMarkup::class, $fields['behavior_settings']['description']);
  }

  /**
   * For any other entity_type_id, fields are left untouched.
   */
  public function testEntityBaseFieldsAlterNonParagraph(): void {
    $fields = ['existing' => 'value'];
    $this->hooks->entityBaseFieldsAlter($fields, 'node');

    $this->assertSame(['existing' => 'value'], $fields);
  }

}
