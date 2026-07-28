<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_publication\Unit\Hook;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\stanford_publication\Hook\FormHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FormHooks.
 */
#[Group('stanford_publication')]
#[CoversClass(FormHooks::class)]
class FormHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_publication\Hook\FormHooks
   */
  protected FormHooks $hooks;

  /**
   * State service mock.
   *
   * @var \Drupal\Core\State\StateInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->state = $this->createMock(StateInterface::class);
    $this->hooks = new FormHooks($this->state);
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Builds a form_state mock configured to return the given vocabulary id.
   *
   * @param string $vocabulary_id
   *   The vocabulary id to return.
   *
   * @return \Drupal\Core\Form\FormStateInterface&\PHPUnit\Framework\MockObject\MockObject
   *   Mocked form state.
   */
  protected function mockFormStateForVocabulary(string $vocabulary_id) {
    $vocabulary = $this->createMock(\Drupal\taxonomy\VocabularyInterface::class);
    $vocabulary->method('id')->willReturn($vocabulary_id);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('get')->with('taxonomy')->willReturn(['vocabulary' => $vocabulary]);

    return $form_state;
  }

  /**
   * A vocabulary other than the publication topics one is left untouched.
   */
  public function testFormTaxonomyOverviewTermsAlterOtherVocabulary() {
    $form_state = $this->mockFormStateForVocabulary('tags');
    $this->state->expects($this->never())->method('get');

    $form = [];
    $this->hooks->formTaxonomyOverviewTermsAlter($form, $form_state);

    $this->assertArrayNotHasKey('citation_format', $form);
    $this->assertArrayNotHasKey('#submit', $form);
  }

  /**
   * The publication topics vocabulary gets the citation format element.
   */
  public function testFormTaxonomyOverviewTermsAlterPublicationTopics() {
    $form_state = $this->mockFormStateForVocabulary('stanford_publication_topics');
    $this->state->method('get')
      ->with('stanford_publication.citation_format', 'chicago')
      ->willReturn('apa');

    $form = [];
    $this->hooks->formTaxonomyOverviewTermsAlter($form, $form_state);

    $this->assertSame('select', $form['citation_format']['#type']);
    $this->assertSame('apa', $form['citation_format']['#default_value']);
    $this->assertArrayHasKey('apa', $form['citation_format']['#options']);
    $this->assertArrayHasKey('chicago', $form['citation_format']['#options']);
    $this->assertSame([FormHooks::class, 'termOverviewSubmit'], $form['#submit'][0]);
  }

  /**
   * The submit handler updates the state and invalidates the cache tag when
   * the value changes.
   */
  public function testTermOverviewSubmitChangesValue() {
    $container = new ContainerBuilder();

    $state = $this->createMock(StateInterface::class);
    $state->method('get')->with('stanford_publication.citation_format')->willReturn('chicago');
    $state->expects($this->once())->method('set')->with('stanford_publication.citation_format', 'apa');
    $container->set('state', $state);

    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator->expects($this->once())->method('invalidateTags')->with(['citation_view']);
    $container->set('cache_tags.invalidator', $invalidator);

    \Drupal::setContainer($container);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->with('citation_format')->willReturn('apa');

    FormHooks::termOverviewSubmit([], $form_state);
  }

  /**
   * The submit handler does nothing when the value has not changed.
   */
  public function testTermOverviewSubmitNoChange() {
    $container = new ContainerBuilder();

    $state = $this->createMock(StateInterface::class);
    $state->method('get')->with('stanford_publication.citation_format')->willReturn('apa');
    $state->expects($this->never())->method('set');
    $container->set('state', $state);

    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $invalidator->expects($this->never())->method('invalidateTags');
    $container->set('cache_tags.invalidator', $invalidator);

    \Drupal::setContainer($container);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->with('citation_format')->willReturn('apa');

    FormHooks::termOverviewSubmit([], $form_state);
  }

}
