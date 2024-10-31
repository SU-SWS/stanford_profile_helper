<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\google_analytics\Constants\GoogleAnalyticsEvents;
use Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent;
use Drupal\google_analytics\GaAccount;
use Drupal\google_analytics\GaJavascriptObject;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;

/**
 * Test the event subscriber.
 *
 * @coversDefaultClass \Drupal\stanford_profile_helper\EventSubscriber\GoogleAnalyticsSubscriber
 */
class GoogleAnalyticsSubscriberTest extends SuProfileHelperKernelTestBase {

  public function testConfigChanges() {
    $config = $this->container->get('config.factory')
      ->getEditable('google_analytics.settings');
    $config->set('account', '');
    $config->save();

    $account = new GaAccount('');
    $javascript = new GaJavascriptObject('');
    $ga_config = new GoogleAnalyticsConfigEvent($javascript, $account);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($ga_config, GoogleAnalyticsEvents::ADD_CONFIG);

    $config = $ga_config->getConfig();
    $this->assertNotEmpty($config['cookie_domain']);
    $this->assertEquals('su', $config['cookie_prefix']);
    $this->assertEquals(15552000, $config['cookie_expires']);
  }

}
