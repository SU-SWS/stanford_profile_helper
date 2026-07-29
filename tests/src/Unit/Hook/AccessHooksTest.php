<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_profile_helper\Hook\AccessHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for AccessHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(AccessHooks::class)]
class AccessHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\AccessHooks
   */
  protected AccessHooks $hooks;

  /**
   * Mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mocked route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Mocked state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->hooks = new AccessHooks($this->configFactory, $this->routeMatch, $this->state);
  }

  /**
   * Builds a node mock with an id, generated url and node-path url.
   */
  protected function mockNode(int $id, string $bundle = 'stanford_page'): NodeInterface {
    $generatedUrl = $this->createMock(GeneratedUrl::class);
    $generatedUrl->method('getGeneratedUrl')->willReturn("/random-url-$id");

    $url = $this->createMock(Url::class);
    $url->method('toString')->with(TRUE)->willReturn($generatedUrl);

    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn($id);
    $node->method('bundle')->willReturn($bundle);
    $node->method('toUrl')->willReturn($url);
    return $node;
  }

  /**
   * Mocks the system.site config with the given "page" setting.
   */
  protected function mockSiteConfig(array $pages): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('page')->willReturn($pages);
    $this->configFactory->method('get')->with('system.site')->willReturn($config);
  }

  // -----------------------------------------------------------------------
  // entityFieldAccess() — status field lock branch.
  // -----------------------------------------------------------------------

  /**
   * Default case, no matching conditions returns neutral.
   */
  public function testEntityFieldAccessDefaultNeutral(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('foo');
    $field_definition->method('getType')->willReturn('string');
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, NULL);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Status field on a locked node URL is forbidden for non-admins.
   */
  public function testEntityFieldAccessStatusFieldForbiddenForLockedPage(): void {
    $node = $this->mockNode(5);
    $this->mockSiteConfig(["/random-url-5", '/node/5']);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($node);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('status');
    $field_definition->method('getTargetEntityTypeId')->willReturn('node');
    $field_definition->method('getType')->willReturn('string');

    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated']);

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, $items);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Status field is not locked when the node urls don't match.
   */
  public function testEntityFieldAccessStatusFieldNeutralWhenNoUrlMatch(): void {
    $node = $this->mockNode(5);
    $this->mockSiteConfig(['/some/other/page']);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($node);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('status');
    $field_definition->method('getTargetEntityTypeId')->willReturn('node');
    $field_definition->method('getType')->willReturn('string');

    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated']);

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Administrators are always allowed to change the status field.
   */
  public function testEntityFieldAccessStatusFieldSkippedForAdmin(): void {
    $node = $this->mockNode(5);
    // The config should never even be consulted for admins.
    $this->configFactory->expects($this->never())->method('get');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($node);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('status');
    $field_definition->method('getTargetEntityTypeId')->willReturn('node');
    $field_definition->method('getType')->willReturn('string');

    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['administrator']);

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * View operations never trigger the status field lock branch.
   */
  public function testEntityFieldAccessStatusFieldSkippedForViewOperation(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('status');
    $field_definition->method('getType')->willReturn('string');
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, NULL);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Fields that aren't "status" never trigger the lock branch.
   */
  public function testEntityFieldAccessStatusFieldSkippedForOtherFieldName(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('title');
    $field_definition->method('getType')->willReturn('string');
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, NULL);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Entities without an id (new/unsaved) never trigger the lock branch.
   */
  public function testEntityFieldAccessStatusFieldSkippedWhenNoEntityId(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn(NULL);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($node);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('status');
    $field_definition->method('getTargetEntityTypeId')->willReturn('node');
    $field_definition->method('getType')->willReturn('string');

    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated']);

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Null $items never triggers the lock branch.
   */
  public function testEntityFieldAccessStatusFieldSkippedWhenItemsNull(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('status');
    $field_definition->method('getType')->willReturn('string');
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, NULL);
    $this->assertTrue($result->isNeutral());
  }

  // -----------------------------------------------------------------------
  // entityFieldAccess() — layout_library handler branch.
  // -----------------------------------------------------------------------

  /**
   * Layout library reference fields are forbidden without permission.
   */
  public function testEntityFieldAccessLayoutLibraryForbiddenWithoutPermission(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('layout_selection');
    $field_definition->method('getType')->willReturn('entity_reference');
    $field_definition->method('getSetting')->with('handler')->willReturn('layout_library');
    $field_definition->method('getTargetEntityTypeId')->willReturn('node');
    $field_definition->method('getTargetBundle')->willReturn('stanford_page');

    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->with('choose layout for node stanford_page')
      ->willReturn(FALSE);

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, NULL);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Layout library reference fields are neutral with permission.
   */
  public function testEntityFieldAccessLayoutLibraryNeutralWithPermission(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('layout_selection');
    $field_definition->method('getType')->willReturn('entity_reference');
    $field_definition->method('getSetting')->with('handler')->willReturn('layout_library');
    $field_definition->method('getTargetEntityTypeId')->willReturn('node');
    $field_definition->method('getTargetBundle')->willReturn('stanford_page');

    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->with('choose layout for node stanford_page')
      ->willReturn(TRUE);

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, NULL);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Non entity_reference fields never trigger the layout library branch.
   */
  public function testEntityFieldAccessLayoutLibrarySkippedForOtherType(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('layout_selection');
    $field_definition->method('getType')->willReturn('string');

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->never())->method('hasPermission');

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, NULL);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Entity reference fields with a different handler are skipped.
   */
  public function testEntityFieldAccessLayoutLibrarySkippedForOtherHandler(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('some_ref');
    $field_definition->method('getType')->willReturn('entity_reference');
    $field_definition->method('getSetting')->with('handler')->willReturn('default:node');

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->never())->method('hasPermission');

    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, NULL);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Non-edit operations never trigger the layout library branch.
   */
  public function testEntityFieldAccessLayoutLibrarySkippedForNonEditOperation(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('layout_selection');
    $field_definition->method('getType')->willReturn('entity_reference');
    $field_definition->method('getSetting')->with('handler')->willReturn('layout_library');

    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->never())->method('hasPermission');

    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, NULL);
    $this->assertTrue($result->isNeutral());
  }

  // -----------------------------------------------------------------------
  // entityFieldAccess() — page title banner branch.
  // -----------------------------------------------------------------------

  /**
   * Builds mocks for the title-field / banner scenario.
   */
  protected function mockTitleFieldScenario(string $bannerBundle, string $bundle = 'stanford_page', int $bannerCount = 1): array {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('bundle')->willReturn($bannerBundle);

    $bannerItem = new \stdClass();
    $bannerItem->entity = $paragraph;

    $bannerField = $this->createMock(FieldItemListInterface::class);
    $bannerField->method('count')->willReturn($bannerCount);
    $bannerField->method('get')->with(0)->willReturn($bannerItem);

    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn(9);
    $node->method('getEntityTypeId')->willReturn('node');
    $node->method('bundle')->willReturn($bundle);
    $node->method('get')->with('su_page_banner')->willReturn($bannerField);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($node);

    $this->routeMatch->method('getRouteName')->willReturn('entity.node.canonical');
    $routeNode = $this->createMock(NodeInterface::class);
    $routeNode->method('id')->willReturn(9);
    $this->routeMatch->method('getParameter')->with('node')->willReturn($routeNode);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('title');
    $field_definition->method('getType')->willReturn('string');

    $account = $this->createMock(AccountInterface::class);

    return [$field_definition, $account, $items];
  }

  /**
   * Title field is forbidden when the page banner is a title banner.
   */
  public function testEntityFieldAccessTitleForbiddenForTitleBanner(): void {
    [$field_definition, $account, $items] = $this->mockTitleFieldScenario('stanford_page_title_banner');
    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, $items);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Title field is allowed when the page banner is a different type.
   */
  public function testEntityFieldAccessTitleAllowedForOtherBanner(): void {
    [$field_definition, $account, $items] = $this->mockTitleFieldScenario('stanford_page_basic_banner');
    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, $items);
    $this->assertFalse($result->isForbidden());
  }

  /**
   * Non-view operations never trigger the title banner branch.
   */
  public function testEntityFieldAccessTitleSkippedForNonViewOperation(): void {
    [$field_definition, $account, $items] = $this->mockTitleFieldScenario('stanford_page_title_banner');
    $result = $this->hooks->entityFieldAccess('edit', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Fields that aren't "title" never trigger the banner branch.
   */
  public function testEntityFieldAccessTitleSkippedForOtherFieldName(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('body');
    $field_definition->method('getType')->willReturn('string');
    $account = $this->createMock(AccountInterface::class);

    $node = $this->createMock(NodeInterface::class);
    $node->method('getEntityTypeId')->willReturn('node');
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($node);

    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Non-node entities never trigger the banner branch.
   */
  public function testEntityFieldAccessTitleSkippedForNonNodeEntity(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('title');
    $field_definition->method('getType')->willReturn('string');
    $account = $this->createMock(AccountInterface::class);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getEntityTypeId')->willReturn('paragraph');
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($paragraph);

    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Nodes of other bundles never trigger the banner branch.
   */
  public function testEntityFieldAccessTitleSkippedForOtherBundle(): void {
    [$field_definition, $account, $items] = $this->mockTitleFieldScenario('stanford_page_title_banner', 'stanford_news');
    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Nodes with no page banner never trigger the banner branch.
   */
  public function testEntityFieldAccessTitleSkippedWhenNoBanner(): void {
    [$field_definition, $account, $items] = $this->mockTitleFieldScenario('stanford_page_title_banner', 'stanford_page', 0);
    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * When not on the node canonical route the banner branch is skipped.
   */
  public function testEntityFieldAccessTitleSkippedForOtherRoute(): void {
    [$field_definition, $account, $items] = $this->mockTitleFieldScenario('stanford_page_title_banner');
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn('some.other.route');
    $this->hooks = new AccessHooks($this->configFactory, $this->routeMatch, $this->state);

    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * When the route node doesn't match the field's entity, skip the branch.
   */
  public function testEntityFieldAccessTitleSkippedForDifferentRouteNode(): void {
    [$field_definition, $account, $items] = $this->mockTitleFieldScenario('stanford_page_title_banner');
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->routeMatch->method('getRouteName')->willReturn('entity.node.canonical');
    $otherNode = $this->createMock(NodeInterface::class);
    $otherNode->method('id')->willReturn(123);
    $this->routeMatch->method('getParameter')->with('node')->willReturn($otherNode);
    $this->hooks = new AccessHooks($this->configFactory, $this->routeMatch, $this->state);

    $result = $this->hooks->entityFieldAccess('view', $field_definition, $account, $items);
    $this->assertTrue($result->isNeutral());
  }

  // -----------------------------------------------------------------------
  // nodeAccess()
  // -----------------------------------------------------------------------

  /**
   * Deleting a node configured as a special page (home/403/404) is forbidden.
   */
  public function testNodeAccessDeleteForbiddenForSpecialPage(): void {
    $node = $this->mockNode(3);
    $this->mockSiteConfig(['/node/3']);
    $this->state->method('get')->willReturn([]);
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->nodeAccess($node, 'delete', $account);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Deleting a node not configured as a special page is neutral.
   */
  public function testNodeAccessDeleteNeutralForOrdinaryPage(): void {
    $node = $this->mockNode(3);
    $this->mockSiteConfig(['/node/999']);
    $this->state->method('get')->willReturn([]);
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->nodeAccess($node, 'delete', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Non-delete operations skip the special page check entirely.
   */
  public function testNodeAccessNonDeleteSkipsSpecialPageCheck(): void {
    $node = $this->mockNode(3);
    $this->configFactory->expects($this->never())->method('get');
    $this->state->method('get')->willReturn([]);
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->nodeAccess($node, 'update', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Locked nodes forbid anonymous viewing.
   */
  public function testNodeAccessLockedNodeViewForbiddenForAnonymous(): void {
    $node = $this->mockNode(7);
    $this->state->method('get')
      ->with('stanford_profile_helper.locked_admin_nodes', [])
      ->willReturn([7]);
    $account = $this->createMock(AccountInterface::class);
    $account->method('isAnonymous')->willReturn(TRUE);

    $result = $this->hooks->nodeAccess($node, 'view', $account);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Locked nodes allow viewing by authenticated users.
   */
  public function testNodeAccessLockedNodeViewNeutralForAuthenticated(): void {
    $node = $this->mockNode(7);
    $this->state->method('get')
      ->with('stanford_profile_helper.locked_admin_nodes', [])
      ->willReturn([7]);
    $account = $this->createMock(AccountInterface::class);
    $account->method('isAnonymous')->willReturn(FALSE);

    $result = $this->hooks->nodeAccess($node, 'view', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Locked nodes forbid all non-view operations outright.
   */
  public function testNodeAccessLockedNodeNonViewForbidden(): void {
    $node = $this->mockNode(7);
    $this->state->method('get')
      ->with('stanford_profile_helper.locked_admin_nodes', [])
      ->willReturn([7]);
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->nodeAccess($node, 'update', $account);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Nodes that are not locked and not special pages are neutral.
   */
  public function testNodeAccessNeutralByDefault(): void {
    $node = $this->mockNode(42);
    $this->state->method('get')
      ->with('stanford_profile_helper.locked_admin_nodes', [])
      ->willReturn([]);
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->nodeAccess($node, 'update', $account);
    $this->assertTrue($result->isNeutral());
  }

}
