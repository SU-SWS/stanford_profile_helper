<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\file\FileInterface;
use Drupal\layout_library\Entity\Layout;
use Drupal\stanford_profile_helper\LayoutLibraryIconInterface;
use Drupal\stanford_profile_helper\Plugin\Field\FieldWidget\LayoutLibraryIconsWidget;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the LayoutLibraryIconsWidget.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(LayoutLibraryIconsWidget::class)]
class LayoutLibraryIconsWidgetTest extends UnitTestCase {

  protected $widget;
  protected $layoutLibraryIcon;
  protected $entityTypeManager;
  protected $renderer;
  protected $translation;

  protected function setUp(): void {
    parent::setUp();

    $this->translation = $this->createMock(TranslationInterface::class);
    $this->translation->method('translate')
      ->willReturnCallback(function ($string) {
        return new TranslatableMarkup($string, [], [], $this->translation);
      });

    $this->layoutLibraryIcon = $this->createMock(LayoutLibraryIconInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->renderer = $this->createMock(RendererInterface::class);

    // Mock field storage definition.
    $field_storage_definition = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_storage_definition->method('getPropertyNames')->willReturn(['target_id']);
    $field_storage_definition->method('isMultiple')->willReturn(FALSE);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getFieldStorageDefinition')->willReturn($field_storage_definition);

    $this->widget = new LayoutLibraryIconsWidget(
      'layout_library_icons',
      [],
      $field_definition,
      [],
      [],
      $this->layoutLibraryIcon,
      $this->entityTypeManager,
      $this->renderer
    );
    $this->widget->setStringTranslation($this->translation);
  }

  public function testFormElementWithLayoutsAndIcons() {
    // Create mock layouts.
    $layout1 = $this->createMock(Layout::class);
    $layout1->method('id')->willReturn('layout_1');
    $layout1->method('label')->willReturn('Layout 1');

    $layout2 = $this->createMock(Layout::class);
    $layout2->method('id')->willReturn('layout_2');
    $layout2->method('label')->willReturn('Layout 2');

    // Create mock file entities for icons.
    $icon1 = $this->createMock(FileInterface::class);
    $icon1->method('getFileUri')->willReturn('public://layout-icon/icon1.svg');

    $icon2 = $this->createMock(FileInterface::class);
    $icon2->method('getFileUri')->willReturn('public://layout-icon/icon2.svg');

    $default_icon = $this->createMock(FileInterface::class);
    $default_icon->method('getFileUri')->willReturn('public://layout-icon/default.svg');

    // Mock the layout library icon service.
    $this->layoutLibraryIcon->method('getDefaultIcon')->willReturn($default_icon);
    $this->layoutLibraryIcon->method('getLayoutIcon')
      ->willReturnMap([
        [$layout1, $icon1],
        [$layout2, $icon2],
      ]);

    // Mock entity storage - loadMultiple is called with option keys including _none.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')
      ->willReturnCallback(function ($ids) use ($layout1, $layout2) {
        // Filter out _none and return matching layouts.
        $layouts = [];
        if (in_array('layout_1', $ids)) {
          $layouts['layout_1'] = $layout1;
        }
        if (in_array('layout_2', $ids)) {
          $layouts['layout_2'] = $layout2;
        }
        return $layouts;
      });

    $this->entityTypeManager->method('getStorage')
      ->with('layout')
      ->willReturn($storage);

    // Mock renderer to return HTML strings.
    $this->renderer->method('render')
      ->willReturnCallback(function ($element) {
        return '<img src="' . $element['#uri'] . '" />';
      });

    // Mock field items.
    $entity = new class {
      public function getEntityTypeId() {
        return 'node';
      }
    };

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($entity);

    // Create field definition with storage.
    $field_storage_definition = $this->createMock(FieldStorageDefinitionInterface::class);
    $field_storage_definition->method('getPropertyNames')->willReturn(['target_id']);
    $field_storage_definition->method('isMultiple')->willReturn(FALSE);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getFieldStorageDefinition')->willReturn($field_storage_definition);

    // Create a widget that can return options.
    $widget = new class(
      'layout_library_icons',
      [],
      $field_definition,
      [],
      [],
      $this->layoutLibraryIcon,
      $this->entityTypeManager,
      $this->renderer
    ) extends LayoutLibraryIconsWidget {
      public function getOptions($entity = NULL) {
        return [
          '_none' => '- Default -',
          'layout_1' => 'Layout 1',
          'layout_2' => 'Layout 2',
        ];
      }
      public function getSelectedOptions(FieldItemListInterface $items, $delta = 0) {
        return ['layout_1'];
      }
    };
    $widget->setStringTranslation($this->translation);

    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $element = ['#title' => 'Layout', '#required' => FALSE];

    $result = $widget->formElement($items, 0, $element, $form, $form_state);

    // Assertions.
    $this->assertEquals('radios', $result['#type']);
    $this->assertArrayHasKey('#options', $result);
    $this->assertEquals('layout_1', $result['#default_value']);
    $this->assertArrayHasKey('layout_1', $result['#options']);
    $this->assertArrayHasKey('layout_2', $result['#options']);
    $this->assertArrayHasKey('_none', $result['#options']);
    $this->assertStringContainsString('<img src="public://layout-icon/icon1.svg"', $result['#options']['layout_1']);
    $this->assertStringContainsString('<img src="public://layout-icon/icon2.svg"', $result['#options']['layout_2']);
    $this->assertStringContainsString('<img src="public://layout-icon/default.svg"', $result['#options']['_none']);
    $this->assertArrayHasKey('#attached', $result);
    $this->assertEquals(['stanford_profile_helper/layout_library_icon_widget'], $result['#attached']['library']);
    $this->assertEquals(['layout-library-icons'], $result['#attributes']['class']);
  }

  public function testGetEmptyLabel() {
    // Use reflection to access protected method.
    $reflection = new \ReflectionClass($this->widget);
    $method = $reflection->getMethod('getEmptyLabel');
    $method->setAccessible(TRUE);

    $label = $method->invoke($this->widget);
    $this->assertInstanceOf(TranslatableMarkup::class, $label);
    // Check the untranslated string.
    $this->assertEquals('- Default -', $label->getUntranslatedString());
  }

}
