<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\stanford_profile_helper\Plugin\Field\FieldWidget\TimeDurationWidget;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the TimeDurationWidget.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(TimeDurationWidget::class)]
class TimeDurationWidgetTest extends UnitTestCase {

  protected $widget;
  protected $translation;

  protected function setUp(): void {
    parent::setUp();

    $this->translation = $this->createMock(TranslationInterface::class);
    $this->translation->method('translate')
      ->willReturnCallback(function ($string) {
        return new TranslatableMarkup($string, [], [], $this->translation);
      });

    $field_definition = $this->createMock(FieldDefinitionInterface::class);

    $this->widget = new TimeDurationWidget('time_duration', [], $field_definition, [], []);
    $this->widget->setStringTranslation($this->translation);
  }

  public function testFormElement() {
    $items = $this->createMock(FieldItemListInterface::class);
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $item = new class {
      public $value = 3661;
      public function toArray() {
        return ['value' => $this->value];
      }
    };

    $items->method('offsetGet')->with(0)->willReturn($item);

    $element = ['#title' => 'Test Field', '#description' => 'Test description'];
    $result = $this->widget->formElement($items, 0, $element, $form, $form_state);

    $this->assertArrayHasKey('time', $result);
    $this->assertEquals('fieldset', $result['time']['#type']);
    $this->assertEquals(1, $result['time']['hour']['#default_value']);
    $this->assertEquals(1, $result['time']['min']['#default_value']);
    $this->assertEquals(1, $result['time']['sec']['#default_value']);
  }

  public function testMassageFormValues() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $values = [['time' => ['hour' => 1, 'min' => 30, 'sec' => 45]]];
    $result = $this->widget->massageFormValues($values, $form, $form_state);

    $this->assertEquals([['value' => 5445]], $result);
  }

  public static function timeConversionProvider(): array {
    return [
      'one hour' => [3600, 1, 0, 0],
      'one minute' => [60, 0, 1, 0],
      'one second' => [1, 0, 0, 1],
      '2h 30m 45s' => [9045, 2, 30, 45],
    ];
  }

  #[DataProvider('timeConversionProvider')]
  public function testTimeConversion(int $seconds, int $hours, int $minutes, int $secs) {
    $items = $this->createMock(FieldItemListInterface::class);
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $item = new class($seconds) {
      public $value;
      public function __construct($value) {
        $this->value = $value;
      }
      public function toArray() {
        return ['value' => $this->value];
      }
    };
    $items->method('offsetGet')->with(0)->willReturn($item);

    $element = [];
    $result = $this->widget->formElement($items, 0, $element, $form, $form_state);

    $this->assertEquals($hours, $result['time']['hour']['#default_value']);
    $this->assertEquals($minutes, $result['time']['min']['#default_value']);
    $this->assertEquals($secs, $result['time']['sec']['#default_value']);
  }
}
