<?php

namespace Drupal\stanford_profile_helper\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Search API processor to run html_entity_decode().
 */
#[SearchApiProcessor(
  id: 'decode_html_entities',
  label: new TranslatableMarkup('Decode HTML Entities'),
  description: new TranslatableMarkup('Run html_entity_decode on the contents. Use this before other proceses'),
  stages: [
    'preprocess_index' => 0,
  ],
)]
class DecodeHtmlEntities extends FieldsProcessorPluginBase {

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
    $value = is_string($value) ? html_entity_decode($value) : $value;
  }

}
