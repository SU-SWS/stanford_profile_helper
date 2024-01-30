<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\EventSubscriber;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\SectionComponent;
use Drupal\stanford_profile_helper\EventSubscriber\EntityEventSubscriber;
use Drupal\stanford_profile_helper\StanfordDefaultContentInterface;
use Drupal\taxonomy_menu\TaxonomyMenuInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Test the event subscriber.
 *
 * @coversDefaultClass \Drupal\stanford_profile_helper\EventSubscriber\EntityEventSubscriber
 */
class EntityEventSubscriberTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();

    $blockManager = $this->createMock(BlockManagerInterface::class);


    $tax_menu = $this->createMock(TaxonomyMenuInterface::class);
    $tax_menu->method('getMenu')->willReturn('foo-bar-baz');
    $entityStorage = $this->createMock(EntityStorageInterface::class);
    $entityStorage->method('loadMultiple')->willReturn([$tax_menu]);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entityStorage);

    $container->set('plugin.manager.block', $blockManager);
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

  public function testLayoutBuilderSections() {
    $event_subscriber = new EntityEventSubscriber($this->createMock(StanfordDefaultContentInterface::class), $this->createMock(StateInterface::class), \Drupal::entityTypeManager());

    // Unrelated component should not be altered at all.
    $component = new SectionComponent('foobar', 'main', ['id' => 'foo']);
    $event = new SectionComponentBuildRenderArrayEvent($component, []);
    $event_subscriber->prepareLayoutBuilderComponent($event);
    $this->assertEmpty($event->getBuild());

    // A component that is a taxonomy menu should set the label display.
    $component = new SectionComponent('foobar', 'main', ['id' => 'system_menu_block:foo-bar-baz']);
    $event = new SectionComponentBuildRenderArrayEvent($component, []);
    $event_subscriber->prepareLayoutBuilderComponent($event);
    $this->assertEquals(['#configuration' => ['label_display' => 'visible']], $event->getBuild());
  }

}
