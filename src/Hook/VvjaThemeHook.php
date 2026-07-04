<?php

declare(strict_types=1);

namespace Drupal\vvja\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theme hook implementation.
 *
 * Declares two themes:
 *   - `views_view_vvja`        — main accordion wrapper template
 *     (compatibility shim that delegates to the SDC).
 *   - `views_view_vvja_fields` — per-row fields wrapper used by the
 *     row plugin to namespace overrides per VVJA-style display.
 *
 * Template names are LOCKED for backwards compatibility with v1.x
 * theme overrides.
 */
final class VvjaThemeHook {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   Resolves the module's templates directory path.
   */
  public function __construct(
    private readonly ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Implements hook_theme().
   *
   * @param array<string, mixed> $existing
   *   Existing theme hooks.
   * @param string $type
   *   The type of extension invoking the hook.
   * @param string $theme
   *   Name of the theme.
   * @param string $path
   *   Path to the extension.
   *
   * @return array<string, array{
   *   variables?: array<string, mixed>,
   *   template: string,
   *   path: string,
   *   }>
   */
  #[Hook('theme')]
  public function theme(
    array $existing,
    string $type,
    string $theme,
    string $path,
  ): array {
    $template_path = $this->moduleExtensionList->getPath('vvja') . '/templates';

    return [
      'views_view_vvja_fields' => [
        'variables' => [
          'view' => NULL,
          'options' => [],
          'row' => NULL,
          'field_alias' => NULL,
          'attributes' => [],
          'title_attributes' => [],
          'content_attributes' => [],
          'title_prefix' => [],
          'title_suffix' => [],
          'fields' => [],
        ],
        'template' => 'views-view-vvja-fields',
        'path' => $template_path,
      ],
      'views_view_vvja' => [
        'variables' => [
          'view' => NULL,
          'rows' => [],
          'options' => [],
        ],
        'template' => 'views-view-vvja',
        'path' => $template_path,
      ],
    ];
  }

}
