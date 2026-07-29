<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_profile_helper\Hook\MediaHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for MediaHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(MediaHooks::class)]
class MediaHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\MediaHooks
   */
  protected MediaHooks $hooks;

  /**
   * Mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->hooks = new MediaHooks($this->currentUser);
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Marks the current user as authorized or not for the embeddable field.
   */
  protected function setAuthorized(bool $authorized): void {
    $this->currentUser->method('hasPermission')
      ->willReturnMap([
        ['create field_media_embeddable_code', $authorized],
        ['edit field_media_embeddable_code', $authorized],
      ]);
  }

  /**
   * Neither the embed nor source field keys exist — nothing happens.
   */
  public function testFormAlterWithNoRelevantFieldsPresent(): void {
    $this->setAuthorized(FALSE);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('get')->willReturnMap([
      ['source_field', 'field_media_oembed_video'],
      ['unstructured_field_name', 'field_media_embeddable_oembed'],
    ]);

    $form = ['container' => []];
    $this->hooks->formMediaLibraryAddFormEmbeddableAlter($form, $form_state);

    $this->assertSame(['container' => []], $form);
  }

  /**
   * When authorized, the embed field is accessible and no description
   * override happens, but the title is still changed.
   */
  public function testFormAlterAuthorizedUser(): void {
    $this->setAuthorized(TRUE);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('get')->willReturnMap([
      ['source_field', 'field_media_oembed_video'],
      ['unstructured_field_name', 'field_media_embeddable_oembed'],
    ]);

    $description = new TranslatableMarkup('Original description', [], [], $this->getStringTranslationStub());
    $form = [
      'container' => [
        'field_media_embeddable_oembed' => ['#access' => FALSE],
        'field_media_oembed_video' => [
          '#description' => $description,
          '#title' => 'Video URL',
        ],
      ],
    ];

    $this->hooks->formMediaLibraryAddFormEmbeddableAlter($form, $form_state);

    $this->assertTrue($form['container']['field_media_embeddable_oembed']['#access']);
    $this->assertSame($description, $form['container']['field_media_oembed_video']['#description']);
    $this->assertSame('oEmbed URL', (string) $form['container']['field_media_oembed_video']['#title']);
  }

  /**
   * When not authorized, the embed field is inaccessible and the source
   * field description is overridden with the support link.
   */
  public function testFormAlterUnauthorizedUser(): void {
    $this->setAuthorized(FALSE);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('get')->willReturnMap([
      ['source_field', 'field_media_oembed_video'],
      ['unstructured_field_name', 'field_media_embeddable_oembed'],
    ]);

    $description = new TranslatableMarkup('Allowed providers: @providers.', ['@providers' => 'YouTube, Vimeo'], [], $this->getStringTranslationStub());
    $form = [
      'container' => [
        'field_media_embeddable_oembed' => ['#access' => TRUE],
        'field_media_oembed_video' => [
          '#description' => $description,
          '#title' => 'Video URL',
        ],
      ],
    ];

    $this->hooks->formMediaLibraryAddFormEmbeddableAlter($form, $form_state);

    $this->assertFalse($form['container']['field_media_embeddable_oembed']['#access']);
    $newDescription = $form['container']['field_media_oembed_video']['#description'];
    $this->assertInstanceOf(TranslatableMarkup::class, $newDescription);
    $this->assertStringContainsString('request support', (string) $newDescription);
    $this->assertSame('oEmbed URL', (string) $form['container']['field_media_oembed_video']['#title']);
  }

  /**
   * A field other than the embeddable oembed field is left untouched.
   */
  public function testFieldWidgetCompleteFormAlterIgnoresOtherFields(): void {
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('field_something_else');

    $this->currentUser->expects($this->never())->method('hasPermission');

    $field_widget_complete_form = ['widget' => []];
    $context = ['items' => $items];
    $this->hooks->fieldWidgetCompleteFormAlter($field_widget_complete_form, $this->createMock(FormStateInterface::class), $context);

    $this->assertSame(['widget' => []], $field_widget_complete_form);
  }

  /**
   * When authorized, the description on the embeddable oembed widget stays
   * untouched.
   */
  public function testFieldWidgetCompleteFormAlterAuthorizedUser(): void {
    $this->setAuthorized(TRUE);
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('field_media_embeddable_oembed');

    $originalDescriptionItem = new TranslatableMarkup('Allowed providers: @providers.', ['@providers' => 'YouTube'], [], $this->getStringTranslationStub());
    $field_widget_complete_form = [
      'widget' => [
        0 => [
          'value' => [
            '#description' => [
              '#items' => [0 => 'foo', 1 => $originalDescriptionItem],
            ],
          ],
        ],
      ],
    ];
    $context = ['items' => $items];

    $this->hooks->fieldWidgetCompleteFormAlter($field_widget_complete_form, $this->createMock(FormStateInterface::class), $context);

    $this->assertSame(
      ['#items' => [0 => 'foo', 1 => $originalDescriptionItem]],
      $field_widget_complete_form['widget'][0]['value']['#description']
    );
  }

  /**
   * When not authorized, the description is replaced with the support link.
   */
  public function testFieldWidgetCompleteFormAlterUnauthorizedUser(): void {
    $this->setAuthorized(FALSE);
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('field_media_embeddable_oembed');

    $originalDescriptionItem = new TranslatableMarkup('Allowed providers: @providers.', ['@providers' => 'YouTube'], [], $this->getStringTranslationStub());
    $field_widget_complete_form = [
      'widget' => [
        0 => [
          'value' => [
            '#description' => [
              '#items' => [0 => 'foo', 1 => $originalDescriptionItem],
            ],
          ],
        ],
      ],
    ];
    $context = ['items' => $items];

    $this->hooks->fieldWidgetCompleteFormAlter($field_widget_complete_form, $this->createMock(FormStateInterface::class), $context);

    $newDescription = $field_widget_complete_form['widget'][0]['value']['#description'];
    $this->assertInstanceOf(TranslatableMarkup::class, $newDescription);
    $this->assertStringContainsString('request support', (string) $newDescription);
  }

}
