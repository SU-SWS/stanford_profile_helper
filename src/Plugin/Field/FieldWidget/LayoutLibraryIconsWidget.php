<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
    protected FileSystemInterface $fileSystem,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('file_system'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element += [
      '#type' => 'radios',
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $this->getSelectedOptions($items) ?: '_none',
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => $this->multiple && count($this->options) > 1,
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
