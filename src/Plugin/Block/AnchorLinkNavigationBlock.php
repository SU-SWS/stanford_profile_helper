<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Plugin\Block;

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
  category: new TranslatableMarkup('Stanford Profile Helper'),
)]
final class AnchorLinkNavigationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'example' => $this->t('Hello world!'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['example'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Example'),
      '#default_value' => $this->configuration['example'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['example'] = $form_state->getValue('example');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['content'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '',
      '#attributes' => ['class' => ['anchor-link-nav']],
      '#attached' => ['library' => ['stanford_profile_helper/anchor_link_nav']],
    ];
    return $build;
  }

}
