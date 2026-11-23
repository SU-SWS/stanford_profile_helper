<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_decoupled\Unit\Hook;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\next\Entity\NextEntityTypeConfigInterface;
use Drupal\stanford_decoupled\Hook\NextHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for NextHooks.
 */
#[Group('stanford_decoupled')]
class NextHooksTest extends UnitTestCase {

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The cache backend mock used by DecoupledConfigOverrides::isDecoupled().
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected CacheBackendInterface $cache;

  /**
   * The cache tags invalidator mock used by Cache::invalidateTags().
   *
   * Only the nextSiteInsert() tests actually invoke this; other tests never
   * call it, so no default expectation is set here.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected CacheTagsInvalidatorInterface $cacheTagsInvalidator;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);

    $container = new ContainerBuilder();
    $container->set('cache.default', $this->cache);
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);
  }

  /**
   * Sets whether DecoupledConfigOverrides::isDecoupled() returns TRUE/FALSE.
   */
  protected function setDecoupled(bool $decoupled): void {
    $this->cache->method('get')
      ->with('stanford_decoupled')
      ->willReturn((object) ['data' => $decoupled]);
  }

  /**
   * Inserting a next site creates all missing entity type configs.
   *
   * When none of the next_entity_type_config entities exist yet, one is
   * created for each node bundle, plus redirect, menu_link_content, and
   * each of the 5 config_pages types.
   */
  public function testNextSiteInsertCreatesAllConfigs(): void {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['library_info']);

    $nodeTypeStorage = $this->createMock(EntityStorageInterface::class);
    $nodeTypeStorage->method('loadMultiple')->willReturn([
      'page' => NULL,
      'article' => NULL,
    ]);

    $nextStorage = $this->createMock(EntityStorageInterface::class);
    $nextStorage->method('load')->willReturn(NULL);

    $createdEntity = $this->createMock(NextEntityTypeConfigInterface::class);
    // 2 node bundles + redirect + menu_link_content + 5 config_pages = 9.
    $createdEntity->expects($this->exactly(9))->method('save');
    $nextStorage->expects($this->exactly(9))->method('create')->willReturn($createdEntity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['next_entity_type_config', $nextStorage],
        ['node_type', $nodeTypeStorage],
      ]);

    $entity = $this->createMock(EntityInterface::class);
    $entity->method('id')->willReturn('main-site');

    $hooks = new NextHooks($this->entityTypeManager);
    $hooks->nextSiteInsert($entity);
  }

  /**
   * Inserting a next site does not recreate existing entity type configs.
   */
  public function testNextSiteInsertSkipsExistingConfigs(): void {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['library_info']);

    $nodeTypeStorage = $this->createMock(EntityStorageInterface::class);
    $nodeTypeStorage->method('loadMultiple')->willReturn([
      'page' => NULL,
    ]);

    $existingEntity = $this->createMock(NextEntityTypeConfigInterface::class);

    $nextStorage = $this->createMock(EntityStorageInterface::class);
    $nextStorage->method('load')->willReturn($existingEntity);
    $nextStorage->expects($this->never())->method('create');

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['next_entity_type_config', $nextStorage],
        ['node_type', $nodeTypeStorage],
      ]);

    $entity = $this->createMock(EntityInterface::class);
    $entity->method('id')->willReturn('main-site');

    $hooks = new NextHooks($this->entityTypeManager);
    $hooks->nextSiteInsert($entity);
  }

  /**
   * View operation, decoupled site: access is allowed.
   */
  public function testRedirectAccessViewDecoupled(): void {
    $this->setDecoupled(TRUE);

    $entity = $this->createMock(EntityInterface::class);
    $account = $this->createMock(AccountInterface::class);

    $hooks = new NextHooks($this->entityTypeManager);
    $result = $hooks->redirectAccess($entity, 'view', $account);

    $this->assertTrue($result->isAllowed());
  }

  /**
   * View operation, non-decoupled site: access is neutral.
   */
  public function testRedirectAccessViewNotDecoupled(): void {
    $this->setDecoupled(FALSE);

    $entity = $this->createMock(EntityInterface::class);
    $account = $this->createMock(AccountInterface::class);

    $hooks = new NextHooks($this->entityTypeManager);
    $result = $hooks->redirectAccess($entity, 'view', $account);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Non-view operation, decoupled site: access is still neutral.
   */
  public function testRedirectAccessNonViewOperation(): void {
    $this->setDecoupled(TRUE);

    $entity = $this->createMock(EntityInterface::class);
    $account = $this->createMock(AccountInterface::class);

    $hooks = new NextHooks($this->entityTypeManager);
    $result = $hooks->redirectAccess($entity, 'update', $account);

    $this->assertTrue($result->isNeutral());
  }

  /**
   * Non-node entity previews are replaced by the original build's content.
   *
   * With no toolbar live_link present in the replacement, nothing else
   * happens.
   */
  public function testNextSitePreviewAlterNonNodeEntityReplacesPreview(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('redirect');

    $preview = ['toolbar' => ['links' => ['#links' => ['live_link' => ['url' => 'should-be-replaced']]]]];
    $context = [
      'entity' => $entity,
      'original_build' => [
        0 => ['content' => ['replaced' => 'content']],
      ],
    ];

    $hooks = new NextHooks($this->entityTypeManager);
    $hooks->nextSitePreviewAlter($preview, $context);

    $this->assertSame(['replaced' => 'content'], $preview);
  }

  /**
   * Node entity previews are left as-is, not replaced with the original build.
   *
   * No toolbar live_link present, so nothing else happens.
   */
  public function testNextSitePreviewAlterNodeEntityKeepsPreview(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');

    $preview = ['some' => 'value'];
    $context = ['entity' => $entity, 'original_build' => [0 => ['content' => ['ignored' => TRUE]]]];

    $hooks = new NextHooks($this->entityTypeManager);
    $hooks->nextSitePreviewAlter($preview, $context);

    $this->assertSame(['some' => 'value'], $preview);
  }

  /**
   * Live link url query with a slug is trimmed down to slug and secret only.
   */
  public function testNextSitePreviewAlterTrimsSlugSecretQuery(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');

    $url = $this->createMock(Url::class);
    $url->method('getOptions')->willReturn([
      'query' => ['slug' => 'my-slug', 'secret' => 'my-secret', 'other' => 'ignored'],
      'other_option' => 'ignored-too',
    ]);
    $url->expects($this->once())
      ->method('setOptions')
      ->with([
        'query' => ['slug' => 'my-slug', 'secret' => 'my-secret'],
        'other_option' => 'ignored-too',
      ]);

    $preview = [
      'toolbar' => ['links' => ['#links' => ['live_link' => ['url' => $url]]]],
    ];
    $context = ['entity' => $entity, 'original_build' => []];

    $hooks = new NextHooks($this->entityTypeManager);
    $hooks->nextSitePreviewAlter($preview, $context);
  }

  /**
   * Live link url query without a slug is left untouched.
   */
  public function testNextSitePreviewAlterLeavesQueryWithoutSlug(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');

    $url = $this->createMock(Url::class);
    $url->method('getOptions')->willReturn(['query' => ['foo' => 'bar']]);
    $url->expects($this->never())->method('setOptions');

    $preview = [
      'toolbar' => ['links' => ['#links' => ['live_link' => ['url' => $url]]]],
    ];
    $context = ['entity' => $entity, 'original_build' => []];

    $hooks = new NextHooks($this->entityTypeManager);
    $hooks->nextSitePreviewAlter($preview, $context);
  }

}
