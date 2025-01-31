<?php

namespace Drupal\Tests\stanford_person_importer\Unit\Plugin\migrate\source;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssembler;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\stanford_person_importer\CapInterface;
use Drupal\stanford_person_importer\Plugin\migrate\source\CapUrl;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CapUrlTest extends UnitTestCase {

  protected $plugin;

  protected $profileCount = 10;

  public function testUrls() {
    $config_pages = $this->createMock(ConfigPagesLoaderServiceInterface::class);

    $config_pages->method('getValue')
      ->will($this->returnCallback([$this, 'getConfigPageValue']));
    $entity = $this->createMock(ContentEntityInterface::class);
    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('getString')->willReturn('org:code');
    $entity->method('get')->willReturn($field_list);
    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('load')->willReturn($entity);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);
    $migration = $this->createMock(MigrationInterface::class);

    $container = new ContainerBuilder();

    $container->set('config_pages.loader', $config_pages);
    $container->set('config.factory', $this->getConfigFactoryStub([
      'migrate_plus.migration.su_stanford_person' => [
        'source' => [
          'fields' => [
            ['selector' => 'foo'],
            ['selector' => 'bar/bin/foo'],
            ['selector' => 'baz'],
          ],
        ],
      ],
    ]));
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('unrouted_url_assembler', $this->getUrlAssembler());

    $cap = $this->createMock(CapInterface::class);
    $cap->method('getTotalProfileCount')
      ->will($this->returnCallback([$this, 'getCapProfileCount']));
    $cap->method('getOrganizationUrl')
      ->willReturn(Url::fromUri('http://orgurl'));
    $cap->method('getWorkgroupUrl')
      ->willReturn(Url::fromUri('http://workgroupurl'));
    $cap->method('getSunetUrl')
      ->willReturn(Url::fromUri('http://suneturl'));

    $container->set('stanford_person_importer.cap', $cap);
    \Drupal::setContainer($container);

    $plugin = TestCapUrl::create($container, [
      'fields' => [],
      'ids' => [],
    ], 'cap_url', [], $migration);

    $this->assertEquals([
      'http://orgurl?ps=15&whitelist=foo%2Cbar%2Cbaz',
      'http://workgroupurl?p=1&ps=15&whitelist=foo%2Cbar%2Cbaz',
      'http://workgroupurl?p=2&ps=15&whitelist=foo%2Cbar%2Cbaz',
      'http://suneturl?whitelist=foo%2Cbar%2Cbaz',
    ], $plugin->getSourceUrls());
  }

  public function getConfigPageValue($bundle, $field, $delta, $key) {
    switch ($field) {
      case 'su_person_orgs':
        return [1, 2, 3];
      case 'su_person_child_orgs':
        return FALSE;
      case 'su_person_workgroup':
        return ['bar:foo', 'bin:foo'];
      case 'su_person_sunetid':
        return ['foofoofoo'];
    }
  }

  protected function getUrlAssembler() {
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $path_processor = $this->createMock(OutboundPathProcessorInterface::class);
    return new UnroutedUrlAssembler($request_stack, $path_processor);
  }

  public function getCapProfileCount() {
    $count =  $this->profileCount;
    $this->profileCount += 10;
    return $count;
  }

}

class TestCapUrl extends CapUrl {

  public function getSourceUrls() {
    return $this->sourceUrls;
  }

}
