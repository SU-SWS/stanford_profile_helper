<?php

namespace Drupal\stanford_profile_helper\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\rabbit_hole\BehaviorInvokerInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the rabbit hole message for the respective node.
 */
#[Block(
  id: "rabbit_hole_message",
  admin_label: new TranslatableMarkup("Rabbit Hole Message"),
  context_definitions: [
    'node' => new EntityContextDefinition('entity:node', new TranslatableMarkup('Node'), TRUE),
  ]
)]
class RabbitHoleMessageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current'),
      $container->get('rabbit_hole.behavior_invoker'),
      $container->get('plugin.manager.rabbit_hole_behavior_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected CurrentPathStack $currentPath,
    protected BehaviorInvokerInterface $rabbitHoleBehavior,
    protected RabbitHoleBehaviorPluginManager $rabbitHolePluginManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $node = $this->getContextValue('node');
    $node_path = $node ? '/node/' . $node->id() : FALSE;
    // Display the block if the path is for the respective node, and the user
    // has permission for it.
    return AccessResult::allowedIf($this->currentPath->getPath() == $node_path && $account->hasPermission('rabbit hole bypass node'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];
    $node = $this->getContextValue('node');

    $rabbitHolePlugin = $this->getRabbitHolePlugin($node);
    if ($rabbitHolePlugin) {
      $redirectResponse = $rabbitHolePlugin->performAction($node);
      if ($redirectResponse instanceof TrustedRedirectResponse) {
        $build = [
          '#theme' => 'rabbit_hole_message',
          '#destination' => self::getTargetUrl($redirectResponse),
        ];
      }
    }
    return $build;
  }

  /**
   * Get the absolute target url from the rabbit hole settings.
   *
   * @param \Drupal\Core\Routing\TrustedRedirectResponse $redirect_response
   *   Rabbit hole redirect response.
   *
   * @return string
   *   Absolute url.
   */
  protected static function getTargetUrl(TrustedRedirectResponse $redirect_response): string {
    $target_url = $redirect_response->getTargetUrl();
    try {
      $url = Url::fromUserInput($target_url, ['absolute' => TRUE]);
    }
    catch (\Exception $e) {
      $url = Url::fromUri($target_url, ['absolute' => TRUE]);
    }
    return $url->toString(TRUE)->getGeneratedUrl();
  }

  /**
   * Get the rabbit hole behavior plugin for the given node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   Node with rabbit hole.
   *
   * @return \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface|null
   *   Redirect behavior plugin if applicable.
   */
  protected function getRabbitHolePlugin(NodeInterface $entity): ?RabbitHoleBehaviorPluginInterface {
    $values = $this->rabbitHoleBehavior->getRabbitHoleValuesForEntity($entity);
    if (isset($values['rh_action']) && $values['rh_action'] == 'page_redirect') {
      return $this->rabbitHolePluginManager->createInstance($values['rh_action'], $values);
    }
    return NULL;
  }

}
