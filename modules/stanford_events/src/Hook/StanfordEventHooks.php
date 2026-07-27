<?php

declare(strict_types=1);

namespace Drupal\stanford_events\Hook;

use Drupal\Core\Hook\Attribute\Hook;

class StanfordEventHooks {
  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_node__stanford_event__full')]
  public function preprocessNodeStanfordEventFull(&$variables) {
    $node = $variables['node'];
    if ($node->hasField('su_event_date_time')) {
      $date_time = $node->get('su_event_date_time')->getValue();
      if (!empty($date_time[0]['end_value'])) {
        $variables['attributes']['data-end-date'] = $date_time[0]['end_value'];
      }
    }
    $variables['#attached']['library'][] = 'stanford_events/event_node';
  }

}
