<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Plugin\migrate;

/**
 * WordPress Content importer deriver.
 */
class WordPressContentDeriver extends WordPressMigrationDeriverBase {

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->buildContentDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives;
  }

}
