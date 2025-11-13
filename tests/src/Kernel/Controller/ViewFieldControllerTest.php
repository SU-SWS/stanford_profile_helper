<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Kernel\Controller;

use Drupal\stanford_profile_helper\Controller\ViewFieldController;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test the ViewFieldController.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(ViewFieldController::class)]
class ViewFieldControllerTest extends SuProfileHelperKernelTestBase {

  /**
   * Controller under test.
   *
   * @var \Drupal\stanford_profile_helper\Controller\ViewFieldController
   */
  protected $controller;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->controller = ViewFieldController::create(\Drupal::getContainer());
  }

  /**
   * Test empty input returns empty response.
   */
  public function testEmptyInput() {
    $request = Request::create('/admin/viewfield/autocomplete/stanford_shared_tags/default');
    $response = $this->controller->handleArgumentsAutocomplete($request, 'stanford_shared_tags', 'default');

    $this->assertEquals('[]', $response->getContent());
  }

  /**
   * Test autocomplete suggestions for matching terms.
   */
  public function testAutocompleteWithMatchingTerms() {
    // Create a vocabulary and some terms.
    Vocabulary::create([
      'vid' => 'su_shared_tags',
      'name' => 'Shared Tags',
    ])->save();

    $term_names = ['Testing One', 'Testing Two', 'Different Term', 'Another Test'];
    foreach ($term_names as $name) {
      Term::create([
        'vid' => 'su_shared_tags',
        'name' => $name,
      ])->save();
    }

    $request = Request::create('/admin/viewfield/autocomplete/stanford_shared_tags/default', 'GET', ['q' => 'test']);
    $response = $this->controller->handleArgumentsAutocomplete($request, 'stanford_shared_tags', 'default');

    $results = json_decode($response->getContent(), TRUE);

    $this->assertCount(3, $results);
    $labels = array_column($results, 'label');
    $this->assertContains('Another Test', $labels);
    $this->assertContains('Testing One', $labels);
    $this->assertContains('Testing Two', $labels);
  }

  /**
   * Test autocomplete with prefix handling.
   */
  public function testAutocompleteWithPrefix() {
    Vocabulary::create([
      'vid' => 'su_shared_tags',
      'name' => 'Shared Tags',
    ])->save();

    Term::create([
      'vid' => 'su_shared_tags',
      'name' => 'Example Term',
    ])->save();

    $request = Request::create('/admin/viewfield/autocomplete/stanford_shared_tags/default', 'GET', ['q' => 'prefix+test/example']);
    $response = $this->controller->handleArgumentsAutocomplete($request, 'stanford_shared_tags', 'default');

    $results = json_decode($response->getContent(), TRUE);

    $this->assertCount(1, $results);
    $this->assertEquals('Example Term', $results[0]['label']);
    $this->assertStringStartsWith('prefix+test/', $results[0]['value']);
  }

  /**
   * Test autocomplete with multiple argument parts using plus separator.
   */
  public function testAutocompleteWithPlusSeparator() {
    Vocabulary::create([
      'vid' => 'su_shared_tags',
      'name' => 'Shared Tags',
    ])->save();

    Term::create([
      'vid' => 'su_shared_tags',
      'name' => 'Last Term',
    ])->save();

    $request = Request::create('/admin/viewfield/autocomplete/stanford_shared_tags/default', 'GET', ['q' => 'first+second+last']);
    $response = $this->controller->handleArgumentsAutocomplete($request, 'stanford_shared_tags', 'default');

    $results = json_decode($response->getContent(), TRUE);

    $this->assertCount(1, $results);
    $this->assertEquals('Last Term', $results[0]['label']);
    $this->assertStringContainsString('first+second+', $results[0]['value']);
  }

  /**
   * Test autocomplete with different view types.
   */
  #[DataProvider('viewTypesProvider')]
  public function testDifferentViewTypes(string $view_id, array $vocab_ids) {
    // Create vocabularies and terms for each view type.
    foreach ($vocab_ids as $vid) {
      Vocabulary::create([
        'vid' => $vid,
        'name' => ucfirst(str_replace('_', ' ', $vid)),
      ])->save();

      Term::create([
        'vid' => $vid,
        'name' => 'Sample Term',
      ])->save();
    }

    $request = Request::create("/admin/viewfield/autocomplete/{$view_id}/default", 'GET', ['q' => 'sample']);
    $response = $this->controller->handleArgumentsAutocomplete($request, $view_id, 'default');

    $results = json_decode($response->getContent(), TRUE);

    $this->assertNotEmpty($results);
    $this->assertEquals('Sample Term', $results[0]['label']);
  }

  /**
   * Data provider for different view types.
   *
   * @return array
   *   Array of view IDs and their corresponding vocabulary IDs.
   */
  public static function viewTypesProvider(): array {
    return [
      'stanford_shared_tags' => ['stanford_shared_tags', ['su_shared_tags']],
      'stanford_courses' => ['stanford_courses', ['su_course_quarters', 'su_course_subjects', 'su_course_tags']],
      'stanford_news' => ['stanford_news', ['stanford_news_topics']],
      'stanford_basic_pages' => ['stanford_basic_pages', ['basic_page_types']],
      'stanford_events' => ['stanford_events', ['stanford_event_types', 'event_audience']],
      'stanford_opportunities' => ['stanford_opportunities', ['opportunity_type']],
      'stanford_person' => ['stanford_person', ['stanford_person_types']],
      'stanford_publications' => ['stanford_publications', ['stanford_publication_topics']],
      'media_content' => ['media_content', ['media_content_types']],
    ];
  }

  /**
   * Test autocomplete with unknown view returns empty results.
   */
  public function testUnknownViewType() {
    $request = Request::create('/admin/viewfield/autocomplete/unknown_view/default', 'GET', ['q' => 'test']);
    $response = $this->controller->handleArgumentsAutocomplete($request, 'unknown_view', 'default');

    $results = json_decode($response->getContent(), TRUE);
    $this->assertEquals([], $results);
  }

  /**
   * Test XSS filtering on input.
   */
  public function testXssFiltering() {
    Vocabulary::create([
      'vid' => 'su_shared_tags',
      'name' => 'Shared Tags',
    ])->save();

    Term::create([
      'vid' => 'su_shared_tags',
      'name' => 'alert("xss")safe',
    ])->save();

    $request = Request::create('/admin/viewfield/autocomplete/stanford_shared_tags/default', 'GET', ['q' => '<script>alert("xss")</script>safe']);
    $response = $this->controller->handleArgumentsAutocomplete($request, 'stanford_shared_tags', 'default');

    $results = json_decode($response->getContent(), TRUE);

    $this->assertNotEmpty($results);
    $this->assertEquals('alert("xss")safe', $results[0]['label']);
  }

  /**
   * Test term suggestions are limited to 10 results.
   */
  public function testResultsLimitedToTen() {
    Vocabulary::create([
      'vid' => 'su_shared_tags',
      'name' => 'Shared Tags',
    ])->save();

    // Create 15 terms that all match.
    for ($i = 1; $i <= 15; $i++) {
      Term::create([
        'vid' => 'su_shared_tags',
        'name' => 'Test Term ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
      ])->save();
    }

    $request = Request::create('/admin/viewfield/autocomplete/stanford_shared_tags/default', 'GET', ['q' => 'test']);
    $response = $this->controller->handleArgumentsAutocomplete($request, 'stanford_shared_tags', 'default');

    $results = json_decode($response->getContent(), TRUE);

    $this->assertCount(10, $results);
  }

  /**
   * Test duplicate term names are removed.
   */
  public function testDuplicateTermNamesRemoved() {
    Vocabulary::create([
      'vid' => 'su_shared_tags',
      'name' => 'Shared Tags',
    ])->save();

    // Create duplicate term names.
    for ($i = 0; $i < 3; $i++) {
      Term::create([
        'vid' => 'su_shared_tags',
        'name' => 'Duplicate Term',
      ])->save();
    }

    Term::create([
      'vid' => 'su_shared_tags',
      'name' => 'Unique Term',
    ])->save();

    $request = Request::create('/admin/viewfield/autocomplete/stanford_shared_tags/default', 'GET', ['q' => 'term']);
    $response = $this->controller->handleArgumentsAutocomplete($request, 'stanford_shared_tags', 'default');

    $results = json_decode($response->getContent(), TRUE);

    $this->assertCount(2, $results);
    $labels = array_column($results, 'label');
    $this->assertContains('Duplicate Term', $labels);
    $this->assertContains('Unique Term', $labels);
  }

}
