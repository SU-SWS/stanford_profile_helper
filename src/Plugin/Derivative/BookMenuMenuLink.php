<?php

namespace Drupal\stanford_profile_helper\Plugin\Derivative;

use Drupal\book\BookManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BookMenuMenuLink extends DeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('book.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(protected BookManagerInterface $bookManager) {}

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->bookManager->getAllBooks() as $book) {
      $book_tree = $this->bookManager->bookTreeAllData($book['bid']);
      $this->getLinkDefinition(reset($book_tree), $base_plugin_definition);
    }
//    dpm($this->derivatives);
    return $this->derivatives;
  }

  public function getLinkDefinition($link, $base_plugin_definition) {
    $this->derivatives["book_menu.{$link['link']['bid']}.{$link['link']['nid']}"] = [
      ...$base_plugin_definition,
      'nid' => $link['link']['nid'],
      'menu_name' => 'books',
      'parent' => "stanford_profile_helper.book_menu:{$link['link']['bid']}:{$link['link']['pid']}",
      'expanded' => TRUE,
      'route_name' => 'entity.node.canonical',
      'route_parameters' => ['node' => $link['link']['nid']],
      'title' => $link['link']['title'],
    ];

    if (!empty($link['below'])) {
      foreach ($link['below'] as $below_link) {
        $this->getLinkDefinition($below_link, $base_plugin_definition);
      }
    }
  }

}
