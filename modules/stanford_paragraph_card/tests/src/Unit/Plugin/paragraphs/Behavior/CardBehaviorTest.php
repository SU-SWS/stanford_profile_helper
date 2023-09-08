<?php

namespace Drupal\Tests\stanford_paragraph_card\Unit\Plugin\paragraphs\Behavior;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_paragraph_card\Plugin\paragraphs\Behavior\CardBehavior;
use Drupal\Tests\UnitTestCase;

class CardBehaviorTest extends UnitTestCase {

  public function setup(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  public function testLinkStyleBehavior() {
    $paragraph_type = $this->createMock(ParagraphsType::class);
    $paragraph_type->method('id')->willReturn($this->randomMachineName());
    $this->assertFalse(CardBehavior::isApplicable($paragraph_type));

    $paragraph_type = $this->createMock(ParagraphsType::class);
    $paragraph_type->method('id')->willReturn('stanford_card');
    $this->assertTrue(CardBehavior::isApplicable($paragraph_type));

    $field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $behavior = new CardBehavior([], '', [], $field_manager);

    $paragraph = $this->createMock(ParagraphInterface::class);

    $form = [];
    $form_State = new FormState();
    $element = $behavior->buildBehaviorForm($paragraph, $form, $form_State);
    $this->assertNull($element['link_style']['#default_value']);

    $paragraph->method('getBehaviorSetting')->willReturn('action');
    $element = $behavior->buildBehaviorForm($paragraph, $form, $form_State);
    $this->assertEquals('action', $element['link_style']['#default_value']);

    $display = $this->createMock(EntityViewDisplayInterface::class);
    $build = [];
    $build['#ds_configuration']['regions']['card_button_label'] = 'label';
    $behavior->view($build, $paragraph, $display, 'default');

    $this->assertEquals('label', $build['#ds_configuration']['regions']['card_cta_label']);
  }

}
