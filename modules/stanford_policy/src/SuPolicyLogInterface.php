<?php

namespace Drupal\stanford_policy;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a policy log entity type.
 */
interface SuPolicyLogInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
