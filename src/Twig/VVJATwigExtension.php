<?php

declare(strict_types=1);

namespace Drupal\vvja\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

/**
 * Provides a custom Twig extension for marking HTML content as safe.
 *
 * SECURITY WARNING: This filter bypasses Twig's automatic escaping and should
 * ONLY be used on HTML that is known to be safe and from trusted sources.
 *
 * DO NOT use this filter on user-generated content without proper sanitization
 * through Drupal's XSS filtering APIs.
 *
 * @package Drupal\vvja\Twig
 */
class VVJATwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   *
   * @return array<\Twig\TwigFilter>
   *   An array of Twig filters provided by this extension.
   */
  public function getFilters(): array {
    return [
      new TwigFilter('safe_html', [$this, 'safeHtml'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Marks the provided HTML string as safe for output.
   *
   * Use ONLY when the content is from a trusted source and has already been
   * sanitized by Drupal's rendering system.
   *
   * @param string $string
   *   The string to mark as safe. Should be pre-sanitized HTML.
   *
   * @return \Twig\Markup
   *   The safe HTML string wrapped in Twig Markup object.
   */
  public function safeHtml(string $string): Markup {
    return new Markup($string, 'UTF-8');
  }

}
