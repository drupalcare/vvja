<?php

declare(strict_types=1);

namespace Drupal\vvja;

/**
 * Vvja-specific constants — accordion's pattern-only values.
 *
 * Cross-cutting constants (token pattern, deeplink reserved words,
 * transition-speed bounds, unique-ID range) moved to vvj_core's
 * `Drupal\vvj_core\Constants\ValidationBounds`. Reference those
 * directly when you need shared values.
 *
 * @internal Final, non-instantiable.
 */
final class VvjaConstants {

  /**
   * Expand-default option values (accordion-specific).
   */
  public const string EXPAND_NONE = 'none';
  public const string EXPAND_FIRST = 'first';
  public const string EXPAND_ALL = 'all';
  public const string DEFAULT_EXPAND = self::EXPAND_NONE;

  /**
   * Default accordion animation (a-bottom = slide from bottom).
   */
  public const string DEFAULT_ANIMATION = 'a-bottom';

  /**
   * Default accordion transition speed (seconds).
   */
  public const float DEFAULT_TRANSITION_SPEED = 0.3;

  /**
   * Default accordion-card width (px); 0 = full-width list.
   */
  public const int DEFAULT_ACCORDION_ITEM_WIDTH = 0;

  /**
   * Default pane padding (px).
   */
  public const int DEFAULT_PANE_PADDING = 16;

  /**
   * Default gap between accordion panels (px).
   */
  public const int DEFAULT_PANEL_GAP = 16;

  /**
   * Minimum accordion-card width (px).
   */
  public const int MIN_ACCORDION_ITEM_WIDTH = 0;

  /**
   * Minimum pane padding (px).
   */
  public const int MIN_PANE_PADDING = 0;

  /**
   * Minimum panel gap (px).
   */
  public const int MIN_PANEL_GAP = 0;

  /**
   * Prevent instantiation.
   */
  private function __construct() {
  }

}
