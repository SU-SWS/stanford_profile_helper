<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

/**
 * Controller routines for admin block routes.
 */
class WordPressMigrateController extends ControllerBase {

  /**
   * Calls a method on a migration entity and reloads the listing page.
   *
   * @param \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration
   *   The migration content entity being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the listing page.
   */
  public function performOperation(WordPressMigrationInterface $wordpress_migration, $op) {
    $wordpress_migration->$op()->save();
    $this->messenger()
      ->addStatus($this->t('The migration settings have been updated.'));
    return $this->redirect('entity.wordpress_migration.collection');
  }

  /**
   * Field mapping source autocomplete suggestions from Step 4.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Client request with search parameters.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Matching suggestions.
   *
   * @see \Drupal\stanford_wordpress_migrate\Form\ImporterStep4FieldMappingForm::buildRow().
   */
  public function handleSourcesAutocomplete(Request $request) {
    $results = [];
    $input = Xss::filter($request->query->get('q'));
    $query = $request->query->getIterator();
    if (!isset($query['sources'])) {
      return new JsonResponse([]);
    }

    foreach ($query['sources'] as $source) {
      $results[] = [
        'value' => $source,
        'label' => $source,
      ];
    }

    // Get the typed string from the URL, if it exists.
    if (!$input) {
      return new JsonResponse($results);
    }
    // Filter out results that don't contain the search query.
    $matchingResults = array_filter($results, fn($choice) => str_contains($choice['value'], $input));
    return new JsonResponse(array_values($matchingResults));
  }

}
