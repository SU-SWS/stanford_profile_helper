<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit;

use Drupal\stanford_profile_helper\SubtitleToParagraphs;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the SubtitleToParagraphs converter.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(SubtitleToParagraphs::class)]
class SubtitleToParagraphsTest extends UnitTestCase {

  /**
   * Test basic SRT parsing.
   */
  public function testConvertFromSrt() {
    $srt = <<<SRT
1
00:00:00,000 --> 00:00:02,000
Hello world.

2
00:00:02,100 --> 00:00:04,000
This is a test.

SRT;

    $result = SubtitleToParagraphs::convertFromSrt($srt);
    $this->assertStringContainsString('Hello world.', $result);
    $this->assertStringContainsString('This is a test.', $result);
    $this->assertStringStartsWith('<p>', $result);
    $this->assertStringEndsWith('</p>', $result);
  }

  /**
   * Test timestamp conversion.
   */
  public function testTimeToSeconds() {
    $converter = new SubtitleToParagraphs();
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('timeToSeconds');
    $method->setAccessible(TRUE);

    $this->assertEquals(0.0, $method->invoke($converter, '00:00:00,000'));
    $this->assertEquals(1.5, $method->invoke($converter, '00:00:01,500'));
    $this->assertEquals(61.0, $method->invoke($converter, '00:01:01,000'));
    $this->assertEquals(3661.0, $method->invoke($converter, '01:01:01,000'));
  }

  /**
   * Test text cleaning.
   */
  public function testCleanText() {
    $converter = new SubtitleToParagraphs();
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('cleanText');
    $method->setAccessible(TRUE);

    $this->assertEquals('Hello world', $method->invoke($converter, '  Hello   world  '));
    $this->assertEquals('Hello world', $method->invoke($converter, '<i>Hello</i> <b>world</b>'));
    $this->assertEquals('Hello world', $method->invoke($converter, '- Hello world'));
    $this->assertEquals('Test', $method->invoke($converter, '<font color="red">Test</font>'));
  }

  /**
   * Test SRT parsing with multiple blocks.
   */
  public function testParseSrt() {
    $srt = <<<SRT
1
00:00:00,000 --> 00:00:02,000
First subtitle

2
00:00:02,500 --> 00:00:04,500
Second subtitle

3
00:00:05,000 --> 00:00:07,000
Third subtitle

SRT;

    $converter = new SubtitleToParagraphs();
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('parseSrt');
    $method->setAccessible(TRUE);

    $subtitles = $method->invoke($converter, $srt);

    $this->assertCount(3, $subtitles);
    $this->assertEquals('First subtitle', $subtitles[0]['text']);
    $this->assertEquals('Second subtitle', $subtitles[1]['text']);
    $this->assertEquals('Third subtitle', $subtitles[2]['text']);
    $this->assertEquals(0.0, $subtitles[0]['start']);
    $this->assertEquals(2.0, $subtitles[0]['end']);
  }

  /**
   * Test parsing skips parenthetical text.
   */
  public function testParseSrtSkipsParenthetical() {
    $srt = <<<SRT
1
00:00:00,000 --> 00:00:02,000
(Music playing)

2
00:00:02,500 --> 00:00:04,500
This should be included

SRT;

    $converter = new SubtitleToParagraphs();
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('parseSrt');
    $method->setAccessible(TRUE);

    $subtitles = $method->invoke($converter, $srt);

    $this->assertCount(1, $subtitles);
    $this->assertEquals('This should be included', $subtitles[0]['text']);
  }

  /**
   * Test paragraph creation with time gaps.
   */
  public function testCreateParagraphsWithTimeGaps() {
    $subtitles = [
      [
        'sequence' => '1',
        'start' => 0.0,
        'end' => 2.0,
        'text' => 'First sentence that is long enough to be its own paragraph.',
      ],
      [
        'sequence' => '2',
        'start' => 2.05,
        'end' => 4.0,
        'text' => 'Second sentence that is also long enough for a paragraph.',
      ],
      // Large gap here.
      [
        'sequence' => '3',
        'start' => 10.0,
        'end' => 12.0,
        'text' => 'New paragraph after a big gap with sufficient length for testing.',
      ],
    ];

    $converter = new SubtitleToParagraphs(maxGapSeconds: 0.1, minParagraphLength: 50);
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('createParagraphs');
    $method->setAccessible(TRUE);

    $paragraphs = $method->invoke($converter, $subtitles);

    // Should have at least 1 paragraph, possibly 2-3 depending on gaps and merging.
    $this->assertNotEmpty($paragraphs);
    $this->assertStringContainsString('First sentence', $paragraphs[0]);
  }

  /**
   * Test paragraph creation with sentence endings.
   */
  public function testCreateParagraphsWithSentenceEndings() {
    $subtitles = [
      [
        'sequence' => '1',
        'start' => 0.0,
        'end' => 2.0,
        'text' => 'First sentence.',
      ],
      [
        'sequence' => '2',
        'start' => 2.5,
        'end' => 4.0,
        'text' => 'New sentence starts here.',
      ],
    ];

    $converter = new SubtitleToParagraphs(maxGapSeconds: 0.1);
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('createParagraphs');
    $method->setAccessible(TRUE);

    $paragraphs = $method->invoke($converter, $subtitles);

    $this->assertIsArray($paragraphs);
    $this->assertNotEmpty($paragraphs);
  }

  /**
   * Test merging short paragraphs.
   */
  public function testMergeParagraphs() {
    $paragraphs = [
      'Short',
      'This is a longer paragraph that should not be merged.',
      'Also short',
      'Another longer paragraph that meets the minimum length requirement.',
    ];

    $converter = new SubtitleToParagraphs(minParagraphLength: 50);
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('mergeParagraphs');
    $method->setAccessible(TRUE);

    $merged = $method->invoke($converter, $paragraphs);

    // Short paragraphs should be merged with adjacent ones.
    $this->assertLessThan(count($paragraphs), count($merged));
  }

  /**
   * Test empty subtitle handling.
   */
  public function testEmptySubtitles() {
    $converter = new SubtitleToParagraphs();
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('createParagraphs');
    $method->setAccessible(TRUE);

    $paragraphs = $method->invoke($converter, []);

    $this->assertEmpty($paragraphs);
  }

  /**
   * Test convert with empty subtitles.
   */
  public function testConvertEmptyContent() {
    $converter = new SubtitleToParagraphs();
    $result = $converter->convert('');

    $this->assertEquals('<p></p>', $result);
  }

  /**
   * Test multi-line subtitle text.
   */
  public function testMultiLineSubtitleText() {
    $srt = <<<SRT
1
00:00:00,000 --> 00:00:02,000
This is line one
and this is line two

SRT;

    $converter = new SubtitleToParagraphs();
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('parseSrt');
    $method->setAccessible(TRUE);

    $subtitles = $method->invoke($converter, $srt);

    $this->assertCount(1, $subtitles);
    // Lines should be joined with space.
    $this->assertStringContainsString('line one', $subtitles[0]['text']);
    $this->assertStringContainsString('line two', $subtitles[0]['text']);
  }

  /**
   * Test different line endings.
   */
  public function testDifferentLineEndings() {
    // Test with Windows line endings.
    $srt = "1\r\n00:00:00,000 --> 00:00:02,000\r\nTest subtitle\r\n";

    $converter = new SubtitleToParagraphs();
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('parseSrt');
    $method->setAccessible(TRUE);

    $subtitles = $method->invoke($converter, $srt);

    $this->assertCount(1, $subtitles);
    $this->assertEquals('Test subtitle', $subtitles[0]['text']);
  }

  /**
   * Test subtitle with hyphenated continuation.
   */
  public function testHyphenatedContinuation() {
    $subtitles = [
      [
        'sequence' => '1',
        'start' => 0.0,
        'end' => 2.0,
        'text' => 'This is a test-',
      ],
      [
        'sequence' => '2',
        'start' => 2.05,
        'end' => 4.0,
        'text' => 'ing subtitle.',
      ],
    ];

    $converter = new SubtitleToParagraphs(maxGapSeconds: 0.1);
    $reflection = new \ReflectionClass($converter);
    $method = $reflection->getMethod('createParagraphs');
    $method->setAccessible(TRUE);

    $paragraphs = $method->invoke($converter, $subtitles);

    $this->assertNotEmpty($paragraphs);
    // Should join without extra space for hyphenated words.
    $this->assertStringContainsString('test-', $paragraphs[0]);
  }

  /**
   * Test complete workflow with realistic SRT content.
   */
  public function testCompleteWorkflow() {
    $srt = <<<SRT
1
00:00:00,500 --> 00:00:02,000
Welcome to this presentation.

2
00:00:02,100 --> 00:00:04,500
Today we'll discuss testing.

3
00:00:10,000 --> 00:00:12,000
This is a new topic.

4
00:00:12,100 --> 00:00:14,000
With continuous content.

SRT;

    $result = SubtitleToParagraphs::convertFromSrt($srt);

    $this->assertIsString($result);
    $this->assertStringStartsWith('<p>', $result);
    $this->assertStringEndsWith('</p>', $result);
    $this->assertStringContainsString('Welcome to this presentation.', $result);
    $this->assertStringContainsString('testing', $result);
    $this->assertStringContainsString('new topic', $result);
  }

  /**
   * Test constructor with custom parameters.
   */
  public function testConstructorWithCustomParameters() {
    $converter = new SubtitleToParagraphs(maxGapSeconds: 5.0, minParagraphLength: 100);

    $reflection = new \ReflectionClass($converter);
    $maxGapProperty = $reflection->getProperty('maxGapSeconds');
    $maxGapProperty->setAccessible(TRUE);
    $minLengthProperty = $reflection->getProperty('minParagraphLength');
    $minLengthProperty->setAccessible(TRUE);

    $this->assertEquals(5.0, $maxGapProperty->getValue($converter));
    $this->assertEquals(100, $minLengthProperty->getValue($converter));
  }

}
