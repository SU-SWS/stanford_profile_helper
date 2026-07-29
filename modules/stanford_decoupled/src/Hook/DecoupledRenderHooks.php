<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\stanford_decoupled\Config\DecoupledConfigOverrides;
use Drupal\views\ViewExecutable;

/**
 * Hooks that adjust rendering behavior for decoupled sites.
 */
class DecoupledRenderHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Route match service.
   */
  public function __construct(protected RouteMatchInterface $routeMatch) {}

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_image')]
  public function preprocessImage(&$variables): void {
    $decoupled = DecoupledConfigOverrides::isDecoupled();

    if ($decoupled && (empty($variables['width']) || empty($variables['height']))) {
      $path = str_starts_with($variables['uri'], '/') ? DRUPAL_ROOT . $variables['uri'] : $variables['uri'];
      $path = preg_replace('/\?.*$/', '', $path);
      if ($size = @getimagesize($path)) {
        $variables['attributes']['data-width'] = $size[0];
        $variables['attributes']['data-height'] = $size[1];
      }
    }
  }

  /**
   * Implements hook_views_pre_execute().
   */
  #[Hook('views_pre_execute')]
  public function viewsPreExecute(ViewExecutable $view): void {
    $route = $this->routeMatch->getRouteName();
    if (!($route == 'entity.node.canonical' && DecoupledConfigOverrides::isDecoupled())) {
      return;
    }
    $current_limit = $view->query->getLimit();
    if ($current_limit <= 0 || $current_limit > 5) {
      $view->query->setLimit(30);
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_file_link')]
  public function preprocessFileLink(&$variables): void {
    if (DecoupledConfigOverrides::isDecoupled()) {
      $variables['link']['#url']->setAbsolute();
    }
  }

}
