<?php

namespace Drupal\stanford_person_importer\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrgCodeDeriver extends DeriverBase implements ContainerDeriverInterface {

  use MigrationDeriverTrait;
  use StringTranslationTrait;

  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id
    );
  }

  public function __construct(protected $basePluginId) {}

  public function getDerivativeDefinitions($base_plugin_definition) {
    $config_pages = \Drupal::service('config_pages.loader');
    $client_id = $config_pages->getValue('stanford_person_importer', 'su_person_cap_username', 0, 'value') ?? '';;
    $client_secret = $config_pages->getValue('stanford_person_importer', 'su_person_cap_password', 0, 'value') ?? '';;
    if (!$client_id || !$client_secret) {
      return $this->derivatives;
    }

    $base_plugin_definition['source']['authentication']['client_id'] = $client_id;
    $base_plugin_definition['source']['authentication']['client_secret'] = $client_secret;

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = \Drupal::service('plugin.manager.migration')
      ->createStubMigration($base_plugin_definition);
    $source = $migration->getSourcePlugin();
    $all_data = [];
    $source->rewind();
    while ($source->valid()) {
      /** @var \Drupal\migrate\Row $row */
      $row = $source->current();
      $all_data[] = $row->getSource();
      $source->next();
    }
    $this->derivatives['base'] = $base_plugin_definition;
    $this->createDerivatives($all_data, $base_plugin_definition, 'children', 'base');
    return $this->derivatives;
  }

  protected function createDerivatives($data, $base_definition, $path, $parent_code = NULL) {
    foreach ($data as $key => $item_data) {
      if (empty($item_data['children'])) {
        continue;
      }

      $plugin = $base_definition;
      asort($item_data['orgCodes']);
      $code = reset($item_data['orgCodes']);

      $plugin['source']['urls'][0] = "https://api.stanford.edu/cap/v1/orgs/$code";
      $plugin['process']['parent/target_id'] = [
        [
          'plugin' => 'default_value',
          'default_value' => $item_data['alias'],
        ],
        [
          'plugin' => 'migration_lookup',
          'migration' => "cap_orgs:$parent_code",
        ],
      ];

      if ($parent_code) {
        $plugin['migration_dependencies']['required'] = [
          "cap_orgs:$parent_code",
        ];
      }

      $this->derivatives[strtolower($code)] = $plugin;
      $this->createDerivatives($item_data['children'], $base_definition, "$path/$key/children", strtolower($code));
    }
  }

}
