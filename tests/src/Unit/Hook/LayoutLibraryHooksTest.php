<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\stanford_profile_helper\Form\LayoutLibraryIconForm;
use Drupal\stanford_profile_helper\Hook\LayoutLibraryHooks;
use Drupal\stanford_profile_helper\StanfordLayoutListBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for LayoutLibraryHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(LayoutLibraryHooks::class)]
class LayoutLibraryHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\LayoutLibraryHooks
   */
  protected LayoutLibraryHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new LayoutLibraryHooks();
  }

  /**
   * No 'layout' key present — nothing on the entity type should be touched.
   */
  public function testEntityTypeAlterNoLayoutKey() {
    $other_type = $this->createMock(EntityTypeInterface::class);
    $other_type->expects($this->never())->method('setLinkTemplate');
    $other_type->expects($this->never())->method('setFormClass');
    $other_type->expects($this->never())->method('setListBuilderClass');

    $entity_types = ['node' => $other_type];
    $this->hooks->entityTypeAlter($entity_types);
  }

  /**
   * Empty entity types array — nothing should be touched.
   */
  public function testEntityTypeAlterEmptyArray() {
    $entity_types = [];
    // No exception thrown and nothing to assert — just confirm it runs cleanly.
    $this->hooks->entityTypeAlter($entity_types);
    $this->assertEmpty($entity_types);
  }

  /**
   * 'layout' key is present — link template, form class, and list builder
   * are all updated on the layout entity type.
   */
  public function testEntityTypeAlterWithLayoutKey() {
    $editFormPath = '/admin/structure/layouts/{layout}/edit';

    $layout = $this->createMock(EntityTypeInterface::class);
    $layout->method('getLinkTemplate')
      ->with('edit-form')
      ->willReturn($editFormPath);

    $layout->expects($this->once())
      ->method('setLinkTemplate')
      ->with('edit-icon-form', $editFormPath . '/edit-icon');

    $layout->expects($this->once())
      ->method('setFormClass')
      ->with('edit-icon', LayoutLibraryIconForm::class);

    $layout->expects($this->once())
      ->method('setListBuilderClass')
      ->with(StanfordLayoutListBuilder::class);

    $entity_types = ['layout' => $layout];
    $this->hooks->entityTypeAlter($entity_types);
  }

  /**
   * 'layout' key present alongside other entity types — only the layout
   * entity type is modified; other types are untouched.
   */
  public function testEntityTypeAlterOnlyModifiesLayoutType() {
    $editFormPath = '/admin/structure/layouts/{layout}/edit';

    $layout = $this->createMock(EntityTypeInterface::class);
    $layout->method('getLinkTemplate')->willReturn($editFormPath);
    $layout->expects($this->once())->method('setLinkTemplate');
    $layout->expects($this->once())->method('setFormClass');
    $layout->expects($this->once())->method('setListBuilderClass');

    $node = $this->createMock(EntityTypeInterface::class);
    $node->expects($this->never())->method('setLinkTemplate');
    $node->expects($this->never())->method('setFormClass');
    $node->expects($this->never())->method('setListBuilderClass');

    $entity_types = ['layout' => $layout, 'node' => $node];
    $this->hooks->entityTypeAlter($entity_types);
  }

  /**
   * The edit-icon-form link template is derived from the existing edit-form
   * path — the '/edit-icon' suffix must be appended.
   */
  public function testEntityTypeAlterEditIconFormLinkTemplate() {
    $editFormPath = '/some/custom/path';

    $layout = $this->createMock(EntityTypeInterface::class);
    $layout->method('getLinkTemplate')
      ->with('edit-form')
      ->willReturn($editFormPath);

    $capturedArgs = [];
    $layout->method('setLinkTemplate')
      ->willReturnCallback(function ($rel, $path) use (&$capturedArgs) {
        $capturedArgs = [$rel, $path];
      });
    $layout->method('setFormClass')->willReturn(NULL);
    $layout->method('setListBuilderClass')->willReturn(NULL);

    $entity_types = ['layout' => $layout];
    $this->hooks->entityTypeAlter($entity_types);

    $this->assertSame('edit-icon-form', $capturedArgs[0]);
    $this->assertStringEndsWith('/edit-icon', $capturedArgs[1]);
    $this->assertStringStartsWith($editFormPath, $capturedArgs[1]);
  }

}
