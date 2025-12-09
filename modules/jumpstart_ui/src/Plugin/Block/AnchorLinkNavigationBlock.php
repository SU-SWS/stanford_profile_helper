<?php

declare(strict_types=1);

namespace Drupal\jumpstart_ui\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an anchor link navigation block.
 */
#[Block(
  id: 'anchor_link_navigation',
  admin_label: new TranslatableMarkup('Anchor Link Navigation'),
)]
final class AnchorLinkNavigationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'orientation' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Orientation'),
      '#default_value' => $this->configuration['orientation'],
      '#empty_option' => $this->t('Vertical'),
      '#options' => [
        'horizontal' => $this->t('Horizontal'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['orientation'] = $form_state->getValue('orientation');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '',
      '#attributes' => [
        'class' => [
          'anchor-link-nav',
          'orientation-' . $this->configuration['orientation'] ?? 'vertical',
        ],
      ],
      '#attached' => ['library' => ['jumpstart_ui/anchor_link_nav']],
    ];
    return $build;
  }

}
