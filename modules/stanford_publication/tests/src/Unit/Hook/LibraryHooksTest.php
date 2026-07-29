<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_publication\Unit\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\node\NodeInterface;
use Drupal\stanford_publication\Hook\LibraryHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for LibraryHooks.
 */
#[Group('stanford_publication')]
#[CoversClass(LibraryHooks::class)]
class LibraryHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_publication\Hook\LibraryHooks
   */
  protected LibraryHooks $hooks;

  /**
   * Module extension list mock.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleExtensionList;

  /**
   * Temporary fixture module directory used for the library scan tests.
   *
   * @var string
   */
  protected string $fixtureDir;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleExtensionList = $this->createMock(ModuleExtensionList::class);
    $this->hooks = new LibraryHooks($this->moduleExtensionList);
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    if (!empty($this->fixtureDir) && is_dir($this->fixtureDir)) {
      $this->removeDirectory($this->fixtureDir);
    }
    parent::tearDown();
  }

  /**
   * Recursively removes a directory tree.
   *
   * @param string $dir
   *   Directory to remove.
   */
  protected function removeDirectory(string $dir): void {
    $items = scandir($dir);
    foreach ($items as $item) {
      if ($item === '.' || $item === '..') {
        continue;
      }
      $path = "$dir/$item";
      if (is_dir($path)) {
        $this->removeDirectory($path);
      }
      else {
        unlink($path);
      }
    }
    rmdir($dir);
  }

  /**
   * Creates a fixture module directory containing the given css files.
   *
   * @param array $relative_paths
   *   List of paths, relative to "dist/css", to create as css files.
   *
   * @return string
   *   The fixture module root path.
   */
  protected function createFixtureModule(array $relative_paths): string {
    $this->fixtureDir = sys_get_temp_dir() . '/stanford_publication_library_test_' . uniqid();
    foreach ($relative_paths as $relative_path) {
      $full_path = "{$this->fixtureDir}/dist/css/$relative_path";
      mkdir(dirname($full_path), 0777, TRUE);
      file_put_contents($full_path, '.foo { color: red; }');
    }
    return $this->fixtureDir;
  }

  /**
   * Css files nested two levels deep build namespaced library definitions.
   */
  public function testLibraryInfoBuildWithFiles() {
    $module_path = $this->createFixtureModule([
      'component/menu/taxonomy_menu.css',
      'component/node/stanford_publication.css',
      'component/views/stanford_publication.css',
    ]);
    $this->moduleExtensionList->method('getPath')
      ->with('stanford_publication')
      ->willReturn($module_path);

    $libraries = $this->hooks->libraryInfoBuild();

    $this->assertArrayHasKey('menu.taxonomy_menu', $libraries);
    $this->assertSame(
      ['dist/css/component/menu/taxonomy_menu.css' => []],
      $libraries['menu.taxonomy_menu']['css']['component']
    );

    $this->assertArrayHasKey('node.stanford_publication', $libraries);
    $this->assertArrayHasKey('views.stanford_publication', $libraries);
  }

  /**
   * With no css files present, an empty libraries array is returned.
   */
  public function testLibraryInfoBuildWithoutFiles() {
    $this->fixtureDir = sys_get_temp_dir() . '/stanford_publication_library_test_' . uniqid();
    mkdir("{$this->fixtureDir}/dist/css", 0777, TRUE);
    $this->moduleExtensionList->method('getPath')
      ->with('stanford_publication')
      ->willReturn($this->fixtureDir);

    $libraries = $this->hooks->libraryInfoBuild();
    $this->assertSame([], $libraries);
  }

  /**
   * Non page contexts are ignored entirely.
   */
  public function testPreprocessNodeNotPage() {
    $variables = [];
    $this->hooks->preprocessNode($variables);
    $this->assertArrayNotHasKey('#attached', $variables);
  }

  /**
   * A falsy page flag is also ignored.
   */
  public function testPreprocessNodeFalsyPage() {
    $variables = ['page' => FALSE];
    $this->hooks->preprocessNode($variables);
    $this->assertArrayNotHasKey('#attached', $variables);
  }

  /**
   * On a page without a node in the variables, nothing is attached.
   */
  public function testPreprocessNodePageWithoutNode() {
    $variables = ['page' => TRUE];
    $this->hooks->preprocessNode($variables);
    $this->assertArrayNotHasKey('#attached', $variables);
  }

  /**
   * A non stanford_publication node bundle is ignored.
   */
  public function testPreprocessNodeWrongBundle() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('page');
    $variables = ['page' => TRUE, 'node' => $node];
    $this->hooks->preprocessNode($variables);
    $this->assertArrayNotHasKey('#attached', $variables);
  }

  /**
   * A non node "node" variable is ignored.
   */
  public function testPreprocessNodeNonNodeVariable() {
    $variables = ['page' => TRUE, 'node' => 'not-a-node'];
    $this->hooks->preprocessNode($variables);
    $this->assertArrayNotHasKey('#attached', $variables);
  }

  /**
   * A stanford_publication node page attaches the node library.
   */
  public function testPreprocessNodeAttachesLibrary() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_publication');
    $variables = ['page' => TRUE, 'node' => $node];
    $this->hooks->preprocessNode($variables);
    $this->assertContains(
      'stanford_publication/node.stanford_publication',
      $variables['#attached']['library']
    );
  }

  /**
   * The publication topics menu preprocess always attaches its libraries.
   */
  public function testPreprocessMenuStanfordPublicationTopics() {
    $variables = [];
    $this->hooks->preprocessMenuStanfordPublicationTopics($variables);
    $this->assertContains(
      'stanford_publication/menu.taxonomy_menu',
      $variables['#attached']['library']
    );
    $this->assertContains(
      'stanford_publication/taxonomy_menu',
      $variables['#attached']['library']
    );
  }

  /**
   * A view other than stanford_publications is left untouched.
   */
  public function testViewsPreRenderOtherView() {
    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn('some_other_view');
    $original_element = $view->element;
    $this->hooks->viewsPreRender($view);
    $this->assertSame($original_element, $view->element);
  }

  /**
   * The stanford_publications view gets the views library attached.
   */
  public function testViewsPreRenderStanfordPublications() {
    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn('stanford_publications');
    $this->hooks->viewsPreRender($view);
    $this->assertContains(
      'stanford_publication/views.stanford_publication',
      $view->element['#attached']['library']
    );
  }

}
