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
use Drupal\pathauto\PathautoPatternInterface;
use Drupal\stanford_profile_helper\StanfordDefaultContentInterface;
use Drupal\stanford_profile_helper\StanfordProfileHelper;
use Drupal\stanford_profile_helper\SubtitleToParagraphs;

/**
 * Node entity hooks.
 */
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
    $this->alterMetatags($entity);

    // Invalidate any search result cached so the updated/new content will be
    // displayed for previously searched terms.
    Cache::invalidateTags(['config:views.view.search']);

    if ($entity->bundle() == 'stanford_media') {
      $this->buildAVTranscript($entity);
    }

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
   * Alter the metatags for a node if the node should not be in searches.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity being saved.
   */
  protected function alterMetatags(NodeInterface $node): void {
    if (!$node->hasField('su_search_exclusion')) {
      return;
    }

    $tags = json_decode($node->get('su_metatags')->getString(), TRUE) ?? [];
    unset($tags['robots']);
    if (!!$node->get('su_search_exclusion')?->getString()) {
      $tags['robots'] = 'noindex, nofollow';
    }
    $node->set('su_metatags', json_encode($tags));
  }

  /**
   * Implements hook_pathauto_pattern_alter().
   */
  #[Hook('pathauto_pattern_alter')]
  public function pathautoPatternAlter(PathautoPatternInterface $pattern, array $context) {
    if (
      isset($context['data']['node']) &&
      $context['data']['node'] instanceof NodeInterface &&
      $context['data']['node']->hasField('deleted') &&
      $context['data']['node']->get('deleted')->getString()
    ) {
      $pattern->setPattern('/trash' . $pattern->getPattern());
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_trash_delete().
   */
  #[Hook('node_trash_delete')]
  public function nodeTrashDelete(NodeInterface $node) {
    $url = ltrim($node->toUrl()->toString(), '/');
    if (str_starts_with($url, 'trash/')) {
      $url = str_replace('trash/', '', $url);
      $redirects = $this->entityTypeManager->getStorage('redirect')
        ->loadByProperties(['redirect_source' => $url]);
      foreach ($redirects as $redirect) {
        $redirect->delete();
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_trash_restore().
   */
  #[Hook('node_trash_restore')]
  public function nodeTrashRestore(NodeInterface $node) {
    $url = ltrim($node->toUrl()->toString(), '/');
    $redirects = $this->entityTypeManager->getStorage('redirect')
      ->loadByProperties(['redirect_source' => "trash/$url"]);
    foreach ($redirects as $redirect) {
      $redirect->delete();
    }
  }

  /**
   * Use the subtitle file upload and convert it into a text transcript.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity being saved.
   */
  protected function buildAVTranscript(NodeInterface $node) {
    if (!$node->hasField('su_media_subtitles')) {
      return;
    }
    if (!$node->get('su_media_subtitles')->count()) {
      $node->set('su_media_transcript', NULL);
      return;
    }

    $subtitleFile = $node->get('su_media_subtitles')->get(0)->getValue();
    $subtitleFile = $this->entityTypeManager->getStorage('file')
      ->load($subtitleFile['target_id']);
    if (!$subtitleFile) {
      $node->set('su_media_transcript', NULL);
      return;
    }
    $subtitles = file_get_contents($subtitleFile->getFileUri());
    if ($subtitles && $paragraphs = SubtitleToParagraphs::convertFromSrt($subtitles)) {
      $transcript = [
        'value' => $paragraphs,
        'format' => 'stanford_minimal_html',
      ];
      $node->set('su_media_transcript', $transcript);
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
