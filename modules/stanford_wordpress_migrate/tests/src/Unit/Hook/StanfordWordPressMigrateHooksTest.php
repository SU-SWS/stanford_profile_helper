<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Hook;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\stanford_wordpress_migrate\Hook\StanfordWordPressMigrateHooks;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for StanfordWordPressMigrateHooks.
 */
class StanfordWordPressMigrateHooksTest extends UnitTestCase {

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Hook\StanfordWordPressMigrateHooks
   */
  protected $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new StanfordWordPressMigrateHooks();
  }

  /**
   * Test hook class construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(StanfordWordPressMigrateHooks::class, $this->hooks);
  }

  /**
   * Test entityTypeAlter method with devel-load link template.
   */
  public function testEntityTypeAlterWithDevelLoad(): void {
    $entityType = $this->createMock(EntityTypeInterface::class);

    // Set up the mock to return TRUE only for devel-load check
    $entityType->method('hasLinkTemplate')
      ->willReturnCallback(function($template) {
        return $template === 'devel-load';
      });

    $entityType->expects($this->once())
      ->method('setLinkTemplate')
      ->with('devel-load', '/devel/wordpress-migration/{wordpress_migration}');

    $entity_types = ['wordpress_migration' => $entityType];

    $this->hooks->entityTypeAlter($entity_types);
  }

  /**
   * Test entityTypeAlter method with devel-definition link template.
   */
  public function testEntityTypeAlterWithDevelDefinition(): void {
    $entityType = $this->createMock(EntityTypeInterface::class);

    $entityType->method('hasLinkTemplate')
      ->willReturnMap([
        ['devel-load', FALSE],
        ['devel-definition', TRUE],
        ['devel-load-with-references', FALSE],
        ['devel-path-alias', FALSE],
      ]);

    $entityType->expects($this->once())
      ->method('setLinkTemplate')
      ->with('devel-definition', '/devel/definition/wordpress-migration/{wordpress_migration}');

    $entity_types = ['wordpress_migration' => $entityType];

    $this->hooks->entityTypeAlter($entity_types);
  }

  /**
   * Test entityTypeAlter method with devel-load-with-references link template.
   */
  public function testEntityTypeAlterWithDevelLoadWithReferences(): void {
    $entityType = $this->createMock(EntityTypeInterface::class);

    $entityType->method('hasLinkTemplate')
      ->willReturnMap([
        ['devel-load', FALSE],
        ['devel-definition', FALSE],
        ['devel-load-with-references', TRUE],
        ['devel-path-alias', FALSE],
      ]);

    $entityType->expects($this->once())
      ->method('setLinkTemplate')
      ->with('devel-load-with-references', '/devel/load-with-references/wordpress-migration/{wordpress_migration}/');

    $entity_types = ['wordpress_migration' => $entityType];

    $this->hooks->entityTypeAlter($entity_types);
  }

  /**
   * Test entityTypeAlter method with devel-path-alias link template.
   */
  public function testEntityTypeAlterWithDevelPathAlias(): void {
    $entityType = $this->createMock(EntityTypeInterface::class);

    $entityType->method('hasLinkTemplate')
      ->willReturnMap([
        ['devel-load', FALSE],
        ['devel-definition', FALSE],
        ['devel-load-with-references', FALSE],
        ['devel-path-alias', TRUE],
      ]);

    $entityType->expects($this->once())
      ->method('setLinkTemplate')
      ->with('devel-path-alias', '/devel/path/alias/wordpress-migration/{wordpress_migration}');

    $entity_types = ['wordpress_migration' => $entityType];

    $this->hooks->entityTypeAlter($entity_types);
  }

  /**
   * Test entityTypeAlter method with all devel link templates.
   */
  public function testEntityTypeAlterWithAllDevelTemplates(): void {
    $entityType = $this->createMock(EntityTypeInterface::class);

    $entityType->method('hasLinkTemplate')
      ->willReturn(TRUE);

    $entityType->expects($this->exactly(4))
      ->method('setLinkTemplate')
      ->willReturnCallback(function($template, $path) {
        $expected = [
          'devel-load' => '/devel/wordpress-migration/{wordpress_migration}',
          'devel-definition' => '/devel/definition/wordpress-migration/{wordpress_migration}',
          'devel-load-with-references' => '/devel/load-with-references/wordpress-migration/{wordpress_migration}/',
          'devel-path-alias' => '/devel/path/alias/wordpress-migration/{wordpress_migration}',
        ];
        $this->assertArrayHasKey($template, $expected);
        $this->assertEquals($expected[$template], $path);
      });

    $entity_types = ['wordpress_migration' => $entityType];

    $this->hooks->entityTypeAlter($entity_types);
  }

  /**
   * Test entityTypeAlter method with no devel link templates.
   */
  public function testEntityTypeAlterWithNoDevelTemplates(): void {
    $entityType = $this->createMock(EntityTypeInterface::class);

    $entityType->method('hasLinkTemplate')
      ->willReturn(FALSE);

    $entityType->expects($this->never())
      ->method('setLinkTemplate');

    $entity_types = ['wordpress_migration' => $entityType];

    $this->hooks->entityTypeAlter($entity_types);
  }

}
