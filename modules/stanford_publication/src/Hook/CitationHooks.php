<?php

declare(strict_types=1);

namespace Drupal\stanford_publication\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\stanford_publication\Entity\CitationInterface;

/**
 * Hooks that keep the nested Citation entity in sync with its parent node.
 */
class CitationHooks {

  use StringTranslationTrait;

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   Entity type bundle info service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected RouteMatchInterface $routeMatch,
    protected EntityTypeBundleInfoInterface $bundleInfo,
  ) {}

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function entityExtraFieldInfo(): array {
    $extra['node']['stanford_publication']['display']['citation_type'] = [
      'label' => $this->t('Publication Type'),
      'visible' => FALSE,
    ];

    return $extra;
  }

  /**
   * Implements hook_entity_view().
   */
  #[Hook('entity_view')]
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode): void {
    if (
      ($view_mode == 'default' || $view_mode == 'full') &&
      $entity instanceof NodeInterface &&
      $entity->bundle() == 'stanford_publication' &&
      $display->getComponent('citation_type') &&
      $citation = $this->getCitationEntity($entity)
    ) {
      $citation_type = $this->entityTypeManager
        ->getStorage('citation_type')
        ->load($citation->bundle());

      $citation_type = $citation_type->label() == 'Other' ? 'Publication' : $citation_type->label();
      $build['citation_type'] = [
        '#type' => 'markup',
        '#markup' => $citation_type,
      ];
    }
  }

  /**
   * Implements hook_entity_view_mode_alter().
   */
  #[Hook('entity_view_mode_alter')]
  public function entityViewModeAlter(&$view_mode, EntityInterface $entity): void {
    if (
      $entity instanceof CitationInterface &&
      $this->routeMatch->getRouteName() == 'entity.taxonomy_term.canonical'
    ) {
      // Change the view mode on taxonomy term pages to what the user chose in
      // the term overview page.
      $view_mode = $this->state
        ->get('stanford_publication.citation_format', $view_mode);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('node_insert')]
  public function nodeInsert(NodeInterface $entity): void {
    $this->nodePostSave($entity);
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('node_update')]
  public function nodeUpdate(NodeInterface $entity): void {
    $this->nodePostSave($entity);
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   */
  #[Hook('node_delete')]
  public function nodeDelete(NodeInterface $entity): void {
    if ($citation_entity = $this->getCitationEntity($entity)) {
      // Clean up nested Citation entity after node deletion.
      $citation_entity->delete();
    }
  }

  /**
   * Implements hook_field_widget_complete_form_alter().
   */
  #[Hook('field_widget_complete_form_alter')]
  public function fieldWidgetCompleteFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    if (!isset($context['items']) || !($context['items'] instanceof FieldItemListInterface)) {
      return;
    }

    // Change the help text on the title field of the citation entity form.
    if (
      $context['items']->getName() == 'title' &&
      $context['items']->getEntity()->getEntityTypeId() == 'citation'
    ) {
      $field_widget_complete_form['widget'][0]['value']['#description'] = $this->t('The title of the Publication');
    }

    if ($context['items']->getName() == 'su_publication_citation') {

      // Tweak the "Add New" button on the inline entity form.
      if (!empty($field_widget_complete_form['widget']['actions']['ief_add'])) {
        /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $button_value */
        $button_value = $field_widget_complete_form['widget']['actions']['ief_add']['#value'];
        $field_widget_complete_form['widget']['actions']['ief_add']['#value'] = $this->t('Add @type_singular', $button_value->getArguments(), $button_value->getOptions());
      }

      // Add the citation bundle name to the top for quick reference.
      if (!empty($field_widget_complete_form['widget']['form']['inline_entity_form'])) {
        $entity_type = $field_widget_complete_form['widget']['form']['inline_entity_form']['#entity_type'];
        $bundle = $field_widget_complete_form['widget']['form']['inline_entity_form']['#bundle'];
        $bundle_name = $this->bundleInfo->getBundleInfo($entity_type)[$bundle]['label'];
        $field_widget_complete_form['widget']['form']['inline_entity_form']['#prefix'] = "$bundle_name - {$field_widget_complete_form['widget']['#title']}";
      }
    }
  }

  /**
   * After the publication node is saved, save some data to the citation entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   Node entity object.
   */
  protected function nodePostSave(NodeInterface $entity): void {
    $citation_entity = $this->getCitationEntity($entity);
    $original_title = '';
    if ($entity->getOriginal()?->id()) {
      $original_title = $entity->getOriginal()->label();
    }

    if (!$citation_entity) {
      return;
    }
    if (empty($citation_entity->label()) || $citation_entity->label() == $original_title) {
      $citation_entity->setLabel($entity->label());
    }
    // Set the entity label to the node label & save the parent entity info.
    $citation_entity->setParentEntity($entity, 'su_publication_citation')
      ->save();
  }

  /**
   * Load the Citation entity from the node that has the citation data.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   *
   * @return \Drupal\stanford_publication\Entity\CitationInterface|null
   *   Loaded entity from the node field value.
   */
  protected function getCitationEntity(NodeInterface $node): ?CitationInterface {
    if ($node->bundle() !== 'stanford_publication') {
      return NULL;
    }

    $citation_field = 'su_publication_citation';
    if (
      $node->hasField($citation_field) &&
      $node->get($citation_field)->count()
    ) {
      $value = $node->get($citation_field)->get(0)->getValue();
      $citation_id = $value['target_id'];

      return $this->entityTypeManager
        ->getStorage('citation')
        ->load($citation_id);
    }

    return NULL;
  }

}
