<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_publication\Unit\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_publication\Entity\CitationInterface;
use Drupal\stanford_publication\Hook\CitationHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for CitationHooks.
 */
#[Group('stanford_publication')]
#[CoversClass(CitationHooks::class)]
class CitationHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_publication\Hook\CitationHooks
   */
  protected CitationHooks $hooks;

  /**
   * Entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * State service mock.
   *
   * @var \Drupal\Core\State\StateInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * Route match mock.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeMatch;

  /**
   * Entity type bundle info mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $bundleInfo;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->bundleInfo = $this->createMock(EntityTypeBundleInfoInterface::class);

    $this->hooks = new CitationHooks(
      $this->entityTypeManager,
      $this->state,
      $this->routeMatch,
      $this->bundleInfo
    );
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Builds a mocked node with a citation reference field.
   *
   * @param bool $has_citation
   *   Whether the field has a referenced citation value.
   * @param string $bundle
   *   Node bundle.
   *
   * @return \Drupal\node\NodeInterface&\PHPUnit\Framework\MockObject\MockObject
   *   Mocked node.
   */
  protected function mockNodeWithCitationField(bool $has_citation, string $bundle = 'stanford_publication') {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn($bundle);
    $node->method('hasField')->with('su_publication_citation')->willReturn(TRUE);

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('count')->willReturn($has_citation ? 1 : 0);

    if ($has_citation) {
      $field_item = $this->createMock(FieldItemInterface::class);
      $field_item->method('getValue')->willReturn(['target_id' => 42]);
      $field_list->method('get')->with(0)->willReturn($field_item);
    }

    $node->method('get')->with('su_publication_citation')->willReturn($field_list);

    return $node;
  }

  /**
   * The extra field info array structure is returned as expected.
   */
  public function testEntityExtraFieldInfo() {
    $extra = $this->hooks->entityExtraFieldInfo();
    $this->assertArrayHasKey('node', $extra);
    $this->assertArrayHasKey('stanford_publication', $extra['node']);
    $this->assertArrayHasKey('citation_type', $extra['node']['stanford_publication']['display']);
    $this->assertFalse($extra['node']['stanford_publication']['display']['citation_type']['visible']);
  }

  /**
   * Wrong view mode means the build array is left untouched.
   */
  public function testEntityViewWrongViewMode() {
    $entity = $this->createMock(NodeInterface::class);
    $entity->expects($this->never())->method('bundle');
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $this->hooks->entityView($build, $entity, $display, 'teaser');
    $this->assertArrayNotHasKey('citation_type', $build);
  }

  /**
   * A non-node entity is left untouched.
   */
  public function testEntityViewNonNodeEntity() {
    $entity = $this->createMock(EntityInterface::class);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $this->hooks->entityView($build, $entity, $display, 'full');
    $this->assertArrayNotHasKey('citation_type', $build);
  }

  /**
   * A node of the wrong bundle is left untouched.
   */
  public function testEntityViewWrongBundle() {
    $entity = $this->createMock(NodeInterface::class);
    $entity->method('bundle')->willReturn('page');
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $this->hooks->entityView($build, $entity, $display, 'full');
    $this->assertArrayNotHasKey('citation_type', $build);
  }

  /**
   * A missing display component leaves the build untouched.
   */
  public function testEntityViewNoDisplayComponent() {
    $entity = $this->createMock(NodeInterface::class);
    $entity->method('bundle')->willReturn('stanford_publication');
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->method('getComponent')->with('citation_type')->willReturn(NULL);
    $build = [];
    $this->hooks->entityView($build, $entity, $display, 'full');
    $this->assertArrayNotHasKey('citation_type', $build);
  }

  /**
   * No related citation entity leaves the build untouched.
   */
  public function testEntityViewNoCitationEntity() {
    $entity = $this->mockNodeWithCitationField(FALSE);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->method('getComponent')->with('citation_type')->willReturn(['foo' => 'bar']);
    $build = [];
    $this->hooks->entityView($build, $entity, $display, 'full');
    $this->assertArrayNotHasKey('citation_type', $build);
  }

  /**
   * A citation type labelled "Other" is displayed as "Publication".
   */
  public function testEntityViewOtherCitationType() {
    $entity = $this->mockNodeWithCitationField(TRUE);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->method('getComponent')->with('citation_type')->willReturn(['foo' => 'bar']);

    $citation = $this->createMock(CitationInterface::class);
    $citation->method('bundle')->willReturn('su_book');

    $citation_storage = $this->createMock(EntityStorageInterface::class);
    $citation_storage->method('load')->with(42)->willReturn($citation);

    $citation_type = new class {

      public function label() {
        return 'Other';
      }

    };
    $citation_type_storage = $this->createMock(EntityStorageInterface::class);
    $citation_type_storage->method('load')->with('su_book')->willReturn($citation_type);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['citation', $citation_storage],
        ['citation_type', $citation_type_storage],
      ]);

    $build = [];
    $this->hooks->entityView($build, $entity, $display, 'default');
    $this->assertSame('Publication', $build['citation_type']['#markup']);
    $this->assertSame('markup', $build['citation_type']['#type']);
  }

  /**
   * A non "Other" citation type label is displayed as is.
   */
  public function testEntityViewNamedCitationType() {
    $entity = $this->mockNodeWithCitationField(TRUE);
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->method('getComponent')->with('citation_type')->willReturn(['foo' => 'bar']);

    $citation = $this->createMock(CitationInterface::class);
    $citation->method('bundle')->willReturn('su_book');

    $citation_storage = $this->createMock(EntityStorageInterface::class);
    $citation_storage->method('load')->with(42)->willReturn($citation);

    $citation_type = new class {

      public function label() {
        return 'Book';
      }

    };
    $citation_type_storage = $this->createMock(EntityStorageInterface::class);
    $citation_type_storage->method('load')->with('su_book')->willReturn($citation_type);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['citation', $citation_storage],
        ['citation_type', $citation_type_storage],
      ]);

    $build = [];
    $this->hooks->entityView($build, $entity, $display, 'full');
    $this->assertSame('Book', $build['citation_type']['#markup']);
  }

  /**
   * Non citation entities never trigger a view mode change.
   */
  public function testEntityViewModeAlterNonCitation() {
    $entity = $this->createMock(EntityInterface::class);
    $this->routeMatch->expects($this->never())->method('getRouteName');
    $view_mode = 'default';
    $this->hooks->entityViewModeAlter($view_mode, $entity);
    $this->assertSame('default', $view_mode);
  }

  /**
   * A citation entity outside the taxonomy term page is untouched.
   */
  public function testEntityViewModeAlterWrongRoute() {
    $entity = $this->createMock(CitationInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn('some.other.route');
    $this->state->expects($this->never())->method('get');
    $view_mode = 'default';
    $this->hooks->entityViewModeAlter($view_mode, $entity);
    $this->assertSame('default', $view_mode);
  }

  /**
   * On the taxonomy term page, the view mode is changed to the stored value.
   */
  public function testEntityViewModeAlterOnTaxonomyPage() {
    $entity = $this->createMock(CitationInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn('entity.taxonomy_term.canonical');
    $this->state->method('get')
      ->with('stanford_publication.citation_format', 'default')
      ->willReturn('apa');
    $view_mode = 'default';
    $this->hooks->entityViewModeAlter($view_mode, $entity);
    $this->assertSame('apa', $view_mode);
  }

  /**
   * Inserting a node runs the post save logic and updates the citation.
   */
  public function testNodeInsertSetsCitationLabel() {
    $entity = $this->mockNodeWithCitationField(TRUE);
    $entity->method('getOriginal')->willReturn(NULL);
    $entity->method('label')->willReturn('New Title');

    $citation = $this->createMock(CitationInterface::class);
    $citation->method('label')->willReturn('');
    $citation->expects($this->once())->method('setLabel')->with('New Title');
    $citation->expects($this->once())->method('setParentEntity')
      ->with($entity, 'su_publication_citation')
      ->willReturnSelf();
    $citation->expects($this->once())->method('save');

    $citation_storage = $this->createMock(EntityStorageInterface::class);
    $citation_storage->method('load')->with(42)->willReturn($citation);
    $this->entityTypeManager->method('getStorage')->with('citation')->willReturn($citation_storage);

    $this->hooks->nodeInsert($entity);
  }

  /**
   * Updating a node with an unchanged title does not relabel the citation.
   */
  public function testNodeUpdateKeepsExistingLabel() {
    $entity = $this->mockNodeWithCitationField(TRUE);

    $original = $this->createMock(NodeInterface::class);
    $original->method('id')->willReturn(42);
    $original->method('label')->willReturn('Old Title');
    $entity->method('getOriginal')->willReturn($original);
    $entity->method('label')->willReturn('New Title');

    $citation = $this->createMock(CitationInterface::class);
    $citation->method('label')->willReturn('Some Other Label');
    $citation->expects($this->never())->method('setLabel');
    $citation->expects($this->once())->method('setParentEntity')
      ->with($entity, 'su_publication_citation')
      ->willReturnSelf();
    $citation->expects($this->once())->method('save');

    $citation_storage = $this->createMock(EntityStorageInterface::class);
    $citation_storage->method('load')->with(42)->willReturn($citation);
    $this->entityTypeManager->method('getStorage')->with('citation')->willReturn($citation_storage);

    $this->hooks->nodeUpdate($entity);
  }

  /**
   * When the citation label matches the original node title, it is renamed.
   */
  public function testNodeUpdateRelabelsWhenMatchesOriginal() {
    $entity = $this->mockNodeWithCitationField(TRUE);

    $original = $this->createMock(NodeInterface::class);
    $original->method('id')->willReturn(42);
    $original->method('label')->willReturn('Old Title');
    $entity->method('getOriginal')->willReturn($original);
    $entity->method('label')->willReturn('New Title');

    $citation = $this->createMock(CitationInterface::class);
    $citation->method('label')->willReturn('Old Title');
    $citation->expects($this->once())->method('setLabel')->with('New Title');
    $citation->method('setParentEntity')->willReturnSelf();

    $citation_storage = $this->createMock(EntityStorageInterface::class);
    $citation_storage->method('load')->with(42)->willReturn($citation);
    $this->entityTypeManager->method('getStorage')->with('citation')->willReturn($citation_storage);

    $this->hooks->nodeUpdate($entity);
  }

  /**
   * When the original entity id is falsy, no original title is looked up.
   */
  public function testNodeUpdateWithOriginalMissingId() {
    $entity = $this->mockNodeWithCitationField(TRUE);

    $original = $this->createMock(NodeInterface::class);
    $original->method('id')->willReturn(NULL);
    $original->expects($this->never())->method('label');
    $entity->method('getOriginal')->willReturn($original);
    $entity->method('label')->willReturn('New Title');

    $citation = $this->createMock(CitationInterface::class);
    $citation->method('label')->willReturn('');
    $citation->expects($this->once())->method('setLabel')->with('New Title');
    $citation->method('setParentEntity')->willReturnSelf();

    $citation_storage = $this->createMock(EntityStorageInterface::class);
    $citation_storage->method('load')->with(42)->willReturn($citation);
    $this->entityTypeManager->method('getStorage')->with('citation')->willReturn($citation_storage);

    $this->hooks->nodeUpdate($entity);
  }

  /**
   * If there is no citation entity, post save does nothing and does not
   * error.
   */
  public function testNodeUpdateWithoutCitationEntity() {
    $entity = $this->mockNodeWithCitationField(FALSE);
    $entity->method('getOriginal')->willReturn(NULL);

    $this->entityTypeManager->expects($this->never())->method('getStorage');
    $this->hooks->nodeUpdate($entity);
    // No exception thrown means success.
    $this->addToAssertionCount(1);
  }

  /**
   * Deleting a node also deletes its related citation entity.
   */
  public function testNodeDeleteRemovesCitation() {
    $entity = $this->mockNodeWithCitationField(TRUE);

    $citation = $this->createMock(CitationInterface::class);
    $citation->expects($this->once())->method('delete');

    $citation_storage = $this->createMock(EntityStorageInterface::class);
    $citation_storage->method('load')->with(42)->willReturn($citation);
    $this->entityTypeManager->method('getStorage')->with('citation')->willReturn($citation_storage);

    $this->hooks->nodeDelete($entity);
  }

  /**
   * Deleting a node with no citation entity does nothing.
   */
  public function testNodeDeleteWithoutCitation() {
    $entity = $this->mockNodeWithCitationField(FALSE);
    $this->hooks->nodeDelete($entity);
    $this->addToAssertionCount(1);
  }

  /**
   * Deleting a node of the wrong bundle never loads a citation.
   */
  public function testNodeDeleteWrongBundle() {
    $entity = $this->createMock(NodeInterface::class);
    $entity->method('bundle')->willReturn('page');
    $this->entityTypeManager->expects($this->never())->method('getStorage');
    $this->hooks->nodeDelete($entity);
    $this->addToAssertionCount(1);
  }

  /**
   * Missing "items" context returns early without error.
   */
  public function testFieldWidgetCompleteFormAlterNoItems() {
    $form = ['widget' => []];
    $form_state = $this->createMock(FormStateInterface::class);
    $context = [];
    $this->hooks->fieldWidgetCompleteFormAlter($form, $form_state, $context);
    $this->assertSame(['widget' => []], $form);
  }

  /**
   * A non FieldItemListInterface "items" context returns early.
   */
  public function testFieldWidgetCompleteFormAlterInvalidItemsType() {
    $form = ['widget' => []];
    $form_state = $this->createMock(FormStateInterface::class);
    $context = ['items' => 'not-a-field-item-list'];
    $this->hooks->fieldWidgetCompleteFormAlter($form, $form_state, $context);
    $this->assertSame(['widget' => []], $form);
  }

  /**
   * The citation entity title field gets a custom description.
   */
  public function testFieldWidgetCompleteFormAlterCitationTitle() {
    $form = ['widget' => [0 => ['value' => []]]];
    $form_state = $this->createMock(FormStateInterface::class);

    $citation_entity = $this->createMock(EntityInterface::class);
    $citation_entity->method('getEntityTypeId')->willReturn('citation');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('title');
    $items->method('getEntity')->willReturn($citation_entity);

    $context = ['items' => $items];
    $this->hooks->fieldWidgetCompleteFormAlter($form, $form_state, $context);
    $this->assertEquals('The title of the Publication', $form['widget'][0]['value']['#description']);
  }

  /**
   * A title field belonging to a non citation entity is untouched.
   */
  public function testFieldWidgetCompleteFormAlterNonCitationTitle() {
    $form = ['widget' => [0 => ['value' => []]]];
    $form_state = $this->createMock(FormStateInterface::class);

    $other_entity = $this->createMock(EntityInterface::class);
    $other_entity->method('getEntityTypeId')->willReturn('node');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('title');
    $items->method('getEntity')->willReturn($other_entity);

    $context = ['items' => $items];
    $this->hooks->fieldWidgetCompleteFormAlter($form, $form_state, $context);
    $this->assertArrayNotHasKey('#description', $form['widget'][0]['value']);
  }

  /**
   * An unrelated field name leaves the form untouched.
   */
  public function testFieldWidgetCompleteFormAlterUnrelatedField() {
    $form = ['widget' => []];
    $form_state = $this->createMock(FormStateInterface::class);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('some_other_field');
    $items->expects($this->never())->method('getEntity');

    $context = ['items' => $items];
    $this->hooks->fieldWidgetCompleteFormAlter($form, $form_state, $context);
    $this->assertSame(['widget' => []], $form);
  }

  /**
   * The inline entity form "Add New" button label and bundle prefix are set.
   */
  public function testFieldWidgetCompleteFormAlterPublicationCitationField() {
    $button_value = $this->getStringTranslationStub()->translate('Add new @type_singular', ['@type_singular' => 'thing']);

    $form = [
      'widget' => [
        'actions' => [
          'ief_add' => ['#value' => $button_value],
        ],
        'form' => [
          'inline_entity_form' => [
            '#entity_type' => 'citation',
            '#bundle' => 'su_book',
          ],
        ],
        '#title' => 'Publication Citation',
      ],
    ];
    $form_state = $this->createMock(FormStateInterface::class);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('su_publication_citation');

    $this->bundleInfo->method('getBundleInfo')
      ->with('citation')
      ->willReturn(['su_book' => ['label' => 'Book']]);

    $context = ['items' => $items];
    $this->hooks->fieldWidgetCompleteFormAlter($form, $form_state, $context);

    $this->assertInstanceOf(
      \Drupal\Core\StringTranslation\TranslatableMarkup::class,
      $form['widget']['actions']['ief_add']['#value']
    );
    $this->assertSame(
      'Book - Publication Citation',
      $form['widget']['form']['inline_entity_form']['#prefix']
    );
  }

  /**
   * When there is no "Add new" button or inline entity form, nothing errors.
   */
  public function testFieldWidgetCompleteFormAlterPublicationCitationFieldEmpty() {
    $form = ['widget' => []];
    $form_state = $this->createMock(FormStateInterface::class);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('su_publication_citation');

    $this->bundleInfo->expects($this->never())->method('getBundleInfo');

    $context = ['items' => $items];
    $this->hooks->fieldWidgetCompleteFormAlter($form, $form_state, $context);
    $this->assertSame(['widget' => []], $form);
  }

}
