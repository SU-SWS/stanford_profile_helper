<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\stanford_profile_helper\StanfordDefaultContentInterface;
use Drupal\stanford_profile_helper\StanfordProfileHelper;

class NodeHooks {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\stanford_profile_helper\StanfordDefaultContentInterface $defaultContent
   *   Default content importer service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Core entity type manager service.
   */
  public function __construct(protected StanfordDefaultContentInterface $defaultContent, protected StateInterface $state, protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * On node insert event listener.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   */
  #[Hook('node_insert')]
  public function insertNode(NodeInterface $node): void {
    // Clear menu links cache if the node has a menu link data.
    if (
      $node->hasField('field_menulink') &&
      !$node->get('field_menulink')->isEmpty()
    ) {
      StanfordProfileHelper::clearMenuCacheTag();
    }
  }

  /**
   * Before saving a node, if a default content list page exists, create it.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node being saved.
   */
  #[Hook('node_presave')]
  public function preSaveNode(NodeInterface $entity): void {
    // Invalidate any search result cached so the updated/new content will be
    // displayed for previously searched terms.
    Cache::invalidateTags(['config:views.view.search']);

    if (
      $entity->bundle() == 'stanford_page' &&
      $entity->get('su_page_components')->count()
    ) {
      /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $component */
      foreach ($entity->get('su_page_components') as $component) {
        $paragraph = $component?->get('entity')?->getValue()?->bundle();
        if ($paragraph == 'stanford_filtered_lists') {
          $entity->set('layout_selection', 'stanford_basic_page_full');
        }
      }
    }

    if (
      InstallerKernel::installationAttempted() ||
      !$entity->isNew()
    ) {
      return;
    }

    $pages = [
      'stanford_news' => '0b83d1e9-688a-4475-9673-a4c385f26247',
      'stanford_event' => '8ba98fcf-d390-4014-92de-c77a59b30f3b',
      'stanford_person' => '673a8fb8-39ac-49df-94c2-ed8d04db16a7',
      'stanford_course' => '14768832-f763-4d27-8df6-7cd784886d57',
    ];
    $bundle = $entity->bundle();
    $state_key = 'stanford_profile_helper.default_content.' . $bundle;

    if (
      array_key_exists($bundle, $pages) &&
      !$this->state->get($state_key)
    ) {
      $this->state->set($state_key, TRUE);
      $count = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $bundle)
        ->count()
        ->execute();

      if ((int) $count == 0) {
        $new_entity = $this->defaultContent->createDefaultContent($pages[$bundle]);
        if ($new_entity?->toUrl()) {
          $this->messenger()
            ->addMessage($this->t('A new page was created automatically for you. View the @link page to make changes.', [
              '@link' => Link::fromTextAndUrl($new_entity->label(), $new_entity->toUrl())
                ->toString(),
            ]));
        }
      }
    }
  }

  /**
   * On node update event listener.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   */
  #[Hook('node_update')]
  public function updateNode(NodeInterface $node): void {
    $original_node = $node->getOriginal();

    // Compare the original menu link with the new menu link data. If any
    // important parts changed, clear the menu links cache.
    if (
      $node->hasField('field_menulink') && (
        !$node->get('field_menulink')->isEmpty() ||
        !$original_node->get('field_menulink')->isEmpty()
      )
    ) {
      if ($original_node->isPublished() != $node->isPublished()) {
        StanfordProfileHelper::clearMenuCacheTag();
        return;
      }

      $keys = ['title', 'description', 'weight', 'expanded', 'parent'];
      $changes = $node->get('field_menulink')->getValue();
      $original = $original_node->get('field_menulink')->getValue();

      foreach ($keys as $key) {
        $change_value = $changes[0][$key] ?? NULL;
        $original_value = $original[0][$key] ?? NULL;

        if ($change_value != $original_value) {
          StanfordProfileHelper::clearMenuCacheTag();
          return;
        }
      }
    }
  }

  /**
   * Force the menu link to clear when a node is deleted.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node being deleted.
   */
  #[Hook('node_delete')]
  public function deleteNode(NodeInterface $node): void {
    // If a node has menu link data, delete the menu link.
    if (
      $node->hasField('field_menulink') &&
      !$node->get('field_menulink')->isEmpty()
    ) {
      \Drupal::database()->delete('menu_tree')
        ->condition('id', 'menu_link_field:%', 'LIKE')
        ->condition('route_param_key', 'node=' . $node->id())
        ->execute();
      \Drupal::service('router.builder')->rebuildIfNeeded();
      StanfordProfileHelper::clearMenuCacheTag();
    }
  }

}
