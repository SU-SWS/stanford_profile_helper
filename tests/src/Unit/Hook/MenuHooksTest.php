<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\stanford_profile_helper\Hook\MenuHooks;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy_menu\Plugin\Menu\TaxonomyMenuMenuLink;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for MenuHooks.
 */
#[Group('stanford_profile_helper')]
class MenuHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\MenuHooks
   */
  protected MenuHooks $hooks;

  /**
   * Mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->hooks = new MenuHooks($this->currentUser, $this->entityTypeManager, $this->createMock(Connection::class), $this->createMock(RouteBuilderInterface::class));
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Calls the protected checkAdminMenuAccess() method via reflection.
   */
  protected function callCheckAdminMenuAccess(array &$menu_items): void {
    $method = new \ReflectionMethod(MenuHooks::class, 'checkAdminMenuAccess');
    $method->invokeArgs($this->hooks, [&$menu_items]);
  }

  /**
   * Builds a mock Url whose route parameters are the given array.
   */
  protected function mockUrl(array $routeParameters): Url {
    $url = $this->createMock(Url::class);
    $url->method('getRouteParameters')->willReturn($routeParameters);
    return $url;
  }

  /**
   * All three known link ids get altered when present.
   */
  public function testMenuLinksDiscoveredAlterWithAllLinksPresent(): void {
    $links = [
      'admin_toolbar_tools.extra_links:node.add.stanford_page' => ['weight' => 0],
      'admin_toolbar_tools.extra_links:media_page' => ['title' => 'Media'],
      'system.admin_content' => ['title' => 'Content'],
    ];

    $this->hooks->menuLinksDiscoveredAlter($links);

    $this->assertSame(-99, $links['admin_toolbar_tools.extra_links:node.add.stanford_page']['weight']);
    $this->assertSame('All Media', (string) $links['admin_toolbar_tools.extra_links:media_page']['title']);
    $this->assertSame('All Content', (string) $links['system.admin_content']['title']);
  }

  /**
   * Nothing happens when none of the known link ids are present.
   */
  public function testMenuLinksDiscoveredAlterWithNoLinksPresent(): void {
    $links = ['some.other.link' => ['title' => 'Other']];
    $this->hooks->menuLinksDiscoveredAlter($links);
    $this->assertSame(['some.other.link' => ['title' => 'Other']], $links);
  }

  /**
   * PreprocessMenu on a non-admin menu never checks admin access permissions.
   */
  public function testPreprocessMenuNonAdminMenuSkipsAccessCheck(): void {
    $this->currentUser->expects($this->never())->method('hasPermission');

    $variables = [
      'menu_name' => 'main',
      'items' => [],
    ];
    $this->hooks->preprocessMenu($variables);
    $this->assertSame([], $variables['#cache']['tags']);
  }

  /**
   * Items whose original_link is not a taxonomy menu link are left alone.
   */
  public function testPreprocessMenuIgnoresNonTaxonomyMenuLinkItems(): void {
    $url = $this->createMock(Url::class);
    $url->expects($this->never())->method('getOption');

    $variables = [
      'menu_name' => 'main',
      'items' => [
        ['original_link' => new \stdClass(), 'url' => $url],
      ],
    ];
    $this->hooks->preprocessMenu($variables);
    $this->assertSame([], $variables['#cache']['tags']);
  }

  /**
   * Taxonomy menu links lose their title attribute and get the term cache tags.
   */
  public function testPreprocessMenuTaxonomyMenuLinkWithExistingTerm(): void {
    /** @var \Drupal\taxonomy_menu\Plugin\Menu\TaxonomyMenuMenuLink $link */
    $link = $this->createMock(TaxonomyMenuMenuLink::class);

    $url = $this->createMock(Url::class);
    $url->method('getOption')->with('attributes')->willReturn(['title' => 'Some description', 'class' => ['foo']]);
    $url->expects($this->once())
      ->method('setOption')
      ->with('attributes', ['class' => ['foo']]);
    $url->method('getRouteParameters')->willReturn(['taxonomy_term' => 7]);

    $term = $this->createMock(TermInterface::class);
    $term->method('bundle')->willReturn('tags');
    $term->method('getCacheTags')->willReturn(['taxonomy_term:7']);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(7)->willReturn($term);
    $this->entityTypeManager->method('getStorage')->with('taxonomy_term')->willReturn($storage);

    $variables = [
      'menu_name' => 'main',
      'items' => [
        ['original_link' => $link, 'url' => $url],
      ],
    ];
    $this->hooks->preprocessMenu($variables);

    $this->assertContains('taxonomy_term_list:tags', $variables['#cache']['tags']);
    $this->assertContains('taxonomy_term:7', $variables['#cache']['tags']);
  }

  /**
   * A missing term adds no cache tags but the title attribute is stripped.
   */
  public function testPreprocessMenuTaxonomyMenuLinkWithMissingTerm(): void {
    $link = $this->createMock(TaxonomyMenuMenuLink::class);

    $url = $this->createMock(Url::class);
    $url->method('getOption')->with('attributes')->willReturn(['title' => 'Some description']);
    $url->expects($this->once())->method('setOption')->with('attributes', []);
    $url->method('getRouteParameters')->willReturn(['taxonomy_term' => 999]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')->with('taxonomy_term')->willReturn($storage);

    $variables = [
      'menu_name' => 'main',
      'items' => [
        ['original_link' => $link, 'url' => $url],
      ],
    ];
    $this->hooks->preprocessMenu($variables);

    $this->assertSame([], $variables['#cache']['tags']);
  }

  /**
   * Existing cache tags are preserved and de-duplicated.
   */
  public function testPreprocessMenuPreservesExistingCacheTagsAndDeduplicates(): void {
    $variables = [
      'menu_name' => 'main',
      'items' => [],
      '#cache' => ['tags' => ['foo', 'foo', 'bar']],
    ];
    $this->hooks->preprocessMenu($variables);
    $this->assertSame(['foo', 'bar'], array_values($variables['#cache']['tags']));
  }

  /**
   * On the admin menu, top level items without permission are removed.
   */
  public function testPreprocessMenuOnAdminMenuChecksAccess(): void {
    $this->currentUser->method('hasPermission')->willReturn(FALSE);

    $url = $this->mockUrl(['taxonomy_vocabulary' => 'tags']);
    $variables = [
      'menu_name' => 'admin',
      'items' => [
        'remove_me' => ['url' => $url, 'below' => []],
      ],
    ];
    $this->hooks->preprocessMenu($variables);

    $this->assertArrayNotHasKey('remove_me', $variables['items']);
  }

  /**
   * Items without a taxonomy_vocabulary route parameter are always kept.
   */
  public function testCheckAdminMenuAccessKeepsItemsWithoutVocabulary(): void {
    $this->currentUser->expects($this->never())->method('hasPermission');

    $menu_items = [
      'settings' => ['url' => $this->mockUrl([]), 'below' => []],
    ];
    $this->callCheckAdminMenuAccess($menu_items);

    $this->assertArrayHasKey('settings', $menu_items);
  }

  /**
   * Users with 'administer taxonomy' keep all vocabulary items.
   */
  public function testCheckAdminMenuAccessKeepsItemsWhenUserIsTaxonomyAdmin(): void {
    $this->currentUser->method('hasPermission')
      ->willReturnCallback(fn($perm) => $perm === 'administer taxonomy');

    $menu_items = [
      'terms' => ['url' => $this->mockUrl(['taxonomy_vocabulary' => 'tags']), 'below' => []],
    ];
    $this->callCheckAdminMenuAccess($menu_items);

    $this->assertArrayHasKey('terms', $menu_items);
  }

  /**
   * An item is removed when the user has none of the required permissions.
   *
   * Its children are never visited due to the early continue.
   */
  public function testCheckAdminMenuAccessRemovesItemsWithoutAnyPermission(): void {
    $this->currentUser->method('hasPermission')->willReturn(FALSE);

    $childUrl = $this->createMock(Url::class);
    $childUrl->expects($this->never())->method('getRouteParameters');

    $menu_items = [
      'terms' => [
        'url' => $this->mockUrl(['taxonomy_vocabulary' => 'tags']),
        'below' => [
          'child' => ['url' => $childUrl, 'below' => []],
        ],
      ],
    ];
    $this->callCheckAdminMenuAccess($menu_items);

    $this->assertArrayNotHasKey('terms', $menu_items);
  }

  /**
   * Items with a create/edit/delete permission for the vocabulary are kept.
   *
   * Their children are still recursed into and potentially removed.
   */
  public function testCheckAdminMenuAccessKeepsSpecificPermissionAndRecursesIntoChildren(): void {
    $this->currentUser->method('hasPermission')
      ->willReturnCallback(fn($perm) => $perm === 'create terms in tags');

    $menu_items = [
      'terms' => [
        'url' => $this->mockUrl(['taxonomy_vocabulary' => 'tags']),
        'below' => [
          'child' => ['url' => $this->mockUrl(['taxonomy_vocabulary' => 'other']), 'below' => []],
        ],
      ],
    ];
    $this->callCheckAdminMenuAccess($menu_items);

    $this->assertArrayHasKey('terms', $menu_items);
    $this->assertArrayNotHasKey('child', $menu_items['terms']['below']);
  }

  /**
   * The system menu block gets the menu tag and loses node/menu config tags.
   */
  public function testBlockBuildAlterForSystemMenuBlock(): void {
    $block = $this->createMock(BlockPluginInterface::class);
    $block->method('getBaseId')->willReturn('system_menu_block');

    $build = [
      '#cache' => [
        'tags' => ['node:1', 'config:system.menu.main', 'keep_me'],
      ],
    ];
    $this->hooks->blockBuildAlter($build, $block);

    $this->assertContains('stanford_profile_helper:menu_links', $build['#cache']['tags']);
    $this->assertContains('keep_me', $build['#cache']['tags']);
    $this->assertNotContains('node:1', $build['#cache']['tags']);
    $this->assertNotContains('config:system.menu.main', $build['#cache']['tags']);
  }

  /**
   * Other block types are left completely untouched.
   */
  public function testBlockBuildAlterForOtherBlocks(): void {
    $block = $this->createMock(BlockPluginInterface::class);
    $block->method('getBaseId')->willReturn('some_other_block');

    $build = ['#cache' => ['tags' => ['node:1']]];
    $this->hooks->blockBuildAlter($build, $block);

    $this->assertArrayNotHasKey('stanford_profile_helper:menu_links', array_flip($build['#cache']['tags'] ?? []));
    $this->assertSame(['node:1'], $build['#cache']['tags']);
  }

  /**
   * The main block gets the menu tag and loses only system menu config tags.
   */
  public function testPreprocessBlockSystemMainBlock(): void {
    $variables = [
      'content' => [
        '#cache' => ['tags' => ['node:1', 'config:system.menu.main']],
      ],
    ];
    $this->hooks->preprocessBlockSystemMainBlock($variables);

    $this->assertContains('stanford_profile_helper:menu_links', $variables['content']['#cache']['tags']);
    $this->assertContains('node:1', $variables['content']['#cache']['tags']);
    $this->assertNotContains('config:system.menu.main', $variables['content']['#cache']['tags']);
  }

  /**
   * The menu block gets the menu tag and loses node and menu config tags.
   */
  public function testPreprocessBlockSystemMenuBlock(): void {
    $variables = [
      'content' => [
        '#cache' => ['tags' => ['node:1', 'config:system.menu.main', 'keep_me']],
      ],
    ];
    $this->hooks->preprocessBlockSystemMenuBlock($variables);

    $this->assertContains('stanford_profile_helper:menu_links', $variables['content']['#cache']['tags']);
    $this->assertContains('keep_me', $variables['content']['#cache']['tags']);
    $this->assertNotContains('node:1', $variables['content']['#cache']['tags']);
    $this->assertNotContains('config:system.menu.main', $variables['content']['#cache']['tags']);
  }

  /**
   * Non-node entities are ignored entirely.
   */
  public function testEntityTrashDeleteIgnoresNonNodeEntities(): void {
    $entity = $this->createMock(EntityInterface::class);
    // No exception, and nothing to assert other than a clean run since no
    // services are touched.
    $this->hooks->entityTrashDelete($entity);
    $this->addToAssertionCount(1);
  }

  /**
   * Nodes without the field_menulink field are ignored.
   */
  public function testEntityTrashDeleteIgnoresNodesWithoutMenuLinkField(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('field_menulink')->willReturn(FALSE);
    $node->expects($this->never())->method('get');

    $this->hooks->entityTrashDelete($node);
    $this->addToAssertionCount(1);
  }

  /**
   * Nodes with an empty field_menulink field are ignored.
   */
  public function testEntityTrashDeleteIgnoresNodesWithEmptyMenuLinkField(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('isEmpty')->willReturn(TRUE);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('field_menulink')->willReturn(TRUE);
    $node->method('get')->with('field_menulink')->willReturn($field);

    $this->hooks->entityTrashDelete($node);
    $this->addToAssertionCount(1);
  }

  /**
   * Trashing a node with a menu link deletes it and clears the menu caches.
   */
  public function testEntityTrashDeleteDeletesMenuLinkForPopulatedField(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('isEmpty')->willReturn(FALSE);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('field_menulink')->willReturn(TRUE);
    $node->method('get')->with('field_menulink')->willReturn($field);
    $node->method('id')->willReturn(42);

    $delete = $this->createMock(Delete::class);
    $delete->method('condition')->willReturnSelf();
    $delete->expects($this->once())->method('execute');

    $database = $this->createMock(Connection::class);
    $database->expects($this->once())
      ->method('delete')
      ->with('menu_tree')
      ->willReturn($delete);

    $routerBuilder = $this->createMock(RouteBuilderInterface::class);
    $routerBuilder->expects($this->once())->method('rebuildIfNeeded');

    $cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['stanford_profile_helper:menu_links']);

    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $eventDispatcher->expects($this->once())->method('dispatch');

    $this->hooks = new MenuHooks($this->currentUser, $this->entityTypeManager, $database, $routerBuilder);

    $container = new ContainerBuilder();
    $container->set('cache_tags.invalidator', $cacheTagsInvalidator);
    $container->set('event_dispatcher', $eventDispatcher);
    \Drupal::setContainer($container);

    $this->hooks->entityTrashDelete($node);
  }

}
