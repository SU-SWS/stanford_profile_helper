<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'Time Duration' formatter.
 */
#[FieldFormatter(
  id: 'time_duration',
  label: new TranslatableMarkup('Time Duration'),
  field_types: ['integer'],
)]
class TimeDurationFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['style' => 'short', 'units' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Format Style'),
      '#options' => [
        'short' => $this->t('Short'),
        'long' => $this->t('Long'),
      ],
      '#default_value' => $this->getSetting('style'),
    ];
    $element['units'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Units to display'),
      '#description' => $this->t('Leave all options unchecked to display all units'),
      '#options' => [
        'hour' => $this->t('Hours'),
        'min' => $this->t('Minutes'),
        'sec' => $this->t('Seconds'),
      ],
      '#default_value' => $this->getSetting('units'),
      '#element_validate' => [
        [self::class, 'settingsUnitValidate'],
      ],
    ];
    return $element;
  }

  /**
   * Element validation to clean up submitted values.
   *
   * @param array $element
   *   Field element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public static function settingsUnitValidate(array $element, FormStateInterface $form_state) {
    $settings = $form_state->getValue($element['#parents']);
    $form_state->setValue($element['#parents'], array_values(array_filter($settings)));
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    foreach ($items as $delta => $item) {
      $seconds = (int) $item->value;
      $duration = $this->getSetting('style') == 'short' ? $this->getShortFormat($seconds) : $this->getLongFormat($seconds);
      $element[$delta] = [
        '#markup' => trim($duration),
      ];
    }
    return $element;
  }

  /**
   * Build a short-style duration string from a number of seconds.
   *
   * Produces a compact "HH:MM:SS", "MM:SS" or "SS" representation depending on
   * the value and the formatter 'units' setting. Each unit is zero-padded to
   * two digits. Hours are omitted when zero unless 'hour' is explicitly enabled
   * in the 'units' setting.
   *
   * @param int $seconds
   *   The number of seconds to format. Expected to be non-negative.
   *
   * @return string
   *   The formatted duration string (short style).
   */
  protected function getShortFormat(int $seconds): string {
    $hours = intval($seconds / 3600);
    $minutes = floor($seconds / 60) % 60;
    $seconds = $seconds % 60;

    $duration = [];
    $unitDisplay = $this->getSetting('units') ? array_filter($this->getSetting('units')) : [];

    if ($hours !== 0 && (!$unitDisplay || in_array('hour', $unitDisplay))) {
      $duration[] = str_pad("$hours", 2, '0', STR_PAD_LEFT) . ':';
    }
    if (!$unitDisplay || in_array('min', $unitDisplay)) {
      $duration[] = str_pad("$minutes", 2, '0', STR_PAD_LEFT) . ':';
    }
    if (!$unitDisplay || in_array('sec', $unitDisplay)) {
      $duration[] = str_pad("$seconds", 2, '0', STR_PAD_LEFT);
    }
    return ltrim(implode('', $duration), '0 ');
  }

  /**
   * Build a long-style, human-readable duration string from a number of
   * seconds.
   *
   * Produces a translated, verbose representation including hours, minutes and
   * seconds. The output respects the formatter 'units' setting to show or hide
   * specific units. Hours and minutes are shown only when non-zero or
   * explicitly enabled via the 'units' setting.
   *
   * @param int $seconds
   *   The number of seconds to format. Expected to be non-negative.
   *
   * @return string
   *   The formatted duration string (long style), translated and trimmed.
   */
  protected function getLongFormat(int $seconds): string {
    $hours = intval($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $remainingSeconds = $seconds % 60;

    $duration = [];
    $unitDisplay = $this->getSetting('units') ? array_filter($this->getSetting('units')) : [];

    if ($hours > 0 && (!$unitDisplay || in_array('hour', $unitDisplay))) {
      $duration[] = $this->t('@hour hours ', ['@hours' => $hours]);
    }

    if (($minutes > 0 || $hours > 0) && (!$unitDisplay || in_array('min', $unitDisplay))) {
      $duration[] = $this->t(' @minutes minutes ', ['@minutes' => $minutes]);
    }
    if (!$unitDisplay || in_array('sec', $unitDisplay)) {
      $duration[] = $this->t('@seconds seconds', ['@seconds' => $remainingSeconds]);
    }
    return ltrim(implode('', $duration), '0 ');
  }

}
