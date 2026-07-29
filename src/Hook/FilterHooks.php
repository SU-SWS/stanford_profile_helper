<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;

/**
 * Hooks that relate to text filter formats.
 */
class FilterHooks {

  /**
   * Filter hook constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   */
  public function __construct(protected ModuleHandlerInterface $moduleHandler) {}

  /**
   * Implements hook_filter_info_alter().
   */
  #[Hook('filter_info_alter')]
  public function filterInfoAlter(&$info): void {
    if (
      isset($info['filter_mathjax']) &&
      $this->moduleHandler->moduleExists('mathjax')
    ) {
      $info['filter_mathjax']['class'] = 'Drupal\stanford_profile_helper\Plugin\Filter\Mathjax';
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('filter_format_access')]
  public function filterFormatAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return AccessResult::forbiddenIf($entity->id() == 'administrative_html');
  }

}
