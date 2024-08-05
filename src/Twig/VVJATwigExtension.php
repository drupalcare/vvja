<?php

namespace Drupal\vvja\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

/**
 * Class VVJATwigExtension.
 *
 * Provides a custom Twig extension for marking HTML content as safe.
 *
 * @package Drupal\vvja\Twig
 */
class VVJATwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('safe_html', $this->safeHtml(...), ['is_safe' => ['html']]),
    ];
  }

  /**
   * Marks the provided HTML string as safe.
   *
   * @param string $string
   *   The string to mark as safe.
   *
   * @return \Twig\Markup
   *   The safe HTML string.
   */
  public function safeHtml($string) {
    return new Markup($string, 'UTF-8');
  }

}
