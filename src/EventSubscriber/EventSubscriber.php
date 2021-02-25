<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventSubscriber.
 *
 * @package Drupal\stanford_profile\EventSubscriber
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [ConfigEvents::IMPORT => 'onConfigImport'];
  }

  /**
   * EventSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * After configuration is imported, run anything we need.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   Config import event.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    // todo: Remove this after the March 2021 Release.
    $this->createPublicationsPage();
  }

  /**
   * Create a publications node page if one doesn't exist.
   *
   * This method can be removed in a later release. It is essentially like an
   * update hook, but because it depends on configuration, we can't do it in an
   * update hook.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createPublicationsPage() {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    $row_storage = $this->entityTypeManager->getStorage('paragraph_row');

    $existing_page = $node_storage->loadByProperties(['title' => 'Publications']);
    $existing_uuid = $node_storage->loadByProperties(['uuid' => 'ce9cb7ca-6c59-4eea-9934-0a33057a7ff2']);
    // If a publications node already exists, leave it be.
    if (!empty($existing_page) || !empty($existing_uuid)) {
      return;
    }

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $paragraph_storage->create([
      'type' => 'stanford_lists',
      'su_list_view' => [
        'target_id' => 'stanford_publications',
        'display_id' => 'apa_list',
      ],
    ]);
    $paragraph->setBehaviorSettings('react', [
      'width' => 12,
      'label' => 'Publication List',
    ]);
    $paragraph->save();
    $row = $row_storage->create([
      'type' => 'node_stanford_page_row',
      'su_page_components' => [
        [
          'entity' => $paragraph,
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);
    $row->save();

    $node_storage->create([
      'type' => 'stanford_page',
      'title' => 'Publications',
      'uuid' => 'ce9cb7ca-6c59-4eea-9934-0a33057a7ff2',
      'su_page_components' => [
        [
          'entity' => $row,
          'target_id' => $row->id(),
          'target_revision_id' => $row->getRevisionId(),
        ],
      ],
    ])->save();

  }

}
