<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_styles\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\editor\Entity\Editor;
use Drupal\node\NodeInterface;
use Symfony\Component\Finder\Finder;

/**
 * Hooks that build and attach the module's asset libraries.
 */
class LibraryHooks {

  /**
   * Library hooks constructor.
   *
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   Admin context service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   Module extension list service.
   */
  public function __construct(protected AdminContext $adminContext, protected RouteMatchInterface $routeMatch, protected ModuleExtensionList $moduleExtensionList) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    // Attach styles to front end pages.
    $is_admin = $this->adminContext->isAdminRoute();
    if ($is_admin) {
      return;
    }
    $attachments['#attached']['library'][] = 'stanford_profile_styles/stanford_profile_styles';
    $attachments['#attached']['library'][] = 'stanford_profile_styles/paragraph.react_paragraphs';
    $attachments['#attached']['library'][] = 'stanford_profile_styles/layout';

    // Get the node from the route.
    $node = $this->routeMatch->getParameter('node');

    // Not a node or on an admin route (node edit) Then just continue.
    if (!$node instanceof NodeInterface) {
      return;
    }

    $node_type = $node->getType();
    $attachments['#attached']['library'][] = "stanford_profile_styles/node.$node_type";

    // Check for our type and add library if a match.
    if ($node_type == "stanford_page") {
      // Check if stanford page is using a particular layout.
      $layout_target = $node->get('layout_selection')->getValue();
      if (isset($layout_target[0]['target_id']) && $layout_target[0]['target_id'] == "stanford_basic_page_full") {
        $attachments['#attached']['library'][] = "stanford_profile_styles/node.stanford_page.layout.full-width";
      }
    }
    if ($node_type == 'stanford_media') {
      $attachments['#attached']['library'][] = "stanford_profile_styles/node.stanford_media_content";
    }
  }

  /**
   * Implements hook_library_info_build().
   */
  #[Hook('library_info_build')]
  public function libraryInfoBuild(): array {
    $libraries = [];
    $module_path = $this->moduleExtensionList->getPath('stanford_profile_styles');

    // Find all css files in the dist/css directory.
    $finder = new Finder();
    $finder->in("$module_path/dist/css")
      ->files()
      ->name('/.css$/');

    foreach ($finder->getIterator() as $file) {
      $local_path = str_replace("$module_path/", '', $file->getPath());

      $path_parts = explode('/', $local_path);
      // Remove `dist` and `css` parts.
      unset($path_parts[0], $path_parts[1]);

      // This is the directory the file lives in.
      $library_level = reset($path_parts);
      $bucket = next($path_parts);
      $lib = basename($file->getFilename(), '.css');

      // Build the library definition.
      $libraries[trim("$bucket.$lib", '. ')] = [
        'css' => [
          $library_level => [
            "$local_path/{$file->getFileName()}" => [],
          ],
        ],
      ];
    }
    return $libraries;
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension): void {
    // Replace react_paragraphs field_formatter css with our own.
    if ($extension == "react_paragraphs" && isset($libraries["field_formatter"])) {
      unset($libraries['field_formatter']['css']['component']['js/build/css/react_paragraphs.field_formatter.css']);
      $libraries['field_formatter']['dependencies'][] = "stanford_profile_styles/paragraph.react_paragraphs";
    }
    // Disable confirm leave library during testing.
    if ($extension == 'confirm_leave' && getenv('CI')) {
      unset($libraries['confirm-leave']);
    }
  }

  /**
   * Implements hook_entity_display_build_alter().
   */
  #[Hook('entity_display_build_alter')]
  public function entityDisplayBuildAlter(&$build, $context): void {
    $is_admin = $this->adminContext->isAdminRoute();
    if (!$is_admin && $context['entity']->getEntityTypeId() == 'node') {
      $bundle = $context['entity']->bundle();
      // Add libraries that correspond to the current paragraph type. No need to
      // check for the existing library. No message is created if no library exists.
      $build['#attached']['library'][] = "stanford_profile_styles/node.$bundle";
    }

    if ($context['entity']->getEntityTypeId() != 'paragraph') {
      return;
    }
    $bundle = str_replace('stanford_', '', $context['entity']->bundle());
    // Add libraries that correspond to the current paragraph type. No need to
    // check for the existing library. No message is created if no library exists.
    $build['#attached']['library'][] = "stanford_profile_styles/paragraph.$bundle";
  }

  /**
   * Implements hook_ckeditor_css_alter().
   */
  #[Hook('ckeditor_css_alter')]
  public function ckeditorCssAlter(array &$css, Editor $editor): void {
    if (!$editor->hasAssociatedFilterFormat()) {
      return;
    }

    $known_formats = [
      'stanford_html',
      'stanford_minimal_html',
    ];

    if (in_array($editor->getFilterFormat()->id(), $known_formats)) {
      $module_path = $this->moduleExtensionList->getPath('stanford_profile_styles');
      $css[] = $module_path . '/dist/css/base/admin/ckeditor.css';
    }
  }

}
