<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\stanford_profile_helper\Hook\CronHooks;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for CronHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(CronHooks::class)]
class CronHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\CronHooks
   */
  protected CronHooks $hooks;

  /**
   * Mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mocked stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected StreamWrapperManagerInterface $streamWrapperManager;

  /**
   * Mocked file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->streamWrapperManager = $this->createMock(StreamWrapperManagerInterface::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->hooks = new CronHooks($this->entityTypeManager, $this->configFactory, $this->streamWrapperManager, $this->fileSystem);
  }

  /**
   * When the config_pages.loader service is unavailable, cron does nothing.
   */
  public function testCronReturnsEarlyWhenConfigPagesUnavailable(): void {
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $this->entityTypeManager->expects($this->never())->method('getStorage');
    $this->fileSystem->expects($this->never())->method('saveData');

    $this->hooks->cron();
  }

  /**
   * Sets up the config_pages.loader mock and returns it.
   */
  protected function mockConfigPagesLoader(): ConfigPagesLoaderServiceInterface {
    $configPages = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $configPages->method('getValue')
      ->willReturnCallback(function ($type, $field, $deltas = [], $key = NULL) {
        return match ($field) {
          'su_site_a11y_contact' => ['a11y@stanford.edu'],
          'su_site_url' => 'https://example.stanford.edu',
          'su_site_created' => '1600000000',
          'su_site_org' => [1, 2],
          'su_site_owner_contact' => ['owner@stanford.edu'],
          'su_site_renewal_due' => '2027-01-01',
          'su_site_tech_contact' => ['tech@stanford.edu'],
          'su_site_type' => 'department',
          'su_site_sunetid' => 'jdoe',
          default => NULL,
        };
      });

    $container = new ContainerBuilder();
    $container->set('config_pages.loader', $configPages);
    \Drupal::setContainer($container);

    return $configPages;
  }

  /**
   * Full happy path with organizations and a last editor found.
   */
  public function testCronHappyPathWithLastEditor(): void {
    $this->mockConfigPagesLoader();

    $org1 = $this->createMock(TermInterface::class);
    $org1->method('label')->willReturn('Org One');
    $org2 = $this->createMock(TermInterface::class);
    $org2->method('label')->willReturn('Org Two');

    $termStorage = $this->createMock(EntityStorageInterface::class);
    $termStorage->method('loadMultiple')->with([1, 2])->willReturn([1 => $org1, 2 => $org2]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->with(FALSE)->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([55 => 55]);

    $accessField = $this->createMock(FieldItemListInterface::class);
    $accessField->method('getString')->willReturn('1690000000');

    $editor = $this->createMock(UserInterface::class);
    $editor->method('get')->with('access')->willReturn($accessField);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('getQuery')->willReturn($query);
    $userStorage->method('load')->with(55)->willReturn($editor);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($type) use ($termStorage, $userStorage) {
        return match ($type) {
          'taxonomy_term' => $termStorage,
          'user' => $userStorage,
        };
      });

    $siteConfig = $this->createMock(ImmutableConfig::class);
    $siteConfig->method('get')->with('name')->willReturn('My Stanford Site');
    $themeConfig = $this->createMock(ImmutableConfig::class);
    $themeConfig->method('get')->with('default')->willReturn('stanford_basic');
    $this->configFactory->method('get')
      ->willReturnCallback(function ($name) use ($siteConfig, $themeConfig) {
        return match ($name) {
          'system.site' => $siteConfig,
          'system.theme' => $themeConfig,
        };
      });

    $this->streamWrapperManager->method('normalizeUri')
      ->with('private://stanford')
      ->willReturn('private://stanford');

    $this->fileSystem->expects($this->once())
      ->method('prepareDirectory')
      ->with('private://stanford', FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $capturedData = NULL;
    $this->fileSystem->expects($this->once())
      ->method('saveData')
      ->with($this->callback(function ($data) use (&$capturedData) {
        $capturedData = json_decode($data, TRUE);
        return TRUE;
      }), 'private://stanford/site-info.json');

    $this->hooks->cron();

    $this->assertSame(['a11y@stanford.edu'], $capturedData['accessibility']);
    $this->assertSame('https://example.stanford.edu', $capturedData['canonicalUrl']);
    $this->assertSame(1600000000, $capturedData['created']);
    $this->assertSame(['Org One', 'Org Two'], $capturedData['organizations']);
    $this->assertSame(['owner@stanford.edu'], $capturedData['owners']);
    $this->assertSame('2027-01-01', $capturedData['renewalDate']);
    $this->assertSame(['tech@stanford.edu'], $capturedData['siteManagers']);
    $this->assertSame('My Stanford Site', $capturedData['siteName']);
    $this->assertSame('department', $capturedData['siteType']);
    $this->assertSame('stanford_basic', $capturedData['theme']);
    $this->assertSame('jdoe', $capturedData['personSunet']);
    $this->assertSame(1690000000, $capturedData['lastEditorAccess']);
  }

  /**
   * When no eligible users exist, lastEditorAccess falls back to 0.
   */
  public function testCronWithNoEligibleUsers(): void {
    $this->mockConfigPagesLoader();

    $termStorage = $this->createMock(EntityStorageInterface::class);
    $termStorage->method('loadMultiple')->willReturn([]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('getQuery')->willReturn($query);
    $userStorage->expects($this->never())->method('load');

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function ($type) use ($termStorage, $userStorage) {
        return match ($type) {
          'taxonomy_term' => $termStorage,
          'user' => $userStorage,
        };
      });

    $siteConfig = $this->createMock(ImmutableConfig::class);
    $siteConfig->method('get')->willReturn('My Stanford Site');
    $themeConfig = $this->createMock(ImmutableConfig::class);
    $themeConfig->method('get')->willReturn('stanford_basic');
    $this->configFactory->method('get')
      ->willReturnCallback(function ($name) use ($siteConfig, $themeConfig) {
        return match ($name) {
          'system.site' => $siteConfig,
          'system.theme' => $themeConfig,
        };
      });

    $this->streamWrapperManager->method('normalizeUri')->willReturn('private://stanford');

    $capturedData = NULL;
    $this->fileSystem->expects($this->once())
      ->method('saveData')
      ->with($this->callback(function ($data) use (&$capturedData) {
        $capturedData = json_decode($data, TRUE);
        return TRUE;
      }), 'private://stanford/site-info.json');

    $this->hooks->cron();

    $this->assertSame(0, $capturedData['lastEditorAccess']);
    $this->assertSame([], $capturedData['organizations']);
  }

}
