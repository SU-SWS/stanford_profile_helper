<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The wordpress_migrate_field_processor attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class WordPressMigrateFieldProcessor extends AttributeBase {

  /**
   * Constructs a new WordPressMigrateFieldProcessor instance.
   *
   * @param string $id
   *   The plugin ID. There are some implementation bugs that make the plugin
   *   available only if the ID follows a specific pattern. It must be either
   *   identical to group or prefixed with the group. E.g. if the group is "foo"
   *   the ID must be either "foo" or "foo:bar".
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the plugin.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label,
    public readonly array $fieldType = [],
  ) {}

}
