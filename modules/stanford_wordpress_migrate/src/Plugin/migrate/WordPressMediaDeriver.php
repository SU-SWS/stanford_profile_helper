<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Plugin\migrate;

/**
 * WordPress Content importer deriver.
 */
class WordPressMediaDeriver extends WordPressFileDeriver {

  const MIGRATE_TABLE_NAME = 'migrate_map_wordpress_media__media';

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    parent::getDerivativeDefinitions($base_plugin_definition);
    $this->buildContentDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives;
  }

}
