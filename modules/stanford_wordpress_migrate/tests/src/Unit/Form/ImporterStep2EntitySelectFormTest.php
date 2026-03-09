<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Form;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\stanford_wordpress_migrate\Form\ImporterStep2EntitySelectForm;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for ImporterStep2EntitySelectForm.
 */
class ImporterStep2EntitySelectFormTest extends UnitTestCase {

  /**
   * The form under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Form\ImporterStep2EntitySelectForm
   */
  protected $form;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);

    $this->form = new ImporterStep2EntitySelectForm($this->entityTypeManager);

    // Set up string translation
    $string_translation = $this->getStringTranslationStub();
    $this->form->setStringTranslation($string_translation);
  }

  /**
   * Test form ID.
   */
  public function testGetFormId(): void {
    $this->assertEquals('entity.wordpress_migration.step_2', $this->form->getFormId());
  }

  /**
   * Test create method.
   */
  public function testCreate(): void {
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
      ->method('get')
      ->with('entity_type.manager')
      ->willReturn($this->entityTypeManager);

    $form = ImporterStep2EntitySelectForm::create($container);
    $this->assertInstanceOf(ImporterStep2EntitySelectForm::class, $form);
  }

  /**
   * Test buildForm with no existing mappings.
   */
  public function testBuildFormNoMappings(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('media', [])
      ->willReturn([]);

    $form_state = $this->createMock(FormStateInterface::class);
    $cached_values = [
      'entity_type' => 'media',
      'wordpress_migration' => $migration,
    ];

    $api_routes = [
      '/wp/v2/media' => 'Media',
      '/wp/v2/posts' => 'Posts',
    ];

    $form_state->method('getTemporaryValue')
      ->willReturnCallback(function ($key) use ($cached_values, $api_routes) {
        if ($key === ['wizard']) {
          return $cached_values;
        }
        if ($key === ['wizard', 'api-routes']) {
          return $api_routes;
        }
        return NULL;
      });

    $form_state->expects($this->once())
      ->method('get')
      ->with('num_mappings')
      ->willReturn(NULL);

    $form_state->expects($this->once())
      ->method('set')
      ->with('num_mappings', 1);

    // Mock entity type definition
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->expects($this->once())
      ->method('getBundleEntityType')
      ->willReturn('media_type');

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('media')
      ->willReturn($entity_type);

    // Mock storage and bundles
    $bundle1 = $this->createMock(\Drupal\Core\Config\Entity\ConfigEntityInterface::class);
    $bundle1->expects($this->any())
      ->method('id')
      ->willReturn('image');
    $bundle1->expects($this->any())
      ->method('label')
      ->willReturn('Image');

    $bundle2 = $this->createMock(\Drupal\Core\Config\Entity\ConfigEntityInterface::class);
    $bundle2->expects($this->any())
      ->method('id')
      ->willReturn('video');
    $bundle2->expects($this->any())
      ->method('label')
      ->willReturn('Video');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['image' => $bundle1, 'video' => $bundle2]);

    // Mock access control
    $access_result = $this->createMock(AccessResultInterface::class);
    $access_result->expects($this->any())
      ->method('isAllowed')
      ->willReturn(TRUE);

    $access_handler = $this->createMock(EntityAccessControlHandlerInterface::class);
    $access_handler->expects($this->exactly(2))
      ->method('createAccess')
      ->willReturnOnConsecutiveCalls(TRUE, TRUE);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('media_type')
      ->willReturn($storage);

    $this->entityTypeManager->expects($this->once())
      ->method('getAccessControlHandler')
      ->with('media_type')
      ->willReturn($access_handler);

    $form = $this->form->buildForm([], $form_state);

    $this->assertArrayHasKey('mapping', $form);
    $this->assertEquals('table', $form['mapping']['#type']);
    $this->assertArrayHasKey(0, $form['mapping']);
    $this->assertArrayHasKey('source', $form['mapping'][0]);
    $this->assertArrayHasKey('destination', $form['mapping'][0]);
    $this->assertArrayHasKey('add_more', $form);
  }

  /**
   * Test buildForm with existing mappings.
   */
  public function testBuildFormWithExistingMappings(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('media', [])
      ->willReturn([
        '/wp/v2/media' => [
          'image' => ['field_mapping'],
          'video' => ['field_mapping'],
        ],
      ]);

    $form_state = $this->createMock(FormStateInterface::class);
    $cached_values = [
      'entity_type' => 'media',
      'wordpress_migration' => $migration,
    ];

    $api_routes = ['/wp/v2/media' => 'Media'];

    $form_state->method('getTemporaryValue')
      ->willReturnCallback(function ($key) use ($cached_values, $api_routes) {
        if ($key === ['wizard']) {
          return $cached_values;
        }
        if ($key === ['wizard', 'api-routes']) {
          return $api_routes;
        }
        return NULL;
      });

    $form_state->expects($this->once())
      ->method('get')
      ->with('num_mappings')
      ->willReturn(NULL);

    $form_state->expects($this->once())
      ->method('set')
      ->with('num_mappings', 2);

    // Mock entity type definition
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->expects($this->once())
      ->method('getBundleEntityType')
      ->willReturn('media_type');

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('media')
      ->willReturn($entity_type);

    // Mock storage and bundles
    $bundle = $this->createMock(\Drupal\Core\Config\Entity\ConfigEntityInterface::class);
    $bundle->expects($this->any())
      ->method('id')
      ->willReturn('image');
    $bundle->expects($this->any())
      ->method('label')
      ->willReturn('Image');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['image' => $bundle]);

    $access_handler = $this->createMock(EntityAccessControlHandlerInterface::class);
    $access_handler->expects($this->once())
      ->method('createAccess')
      ->willReturn(TRUE);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('media_type')
      ->willReturn($storage);

    $this->entityTypeManager->expects($this->once())
      ->method('getAccessControlHandler')
      ->with('media_type')
      ->willReturn($access_handler);

    $form = $this->form->buildForm([], $form_state);

    $this->assertArrayHasKey('mapping', $form);
    $this->assertArrayHasKey(0, $form['mapping']);
    $this->assertArrayHasKey(1, $form['mapping']);
    $this->assertEquals('/wp/v2/media', $form['mapping'][0]['source']['#default_value']);
    $this->assertEquals('image', $form['mapping'][0]['destination']['#default_value']);
    $this->assertEquals('/wp/v2/media', $form['mapping'][1]['source']['#default_value']);
    $this->assertEquals('video', $form['mapping'][1]['destination']['#default_value']);
  }

  /**
   * Test submitForm removes old mappings.
   */
  public function testSubmitFormRemovesOldMappings(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);

    // First call returns existing config with old mappings
    // Second call returns config after filtering
    $migration->expects($this->exactly(2))
      ->method('getConfigurationValue')
      ->willReturnCallback(function ($key, $default) {
        if ($key === 'media') {
          return [
            '/wp/v2/media' => [
              'image' => ['old_mapping'],
              'video' => ['old_mapping'],
            ],
            '/wp/v2/posts' => [
              'article' => ['old_mapping'],
            ],
          ];
        }
        // For the individual field mapping calls
        return [];
      });

    $set_call_count = 0;
    $migration->expects($this->exactly(2))
      ->method('setConfigurationValue')
      ->willReturnCallback(function ($key, $value) use (&$set_call_count) {
        $set_call_count++;
        if ($set_call_count === 1) {
          $this->assertEquals('media', $key);
          $this->assertIsArray($value);
        }
        else {
          $this->assertEquals(['media', '/wp/v2/media', 'image'], $key);
          $this->assertIsArray($value);
        }
      });

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->once())
      ->method('getTemporaryValue')
      ->with(['wizard'])
      ->willReturn([
        'wordpress_migration' => $migration,
        'entity_type' => 'media',
      ]);

    $form_state->expects($this->once())
      ->method('getValue')
      ->with('mapping')
      ->willReturn([
        ['source' => '/wp/v2/media', 'destination' => 'image'],
        ['source' => '', 'destination' => ''],
      ]);

    $form = [];
    $this->form->submitForm($form, $form_state);
  }

  /**
   * Test submitForm with no selected mappings.
   */
  public function testSubmitFormNoMappings(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);

    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('media', [])
      ->willReturn([]);

    $migration->expects($this->once())
      ->method('setConfigurationValue')
      ->with('media', []);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->once())
      ->method('getTemporaryValue')
      ->with(['wizard'])
      ->willReturn([
        'wordpress_migration' => $migration,
        'entity_type' => 'media',
      ]);

    $form_state->expects($this->once())
      ->method('getValue')
      ->with('mapping')
      ->willReturn([
        ['source' => '', 'destination' => ''],
      ]);

    $form = [];
    $this->form->submitForm($form, $form_state);
  }

  /**
   * Test submitForm adds new mappings.
   */
  public function testSubmitFormAddsNewMappings(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);

    $migration->expects($this->exactly(3))
      ->method('getConfigurationValue')
      ->willReturnCallback(function ($key, $default) {
        if ($key === 'media') {
          return [];
        }
        return [];
      });

    $migration->expects($this->exactly(3))
      ->method('setConfigurationValue');

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->once())
      ->method('getTemporaryValue')
      ->with(['wizard'])
      ->willReturn([
        'wordpress_migration' => $migration,
        'entity_type' => 'media',
      ]);

    $form_state->expects($this->once())
      ->method('getValue')
      ->with('mapping')
      ->willReturn([
        ['source' => '/wp/v2/media', 'destination' => 'image'],
        ['source' => '/wp/v2/media', 'destination' => 'video'],
      ]);

    $form = [];
    $this->form->submitForm($form, $form_state);
  }

  /**
   * Test getAllowedBundles filters by user access.
   */
  public function testGetAllowedBundlesFiltersAccess(): void {
    // Mock entity type definition
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->expects($this->once())
      ->method('getBundleEntityType')
      ->willReturn('node_type');

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('node')
      ->willReturn($entity_type);

    // Mock bundles
    $bundle1 = $this->createMock(\Drupal\Core\Config\Entity\ConfigEntityInterface::class);
    $bundle1->expects($this->any())
      ->method('id')
      ->willReturn('article');
    $bundle1->expects($this->any())
      ->method('label')
      ->willReturn('Article');

    $bundle2 = $this->createMock(\Drupal\Core\Config\Entity\ConfigEntityInterface::class);
    $bundle2->expects($this->any())
      ->method('id')
      ->willReturn('page');
    $bundle2->expects($this->any())
      ->method('label')
      ->willReturn('Page');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['article' => $bundle1, 'page' => $bundle2]);

    // Mock access handler - allow article, deny page
    $access_handler = $this->createMock(EntityAccessControlHandlerInterface::class);
    $access_handler->expects($this->exactly(2))
      ->method('createAccess')
      ->willReturnOnConsecutiveCalls(TRUE, FALSE);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node_type')
      ->willReturn($storage);

    $this->entityTypeManager->expects($this->once())
      ->method('getAccessControlHandler')
      ->with('node_type')
      ->willReturn($access_handler);

    $reflection = new \ReflectionClass($this->form);
    $method = $reflection->getMethod('getAllowedBundles');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->form, 'node');

    $this->assertCount(1, $result);
    $this->assertArrayHasKey('article', $result);
    $this->assertArrayNotHasKey('page', $result);
    $this->assertEquals('Article', $result['article']);
  }

}
