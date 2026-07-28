<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_styles\Unit\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_profile_styles\Hook\FormHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FormHooks.
 */
#[Group('stanford_profile_styles')]
#[CoversClass(FormHooks::class)]
class FormHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_styles\Hook\FormHooks
   */
  protected FormHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->hooks = new FormHooks();
  }

  /**
   * The paragraphs widget form alter attaches the field widget library.
   */
  public function testFieldWidgetCompleteParagraphsFormAlter(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form = [];
    $this->hooks->fieldWidgetCompleteParagraphsFormAlter($form, $form_state, []);

    $this->assertContains('stanford_profile_styles/admin.field_widgets', $form['#attached']['library']);
  }

  /**
   * The smartdate timezone widget form alter attaches the field widget
   * library.
   */
  public function testFieldWidgetCompleteSmartdateTimezoneFormAlter(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form = [];
    $this->hooks->fieldWidgetCompleteSmartdateTimezoneFormAlter($form, $form_state, []);

    $this->assertContains('stanford_profile_styles/admin.field_widgets', $form['#attached']['library']);
  }

  /**
   * The node form alter attaches the nodeFormWide process callback.
   */
  public function testFormNodeFormAlter(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form = [];
    $this->hooks->formNodeFormAlter($form, $form_state, 'stanford_page_node_form');

    $this->assertSame([[FormHooks::class, 'nodeFormWide']], $form['#process']);
  }

  /**
   * A non-stanford_page node bundle is left completely untouched.
   */
  public function testNodeFormWideNonStanfordPage(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_news');

    $form_object = $this->createMock(EntityFormInterface::class);
    $form_object->method('getEntity')->willReturn($node);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getFormObject')->willReturn($form_object);

    $form = ['advanced' => [], 'meta' => []];
    $result = FormHooks::nodeFormWide($form, $form_state);

    $this->assertSame($form, $result);
    $this->assertArrayNotHasKey('#type', $result['advanced']);
  }

  /**
   * A stanford_page node bundle gets the advanced vertical tabs rearranged.
   */
  public function testNodeFormWideStanfordPage(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_page');

    $form_object = $this->createMock(EntityFormInterface::class);
    $form_object->method('getEntity')->willReturn($node);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getFormObject')->willReturn($form_object);

    $form = [
      'advanced' => [],
      'meta' => [],
      'layout_selection' => [],
    ];
    $result = FormHooks::nodeFormWide($form, $form_state);

    $this->assertSame('vertical_tabs', $result['advanced']['#type']);
    $this->assertSame('details', $result['meta']['#type']);
    $this->assertSame('Publishing Information', (string) $result['meta']['#title']);
    $this->assertSame('details', $result['layout_selection']['#type']);
    $this->assertSame('Layout Options', (string) $result['layout_selection']['#title']);
    $this->assertSame('advanced', $result['layout_selection']['#group']);
    $this->assertSame(-11, $result['layout_selection']['#weight']);
    $this->assertContains('stanford_profile_styles/admin.node_forms', $result['#attached']['library']);
  }

}
