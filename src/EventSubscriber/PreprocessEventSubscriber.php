<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\preprocess_event_dispatcher\Event\BlockPreprocessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PreprocessEventSubscriber.
 */
class PreprocessEventSubscriber implements EventSubscriberInterface {

  /**
   * Core route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      'preprocess_block' => 'preprocessBlock',
      'preprocess_toolbar' => 'preprocesToolbar',
    ];
  }

  /**
   * PreprocessEventSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Core route match service.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Preprocess block actions.
   *
   * @param \Drupal\preprocess_event_dispatcher\Event\BlockPreprocessEvent $event
   *   Triggered event.
   *
   * @see hook_preprocess_HOOK().
   *
   */
  public function preprocessBlock(BlockPreprocessEvent $event) {
    $variables = $event->getVariables();
    if ($variables->get('plugin_id') == 'help_block') {
      if ($this->routeMatch->getRouteName() == 'help.main') {
        // Removes the help text from core help module. It's not helpful, and
        // we're going to provide our own help text.
        // @see help_help()
        $variables->remove('content');
      }
    }
  }

}
