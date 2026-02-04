<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_profile_helper\LayoutLibraryIconInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'layout_library_icons' field widget.
 */
#[FieldWidget(
  id: 'layout_library_icons',
  label: new TranslatableMarkup('Layout Library Icons'),
  field_types: ['entity_reference'],
)]
final class LayoutLibraryIconsWidget extends OptionsWidgetBase {

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected LayoutLibraryIconInterface $layoutLibraryIcon,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('stanford_profile_helper.layout_library_icon'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $options = $this->getOptions($items->getEntity());
    /** @var \Drupal\layout_library\Entity\Layout[] $layouts */
    $layouts = $this->entityTypeManager->getStorage('layout')
      ->loadMultiple(array_keys($options));
    $default_icon = $this->layoutLibraryIcon->getDefaultIcon();

    foreach ($layouts as $layout) {
      $icon = $this->layoutLibraryIcon->getLayoutIcon($layout) ?: $default_icon;

      if ($icon) {
        $image = [
          '#theme' => 'image',
          '#uri' => $icon->getFileUri(),
          '#alt' => '',
          '#width' => 200,
          '#height' => 200,
        ];
        $options[$layout->id()] .= $this->renderer->render($image);
      }
    }
    if ($default_icon && isset($options['_none'])) {
      $image = [
        '#theme' => 'image',
        '#uri' => $default_icon->getFileUri(),
        '#alt' => '',
        '#width' => 200,
        '#height' => 200,
      ];
      $options['_none'] .= $this->renderer->render($image);
    }

    $element += [
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $this->getSelectedOptions($items) ?: '_none',
      '#attached' => ['library' => ['stanford_profile_helper/layout_library_icon_widget']],
      '#attributes' => ['class' => ['layout-library-icons']],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    return $this->t('- Default -');
  }

}
