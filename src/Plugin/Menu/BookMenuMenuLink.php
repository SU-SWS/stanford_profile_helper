<?php

namespace Drupal\stanford_profile_helper\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BookMenuMenuLink extends MenuLinkBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription() {
    return "foo";
  }

  /**
   * {@inheritDoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  public function getUrlObject($title_attribute = TRUE) {
    return parent::getUrlObject($title_attribute); // TODO: Change the autogenerated stub
  }

  /**
   * {@inheritDoc}
   */
  public function updateLink(array $new_definition_values, $persist) {}

  /**
   * {@inheritDoc}
   */
  public function isDeletable() {
    return FALSE;
  }

  public function getEditRoute() {
    preg_match('/\d+$/', $this->getPluginId(), $nid_match);
    $nid = $nid_match[0];
    return Url::fromRoute('entity.node.edit_form', ['node' => $nid]);
  }

}
