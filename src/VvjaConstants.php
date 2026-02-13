<?php

declare(strict_types=1);

namespace Drupal\vvja;

/**
 * Defines constants for the VVJA module.
 */
final class VvjaConstants {

  public const TOKEN_NAMESPACE = 'vvja';
  public const TOKEN_PLAIN_SUFFIX = ':plain';
  public const TOKEN_PATTERN = '/^[a-zA-Z0-9_]+(:plain)?$/';

  public const DATA_ATTRIBUTE_MAP = [
    'animation' => 'animation',
    'unique_id' => 'unique-id',
    'transition_speed' => 'transition-speed',
    'accordion_item_width' => 'accordion-item-width',
    'expand_default' => 'expand-default',
  ];

  public const BOOLEAN_ATTRIBUTE_MAP = [
    'global_toggle' => 'global-toggle',
    'single_toggle' => 'single-toggle',
    'enable_css' => 'enable-css',
    'exclusive_panel' => 'exclusive-panel',
  ];

  public const VIEWS_TYPE_INTEGER = 'integer';
  public const VIEWS_TYPE_STRING = 'string';
  public const VIEWS_TYPE_BOOLEAN = 'boolean';
  public const VIEWS_TYPE_FLOAT = 'float';

  /**
   * Deep linking configuration constants.
   */
  public const DEEPLINK_IDENTIFIER_MAX_LENGTH = 20;
  public const DEEPLINK_IDENTIFIER_PATTERN = '/^[a-z][a-z0-9-]*[a-z0-9]$/';
  public const DEEPLINK_RESERVED_WORDS = ['accordion', 'panel', 'vvja'];

  /**
   * Expand default option constants.
   */
  public const EXPAND_NONE = 'none';
  public const EXPAND_FIRST = 'first';
  public const EXPAND_ALL = 'all';
  public const DEFAULT_EXPAND = 'none';

  public const DEFAULT_ANIMATION = 'a-bottom';
  public const DEFAULT_TRANSITION_SPEED = 0.3;
  public const DEFAULT_ACCORDION_ITEM_WIDTH = 0;
  public const DEFAULT_PANE_PADDING = 16;
  public const DEFAULT_PANEL_GAP = 16;

  public const MIN_TRANSITION_SPEED = 0.1;
  public const MAX_TRANSITION_SPEED = 2.0;
  public const MIN_ACCORDION_ITEM_WIDTH = 0;
  public const MIN_PANE_PADDING = 0;
  public const MIN_PANEL_GAP = 0;

  public const MIN_UNIQUE_ID = 10000000;
  public const MAX_UNIQUE_ID = 99999999;

  private function __construct() {
  }

}
