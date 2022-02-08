<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hook event dispatcher base class with some helpful methods.
 *
 * @codeCoverageIgnore
 */
abstract class BaseEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Config Factory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity Type Manager Service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current active account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Core module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Get the messenger service.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   Messenger Service.
   */
  protected function messenger(): MessengerInterface {
    if (!$this->messenger) {
      $this->messenger = \Drupal::messenger();
    }
    return $this->messenger;
  }

  /**
   * Get the logger service.
   *
   * @param string $channel
   *   Logger service channel.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   Logger Service.
   */
  protected function logger(string $channel = 'stanford_profile_helper'): LoggerChannelInterface {
    if (!$this->logger) {
      $this->logger = \Drupal::logger($channel);
    }
    return $this->logger;
  }

  /**
   * Get the config factory service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   Config factory service.
   */
  protected function configFactory(): ConfigFactoryInterface {
    if (!$this->configFactory) {
      $this->configFactory = \Drupal::configFactory();
    }
    return $this->configFactory;
  }

  /**
   * Get the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   Entity type manager service.
   */
  protected function entityTypeManager(): EntityTypeManagerInterface {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

  /**
   * Get the current active user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   Current user object.
   */
  protected function currentUser(): AccountProxyInterface {
    if (!$this->currentUser) {
      $this->currentUser = \Drupal::currentUser();
    }
    return $this->currentUser;
  }

  /**
   * Get the core module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   Module handler service.
   */
  protected function moduleHandler(): ModuleHandlerInterface {
    if (!$this->moduleHandler) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }
    return $this->moduleHandler;
  }

}
