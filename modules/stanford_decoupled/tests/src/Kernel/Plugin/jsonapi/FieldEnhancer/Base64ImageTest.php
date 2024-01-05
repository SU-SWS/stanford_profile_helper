<?php

namespace Drupal\Tests\stanford_decoupled\Kernel\Plugin\jsonapi\FieldEnhancer;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Shaper\Util\Context;

/**
 * @coversDefaultClass \Drupal\stanford_decoupled\Plugin\jsonapi\FieldEnhancer\Base64Image
 */
class Base64ImageTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'file',
    'image',
    'serialization',
    'jsonapi',
    'jsonapi_extras',
    'stanford_decoupled',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    ImageStyle::create(['name' => 'thumbnail', 'label' => 'thumb'])->save();
  }

  public function testTransform() {
    /** @var \Drupal\stanford_decoupled\Plugin\jsonapi\FieldEnhancer\Base64Image $plugin */
    $plugin = $this->container->get('plugin.manager.resource_field_enhancer')
      ->createInstance('base64_image', ['style' => 'thumbnail']);
    $form = $plugin->getSettingsForm([]);

    $this->assertNotEmpty($form['style']['#options']);

    $context = new Context();
    $data = ['value' => 'foobar'];
    $this->assertEquals($data, $plugin->undoTransform($data, $context));

    file_put_contents('public://baseimage.jpg', file_get_contents('core/misc/druplicon.png'));
    $image = File::create(['uri' => 'public://baseimage.jpg']);
    $image->save();

    $data = ['value' => 'public://baseimage.jpg'];
    $result = $plugin->undoTransform($data, $context);
    $this->assertNotEmpty($result['base64']);
  }

}
