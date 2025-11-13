<?php

namespace Drupal\stanford_wordpress_migrate\Wizard;

/**
 * Edit wizard does the same as add wizard, but different route name.
 */
class ImportEditWizard extends ImportAddWizard {

  /**
   * {@inheritdoc}
   */
  public function getRouteName(): string {
    return 'entity.wordpress_migration.edit_form';
  }

}
