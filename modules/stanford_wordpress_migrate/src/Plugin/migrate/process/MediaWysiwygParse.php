<?php

namespace Drupal\stanford_wordpress_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Replace possible embed code and image tags with media tags.
 *
 * Examples:
 *
 * @code
 *   process:
 *     result:
 *       plugin: media_wysiwyg_parse
 *       source: html
 *       image_domain: 'foo.bar.com'
 * @endcode
 */
#[MigrateProcess(id: 'media_wysiwyg_parse')]
class MediaWysiwygParse extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Logger channel service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('http_client'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * Media wysiwyg parser process plugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   File system service.
   * @param \GuzzleHttp\ClientInterface $client
   *   Guzzle client service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FileSystemInterface $fileSystem,
    protected ClientInterface $client,
    protected EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $loggerFactory->get('stanford_wordpress_migrate');
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      return $value;
    }

    preg_match_all('/<iframe.*?><\/iframe>/', $value, $iframes);
    preg_match_all('/<img.*?>/', $value, $images);

    // Loop through and replace iframes with media tokens.
    foreach ($iframes[0] as $iframe) {
      try {
        if ($replacement = $this->getMediaTokenFromMarkup($iframe)) {
          $value = str_replace($iframe, $replacement, $value);
        }
      }
      catch (\Throwable $e) {
      }
    }

    if (empty($this->configuration['image_domain'])) {
      return $value;
    }

    // Loop through and replace images with media tokens.
    foreach ($images[0] as $image) {
      try {
        if ($replacement = $this->getMediaTokenFromMarkup($image)) {
          $value = str_replace($image, $replacement, $value);
        }
      }
      catch (\Throwable $e) {
      }
    }
    return $value;
  }

  /**
   * @param string $markup
   *
   * @return string|null
   */
  protected function getMediaTokenFromMarkup(string $markup): ?string {
    if (str_contains($markup, '<img ')) {
      preg_match('/src="(.*)"/', $markup, $imageSource);
      preg_match('/alt="(.*)"/', $markup, $imageAlt);

      // Only download images that match the configured domain.
      if (!str_contains($imageSource[1], $this->configuration['image_domain'])) {
        return NULL;
      }

      $media = $this->getImageMedia($imageSource[1], $imageAlt[1] ?? '');
    }
    else {
      $media = $this->getEmbedMedia($markup);
    }

    return $media ? sprintf('<drupal-media data-entity-type="media" data-entity-uuid="%s">&nbsp;</drupal-media>', $media->uuid()) : NULL;
  }

  /**
   * @param string $source
   * @param string $alt
   *
   * @return \Drupal\media\MediaInterface|null
   */
  protected function getImageMedia(string $source, string $alt = ''): ?MediaInterface {
    $mediaStorage = $this->entityTypeManager->getStorage('media');

    $directory = 'public://media/image/wordpress/wysiwyg';
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $destinationUri = "$directory/" . basename($source);

    if ($media = $this->getExistingMedia($destinationUri)) {
      return $media;
    }
    $file = $this->downloadFile($source, $destinationUri);

    /** @var \Drupal\media\MediaInterface $media */
    $media = $mediaStorage->create([
      'bundle' => 'image',
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => $alt,
        'title' => '',
      ],
    ]);
    $media->save();
    return $media;
  }

  /**
   * Find an existing media that is associated to a file with the give dest.
   *
   * @param string $destination
   *   Local path to the file.
   *
   * @return \Drupal\media\MediaInterface|null
   *   Found media entity if one exists.
   */
  protected function getExistingMedia(string $destination): ?MediaInterface {
    $fileStorage = $this->entityTypeManager->getStorage('file');
    $mediaStorage = $this->entityTypeManager->getStorage('media');

    $existingFile = $fileStorage->loadByProperties(['uri' => $destination]);
    if (!$existingFile) {
      return NULL;
    }
    $fileId = reset($existingFile)->id();
    $existingMedia = $mediaStorage->loadByProperties([
      'bundle' => 'image',
      'field_media_image' => $fileId,
    ]);
    return $existingMedia ? reset($existingMedia) : NULL;
  }

  /**
   * Download the give url and create a file entity for it.
   *
   * @param string $source
   *   External source url.
   * @param string $destination
   *   Local path to download the file to.
   *
   * @return \Drupal\file\FileInterface
   *   Generated file entity.
   */
  protected function downloadFile(string $source, string $destination): FileInterface {
    $destStream = @fopen($destination, 'w');
    $this->client->request('GET', $source, ['sink' => $destStream]);
    if (is_resource($destStream)) {
      fclose($destStream);
    }
    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')
      ->create(['uri' => $destination]);
    $file->setPermanent();
    $file->save();
    return $file;
  }

  /**
   * @param string $markup
   *
   * @return \Drupal\media\MediaInterface|null
   */
  protected function getEmbedMedia(string $markup): ?MediaInterface {
    preg_match('/src="(.*?)"/', $markup, $src);
    $field = 'field_media_embeddable_code';
    $bundle = 'embeddable';
    $name = 'Imported Embed Code - ' . substr(md5($markup), 0, 5);

    if ($src && str_contains($src[1], 'youtube')) {
      preg_match('/(\/embed\/|v=)([\w-]+)/', $src[1], $videoId);

      if ($videoId) {
        $field = 'field_media_oembed_video';
        $bundle = 'video';
        $markup = sprintf('https://www.youtube.com/watch?v=%s', $videoId[2]);
        $name = NULL;
      }
    }

    $mediaStorage = $this->entityTypeManager->getStorage('media');
    $existingMedia = $mediaStorage->loadByProperties([
      'bundle' => $bundle,
      $field => $markup,
    ]);
    if ($existingMedia) {
      return reset($existingMedia);
    }

    if ($bundle == 'embeddable' && !preg_match('/title=".+?"/', $markup)) {
      $this->logger->warning('Embed media does not contain a title attribute: %embed', ['%embed' => $markup]);
    }
    /** @var \Drupal\media\MediaInterface $media */
    $media = $mediaStorage->create([
      'bundle' => $bundle,
      'name' => $name,
      $field => $markup,
    ]);
    $media->save();

    return $media;
  }

}
