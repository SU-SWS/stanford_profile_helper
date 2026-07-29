<?php

namespace Drupal\Tests\stanford_decoupled\Unit\Plugin\Next\PreviewUrlGenerator;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssembler;
use Drupal\next\Entity\NextSiteInterface;
use Drupal\next\PreviewSecretGeneratorInterface;
use Drupal\stanford_decoupled\Plugin\Next\PreviewUrlGenerator\SimplePreview;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(SimplePreview::class)]
class SimplePreviewTest extends UnitTestCase {

  /**
   * Shared DI container for tests.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected ContainerBuilder $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container->set('current_user', $this->createMock(AccountProxyInterface::class));
    $this->container->set('datetime.time', $this->createMock(TimeInterface::class));
    $this->container->set('next.preview_secret_generator', $this->createMock(PreviewSecretGeneratorInterface::class));
    $this->container->set('entity_type.manager', $this->createMock(EntityTypeManagerInterface::class));
    $this->container->set('path.validator', $this->createMock(PathValidatorInterface::class));
    $this->container->set('string_translation', $this->getStringTranslationStub());
    $this->container->set('unrouted_url_assembler', $this->getUrlAssembler());
    \Drupal::setContainer($this->container);
  }

  public function testDefaultConfiguration() {
    $plugin = SimplePreview::create($this->container, [], '', []);
    $this->assertEquals(['vercel_bypass' => ''], $plugin->defaultConfiguration());
  }

  public function testBuildConfigurationForm() {
    $plugin = SimplePreview::create($this->container, [], '', []);
    $form_state = $this->createMock(FormStateInterface::class);

    $form = $plugin->buildConfigurationForm([], $form_state);

    $this->assertArrayHasKey('vercel_bypass', $form);
    $this->assertEquals('textfield', $form['vercel_bypass']['#type']);
    $this->assertEquals('', $form['vercel_bypass']['#default_value']);
  }

  public function testBuildConfigurationFormWithExistingValue() {
    $plugin = SimplePreview::create($this->container, ['vercel_bypass' => 'my-token'], '', []);
    $form_state = $this->createMock(FormStateInterface::class);

    $form = $plugin->buildConfigurationForm([], $form_state);

    $this->assertEquals('my-token', $form['vercel_bypass']['#default_value']);
  }

  public function testSubmitConfigurationForm() {
    $plugin = SimplePreview::create($this->container, [], '', []);
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')
      ->with('vercel_bypass')
      ->willReturn('new-bypass-token');

    $form = [];
    $plugin->submitConfigurationForm($form, $form_state);

    $config = $plugin->getConfiguration();
    $this->assertEquals('new-bypass-token', $config['vercel_bypass']);
  }

  public function testGenerator() {
    $plugin = SimplePreview::create($this->container, [], '', []);

    $site = $this->createMock(NextSiteInterface::class);
    $site->method('getPreviewUrl')->willReturn('http://example.test/foo/bar');
    $site->method('getPreviewSecret')->willReturn('baz');

    $entity_url = Url::fromUserInput('/bar/foo');
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('toUrl')->willReturn($entity_url);

    $url = $plugin->generate($site, $entity)->toString();
    $this->assertEquals('http://example.test/foo/bar?slug=/bar/foo&secret=baz', $url);
  }

  public function testGeneratorWithVercelBypass() {
    $plugin = SimplePreview::create($this->container, ['vercel_bypass' => 'bypass-secret'], '', []);

    $site = $this->createMock(NextSiteInterface::class);
    $site->method('getPreviewUrl')->willReturn('http://example.test/preview');
    $site->method('getPreviewSecret')->willReturn('token123');

    $entity_url = Url::fromUserInput('/node/1');
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('toUrl')->willReturn($entity_url);

    $url = $plugin->generate($site, $entity)->toString();
    $this->assertEquals(
      'http://example.test/preview?slug=/node/1&secret=token123&x-vercel-protection-bypass=bypass-secret&x-vercel-set-bypass-cookie=samesitenone',
      $url
    );
  }

  public function testGeneratorReturnsNullOnInvalidPreviewUrl() {
    $plugin = SimplePreview::create($this->container, [], '', []);

    $site = $this->createMock(NextSiteInterface::class);
    // A URI without a scheme causes Url::fromUri() to throw.
    $site->method('getPreviewUrl')->willReturn('not-a-valid-uri');
    $site->method('getPreviewSecret')->willReturn('baz');

    $entity_url = Url::fromUserInput('/bar/foo');
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('toUrl')->willReturn($entity_url);

    $result = $plugin->generate($site, $entity);
    $this->assertNull($result);
  }

  public function testValidate() {
    $plugin = SimplePreview::create($this->container, [], '', []);
    // validate() is a no-op; ensure it can be called without error.
    $result = $plugin->validate(new Request());
    $this->assertNull($result);
  }

  protected function getUrlAssembler() {
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $path_processor = $this->createMock(OutboundPathProcessorInterface::class);
    return new UnroutedUrlAssembler($request_stack, $path_processor);
  }

}
