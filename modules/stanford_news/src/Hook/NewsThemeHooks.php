<?php

declare(strict_types=1);

namespace Drupal\stanford_news\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;

/**
 * Theming and asset attachment hooks for stanford_news.
 */
class NewsThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'signup_block' => [
        'variables' => [
          'form_action' => NULL,
        ],
        'template' => 'block/signup-block',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_field__su_news_dek')]
  public function preprocessFieldSuNewsDek(&$variables): void {
    $variables['attributes']['class'][] = 'flex-10-of-12';
  }

  /**
   * Implements hook_preprocess_block().
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(&$variables): void {
    // Attach Library to the signup block wherever it goes.
    if (!empty($variables['elements']['#id'])) {
      if ($variables['elements']['#id'] == 'newslettersignup') {
        $variables['#attached']['library'][] = 'stanford_news/newsletter_signup';
      }
    }
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    // Get the node from the route.
    $node = \Drupal::routeMatch()->getParameter('node');

    // Not a node. Then just continue.
    if (!$node instanceof NodeInterface || $node->bundle() != 'stanford_news') {
      return;
    }
    if ($node->hasField('su_news_hide_social')) {
      $attachments['#attached']['drupalSettings']['stanfordNews'] = [
        'hideSocial' => (bool) $node->get('su_news_hide_social')->getString(),
      ];
    }
    $attachments['#attached']['library'][] = 'stanford_news/news_node';
  }

}
