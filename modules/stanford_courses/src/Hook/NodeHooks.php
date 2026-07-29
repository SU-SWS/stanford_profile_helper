<?php

declare(strict_types=1);

namespace Drupal\stanford_courses\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\pathauto\PathautoPatternInterface;

/**
 * Node hooks for stanford_courses.
 */
class NodeHooks {

  /**
   * Implements hook_pathauto_alias_alter().
   */
  /**
   * Implements hook_pathauto_pattern_alter().
   */
  #[Hook('pathauto_pattern_alter')]
  public function pathautoPatternAlter(PathautoPatternInterface $pattern, array $context): void {
    if (isset($context['data']['node']) && $context['bundle'] == 'stanford_course') {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $context['data']['node'];
      if (
        !$node->get('su_course_subject')->count() &&
        !$node->get('su_course_code')->count()
      ) {
        $pattern->setPattern('/courses/[node:title]');
      }
    }
  }

}
