<?php

namespace Drupal\Tests\stanford_layout_paragraphs\Kernel\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_layout_paragraphs\Plugin\paragraphs\Behavior\LayoutParagraphs;

/**
 * Test layout paragraph plugin overriddes.
 */
class LayoutParagraphsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'layout_discovery',
    'paragraphs',
    'layout_paragraphs',
    'stanford_layout_paragraphs',
  ];

  /**
   * Test the behavior plugin.
   */
  public function testBehaviorPlugin() {
    /** @var \Drupal\paragraphs\ParagraphsBehaviorManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.paragraphs.behavior');
    $plugin = $plugin_manager->createInstance('layout_paragraphs');
    $this->assertInstanceOf(LayoutParagraphs::class, $plugin);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $form = ['#parents' => []];
    $form_state = new FormState();
    $form_state->setUserInput(['layout' => 'layout_paragraphs_1_column']);
    $form = $plugin->buildBehaviorForm($paragraph, $form, $form_state);

    $this->assertContains('choose-layout-field', $form['layout']['#attributes']['class']);
    $this->assertTrue($form['config']['#open']);
  }

}
