<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_person_importer\Unit\Hook;

use Drupal\config_pages\ConfigPagesInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_migrate\StanfordMigrateInterface;
use Drupal\stanford_person_importer\CapInterface;
use Drupal\stanford_person_importer\Hook\ImportPresaveHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ImportPresaveHooks.
 */
#[Group('stanford_person_importer')]
#[CoversClass(ImportPresaveHooks::class)]
class ImportPresaveHooksTest extends UnitTestCase {

  /**
   * Sets a container with the given (or default mocked) services.
   *
   * @return array{
   *   stanford_migrate: \PHPUnit\Framework\MockObject\MockObject&\Drupal\stanford_migrate\StanfordMigrateInterface,
   *   cap: \PHPUnit\Framework\MockObject\MockObject&\Drupal\stanford_person_importer\CapInterface,
   *   invalidator: \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Cache\CacheTagsInvalidatorInterface,
   * }
   *   The mocked services keyed by short name.
   */
  protected function setUpContainer(): array {
    $stanford_migrate = $this->createMock(StanfordMigrateInterface::class);
    $cap = $this->createMock(CapInterface::class);
    $cap->method('setClientId')->willReturnSelf();
    $cap->method('setClientSecret')->willReturnSelf();
    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);

    $container = new ContainerBuilder();
    $container->set('stanford_migrate', $stanford_migrate);
    $container->set('stanford_person_importer.cap', $cap);
    $container->set('cache_tags.invalidator', $invalidator);
    \Drupal::setContainer($container);

    return [
      'stanford_migrate' => $stanford_migrate,
      'cap' => $cap,
      'invalidator' => $invalidator,
    ];
  }

  /**
   * A node without the photo field is left completely untouched.
   */
  public function testNodePresaveNoPhotoField() {
    $services = $this->setUpContainer();
    $services['stanford_migrate']->expects($this->never())->method('getEntityMigration');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->never())->method('getStorage');

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_person_photo')->willReturn(FALSE);
    $node->expects($this->never())->method('set');

    $hooks = new ImportPresaveHooks($entity_type_manager);
    $hooks->nodePresave($node);
  }

  /**
   * A node that was not created by a migration is left untouched.
   */
  public function testNodePresaveNoMigration() {
    $services = $this->setUpContainer();
    $services['stanford_migrate']->method('getEntityMigration')->willReturn(NULL);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->never())->method('getStorage');

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_person_photo')->willReturn(TRUE);
    $node->expects($this->never())->method('set');

    $hooks = new ImportPresaveHooks($entity_type_manager);
    $hooks->nodePresave($node);
  }

  /**
   * A migrated node that already has a valid media photo is left alone.
   */
  public function testNodePresaveExistingValidPhoto() {
    $services = $this->setUpContainer();
    $services['stanford_migrate']->method('getEntityMigration')->willReturn($this->createMock(\Drupal\migrate\Plugin\MigrationInterface::class));

    $media = $this->createMock(MediaInterface::class);
    $media_storage = $this->createMock(EntityStorageInterface::class);
    $media_storage->method('load')->with(123)->willReturn($media);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('media')->willReturn($media_storage);

    $photo_items = $this->createMock(FieldItemListInterface::class);
    $photo_items->method('getValue')->willReturn([['target_id' => 123]]);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_person_photo')->willReturn(TRUE);
    $node->method('get')->with('su_person_photo')->willReturn($photo_items);
    $node->expects($this->never())->method('set');

    $hooks = new ImportPresaveHooks($entity_type_manager);
    $hooks->nodePresave($node);
  }

  /**
   * A migrated node with no photo values at all gets the default photo set.
   */
  public function testNodePresaveEmptyPhotoValuesSetsDefault() {
    $services = $this->setUpContainer();
    $services['stanford_migrate']->method('getEntityMigration')->willReturn($this->createMock(\Drupal\migrate\Plugin\MigrationInterface::class));

    $media_storage = $this->createMock(EntityStorageInterface::class);
    $media_storage->expects($this->never())->method('load');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('media')->willReturn($media_storage);

    $photo_items = $this->createMock(FieldItemListInterface::class);
    $photo_items->method('getValue')->willReturn([]);

    $default_photo = ['target_id' => 0];
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getDefaultValue')->willReturn($default_photo);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_person_photo')->willReturn(TRUE);
    $node->method('get')->with('su_person_photo')->willReturn($photo_items);
    $node->method('getFieldDefinition')->with('su_person_photo')->willReturn($field_definition);
    $node->expects($this->once())->method('set')->with('su_person_photo', $default_photo);

    $hooks = new ImportPresaveHooks($entity_type_manager);
    $hooks->nodePresave($node);
  }

  /**
   * A migrated node whose photo values all fail to load gets the default
   * photo set after exhausting the loop.
   */
  public function testNodePresaveNoValidPhotoSetsDefault() {
    $services = $this->setUpContainer();
    $services['stanford_migrate']->method('getEntityMigration')->willReturn($this->createMock(\Drupal\migrate\Plugin\MigrationInterface::class));

    $media_storage = $this->createMock(EntityStorageInterface::class);
    $media_storage->method('load')->willReturn(NULL);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('media')->willReturn($media_storage);

    $photo_items = $this->createMock(FieldItemListInterface::class);
    $photo_items->method('getValue')->willReturn([
      ['target_id' => 1],
      ['target_id' => 2],
    ]);

    $default_photo = ['target_id' => 0];
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getDefaultValue')->willReturn($default_photo);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_person_photo')->willReturn(TRUE);
    $node->method('get')->with('su_person_photo')->willReturn($photo_items);
    $node->method('getFieldDefinition')->with('su_person_photo')->willReturn($field_definition);
    $node->expects($this->once())->method('set')->with('su_person_photo', $default_photo);

    $hooks = new ImportPresaveHooks($entity_type_manager);
    $hooks->nodePresave($node);
  }

  /**
   * Get a mocked taxonomy term query that returns the given execute() result.
   */
  protected function getTermStorage(array $query_result): EntityStorageInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn($query_result);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    return $storage;
  }

  /**
   * Config pages entities of other bundles are ignored entirely.
   */
  public function testConfigPagesPresaveOtherBundle() {
    $services = $this->setUpContainer();
    $services['invalidator']->expects($this->never())->method('invalidateTags');
    $services['cap']->expects($this->never())->method('updateOrganizations');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->never())->method('getStorage');

    $entity = $this->createMock(ConfigPagesInterface::class);
    $entity->method('bundle')->willReturn('some_other_bundle');

    $hooks = new ImportPresaveHooks($entity_type_manager);
    $hooks->configPagesPresave($entity);
  }

  /**
   * When no org code terms exist yet, the CAP service updates organizations
   * and the migration plugin cache is invalidated.
   */
  public function testConfigPagesPresaveNoExistingTermsUpdatesOrgs() {
    $services = $this->setUpContainer();
    $services['cap']->expects($this->once())->method('setClientId')->with('user1');
    $services['cap']->expects($this->once())->method('setClientSecret')->with('pass1');
    $services['cap']->expects($this->once())->method('updateOrganizations');
    $services['invalidator']->expects($this->once())
      ->method('invalidateTags')
      ->with(['migration_plugins']);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($this->getTermStorage([]));

    $username_item = $this->createMock(FieldItemListInterface::class);
    $username_item->method('getString')->willReturn('user1');
    $password_item = $this->createMock(FieldItemListInterface::class);
    $password_item->method('getString')->willReturn('pass1');

    $entity = $this->createMock(ConfigPagesInterface::class);
    $entity->method('bundle')->willReturn('stanford_person_importer');
    $entity->method('get')->willReturnMap([
      ['su_person_cap_username', $username_item],
      ['su_person_cap_password', $password_item],
    ]);

    $hooks = new ImportPresaveHooks($entity_type_manager);
    $hooks->configPagesPresave($entity);
  }

  /**
   * When org code terms already exist, the CAP service is not called again
   * but the migration plugin cache is still invalidated.
   */
  public function testConfigPagesPresaveExistingTermsSkipsUpdate() {
    $services = $this->setUpContainer();
    $services['cap']->expects($this->never())->method('updateOrganizations');
    $services['invalidator']->expects($this->once())
      ->method('invalidateTags')
      ->with(['migration_plugins']);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($this->getTermStorage([1, 2]));

    $entity = $this->createMock(ConfigPagesInterface::class);
    $entity->method('bundle')->willReturn('stanford_person_importer');

    $hooks = new ImportPresaveHooks($entity_type_manager);
    $hooks->configPagesPresave($entity);
  }

}
