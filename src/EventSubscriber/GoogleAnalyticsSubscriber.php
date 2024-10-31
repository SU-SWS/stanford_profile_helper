<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\State\StateInterface;
use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event subscriber for Google Analytics module.
 */
final class GoogleAnalyticsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      GoogleAnalyticsEvents::ADD_CONFIG => 'addGoogleAnalyticsConfig',
    ];
  }

  /**
   * Constructs a GoogleAnalyticsSubscriber object.
   */
  public function __construct(private readonly ConfigPagesLoaderServiceInterface $configPagesLoader, private readonly RequestStack $requestStack, private readonly StateInterface $state) {}

  /**
   * Adjust Google Analytics configuration.
   *
   * @param \Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent $event
   *   GA module event.
   */
  public function addGoogleAnalyticsConfig(GoogleAnalyticsConfigEvent $event) {
    $canonical_url = $this->configPagesLoader->getValue('stanford_basic_site_settings', 'su_site_url', 0, 'uri');
    $current_host = $this->requestStack->getCurrentRequest()->getHttpHost();
    $domain = $canonical_url ? parse_url($canonical_url, PHP_URL_HOST) : $current_host;

    $event->addConfig('cookie_domain', $this->state->get('ga-domain', $domain));
    $event->addConfig('cookie_prefix', $this->state->get('ga-prefix', 'su'));
    $event->addConfig('cookie_expires', $this->state->get('ga-expire', 60 * 60 * 24 * 180));
  }

}
