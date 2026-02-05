<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper;

/**
 * Convert SRT file to paragraphs based on timestamps and sentence structure.
 */
class SubtitleToParagraphs {

  /**
   * Convert constructor.
   *
   * @param float $maxGapSeconds
   *   Maximum gap between subtitles to keep in same paragraph.
   * @param $minParagraphLength
   *   Minimum characters for a paragraph.
   */
  public function __construct(protected float $maxGapSeconds = 0.1, protected $minParagraphLength = 50) {}

  /**
   * Parse SRT file and convert to paragraphs.
   *
   * @param string $srtContent
   *   The content of the SRT file.
   *
   * @return string
   *   Formatted paragraphs HTML.
   */
  public static function convertFromSrt(string $srtContent): string {
    $converter = new self();
    return $converter->convert($srtContent);
  }

  /**
   * Parse SRT file and convert to paragraphs.
   *
   * @param string $srtContent
   *   The content of the SRT file.
   *
   * @return string
   *   Formatted paragraphs HTML.
   */
  public function convert(string $srtContent): string {
    $subtitles = $this->parseSrt($srtContent);
    $paragraphs = $this->createParagraphs($subtitles);

    return '<p>' . implode('</p><p>', $paragraphs) . '</p>';
  }

  /**
   * Parse SRT content into structured array.
   *
   * @param string $srtContent
   *   SRT file contents.
   *
   * @return array
   *   Array of subtitle entries.
   */
  protected function parseSrt(string $srtContent): array {
    $subtitles = [];

    // Normalize line endings
    $srtContent = str_replace(["\r\n", "\r"], "\n", $srtContent);

    // Split into blocks
    $blocks = preg_split('/\n\s*\n/', trim($srtContent));

    foreach ($blocks as $block) {
      $lines = explode("\n", trim($block));

      if (count($lines) < 3) {
        continue;
      }

      // First line is the sequence number.
      $sequence = trim($lines[0]);

      // Second line is the timestamp.
      $timestamp = trim($lines[1]);

      // Remaining lines are the text.
      $text = implode(' ', array_slice($lines, 2));

      if (preg_match('/^\(.*\)$/', $text)) {
        continue;
      }

      // Parse timestamp.
      if (preg_match('/(\d{2}:\d{2}:\d{2},\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2},\d{3})/', $timestamp, $matches)) {
        $startTime = $this->timeToSeconds($matches[1]);
        $endTime = $this->timeToSeconds($matches[2]);

        $subtitles[] = [
          'sequence' => $sequence,
          'start' => $startTime,
          'end' => $endTime,
          'text' => $this->cleanText($text),
        ];
      }
    }

    return $subtitles;
  }

  /**
   * Convert timestamp to seconds.
   *
   * @param string $timestamp
   *   Format: HH:MM:SS,mmm.
   *
   * @return float
   *   Seconds.
   */
  protected function timeToSeconds(string $timestamp): float {
    $timestamp = str_replace(',', '.', $timestamp);
    $parts = explode(':', $timestamp);

    $hours = (int) $parts[0];
    $minutes = (int) $parts[1];
    $seconds = (float) $parts[2];

    return $hours * 3600 + $minutes * 60 + $seconds;
  }

  /**
   * Clean subtitle text
   *
   * @param string $text
   *   Text contents.
   *
   * @return string
   *   Cleaned text
   */
  protected function cleanText(string $text): string {
    // Remove HTML tags.
    $text = strip_tags($text);

    // Remove formatting tags like <i>, <b>, etc.
    $text = preg_replace('/<[^>]+>/', '', $text);

    // Remove multiple spaces.
    $text = preg_replace('/\s+/', ' ', $text);
    return ltrim(trim($text), " -");
  }

  /**
   * Create paragraphs from subtitles based on timing and sentence structure.
   *
   * @param array $subtitles
   *   Array of parsed subtitles.
   *
   * @return array
   *   Paragraphs.
   */
  protected function createParagraphs(array $subtitles): array {
    if (empty($subtitles)) {
      return [];
    }

    $paragraphs = [];
    $currentParagraph = '';
    $lastEndTime = 0;

    foreach ($subtitles as $index => $subtitle) {
      $text = $subtitle['text'];

      if (empty($text)) {
        continue;
      }

      // Calculate gap from previous subtitle
      $gap = $subtitle['start'] - $lastEndTime;

      // Determine if we should start a new paragraph
      $shouldStartNew = FALSE;

      if (!empty($currentParagraph)) {
        // Check time gap
        if ($gap > $this->maxGapSeconds) {
          $shouldStartNew = TRUE;
        }

        // Check if previous text ended with sentence-ending punctuation.
        if (preg_match('/[.!?]\s*$/', $currentParagraph) && $gap > $this->maxGapSeconds) {
          $shouldStartNew = TRUE;
        }

        // Check if current text starts with capital letter and previous ended
        // with period.
        if (preg_match('/^[A-Z]/', $text) && preg_match('/\.\s*$/', $currentParagraph) && $gap > $this->maxGapSeconds) {
          $shouldStartNew = TRUE;
        }
      }

      if ($shouldStartNew) {
        // Save current paragraph
        $paragraphs[] = trim($currentParagraph);
        $currentParagraph = $text;
      }
      else {
        // Add to current paragraph
        if (!empty($currentParagraph)) {
          // Check if we need a space
          if (!preg_match('/-\s*$/', $currentParagraph)) {
            $currentParagraph .= ' ';
          }
        }
        $currentParagraph .= $text;
      }

      $lastEndTime = $subtitle['end'];
    }

    // Add the last paragraph
    if (!empty($currentParagraph)) {
      $paragraphs[] = trim($currentParagraph);
    }

    return $this->mergeParagraphs($paragraphs);
  }

  /**
   * Merge very short paragraphs with adjacent ones.
   *
   * @param array $paragraphs
   *   Parsed paragraphs.
   *
   * @return array
   *   Merged paragraphs.
   */
  protected function mergeParagraphs(array $paragraphs): array {
    $merged = [];
    $current = '';

    foreach ($paragraphs as $paragraph) {
      if (strlen($current) < $this->minParagraphLength && !empty($current)) {
        $current .= ' ' . $paragraph;
      }
      else {
        if (!empty($current)) {
          $merged[] = $current;
        }
        $current = $paragraph;
      }
    }

    if (!empty($current)) {
      $merged[] = $current;
    }

    return $merged;
  }

}
