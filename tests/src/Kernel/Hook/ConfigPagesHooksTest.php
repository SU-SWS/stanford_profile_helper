<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\stanford_profile_helper\Hook\ConfigPagesHooks;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test the event subscriber.
 */
#[RunTestsInSeparateProcesses]
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

  public function testRedirectUser() {
    $this->assertFalse(ConfigPagesHooks::redirectUser());
    // Cache line.
    $this->assertFalse(ConfigPagesHooks::redirectUser());

    $account = $this->createMock(AccountProxyInterface::class);
    $account->method('hasPermission')->willReturn(TRUE);
    $account->method('getRoles')->willReturn([]);
    \Drupal::currentUser()->setAccount($account);


    $configPageLoader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $configPageLoader->method('getValue')->willReturn(date('c', time() - 5));
    \Drupal::getContainer()->set('config_pages.loader', $configPageLoader);

    putenv('CI=');
    $this->assertTrue(ConfigPagesHooks::redirectUser());
  }

}
