<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;

/**
 * Test the event subscriber.
 */
class ConfigPagesHooksTest extends SuProfileHelperKernelTestBase {

  public function testConfigPages() {
    ConfigPagesType::create([
      'id' => 'foo',
      'context' => [],
      'menu' => ['path' => '/foo'],
    ])->save();
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'su_site_url',
      'entity_type' => 'config_pages',
      'type' => 'link',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'foo',
    ])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'su_site_nobots',
      'entity_type' => 'config_pages',
      'type' => 'boolean',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'foo',
    ])->save();

    ConfigPages::create([
      'type' => 'foo',
      'su_site_url' => ['uri' => 'https://foo.bar'],
      'context' => 'a:0:{}',
    ])->save();

    $this->assertEquals('https://foo.bar', \Drupal::state()
      ->get('xmlsitemap_base_url'));
  }

}
