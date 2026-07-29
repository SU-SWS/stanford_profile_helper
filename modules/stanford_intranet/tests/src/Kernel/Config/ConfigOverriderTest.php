<?php

namespace Drupal\Tests\stanford_intranet\Kernel\Config;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\stanford_intranet\Kernel\IntranetKernelTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Class ConfigOverriderTest.
 */
#[RunTestsInSeparateProcesses]
class ConfigOverriderTest extends IntranetKernelTestBase {

  /**
   * File fields uri scheme should change to private.
   */
  public function testFileFieldConfigOverrides() {
    $this->assertEquals('public', $this->fieldStorage->getSetting('uri_scheme'));
    $this->container->get('state')->set('stanford_intranet', TRUE);
    $this->container->get('kernel')->rebuildContainer();

    // Reload the field storage.
    $this->fieldStorage = FieldStorageConfig::load($this->fieldStorage->id());
    $this->assertEquals('private', $this->fieldStorage->getSetting('uri_scheme'));

    $this->assertNull(\Drupal::service('stanford_intranet.config_overrider')->createConfigObject('foo'));
  }

}
