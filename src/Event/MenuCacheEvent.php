<?php

namespace Drupal\stanford_profile_helper\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Menu cache events.
 */
class MenuCacheEvent extends Event {

  const CACHE_CLEARED = 'cache_cleared';

}
