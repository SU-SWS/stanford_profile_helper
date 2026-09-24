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
use Drupal\Core\State\StateInterface;
use Drupal\jumpstart_ui\Plugin\paragraphs\Behavior\HeroPatternBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class HeroPatternBehaviorTest
 */
#[Group('jumpstart_ui')]
class HeroPatternBehaviorTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $state = $this->createMock(StateInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $field_manager);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('state', $state);
    \Drupal::setContainer($container);
  }

  /**
   * The paragraph behavior should only be available to hero pattern displays.
   */
  public function testApplication() {
    $paragraph_type = $this->createMock(ParagraphsType::class);
    $paragraph_type->method('id')->willReturn('foo');
    $this->assertFalse(HeroPatternBehavior::isApplicable($paragraph_type));

    $paragraph_type = $this->createMock(ParagraphsType::class);
    $paragraph_type->method('id')->willReturn('stanford_banner');
    $this->assertTrue(HeroPatternBehavior::isApplicable($paragraph_type));
  }

  public function testForm() {
    $plugin = HeroPatternBehavior::create(\Drupal::getContainer(), [], '', []);
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')->willReturn('right');
    $form = [];
    $form_state = new FormState();
    $form = $plugin->buildBehaviorForm($paragraph, $form, $form_state);
    $this->assertArrayHasKey('overlay_position', $form);
    $this->assertEquals('right', $form['overlay_position']['#default_value']);
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

  /**
   * The overlay colour options only show when allowed by state.
   */
  public function testFormOverlayColor() {
    $state = $this->createMock(StateInterface::class);
    $state->method('get')
      ->with('allow_hero_pattern_overlay_color')
      ->willReturn(TRUE);
    \Drupal::getContainer()->set('state', $state);

    $plugin = HeroPatternBehavior::create(\Drupal::getContainer(), [], '', []);
    $paragraph = $this->createMock(ParagraphInterface::class);
    $form = [];
    $form = $plugin->buildBehaviorForm($paragraph, $form, new FormState());
    $this->assertArrayHasKey('center', $form['overlay_position']['#options']);
    $this->assertTrue($form['overlay_color_wrapper']['#access']);
    $this->assertTrue($form['overlay_color_wrapper']['overlay_color']['#access']);
  }

  /**
   * The overlay colour is moved out of its wrapper when saved.
   */
  public function testSubmitBehaviorForm() {
    $plugin = HeroPatternBehavior::create(\Drupal::getContainer(), [], 'hero_pattern', []);

    $saved = [];
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('setBehaviorSettings')
      ->willReturnCallback(function ($plugin_id, $settings) use (&$saved) {
        $saved = $settings;
      });

    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'overlay_position' => 'center',
      'overlay_color_wrapper' => ['overlay_color' => '#620059'],
    ]);
    $plugin->submitBehaviorForm($paragraph, $form, $form_state);

    $this->assertEquals(['overlay_position' => 'center', 'overlay_color' => '#620059'], $form_state->getValues());
    $this->assertEquals(['overlay_position' => 'center', 'overlay_color' => '#620059'], $saved);
  }

}
