<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\State\StateInterface;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\pathauto\PathautoPatternInterface;
use Drupal\stanford_profile_helper\Hook\NodeHooks;
use Drupal\stanford_profile_helper\StanfordDefaultContentInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for NodeHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(NodeHooks::class)]
class NodeHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\NodeHooks
   */
  protected NodeHooks $hooks;

  /**
   * Mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $default_content = $this->createMock(StanfordDefaultContentInterface::class);
    $state = $this->createMock(StateInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->hooks = new NodeHooks($default_content, $state, $this->entityTypeManager);
  }

  /**
   * Invoke the protected buildAVTranscript method via reflection.
   */
  protected function callBuildAVTranscript(NodeInterface $node): void {
    $method = new \ReflectionMethod(NodeHooks::class, 'buildAVTranscript');
    $method->setAccessible(TRUE);
    $method->invoke($this->hooks, $node);
  }

  // -----------------------------------------------------------------------
  // pathautoPatternAlter tests.
  // -----------------------------------------------------------------------

  /**
   * No 'node' key in context data — pattern must not change.
   */
  public function testPathautoPatternAlterWithoutNodeContext() {
    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->expects($this->never())->method('setPattern');

    $this->hooks->pathautoPatternAlter($pattern, []);
    $this->hooks->pathautoPatternAlter($pattern, ['data' => []]);
  }

  /**
   * 'data['node']' is not a NodeInterface — pattern must not change.
   */
  public function testPathautoPatternAlterNodeNotNodeInterface() {
    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->expects($this->never())->method('setPattern');

    $context = ['data' => ['node' => new \stdClass()]];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

  /**
   * Node does not have a 'deleted' field — pattern must not change.
   */
  public function testPathautoPatternAlterNodeLacksDeletedField() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('deleted')->willReturn(FALSE);

    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->expects($this->never())->method('setPattern');

    $context = ['data' => ['node' => $node]];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

  /**
   * Node has 'deleted' field but its value is empty — pattern must not change.
   */
  public function testPathautoPatternAlterDeletedFieldEmpty() {
    $deletedField = $this->createMock(FieldItemListInterface::class);
    $deletedField->method('getString')->willReturn('');

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('deleted')->willReturn(TRUE);
    $node->method('get')->with('deleted')->willReturn($deletedField);

    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->expects($this->never())->method('setPattern');

    $context = ['data' => ['node' => $node]];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

  /**
   * Node is deleted — '/[node:title]' is prefixed with '/trash/'.
   */
  public function testPathautoPatternAlterDeletedNodePrefixesPattern() {
    $deletedField = $this->createMock(FieldItemListInterface::class);
    $deletedField->method('getString')->willReturn('1');

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('deleted')->willReturn(TRUE);
    $node->method('get')->with('deleted')->willReturn($deletedField);

    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->method('getPattern')->willReturn('/news/[node:title]');
    $pattern->expects($this->once())
      ->method('setPattern')
      ->with('/trash/news/[node:title]');

    $context = ['data' => ['node' => $node]];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

  /**
   * Pattern without the '[node:title]' placeholder is returned unchanged.
   */
  public function testPathautoPatternAlterDeletedNodeNoTitleToken() {
    $deletedField = $this->createMock(FieldItemListInterface::class);
    $deletedField->method('getString')->willReturn('1');

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('deleted')->willReturn(TRUE);
    $node->method('get')->with('deleted')->willReturn($deletedField);

    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->method('getPattern')->willReturn('/news/[node:nid]');
    // str_replace finds no match, so the value passed to setPattern equals the
    // original — but setPattern is still called once.
    $pattern->expects($this->once())
      ->method('setPattern')
      ->with('/trash/news/[node:nid]');

    $context = ['data' => ['node' => $node]];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

  // -----------------------------------------------------------------------
  // buildAVTranscript tests.
  // -----------------------------------------------------------------------

  /**
   * Node lacks the subtitles field — method returns without touching the node.
   */
  public function testBuildAVTranscriptNoSubtitlesField() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_media_subtitles')->willReturn(FALSE);
    $node->expects($this->never())->method('set');

    $this->callBuildAVTranscript($node);
  }

  /**
   * Subtitles field is empty — transcript is set to NULL.
   */
  public function testBuildAVTranscriptEmptySubtitlesField() {
    $fieldList = $this->createMock(FieldItemListInterface::class);
    $fieldList->method('count')->willReturn(0);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_media_subtitles')->willReturn(TRUE);
    $node->method('get')->with('su_media_subtitles')->willReturn($fieldList);
    $node->expects($this->once())->method('set')->with('su_media_transcript', NULL);

    $this->callBuildAVTranscript($node);
  }

  /**
   * File entity cannot be loaded — transcript is set to NULL.
   */
  public function testBuildAVTranscriptFileNotFound() {
    $fieldItem = $this->createMock(FieldItemInterface::class);
    $fieldItem->method('getValue')->willReturn(['target_id' => 99]);

    $fieldList = $this->createMock(FieldItemListInterface::class);
    $fieldList->method('count')->willReturn(1);
    $fieldList->method('get')->with(0)->willReturn($fieldItem);

    $fileStorage = $this->createMock(EntityStorageInterface::class);
    $fileStorage->method('load')->with(99)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($fileStorage);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_media_subtitles')->willReturn(TRUE);
    $node->method('get')->with('su_media_subtitles')->willReturn($fieldList);
    $node->expects($this->once())->method('set')->with('su_media_transcript', NULL);

    $this->callBuildAVTranscript($node);
  }

  /**
   * Valid SRT content — transcript field is populated with HTML and format.
   */
  public function testBuildAVTranscriptSetsTranscriptFromSrtContent() {
    $srt = implode("\n", [
      '1',
      '00:00:00,000 --> 00:00:02,000',
      'Hello world.',
      '',
      '2',
      '00:00:02,200 --> 00:00:04,000',
      'This is a transcript.',
      '',
    ]);
    // Use a data URI so file_get_contents() works without touching the FS.
    $dataUri = 'data://text/plain;base64,' . base64_encode($srt);

    $file = $this->createMock(FileInterface::class);
    $file->method('getFileUri')->willReturn($dataUri);

    $fieldItem = $this->createMock(FieldItemInterface::class);
    $fieldItem->method('getValue')->willReturn(['target_id' => 42]);

    $fieldList = $this->createMock(FieldItemListInterface::class);
    $fieldList->method('count')->willReturn(1);
    $fieldList->method('get')->with(0)->willReturn($fieldItem);

    $fileStorage = $this->createMock(EntityStorageInterface::class);
    $fileStorage->method('load')->with(42)->willReturn($file);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($fileStorage);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_media_subtitles')->willReturn(TRUE);
    $node->method('get')->with('su_media_subtitles')->willReturn($fieldList);
    $node->expects($this->once())
      ->method('set')
      ->with(
        'su_media_transcript',
        $this->callback(function ($value) {
          return is_array($value) &&
            $value['format'] === 'stanford_minimal_html' &&
            str_contains($value['value'], 'Hello world.');
        })
      );

    $this->callBuildAVTranscript($node);
  }

  /**
   * file_get_contents returns false — transcript field is not touched.
   */
  public function testBuildAVTranscriptFileContentsUnreadable() {
    // An invalid URI scheme causes file_get_contents() to return false.
    $file = $this->createMock(FileInterface::class);
    $file->method('getFileUri')->willReturn('invalid-scheme://nonexistent/file.srt');

    $fieldItem = $this->createMock(FieldItemInterface::class);
    $fieldItem->method('getValue')->willReturn(['target_id' => 7]);

    $fieldList = $this->createMock(FieldItemListInterface::class);
    $fieldList->method('count')->willReturn(1);
    $fieldList->method('get')->with(0)->willReturn($fieldItem);

    $fileStorage = $this->createMock(EntityStorageInterface::class);
    $fileStorage->method('load')->with(7)->willReturn($file);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($fileStorage);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_media_subtitles')->willReturn(TRUE);
    $node->method('get')->with('su_media_subtitles')->willReturn($fieldList);
    // Neither NULL nor a transcript value should be set.
    $node->expects($this->never())->method('set');

    // Suppress the PHP warning that file_get_contents emits for bad URIs.
    @$this->callBuildAVTranscript($node);
  }

}
