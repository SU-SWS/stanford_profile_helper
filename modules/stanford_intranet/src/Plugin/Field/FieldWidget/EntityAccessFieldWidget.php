<?php

namespace Drupal\stanford_intranet\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_access' widget.
 *
 * @FieldWidget(
 *   id = "entity_access",
 *   module = "stanford_intranet",
 *   label = @Translation("Entity Access"),
 *   field_types = {
 *     "entity_access"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityAccessFieldWidget extends WidgetBase {

  /**
   * State servivce.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('state'),
      $container->get('entity_type.manager')
    );
  }

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if (!$this->state->get('stanford_intranet', FALSE)) {
      return $element;
    }

    $default_value = [];
    foreach ($items as $item) {
      $default_value[] = $item->getValue()['role'];
    }

    $element['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allow users with the following roles to view this content.'),
      '#options' => $this->getUserRoleOptions(),
      '#default_value' => $default_value ?: ['authenticated'],
    ];
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach (array_filter($values['roles']) as $role) {
      $new_values[] = ['role' => $role, 'access' => serialize(['view'])];
    }
    return $new_values;
  }

  /**
   * Get the list of user roles for checkbox form input.
   *
   * @return array
   *   Keyed array of user role ids to labels.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getUserRoleOptions() {
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $options = [];
    foreach ($roles as $user_role) {
      $options[$user_role->id()] = $user_role->label();
    }
    return $options;
  }

}
