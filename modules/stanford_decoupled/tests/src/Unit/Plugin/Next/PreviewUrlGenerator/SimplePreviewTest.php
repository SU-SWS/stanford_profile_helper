<?php

namespace Drupal\Tests\stanford_decoupled\Unit\Plugin\Next\PreviewUrlGenerator;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssembler;
use Drupal\next\Entity\NextSiteInterface;
use Drupal\next\PreviewSecretGeneratorInterface;
use Drupal\stanford_decoupled\Plugin\Next\PreviewUrlGenerator\SimplePreview;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SimplePreviewTest extends UnitTestCase {

  public function testGenerator() {
    $container = new ContainerBuilder();
    $container->set('current_user', $this->createMock(AccountProxyInterface::class));
    $container->set('datetime.time', $this->createMock(TimeInterface::class));
    $container->set('next.preview_secret_generator', $this->createMock(PreviewSecretGeneratorInterface::class));
    $container->set('entity_type.manager', $this->createMock(EntityTypeManagerInterface::class));
    $container->set('path.validator', $this->createMock(PathValidatorInterface::class));
    $container->set('unrouted_url_assembler', $this->getUrlAssembler());
    \Drupal::setContainer($container);

    $plugin = SimplePreview::create($container, [], '', []);

    $site = $this->createMock(NextSiteInterface::class);
    $site->method('getPreviewUrl')->willReturn('http://example.test/foo/bar');
    $site->method('getPreviewSecret')->willReturn('baz');

    $entity_url = Url::fromUserInput('/bar/foo');
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('toUrl')->willReturn($entity_url);

    $url = $plugin->generate($site, $entity)->toString();
    $this->assertEquals('http://example.test/foo/bar?slug=/bar/foo&secret=baz', $url);
  }

  protected function getUrlAssembler() {
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $path_processor = $this->createMock(OutboundPathProcessorInterface::class);
    return new UnroutedUrlAssembler($request_stack, $path_processor);
  }

}
