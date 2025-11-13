<?php

namespace Drupal\stanford_profile_helper\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\pathauto\AliasCleanerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to handle viewfield autocomplete suggestions.
 */
class ViewFieldController extends ControllerBase {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pathauto.alias_cleaner'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Controller constructor.
   *
   * @param \Drupal\pathauto\AliasCleanerInterface $aliasCleaner
   *   Pathauto alias cleaner.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected AliasCleanerInterface $aliasCleaner, ModuleHandlerInterface $moduleHandler, EntityTypeManagerInterface $entityTypeManager) {
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Handle the autocomplete suggestions based on the input string.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request with query.
   * @param string $view
   *   View id.
   * @param string $display
   *   Display id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Autocomplete suggestions.
   */
  public function handleArgumentsAutocomplete(Request $request, string $view, string $display) {
    $input = Xss::filter($request->query->get('q'));

    if (!$input) {
      return new JsonResponse([]);
    }

    $argParts = explode('/', $input);
    $lastArgPart = explode('+', array_pop($argParts));
    $arg = array_pop($lastArgPart);

    $prefix = $argParts ? implode('/', $argParts) . '/' : '';
    $prefix .= $lastArgPart ? implode('+', $lastArgPart) . '+' : '';

    $vocabs = match ($view) {
      'stanford_shared_tags' => ['su_shared_tags'],
      'stanford_courses' => [
        'su_course_quarters',
        'su_course_subjects',
        'su_course_tags',
      ],
      'stanford_news' => ['stanford_news_topics'],
      'stanford_basic_pages' => ['basic_page_types'],
      'stanford_events' => ['stanford_event_types', 'event_audience'],
      'stanford_opportunities' => ['opportunity_type'],
      'stanford_person' => ['stanford_person_types'],
      'stanford_publications' => ['stanford_publication_topics'],
      'media_content' => ['media_content_types'],
      default => []
    };

    $this->moduleHandler->alter('viewfield_argument_suggestion_vocabs', $vocabs, $view, $display);

    $term_names = $this->getTermNameSuggestions($arg, $vocabs);
    $this->moduleHandler->alter('viewfield_argument_suggestions', $term_names, $view, $display);

    $results = [];
    foreach ($term_names as $suggestion) {
      $results[] = [
        'value' => $prefix . $this->aliasCleaner->cleanString($suggestion),
        'label' => $suggestion,
      ];
    }

    return new JsonResponse($results);
  }

  /**
   * Fetch the term names from the given vocabularies that contain the input.
   *
   * @param string $input
   *   String query.
   * @param array $vocabs
   *   Vocabulary ids.
   *
   * @return array
   *   Indexed array of term names.
   */
  protected function getTermNameSuggestions(string $input, array $vocabs) {
    $query = $this->entityTypeManager->getStorage('taxonomy_term')
      ->getQuery()
      ->accessCheck()
      ->condition('name', "%$input%", 'LIKE');

    // Fetch a matching term from any vocabulary if none provided.
    if ($vocabs) {
      $query->condition('vid', $vocabs, 'IN');
    }

    // Fetch 20 terms in case there's some duplicate names. Slice it down to 10
    // at the end.
    $tids = $query->range(0, 20)
      ->sort('name')
      ->execute();

    // No terms match the input string.
    if (!$tids) {
      return [];
    }

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadMultiple($tids);
    foreach ($terms as &$term) {
      $term = $term->label();
    }
    return array_values(array_slice(array_unique($terms), 0, 10));
  }

}
