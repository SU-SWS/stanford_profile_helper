<?php

namespace Drupal\Tests\stanford_events_importer\Unit\Plugin\Field\FieldWidget;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormState;
use Drupal\stanford_events_importer\Plugin\Field\FieldWidget\StanfordEventsImporterAPIURLFieldWidget;
use Drupal\Tests\UnitTestCase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\Attributes\Group;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Class StanfordEventsImporterAPIURLFieldWidget.
 */
#[Group('stanford_events_importer')]
class StanfordEventsImporterAPIURLFieldWidgetTest extends UnitTestCase {

  /**
   * The fieldWidget Plugin.
   *
   * @var \Drupal\stanford_events_importer\Plugin\Field\FieldWidget\StanfordEventsImporterAPIURLFieldWidget
   */
  public $plugin;

  /**
   * The field def.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  public $fieldDefinition;

  /**
   * The mocked field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  public $container;

  /**
   * Test.
   */
  protected function setUp(): void {
    parent::setUp();
    $plugin_id = "stanford_events_importer_apiurl_field_widget";

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());

    $this->fieldTypePluginManager = $this
      ->createMock('Drupal\\Core\\Field\\FieldTypePluginManagerInterface');
    $this->fieldTypePluginManager
      ->expects($this
        ->any())
      ->method('getDefaultStorageSettings')
      ->willReturn([]);
    $this->fieldTypePluginManager
      ->expects($this
        ->any())
      ->method('getDefaultFieldSettings')
      ->willReturn([]);
    $this->container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($this->container);

    // Mock the Cache Backend.
    $cache = $this->createMock(CacheBackendInterface::class);
    $obj = new \StdClass();
    $obj->data = [
      19 => "Class",
      99 => "Gretzky",
    ];
    $cache->method('get')->willReturn($obj);

    $plugin_definition = [
      "field_types" => ["link"],
      "multiple_values" => FALSE,
      "id" => "stanford_events_importer_apiurl_field_widget",
      "module" => "stanford_events_importer",
      "label" => "Label",
      "class" => "Drupal\stanford_events_importer\Plugin\Field\FieldWidget\StanfordEventsImporterAPIURLFieldWidget",
      "provider" => "stanford_events_importer",
    ];

    $field_storage = new FieldStorageConfig([
      "langcode" => "en",
      "status" => TRUE,
      "dependencies" => [
        "module" => [
          "config_pages",
          "field_permissions",
          "link",
        ],
      ],
      "third_party_settings" => [
        "field_permissions" => [
          "permission_type" => "public",
        ],
      ],
      "id" => "config_pages.su_event_xml_url",
      "field_name" => "su_event_xml_url",
      "entity_type" => "config_pages",
      "type" => "link",
      "settings" => [],
      "module" => "link",
      "locked" => FALSE,
      "cardinality" => -1,
      "translatable" => TRUE,
      "indexes" => [],
      "persist_with_no_fields" => FALSE,
      "custom_storage" => FALSE,
    ]);

    $field_definition = new FieldConfig([
      "langcode" => "en",
      "deleted" => FALSE,
      "fieldStorage" => $field_storage,
      "status" => TRUE,
      "dependencies" => [
        "config" => [
          "config_pages.type.stanford_events_importer",
          "field.storage.config_pages.su_event_xml_url",
        ],
        "module" => [
          "link",
        ],
      ],
      "id" => "config_pages.stanford_events_importer.su_event_xml_url",
      "field_name" => "su_event_xml_url",
      "entity_type" => "config_pages",
      "bundle" => "stanford_events_importer",
      "label" => 'Events to be imported',
      "description" => '',
      "required" => TRUE,
      "translatable" => FALSE,
      "default_value" => [],
      "default_value_callback" => '',
      "settings" => [
        "link_type" => 16,
        "title" => 0,
      ],
      "field_type" => "link",
    ]);

    $this->fieldDefinition = $field_definition;

    $settings = [
      "placeholder_url" => "",
      "placeholder_title" => "",
    ];

    $third_party_settings = [];

    // Make it.
    $this->plugin = new StanfordEventsImporterAPIURLFieldWidget($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $cache);
  }

  /**
   * Test.
   */
  public function testMassageFormValues() {
    $values = [
      [
        "_other" => [
          'type' => 'organization',
          'org_status' => 'bookmarked',
          'organization' => 23,
        ],
      ],
      [
        "_other" => [
          'type' => 'category',
          'category' => 23,
        ],
      ],
      [
        "_other" => [
          'type' => 'featured',
        ],
      ],
      [
        "_other" => [
          'type' => 'today',
        ],
      ],

    ];

    $form = [];
    $form_state = new FormState();
    $results = $this->plugin->massageFormValues($values, $form, $form_state);
    $this->assertEquals("https://events-legacy.stanford.edu/xml/drupal/v2.php?organization=23&bookmarked", $results[0]['uri']);
    $this->assertEquals("https://events-legacy.stanford.edu/xml/drupal/v2.php?category=23", $results[1]['uri']);
    $this->assertEquals("https://events-legacy.stanford.edu/xml/drupal/v2.php?featured", $results[2]['uri']);
    $this->assertEquals("https://events-legacy.stanford.edu/xml/drupal/v2.php?today", $results[3]['uri']);

  }

  /**
   * Test.
   */
  public function testIsApplicable() {
    $this->assertTrue(StanfordEventsImporterAPIURLFieldWidget::isApplicable($this->fieldDefinition));
  }

  /**
   * Options missing from the cache are fetched from the feed only once.
   */
  public function testOptionsFetchedOnEmptyCache() {
    // The fetch flag is static, so reset it in case another test set it.
    (new \ReflectionProperty(StanfordEventsImporterAPIURLFieldWidget::class, 'optionsFetched'))->setValue(NULL, FALSE);

    $cached = new \stdClass();
    $cached->data = [279 => 'AASA'];
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturnOnConsecutiveCalls(FALSE, $cached, FALSE);

    $client = $this->createMock(ClientInterface::class);
    // One fetch of the category and organization feeds for both option lists.
    $client->expects($this->exactly(2))
      ->method('request')
      ->willReturn(new Response(200, [], '<OrganizationList><Organization><guid>279</guid><name>AASA</name></Organization></OrganizationList>'));
    $this->container->set('http_client', $client);
    $this->container->set('cache.default', $cache);
    $this->container->set('messenger', $this->createMock(MessengerInterface::class));

    $plugin = new StanfordEventsImporterAPIURLFieldWidget('stanford_events_importer_apiurl_field_widget', [], $this->fieldDefinition, [], [], $cache);

    $get_orgs = new \ReflectionMethod($plugin, 'getOrgOptions');
    $this->assertEquals([279 => 'AASA'], $get_orgs->invoke($plugin));

    // Nothing could be cached, and the feeds aren't requested again.
    $get_cats = new \ReflectionMethod($plugin, 'getCatOptions');
    $this->assertEquals([], $get_cats->invoke($plugin));
  }

}
