<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\EventSubscriber;

use Codeception\Attribute\Group;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\State\StateInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\SectionComponent;
use Drupal\node\NodeInterface;
use Drupal\stanford_profile_helper\EventSubscriber\EntityEventSubscriber;
use Drupal\stanford_profile_helper\StanfordDefaultContentInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test the EntityEventSubscriber.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(EntityEventSubscriber::class)]
class EntityEventSubscriberTest extends UnitTestCase {

  /**
   * Test that getSubscribedEvents returns expected events.
   */
  public function testGetSubscribedEvents() {
    $events = EntityEventSubscriber::getSubscribedEvents();
    $this->assertIsArray($events);
    $this->assertArrayHasKey(\Drupal\layout_builder\LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY, $events);
    $this->assertEquals('prepareLayoutBuilderComponent', $events[\Drupal\layout_builder\LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY]);
  }

  /**
   * Test printable links block is hidden for stanford_media without transcript.
   */
  public function testPrintableLinksBlockHiddenWithoutTranscript() {
    // Mock a field item list with no items (empty transcript).
    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('count')->willReturn(0);

    // Mock a stanford_media node without a transcript.
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_media');
    $node->method('get')
      ->with('su_media_transcript')
      ->willReturn($field_list);

    // Mock the route match to return the node.
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getParameter')
      ->with('node')
      ->willReturn($node);

    // Mock entity storage with no taxonomy menus.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([]);

    // Mock entity type manager.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('taxonomy_menu')
      ->willReturn($storage);

    // Create subscriber.
    $default_content = $this->createMock(StanfordDefaultContentInterface::class);
    $state = $this->createMock(StateInterface::class);

    $subscriber = new EntityEventSubscriber(
      $default_content,
      $state,
      $entity_type_manager,
      $route_match
    );

    // Create a component with printable_links_block configuration.
    $component = $this->getMockBuilder(SectionComponent::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
    $component->method('get')
      ->with('configuration')
      ->willReturn([
        'id' => 'printable_links_block:node',
        'label' => 'Printable Links',
      ]);

    $build = ['#markup' => 'Original build'];
    $event = $this->getMockBuilder(SectionComponentBuildRenderArrayEvent::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getComponent', 'getBuild', 'setBuild'])
      ->getMock();
    $event->method('getComponent')->willReturn($component);
    $event->method('getBuild')->willReturn($build);
    $event->expects($this->once())
      ->method('setBuild')
      ->with([]);

    // Call the method.
    $subscriber->prepareLayoutBuilderComponent($event);
  }

  /**
   * Test printable links block is visible for stanford_media with transcript.
   */
  public function testPrintableLinksBlockVisibleWithTranscript() {
    // Mock a field item list with items (transcript present).
    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('count')->willReturn(1);

    // Mock a stanford_media node with a transcript.
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_media');
    $node->method('get')
      ->with('su_media_transcript')
      ->willReturn($field_list);

    // Mock the route match to return the node.
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getParameter')
      ->with('node')
      ->willReturn($node);

    // Mock entity storage with no taxonomy menus.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([]);

    // Mock entity type manager.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('taxonomy_menu')
      ->willReturn($storage);

    // Create subscriber.
    $default_content = $this->createMock(StanfordDefaultContentInterface::class);
    $state = $this->createMock(StateInterface::class);

    $subscriber = new EntityEventSubscriber(
      $default_content,
      $state,
      $entity_type_manager,
      $route_match
    );

    // Create a component with printable_links_block configuration.
    $component = $this->createMock(SectionComponent::class);
    $component->method('get')
      ->with('configuration')
      ->willReturn([
        'id' => 'printable_links_block:node',
        'label' => 'Printable Links',
      ]);

    $build = ['#markup' => 'Original build'];
    $event = $this->createMock(SectionComponentBuildRenderArrayEvent::class);
    $event->method('getComponent')->willReturn($component);
    $event->method('getBuild')->willReturn($build);
    $event->expects($this->never())
      ->method('setBuild');

    // Call the method.
    $subscriber->prepareLayoutBuilderComponent($event);
  }

  /**
   * Test printable links block is visible for non-stanford_media nodes.
   */
  public function testPrintableLinksBlockVisibleForOtherContentTypes() {
    // Mock a regular node (not stanford_media).
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_event');

    // Mock the route match to return the node.
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getParameter')
      ->with('node')
      ->willReturn($node);

    // Mock entity storage with no taxonomy menus.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([]);

    // Mock entity type manager.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('taxonomy_menu')
      ->willReturn($storage);

    // Create subscriber.
    $default_content = $this->createMock(StanfordDefaultContentInterface::class);
    $state = $this->createMock(StateInterface::class);

    $subscriber = new EntityEventSubscriber(
      $default_content,
      $state,
      $entity_type_manager,
      $route_match
    );

    // Create a component with printable_links_block configuration.
    $component = $this->createMock(SectionComponent::class);
    $component->method('get')
      ->with('configuration')
      ->willReturn([
        'id' => 'printable_links_block:node',
        'label' => 'Printable Links',
      ]);

    $build = ['#markup' => 'Original build'];
    $event = $this->createMock(SectionComponentBuildRenderArrayEvent::class);
    $event->method('getComponent')->willReturn($component);
    $event->method('getBuild')->willReturn($build);
    $event->expects($this->never())
      ->method('setBuild');

    // Call the method.
    $subscriber->prepareLayoutBuilderComponent($event);
  }

  /**
   * Test taxonomy menu blocks always have visible labels.
   */
  public function testTaxonomyMenuLabelDisplay() {
    // Mock taxonomy menu entity.
    $taxonomy_menu = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getMenu'])
      ->getMock();
    $taxonomy_menu->method('getMenu')->willReturn('test-taxonomy-menu');

    // Mock entity storage.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([$taxonomy_menu]);

    // Mock entity type manager.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('taxonomy_menu')
      ->willReturn($storage);

    // Create subscriber.
    $default_content = $this->createMock(StanfordDefaultContentInterface::class);
    $state = $this->createMock(StateInterface::class);
    $route_match = $this->createMock(RouteMatchInterface::class);

    $subscriber = new EntityEventSubscriber(
      $default_content,
      $state,
      $entity_type_manager,
      $route_match
    );

    // Create a component with taxonomy menu block configuration.
    $component = $this->createMock(SectionComponent::class);
    $component->method('get')
      ->with('configuration')
      ->willReturn([
        'id' => 'system_menu_block:test-taxonomy-menu',
        'label' => 'Test Taxonomy Menu',
        'label_display' => 'hidden',
      ]);

    $build = [
      '#configuration' => [
        'id' => 'system_menu_block:test-taxonomy-menu',
        'label_display' => 'hidden',
      ],
    ];
    $event = $this->createMock(SectionComponentBuildRenderArrayEvent::class);
    $event->method('getComponent')->willReturn($component);
    $event->method('getBuild')->willReturn($build);
    $event->expects($this->once())
      ->method('setBuild')
      ->with($this->callback(function ($arg) {
        return $arg['#configuration']['label_display'] === 'visible';
      }));

    // Call the method.
    $subscriber->prepareLayoutBuilderComponent($event);
  }

  /**
   * Test non-taxonomy menu blocks are not affected.
   */
  public function testNonTaxonomyMenuNotAffected() {
    // Mock entity storage with no taxonomy menus.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([]);

    // Mock entity type manager.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('taxonomy_menu')
      ->willReturn($storage);

    // Create subscriber.
    $default_content = $this->createMock(StanfordDefaultContentInterface::class);
    $state = $this->createMock(StateInterface::class);
    $route_match = $this->createMock(RouteMatchInterface::class);

    $subscriber = new EntityEventSubscriber(
      $default_content,
      $state,
      $entity_type_manager,
      $route_match
    );

    // Create a component with a regular menu block configuration.
    $component = $this->createMock(SectionComponent::class);
    $component->method('get')
      ->with('configuration')
      ->willReturn([
        'id' => 'system_menu_block:main',
        'label' => 'Main Menu',
        'label_display' => 'hidden',
      ]);

    $build = [
      '#configuration' => [
        'id' => 'system_menu_block:main',
        'label_display' => 'hidden',
      ],
    ];
    $event = $this->createMock(SectionComponentBuildRenderArrayEvent::class);
    $event->method('getComponent')->willReturn($component);
    $event->method('getBuild')->willReturn($build);
    $event->expects($this->never())
      ->method('setBuild');

    // Call the method.
    $subscriber->prepareLayoutBuilderComponent($event);
  }

}
