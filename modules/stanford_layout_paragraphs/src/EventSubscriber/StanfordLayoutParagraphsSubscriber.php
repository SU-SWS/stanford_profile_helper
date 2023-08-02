<?php

namespace Drupal\stanford_layout_paragraphs\EventSubscriber;

use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Stanford layout paragraphs event subscriber.
 */
class StanfordLayoutParagraphsSubscriber implements EventSubscriberInterface {

  /**
   * Drupal core layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LayoutParagraphsAllowedTypesEvent::EVENT_NAME => 'layoutParagraphsAllowedTypes',
    ];
  }

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManager $layoutPluginManager
   *   Drupal core layout plugin manager.
   */
  public function __construct(LayoutPluginManager $layoutPluginManager) {
    $this->layoutPluginManager = $layoutPluginManager;
  }

  /**
   * Adjust the paragraphs allowed in the layout paragraphs widget.
   *
   * @param \Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent $event
   *   Layout paragraphs event.
   */
  public function layoutParagraphsAllowedTypes(LayoutParagraphsAllowedTypesEvent $event): void {
    $parent_component = $event->getLayout()
      ->getComponentByUuid($event->getParentUuid());

    // If adding a new layout, it won't have a parent.
    if ($parent_component) {

      $layout_settings = $parent_component->getSettings();
      $layout_regions = $this->layoutPluginManager
        ->getDefinition($layout_settings['layout'])->getRegions();
      if (count($layout_regions) > 1) {
        $types = $event->getTypes();
        unset($types['stanford_banner'], $types['stanford_gallery']);
        $event->setTypes($types);
      }
    }
  }

}
