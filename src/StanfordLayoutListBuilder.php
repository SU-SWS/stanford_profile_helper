<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\layout_library\Entity\Layout;
use Drupal\layout_library\Entity\LayoutListBuilder;

/**
 * Replaces the original layout library list builder to add data.
 *
 * @codeCoverageIgnore
 */
class StanfordLayoutListBuilder extends LayoutListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();
    $operations = $header['operations'];
    unset($header['operations']);
    $header['icon'] = $this->t('Icon');
    $header['operations'] = $operations;
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    $operations = $row['operations'];
    unset($row['operations']);

    $icon = self::getLayoutIcon($entity);

    $row['icon'] = ['data' => NULL];
    if ($icon) {
      $row['icon']['data'] = [
        '#theme' => 'image',
        '#uri' => $icon->getFileUri(),
        '#alt' => '',
        '#width' => 100,
        '#height' => 100,
      ];
    }

    $row['operations'] = $operations;
    return $row;
  }

  /**
   * Get the file entity for the configured icon on the layout library entity.
   *
   * @param \Drupal\layout_library\Entity\Layout $layout
   *   Layout library entity.
   *
   * @return \Drupal\file\FileInterface|null
   *   File entity if an icon is available.
   */
  protected static function getLayoutIcon(Layout $layout): ?FileInterface {
    return \Drupal::service('stanford_profile_helper.layout_library_icon')
      ->getLayoutIcon($layout);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity, ?CacheableMetadata $cacheability = NULL) {
    $operations = parent::getDefaultOperations($entity, $cacheability);
    if (isset($operations['edit'])) {
      $operations['icon'] = [
        'title' => $this->t('Edit Icon'),
        'weight' => 50,
        'url' => Url::fromRoute('entity.layout.edit_icon_form', [
          'node_type' => $entity->get('targetBundle'),
          'layout' => $entity->id(),
        ]),
      ];
    }
    return $operations;
  }

}
