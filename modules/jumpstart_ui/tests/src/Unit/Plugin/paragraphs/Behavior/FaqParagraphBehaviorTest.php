<?php

namespace Drupal\Tests\jumpstart_ui\Unit\Plugin\paragraphs\Behavior;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\jumpstart_ui\Plugin\paragraphs\Behavior\FaqParagraphBehavior;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the FAQ accordion paragraph behavior.
 */
#[Group('jumpstart_ui')]
class FaqParagraphBehaviorTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $this->createMock(EntityFieldManagerInterface::class));
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * The behavior only applies to the FAQ paragraph type.
   */
  public function testApplicable() {
    $paragraph_type = $this->createMock(ParagraphsTypeInterface::class);
    $paragraph_type->method('id')->willReturn('stanford_lists');
    $this->assertFalse(FaqParagraphBehavior::isApplicable($paragraph_type));

    $paragraph_type = $this->createMock(ParagraphsTypeInterface::class);
    $paragraph_type->method('id')->willReturn('stanford_faq');
    $this->assertTrue(FaqParagraphBehavior::isApplicable($paragraph_type));
  }

  /**
   * The form offers the heading levels with the saved default.
   */
  public function testForm() {
    $plugin = FaqParagraphBehavior::create(\Drupal::getContainer(), [], 'faq_accordions', []);
    $this->assertEquals(['heading' => 'h2'], $plugin->defaultConfiguration());

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')
      ->with('faq_accordions', 'heading', 'h2')
      ->willReturn('h3');

    $form = [];
    $form = $plugin->buildBehaviorForm($paragraph, $form, new FormState());
    $this->assertEquals(['h2', 'h3', 'h4'], array_keys($form['heading']['#options']));
    $this->assertEquals('h3', $form['heading']['#default_value']);
  }

  /**
   * The headline tag is swapped for the configured heading level.
   */
  public function testView() {
    $plugin = FaqParagraphBehavior::create(\Drupal::getContainer(), [], 'faq_accordions', []);
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')->willReturn('h4');
    $display = $this->createMock(EntityViewDisplayInterface::class);

    $build = ['su_faq_headline' => [['#tag' => 'h2']]];
    $plugin->view($build, $paragraph, $display, 'default');
    $this->assertEquals('h4', $build['su_faq_headline'][0]['#tag']);

    // Without a headline tag, nothing is added.
    $build = ['su_faq_headline' => []];
    $plugin->view($build, $paragraph, $display, 'default');
    $this->assertEquals(['su_faq_headline' => []], $build);
  }

}
