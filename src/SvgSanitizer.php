<?php

declare(strict_types=1);

namespace Drupal\vvja;

/**
 * Sanitizes SVG markup by whitelisting safe elements and attributes.
 */
final class SvgSanitizer {

  /**
   * Allowed SVG elements.
   */
  private const ALLOWED_ELEMENTS = [
    'svg', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon',
    'ellipse', 'g', 'defs', 'use', 'symbol', 'clippath', 'mask',
    'lineargradient', 'radialgradient', 'stop', 'title', 'desc',
  ];

  /**
   * Allowed SVG attributes.
   */
  private const ALLOWED_ATTRIBUTES = [
    // Core.
    'id', 'class', 'xmlns', 'xmlns:xlink', 'xml:space',
    // Dimensions & positioning.
    'viewbox', 'width', 'height', 'x', 'y', 'x1', 'y1', 'x2', 'y2',
    'cx', 'cy', 'r', 'rx', 'ry', 'dx', 'dy',
    // Path & shape.
    'd', 'points',
    // Presentation.
    'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
    'stroke-dasharray', 'stroke-dashoffset', 'stroke-miterlimit',
    'stroke-opacity', 'fill-opacity', 'fill-rule', 'clip-rule', 'opacity',
    'transform', 'display', 'visibility', 'color',
    // Gradient.
    'offset', 'stop-color', 'stop-opacity', 'gradientunits',
    'gradienttransform', 'spreadmethod', 'fx', 'fy',
    // Clip/mask.
    'clip-path', 'clipPathUnits', 'mask', 'maskunits',
    // Reference (validated separately for safe values).
    'href', 'xlink:href',
  ];

  /**
   * Sanitize an SVG string.
   *
   * @param string $svg
   *   The raw SVG markup.
   *
   * @return string
   *   The sanitized SVG markup, or empty string if invalid.
   */
  public static function sanitize(string $svg): string {
    $svg = trim($svg);
    if ($svg === '') {
      return '';
    }

    // Must start with <svg.
    if (stripos($svg, '<svg') !== 0) {
      return '';
    }

    // Block dangerous patterns before parsing.
    if (self::containsDangerousPatterns($svg)) {
      return '';
    }

    $dom = new \DOMDocument();
    $dom->formatOutput = FALSE;
    $dom->preserveWhiteSpace = FALSE;

    libxml_use_internal_errors(TRUE);
    $loaded = $dom->loadXML($svg);
    libxml_clear_errors();

    if (!$loaded) {
      return '';
    }

    $root = $dom->documentElement;
    if (!$root || strtolower($root->nodeName) !== 'svg') {
      return '';
    }

    self::sanitizeNode($root);

    $result = $dom->saveXML($root);
    return $result !== FALSE ? $result : '';
  }

  /**
   * Validate that an SVG string is safe without modifying it.
   *
   * @param string $svg
   *   The SVG markup to validate.
   *
   * @return array
   *   An array of error messages. Empty if the SVG is valid.
   */
  public static function validate(string $svg): array {
    $errors = [];
    $svg = trim($svg);

    if ($svg === '') {
      return $errors;
    }

    if (stripos($svg, '<svg') !== 0) {
      $errors[] = 'SVG markup must start with an &lt;svg&gt; element.';
      return $errors;
    }

    if (self::containsDangerousPatterns($svg)) {
      $errors[] = 'SVG contains potentially dangerous content (scripts, event handlers, or external references).';
      return $errors;
    }

    $dom = new \DOMDocument();
    libxml_use_internal_errors(TRUE);
    $loaded = $dom->loadXML($svg);
    $xml_errors = libxml_get_errors();
    libxml_clear_errors();

    if (!$loaded || !empty($xml_errors)) {
      $errors[] = 'SVG markup is not valid XML.';
      return $errors;
    }

    $root = $dom->documentElement;
    if (!$root || strtolower($root->nodeName) !== 'svg') {
      $errors[] = 'Root element must be &lt;svg&gt;.';
      return $errors;
    }

    $disallowed = self::findDisallowedElements($root);
    if (!empty($disallowed)) {
      $errors[] = 'SVG contains disallowed elements: ' . implode(', ', array_unique($disallowed)) . '.';
    }

    return $errors;
  }

  /**
   * Check for dangerous patterns in raw SVG string.
   */
  private static function containsDangerousPatterns(string $svg): bool {
    $lower = strtolower($svg);

    // Block script elements and event handlers.
    $dangerous = [
      '<script',
      '<foreignobject',
      '<iframe',
      '<object',
      '<embed',
      '<applet',
      'javascript:',
      'data:text/',
      'data:application/',
      'vbscript:',
    ];

    foreach ($dangerous as $pattern) {
      if (str_contains($lower, $pattern)) {
        return TRUE;
      }
    }

    // Block on* event handler attributes.
    if (preg_match('/\bon[a-z]+\s*=/i', $svg)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Recursively sanitize a DOM node.
   */
  private static function sanitizeNode(\DOMNode $node): void {
    $remove = [];

    foreach ($node->childNodes as $child) {
      if ($child instanceof \DOMElement) {
        $tagName = strtolower($child->nodeName);
        if (!in_array($tagName, self::ALLOWED_ELEMENTS, TRUE)) {
          $remove[] = $child;
          continue;
        }
        self::sanitizeAttributes($child);
        self::sanitizeNode($child);
      }
      elseif ($child instanceof \DOMProcessingInstruction || $child instanceof \DOMComment) {
        $remove[] = $child;
      }
    }

    foreach ($remove as $child) {
      $node->removeChild($child);
    }

    // Sanitize attributes on the current element.
    if ($node instanceof \DOMElement) {
      self::sanitizeAttributes($node);
    }
  }

  /**
   * Remove disallowed attributes from an element.
   */
  private static function sanitizeAttributes(\DOMElement $element): void {
    $remove = [];

    foreach ($element->attributes as $attr) {
      $name = strtolower($attr->nodeName);

      // Block event handlers.
      if (str_starts_with($name, 'on')) {
        $remove[] = $attr->nodeName;
        continue;
      }

      // Check whitelist.
      if (!in_array($name, self::ALLOWED_ATTRIBUTES, TRUE)) {
        $remove[] = $attr->nodeName;
        continue;
      }

      // Validate href values.
      if ($name === 'href' || $name === 'xlink:href') {
        $val = strtolower(trim($attr->value));
        if (str_starts_with($val, 'javascript:') || str_starts_with($val, 'data:') || str_starts_with($val, 'vbscript:')) {
          $remove[] = $attr->nodeName;
          continue;
        }
        // Only allow fragment references (e.g., #id) for use elements.
        if (!str_starts_with($val, '#')) {
          $remove[] = $attr->nodeName;
        }
      }
    }

    foreach ($remove as $name) {
      $element->removeAttribute($name);
    }
  }

  /**
   * Find disallowed element names for validation reporting.
   */
  private static function findDisallowedElements(\DOMNode $node): array {
    $disallowed = [];

    foreach ($node->childNodes as $child) {
      if ($child instanceof \DOMElement) {
        $tagName = strtolower($child->nodeName);
        if (!in_array($tagName, self::ALLOWED_ELEMENTS, TRUE)) {
          $disallowed[] = '&lt;' . $tagName . '&gt;';
        }
        $disallowed = array_merge($disallowed, self::findDisallowedElements($child));
      }
    }

    return $disallowed;
  }

  /**
   * Prevent instantiation.
   */
  private function __construct() {
  }

}
