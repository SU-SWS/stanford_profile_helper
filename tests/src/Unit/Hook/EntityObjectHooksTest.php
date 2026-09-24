<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\stanford_profile_helper\Hook\EntityObjectHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for EntityObjectHooks.
 */
#[Group('stanford_profile_helper')]
class EntityObjectHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\EntityObjectHooks
   */
  protected EntityObjectHooks $hooks;

  /**
   * Service container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected ContainerBuilder $container;

  /**
   * Purgers service stand-in that records invalidation calls.
   *
   * @var object
   */
  protected object $purgers;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new EntityObjectHooks();

    $path_validator = $this->createMock(PathValidatorInterface::class);
    $path_validator->method('getUrlIfValidWithoutAccessCheck')->willReturn(FALSE);

    $url_assembler = $this->createMock(UnroutedUrlAssemblerInterface::class);
    $url_assembler->method('assemble')
      ->willReturnCallback(fn($uri) => (new GeneratedUrl())->setGeneratedUrl(str_replace('base:', 'https://example.com/', $uri)));

    // Purge isn't a dependency of this module, so use simple stand-ins for its
    // services rather than mocking its interfaces.
    $invalidation_factory = new class() {

      public function get($type, $expression) {
        return "$type:$expression";
      }

    };
    $processors = new class() {

      public function get($id) {
        return $id;
      }

    };
    $this->purgers = new class() {

      public array $calls = [];

      public ?\Exception $exception = NULL;

      public function invalidate($processor, array $invalidations) {
        $this->calls[] = [$processor, $invalidations];
        if ($this->exception) {
          throw $this->exception;
        }
      }

    };

    $this->container = new ContainerBuilder();
    $this->container->set('path.validator', $path_validator);
    $this->container->set('unrouted_url_assembler', $url_assembler);
    $this->container->set('purge.invalidation.factory', $invalidation_factory);
    $this->container->set('purge.processors', $processors);
    $this->container->set('purge.purgers', $this->purgers);
    \Drupal::setContainer($this->container);
  }

  /**
   * Menu links get the url constraint and media types get a dialog link.
   */
  public function testEntityTypeAlter(): void {
    $menu_link = $this->createMock(EntityTypeInterface::class);
    $menu_link->expects($this->once())
      ->method('addConstraint')
      ->with('menu_link_item_url_constraint');
    $menu_link->expects($this->never())->method('setLinkTemplate');

    $media_type = $this->createMock(EntityTypeInterface::class);
    $media_type->method('getBundleOf')->willReturn('media');
    $media_type->expects($this->once())
      ->method('setLinkTemplate')
      ->with('dialog', '/media/dialog/{media}');

    $node_type = $this->createMock(EntityTypeInterface::class);
    $node_type->method('getBundleOf')->willReturn('node');
    $node_type->expects($this->never())->method('setLinkTemplate');

    $entity_types = [
      'menu_link_content' => $menu_link,
      'media_type' => $media_type,
      'node_type' => $node_type,
    ];
    $this->hooks->entityTypeAlter($entity_types);
  }

  /**
   * Only the redirect source field gets the trash constraint.
   */
  public function testEntityBaseFieldInfoAlter(): void {
    $source = $this->createMock(BaseFieldDefinition::class);
    $source->expects($this->once())
      ->method('addConstraint')
      ->with('redirect_source_trash', []);
    $fields = ['redirect_source' => $source];

    $node = $this->createMock(EntityTypeInterface::class);
    $node->method('id')->willReturn('node');
    $this->hooks->entityBaseFieldInfoAlter($fields, $node);

    $redirect = $this->createMock(EntityTypeInterface::class);
    $redirect->method('id')->willReturn('redirect');
    $this->hooks->entityBaseFieldInfoAlter($fields, $redirect);
  }

  /**
   * The global message config page gets its constraint.
   */
  public function testEntityBundleFieldInfoAlter(): void {
    $config_pages = $this->createMock(EntityTypeInterface::class);
    $config_pages->method('id')->willReturn('config_pages');

    // The bundle doesn't have the field.
    $fields = [];
    $this->hooks->entityBundleFieldInfoAlter($fields, $config_pages, 'stanford_global_message');
    $this->assertEmpty($fields);

    $enabled = $this->createMock(BaseFieldDefinition::class);
    $enabled->expects($this->once())
      ->method('addConstraint')
      ->with('global_message_constraint', []);
    $fields = ['su_global_msg_enabled' => $enabled];
    $this->hooks->entityBundleFieldInfoAlter($fields, $config_pages, 'other_bundle');
    $this->hooks->entityBundleFieldInfoAlter($fields, $config_pages, 'stanford_global_message');
  }

  /**
   * Redirect sources are only purged when the late runtime processor exists.
   */
  public function testPurgeRedirectSource(): void {
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')
      ->with('purge_processor_lateruntime')
      ->willReturnOnConsecutiveCalls(FALSE, TRUE);
    $this->container->set('module_handler', $module_handler);

    $redirect = $this->getRedirect('/foo/bar/');

    $this->hooks->purgeRedirectSource($redirect);
    $this->assertEmpty($this->purgers->calls);

    $this->hooks->purgeRedirectSource($redirect);
    $this->assertEquals([
      ['lateruntime', ['url:https://example.com/foo/bar']],
    ], $this->purgers->calls);
  }

  /**
   * A failed purge is logged instead of breaking the redirect save.
   */
  public function testPurgeRedirectSourceFailure(): void {
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->method('moduleExists')->willReturn(TRUE);
    $this->container->set('module_handler', $module_handler);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())->method('error')->with('Purge failed');
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')
      ->with('stanford_profile_helper')
      ->willReturn($logger);
    $this->container->set('logger.factory', $logger_factory);

    $this->purgers->exception = new \Exception('Purge failed');
    $this->hooks->purgeRedirectSource($this->getRedirect('foo'));
    $this->assertCount(1, $this->purgers->calls);
  }

  /**
   * Get a mocked redirect entity.
   *
   * @param string $source
   *   Redirect source path.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Mocked redirect.
   */
  protected function getRedirect(string $source): ContentEntityInterface {
    $source_field = $this->createMock(FieldItemListInterface::class);
    $source_field->method('getString')->willReturn($source);

    $redirect = $this->createMock(ContentEntityInterface::class);
    $redirect->method('get')
      ->with('redirect_source')
      ->willReturn($source_field);
    return $redirect;
  }

}
