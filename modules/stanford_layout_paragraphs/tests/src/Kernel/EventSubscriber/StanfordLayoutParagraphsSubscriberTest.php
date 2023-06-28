<?php

namespace Drupal\Tests\stanford_layout_paragraphs\Kernel\EventSubscriber;

use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;
use Drupal\layout_paragraphs\LayoutParagraphsComponent;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Test event subscriber.
 */
class StanfordLayoutParagraphsSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'layout_discovery',
    'paragraphs',
    'layout_paragraphs',
    'stanford_layout_paragraphs',
  ];

  /**
   * Test the behavior plugin.
   */
  public function testEventSubscriber() {
    $types = [
      'stanford_banner' => ['is_section' => FALSE],
      'foo' => ['is_section' => FALSE],
    ];

    $layout = $this->createMock(LayoutParagraphsLayout::class);
    $layout->method('getSettings')->willReturn([
      'require_layouts' => FALSE,
      'nesting_depth' => 0,
    ]);
    $layout->method('getComponentByUuid')
      ->will($this->returnCallback(function($arg) {
        if ($arg == 'foo') {
          $component = $this->createMock(LayoutParagraphsComponent::class);
          $component->method('getSettings')
            ->wiLlreturn(['layout' => 'layout_paragraphs_2_column']);
          return $component;
        }
      }));

    $context = ['parent_uuid' => 'foo', 'region' => 'foo'];
    $event = new LayoutParagraphsAllowedTypesEvent($types, $layout, $context);
    // Get the event_dispatcher service and dispatch the event.
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event, LayoutParagraphsAllowedTypesEvent::EVENT_NAME);

    $this->assertArrayNotHasKey('stanford_banner', $event->getTypes());
    $this->assertArrayHasKey('foo', $event->getTypes());
  }

}
