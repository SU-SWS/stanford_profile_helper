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
 *
 * @group jumpstart_ui
 * @coversDefaultClass \Drupal\jumpstart_ui\Plugin\paragraphs\Behavior\TeaserParagraphBehavior
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
