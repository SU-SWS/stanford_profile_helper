<?php

declare(strict_types=1);

namespace Drupal\jumpstart_ui\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;

class JumpstartUiConfigPagesHooks {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected PathMatcherInterface $pathMatcher,
    protected RedirectDestinationInterface $redirectDestination,
    protected AccountProxyInterface $account
  ) {}

  #[Hook('preprocess_config_pages__stanford_local_footer')]
  public function preprocessConfigPagesLocalFooter(&$variables) {
    $redirectDestination = preg_replace('/\?.*$/', '', $this->redirectDestination->get());
    if ($this->pathMatcher->isFrontPage()) {
      $redirectDestination = '/';
    }

    $login_path = Url::fromRoute('samlauth.saml_controller_login', ['destination' => $redirectDestination]);

    /** @var \Drupal\config_pages\ConfigPagesInterface $config_page */
    $config_page = $variables['config_pages'];

    foreach ($variables['content']['su_local_foot_social'][0]['#items'] as &$link) {
      $url = $link['#url']->toString();
      $host = explode('.', parse_url($url, PHP_URL_HOST));
      $host = count($host) == 2 ? $host[0] : $host[1];
      $link['#attributes']['class'][] = Html::cleanCssIdentifier("su-local-footer__social-$host");
      $link['#title'] = [
        ['#type' => 'html_tag', '#tag' => 'i'],
        ['#type' => 'html_tag', '#tag' => 'span', '#value' => $link['#title']],
      ];
    }

    $component = [
      '#type' => 'component',
      '#component' => 'jumpstart_ui:localfooter',
      '#props' => [
        'custom_lockup' => !$config_page->get('su_local_foot_use_loc')
          ?->getString(),
        'lockup_option' => $config_page->get('su_local_foot_loc_op')
          ?->getString(),
        'use_default_logo' => !!$config_page->get('su_local_foot_use_logo')
          ?->getString(),
      ],
      '#slots' => [
        'lockup_title' => $this->configFactory->get('system.site')->get('name'),
        'cell1' => $variables['content']['su_local_foot_pr_co'] ?? NULL,
        'cell2' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['flex-container']],
          0 => [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'flex-md-6-of-12',
                'su-margin-bottom-1',
              ],
            ],
            'content' => $variables['content']['su_local_foot_se_co'] ?? NULL,
          ],
          1 => [
            '#type' => 'container',
            '#attributes' => ['class' => ['flex-md-6-of-12']],
            'content' => $variables['content']['su_local_foot_tr2_co'] ?? NULL,
          ],
        ],
        'cell3' => $variables['content']['su_local_foot_tr_co'] ?? NULL,
        'address' => $variables['content']['su_local_foot_address'] ?? NULL,
        'action_links' => $variables['content']['su_local_foot_action'] ?? NULL,
        'social_links' => $variables['content']['su_local_foot_social'] ?? NULL,
        'primary_links_header' => $variables['content']['su_local_foot_prime_h'] ?? NULL,
        'primary_links' => $variables['content']['su_local_foot_primary'] ?? NULL,
        'secondary_links_header' => $variables['content']['su_local_foot_second_h'] ?? NULL,
        'secondary_links' => $variables['content']['su_local_foot_second'] ?? NULL,
        'signup_form_content' => $variables['content']['su_local_foot_f_intro'] ?? NULL,
        'signup_form_action' => $variables['content']['su_local_foot_f_url'] ?? NULL,
        'signup_form_method' => $variables['content']['su_local_foot_f_method'] ?? NULL,
        'signup_form_field_submit_value' => $variables['content']['su_local_foot_f_button'] ?? NULL,
        'weblogin_text' => $variables['content']['su_local_foot_sunet_t'] ?? NULL,
        'weblogin_url' => $this->account->isAnonymous() ? $login_path->toString() : NULL,
        'site_logo' => $variables['content']['su_local_foot_loc_img'] ?? NULL,
        'site_logo_alt' => 'image alt',
        'line1' => $variables['content']['su_local_foot_line_1'] ?? NULL,
        'line2' => $variables['content']['su_local_foot_line_2'] ?? NULL,
        'line3' => $variables['content']['su_local_foot_line_3'] ?? NULL,
        'line4' => $variables['content']['su_local_foot_line_4'] ?? NULL,
        'line5' => $variables['content']['su_local_foot_line_5'] ?? NULL,
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
      ],
    ];
    $variables['content'] = [
      'contextual_links' => $variables['content']['contextual_links'] ?? NULL,
      'contents' => [
        '#access' => FALSE,
        ...$variables['content'],
      ],
      'component' => $component,
    ];
  }

  #[Hook('preprocess_config_pages__stanford_global_message')]
  public function preprocessConfigPagesGlobalMessage(&$variables) {
    /** @var \Drupal\config_pages\ConfigPagesInterface $config_page */
    $config_page = $variables['config_pages'];
    $level = $config_page->get('su_global_msg_type')->getString();

    $variables['content'] = [
      'contextual_links' => $variables['content']['contextual_links'] ?? NULL,
      'contents' => [
        '#access' => FALSE,
        ...$variables['content'],
      ],
      'component' => [
        '#type' => 'component',
        '#component' => 'jumpstart_ui:alert',
        '#props' => [
          'level' => $level,
        ],
        '#slots' => [
          'label' => $variables['content']['su_global_msg_label'] ?? NULL,
          'message' => [
            $variables['content']['su_global_msg_header'] ?? NULL,
            $variables['content']['su_global_msg_message'] ?? NULL,
            $variables['content']['su_global_msg_link'] ?? NULL,
          ],
        ],
      ],
    ];
  }

}
