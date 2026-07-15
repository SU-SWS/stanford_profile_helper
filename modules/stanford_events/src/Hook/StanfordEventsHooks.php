<?php

declare(strict_types=1);

namespace Drupal\stanford_events\Hook;

use Drupal\Core\Hook\Attribute\Hook;

class StanfordEventsHooks {

  #[Hook('preprocess_paragraph__stanford_schedule')]
  public function preprocessParagraphStanfordSchedule(&$variables) {
    $variables['content'] = [
      '#type' => 'component',
      '#component' => 'stanford_events:events_schedule',
      '#slots' => [
        'time' => $variables['content']['su_schedule_date_time'] ?? NULL,
        'headline' => $variables['content']['su_schedule_headline'] ?? NULL,
        'description' => $variables['content']['su_schedule_description'] ?? NULL,
        'location' => $variables['content']['su_schedule_location'] ?? NULL,
        'url' => $variables['content']['su_schedule_url'] ?? NULL,
        'speakers' => $variables['content']['su_schedule_speaker'] ?? NULL,
      ],
    ];
  }

  #[Hook('preprocess_paragraph__stanford_person_cta')]
  public function preprocessParagraphStanfordPersonCta(&$variables) {
    $variables['content'] = [
      '#type' => 'component',
      '#component' => 'stanford_events:events_person_cta',
      '#slots' => [
        'image' => $variables['content']['su_person_cta_image'] ?? NULL,
        'name' => $variables['content']['su_person_cta_name'] ?? NULL,
        'title' => $variables['content']['su_person_cta_title'] ?? NULL,
        'link' => $variables['content']['su_person_cta_link'] ?? NULL,
      ],
    ];
  }

}
