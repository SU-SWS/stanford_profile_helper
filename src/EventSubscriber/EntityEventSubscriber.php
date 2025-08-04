<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\stanford_profile_helper\StanfordDefaultContentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Entity event subscriber service.
 */
class EntityEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY => 'prepareLayoutBuilderComponent',
    ];
  }

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\stanford_profile_helper\StanfordDefaultContentInterface $defaultContent
   *   Default content importer service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Core entity type manager service.
   */
  public function __construct(protected StanfordDefaultContentInterface $defaultContent, protected StateInterface $state, protected EntityTypeManagerInterface $entityTypeManager) {}


  /**
   * Modify the component build for layout builder.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   Triggered event.
   */
  public function prepareLayoutBuilderComponent(SectionComponentBuildRenderArrayEvent $event) {
    $menus = self::getTaxonomyMenuIds();

    $component_config = $event->getComponent()->get('configuration');

    if (in_array($component_config['id'], $menus)) {
      // Always display the label for taxonomy menus due to the twig template.
      $build = $event->getBuild();
      $build['#configuration']['label_display'] = 'visible';
      $event->setBuild($build);
    }
  }

  /**
   * Get the list of all taxonomy menus.
   *
   * @return string[]
   *   Menu id strings.
   */
  protected function getTaxonomyMenuIds(): array {
    $menu_ids = &drupal_static(self::class . __METHOD__, []);
    if ($menu_ids) {
      return $menu_ids;
    }

    $tax_menus = $this->entityTypeManager->getStorage('taxonomy_menu')
      ->loadMultiple();
    foreach ($tax_menus as $menu) {
      $menu_ids[] = 'system_menu_block:' . $menu->getMenu();
    }
    return $menu_ids;
  }

}
