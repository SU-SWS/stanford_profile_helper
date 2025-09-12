<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for migration events.
 */
class WordPressMigrateSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::POST_IMPORT => ['onPostMigrateImport'],
      MigrateEvents::POST_ROLLBACK => ['onPostMigrateRollback'],
    ];
  }

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $migrationPluginManager
   *   Plugin manager service.
   */
  public function __construct(
    protected readonly MigrationPluginManager $migrationPluginManager,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly Connection $database
  ) {}

  /**
   * After importing WordPress content, clear migration plugins so the media
   * importer can run correctly.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   Post migration event.
   */
  public function onPostMigrateImport(MigrateImportEvent $event): void {
    $this->flushMigrationPlugins($event->getMigration());
    if ($event->getMigration()->id() == 'wordpress_files:files') {
      $this->deleteStubbedFiles();
    }
  }

  /**
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   RoleBack event.
   */
  public function onPostMigrateRollback(MigrateRollbackEvent $event): void {
    $this->flushMigrationPlugins($event->getMigration());
  }

  /**
   * Delete all stubbed files that were made from migration_lookup.
   */
  protected function deleteStubbedFiles(): void {
    $dirFiles = array_keys($this->fileSystem->scanDirectory('public://', '/.*/', ['recurse' => FALSE]));
    $dirFiles = array_filter($dirFiles, fn($file) => !is_dir($file) && !preg_match('/\.\w+/', $file) && filesize($file) == 0);

    $files = $this->database->select('file_managed', 'f')
      ->fields('f', ['fid', 'uri'])
      ->execute()
      ->fetchAllKeyed();
    foreach (array_diff($dirFiles, $files) as $file) {
      $this->fileSystem->unlink($file);
    }
  }

  /**
   * Clear migration plugins if the migration was for WordPress content.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Completed migration.
   */
  protected function flushMigrationPlugins(MigrationInterface $migration): void {
    if (str_starts_with($migration->id(), 'wordpress_content:')) {
      $this->migrationPluginManager->clearCachedDefinitions();
    }
  }

}
