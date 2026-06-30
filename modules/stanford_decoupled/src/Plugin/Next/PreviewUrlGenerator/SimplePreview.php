<?php

namespace Drupal\stanford_decoupled\Plugin\Next\PreviewUrlGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\next\Entity\NextSiteInterface;
use Drupal\next\Plugin\ConfigurablePreviewUrlGeneratorBase;
use Drupal\next\Plugin\PreviewUrlGeneratorBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the preview_url_generator plugin based on simple query string.
 *
 * @PreviewUrlGenerator(
 *  id = "simple_preview",
 *  label = "Simple Preview",
 *  description = "Use the preview token string for the parameter."
 * )
 */
class SimplePreview extends ConfigurablePreviewUrlGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['vercel_bypass' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['vercel_bypass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vercel Preview Bypass'),
      '#default_value' => $this->configuration['vercel_bypass'] ?? '',
      '#description' => $this->t('Vercel preview protection bypass token. Find this in Settings → Deployment Protection of your Vercel application.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['vercel_bypass'] = $form_state->getValue('vercel_bypass');
  }

  /**
   * {@inheritdoc}
   */
  public function generate(NextSiteInterface $next_site, EntityInterface $entity, ?string $resource_version = NULL): ?Url {
    $query = [
      'slug' => $entity->toUrl()->toString(TRUE)->getGeneratedUrl(),
      'secret' => $next_site->getPreviewSecret(),
      'x-vercel-protection-bypass' => $this->configuration['vercel_bypass'] ?? NULL,
      'x-vercel-set-bypass-cookie' => $this->configuration['vercel_bypass'] ? 'samesitenone' : NULL,
    ];
    try {
      return Url::fromUri($next_site->getPreviewUrl(), ['query' => array_filter($query),]);
    }
    catch (\Throwable $e) {
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(Request $request) {}

}
