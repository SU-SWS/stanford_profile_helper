<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\redirect\Entity\Redirect;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;

/**
 * Test the event subscriber.
 */
class EntityObjectHooksTest extends SuProfileHelperKernelTestBase {

  protected $strictConfigSchema = FALSE;

  public function testMenuCacheClears() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_menulink',
      'entity_type' => 'node',
      'type' => 'menu_link',
    ]);
    $field_storage->save();
    $field_config = FieldConfig::create([
      'field_name' => 'field_menulink',
      'entity_type' => 'node',
      'bundle' => 'stanford_event',
      'label' => 'menulink',
    ]);
    $field_config->save();
    $node = Node::create([
      'type' => 'stanford_event',
      'title' => 'Foo Bar',
      'field_menulink' => [
        'title' => 'foobar',
        'description' => '',
        'weight' => 0,
        'parent' => NULL,
        'menu_name' => 'main',
      ],
    ]);
    $node->save();

    $node = Node::load($node->id());
    $node->setUnpublished()->save();

    $node->setPublished()->save();
    $node->set('field_menulink', [
      'title' => 'foobar2',
      'description' => '',
      'weight' => 0,
      'parent' => NULL,
      'menu_name' => 'main',
    ])->save();

    $node->delete();
  }

  /**
   * Test menu item events.
   */
  public function testMenuItems() {
    $node = Node::create(['type' => 'stanford_event', 'title' => 'Foo Bar']);
    $node->save();
    PathAlias::create([
      'path' => '/node/' . $node->id(),
      'alias' => '/foo/bar',
    ])->save();

    $parent_item = MenuLinkContent::create([
      'title' => 'Parent',
      'description' => 'Llama Gabilondo',
      'link' => 'entity:node/' . $node->id(),
      'weight' => 0,
      'menu_name' => 'main',
    ]);
    $parent_item->save();

    $menu_item = MenuLinkContent::create([
      'title' => 'Llama Gabilondo',
      'description' => 'Llama Gabilondo',
      'link' => 'internal:/foo/bar',
      'weight' => 0,
      'menu_name' => 'main',
      'parent' => 'menu_link_content:' . $parent_item->uuid(),
    ]);
    $this->assertEquals(SAVED_NEW, $menu_item->save());
    $this->assertEquals('entity:node/' . $node->id(), $menu_item->get('link')
      ->get(0)
      ->get('uri')
      ->getString());
    $this->assertEquals(SAVED_UPDATED, $menu_item->save());
    $this->assertNull($menu_item->delete());
  }

  /**
   * Test redirect entities.
   */
  public function testRedirects() {
    $node = Node::create(['type' => 'stanford_event', 'title' => 'Foo Bar']);
    $node->save();
    PathAlias::create([
      'path' => '/node/' . $node->id(),
      'alias' => '/foo/bar',
    ])->save();

    $redirect = Redirect::create([
      'redirect_redirect' => 'internal:/foo/bar',
      'redirect_source' => '/bar/foo',
    ]);
    $redirect->save();

    $this->assertEquals('entity:node/' . $node->id(), $redirect->get('redirect_redirect')
      ->getString());
  }

  public function testFields() {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_foo',
      'entity_type' => 'node',
      'type' => 'text',
    ]);
    $field_storage->setThirdPartySetting('field_permissions', 'permission_type', 'public');
    $field_storage->save();

    $this->assertNull($field_storage->getThirdPartySetting('field_permissions', 'permission_type'));
  }

}
