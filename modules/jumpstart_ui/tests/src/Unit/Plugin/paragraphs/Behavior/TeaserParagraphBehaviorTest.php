<?php

namespace Drupal\Tests\jumpstart_ui\Unit\Plugin\paragraphs\Behavior;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormState;
use Drupal\jumpstart_ui\Plugin\paragraphs\Behavior\TeaserParagraphBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Class TeaserParagraphBehaviorTest
 */
class TeaserParagraphBehaviorTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();

    $field_manager = $this->createMock(EntityFieldManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $field_manager);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * The paragraph behavior should only be available to hero pattern displays.
   */
  public function testApplication() {
    $paragraph_type = $this->createMock(ParagraphsType::class);
    $paragraph_type->method('id')->willReturn('foo');
    $this->assertFalse(TeaserParagraphBehavior::isApplicable($paragraph_type));

    $paragraph_type = $this->createMock(ParagraphsType::class);
    $paragraph_type->method('id')->willReturn('stanford_entity');
    $this->assertTrue(TeaserParagraphBehavior::isApplicable($paragraph_type));
  }

  public function testForm() {
    $plugin = TeaserParagraphBehavior::create(\Drupal::getContainer(), [], '', []);
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')->willReturn('show');
    $form = [];
    $form_state = new FormState();
    $form = $plugin->buildBehaviorForm($paragraph, $form, $form_state);
    $this->assertArrayHasKey('heading_behavior', $form);
    $this->assertEquals('show', $form['heading_behavior']['#default_value']);

    // Test that image size field is present.
    $this->assertArrayHasKey('image_size', $form);
    $this->assertEquals('select', $form['image_size']['#type']);
    $this->assertArrayHasKey('large', $form['image_size']['#options']);
    $this->assertArrayHasKey('small', $form['image_size']['#options']);
  }

  public function testImageSizeFormDefault() {
    $plugin = TeaserParagraphBehavior::create(\Drupal::getContainer(), [], '', []);
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')
      ->willReturnCallback(function($plugin_id, $key, $default) {
        if ($key === 'image_size') {
          return 'large';
        }
        return $default;
      });

    $form = [];
    $form_state = new FormState();
    $form = $plugin->buildBehaviorForm($paragraph, $form, $form_state);

    $this->assertEquals('large', $form['image_size']['#default_value']);
  }

  public function testImageSizeFormSmall() {
    $plugin = TeaserParagraphBehavior::create(\Drupal::getContainer(), [], '', []);
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')
      ->willReturnCallback(function($plugin_id, $key, $default) {
        if ($key === 'image_size') {
          return 'small';
        }
        return $default;
      });

    $form = [];
    $form_state = new FormState();
    $form = $plugin->buildBehaviorForm($paragraph, $form, $form_state);

    $this->assertEquals('small', $form['image_size']['#default_value']);
  }

  public function testView() {
    $plugin = TeaserParagraphBehavior::create(\Drupal::getContainer(), [], '', []);

    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('getBehaviorSetting')->willReturn('hide');
    $display = $this->createMock(EntityViewDisplayInterface::class);

    $build = [
      'su_entity_headline' => ['foo'],
      'su_entity_item' => [
        [
          '#view_mode' => 'foobar',
          '#cache' => ['keys' => ['foobar']],
        ],
      ],
    ];
    $plugin->view($build, $paragraph, $display, 'foo');
    $this->assertContains('visually-hidden', $build['su_entity_headline']['#attributes']['class']);
    $this->assertContains('stanford_h3_card', $build['su_entity_item'][0]['#cache']['keys']);
  }

  public function testViewWithSpotlightImageSize() {
    $plugin = TeaserParagraphBehavior::create(\Drupal::getContainer(), [], '', []);

    // Mock a spotlight news node.
    $layout = $this->createMock(\Drupal\layout_library\Entity\Layout::class);
    $layout->method('id')->willReturn('news_spotlight');

    $field_item_list = $this->createMock(\Drupal\Core\Field\FieldItemListInterface::class);
    $field_item_list->method('isEmpty')->willReturn(FALSE);
    $field_item_list->method('__get')->with('entity')->willReturn($layout);

    $news_node = $this->createMock(\Drupal\node\NodeInterface::class);
    $news_node->method('getEntityTypeId')->willReturn('node');
    $news_node->method('bundle')->willReturn('stanford_news');
    $news_node->method('hasField')->with('layout_selection')->willReturn(TRUE);
    $news_node->method('get')->with('layout_selection')->willReturn($field_item_list);

    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('hasField')->with('su_entity_item')->willReturn(TRUE);
    $paragraph->method('get')->with('su_entity_item')->willReturnSelf();
    $paragraph->method('referencedEntities')->willReturn([$news_node]);
    $paragraph->method('getBehaviorSetting')
      ->willReturnCallback(function($plugin_id, $key, $default) {
        if ($key === 'image_size') {
          return 'large';
        }
        return $default;
      });

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $plugin->view($build, $paragraph, $display, 'default');

    // Verify image size is added to build array.
    $this->assertEquals('large', $build['#spotlight_image_size']);
    $this->assertContains('spotlight-image-large', $build['#attributes']['class']);
  }

  public function testViewWithSpotlightImageSizeSmall() {
    $plugin = TeaserParagraphBehavior::create(\Drupal::getContainer(), [], '', []);

    // Mock a spotlight news node.
    $layout = $this->createMock(\Drupal\layout_library\Entity\Layout::class);
    $layout->method('id')->willReturn('news_spotlight');

    $field_item_list = $this->createMock(\Drupal\Core\Field\FieldItemListInterface::class);
    $field_item_list->method('isEmpty')->willReturn(FALSE);
    $field_item_list->method('__get')->with('entity')->willReturn($layout);

    $news_node = $this->createMock(\Drupal\node\NodeInterface::class);
    $news_node->method('getEntityTypeId')->willReturn('node');
    $news_node->method('bundle')->willReturn('stanford_news');
    $news_node->method('hasField')->with('layout_selection')->willReturn(TRUE);
    $news_node->method('get')->with('layout_selection')->willReturn($field_item_list);

    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('hasField')->with('su_entity_item')->willReturn(TRUE);
    $paragraph->method('get')->with('su_entity_item')->willReturnSelf();
    $paragraph->method('referencedEntities')->willReturn([$news_node]);
    $paragraph->method('getBehaviorSetting')
      ->willReturnCallback(function($plugin_id, $key, $default) {
        if ($key === 'image_size') {
          return 'small';
        }
        return $default;
      });

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $plugin->view($build, $paragraph, $display, 'default');

    // Verify image size is added to build array.
    $this->assertEquals('small', $build['#spotlight_image_size']);
    $this->assertContains('spotlight-image-small', $build['#attributes']['class']);
  }

  public function testViewWithoutSpotlightNews() {
    $plugin = TeaserParagraphBehavior::create(\Drupal::getContainer(), [], '', []);

    // Mock a regular page node (not spotlight news).
    $page_node = $this->createMock(\Drupal\node\NodeInterface::class);
    $page_node->method('getEntityTypeId')->willReturn('node');
    $page_node->method('bundle')->willReturn('stanford_page');

    $paragraph = $this->createMock(Paragraph::class);
    $paragraph->method('hasField')->with('su_entity_item')->willReturn(TRUE);
    $paragraph->method('get')->with('su_entity_item')->willReturnSelf();
    $paragraph->method('referencedEntities')->willReturn([$page_node]);
    $paragraph->method('getBehaviorSetting')->willReturn('show');

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $plugin->view($build, $paragraph, $display, 'default');

    // Verify image size is NOT added when not a spotlight news item.
    $this->assertArrayNotHasKey('#spotlight_image_size', $build);
  }

  /**
   * Load and get mock display entities.
   *
   * @param array $ids
   *   Array of display ids.
   *
   * @return array
   *   Keyed array of mock displays.
   */
  public function loadMultipleDisplayCallback($ids = []) {
    $return = [];
    foreach ($ids as $id) {
      $ds_settings = NULL;
      switch ($id) {
        case 'paragraph.foo.hero':
          $ds_settings = ['id' => 'pattern_hero'];
          break;
      }
      $return[$id] = $this->createMock(EntityDisplayInterface::class);
      $return[$id]->method('getThirdPartySetting')
        ->willReturn($ds_settings);
    }
    return $return;
  }

}
