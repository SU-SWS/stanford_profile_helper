<?php

namespace Drupal\stanford_profile_helper\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Search API processor to remove only the desired html tags.
 */
#[SearchApiProcessor(
  id: 'space_tags',
  label: new TranslatableMarkup('Space HTML Tags'),
  description: new TranslatableMarkup('Add spaces between closing and opening HTML tags'),
  stages: [
    'preprocess_index' => 0,
  ],
)]
class SpaceTags extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['all_fields'] = TRUE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function processFieldValue(&$value, $type) {
    $value = str_replace('><', '> <', $value);
  }

}
