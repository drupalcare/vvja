<?php

declare(strict_types=1);

namespace Drupal\vvja\Hook;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;
use Drupal\Core\Template\Attribute;
use Drupal\views\Hook\ViewsThemeHooks;
use Drupal\views\ViewExecutable;
use Drupal\vvj_core\Service\SvgSanitizer;
use Drupal\vvja\Plugin\views\style\Accordion;

/**
 * Preprocess hooks for VVJA accordion.
 *
 * Two responsibilities (both vvja-specific — the universal
 * `vvj-component` class is added by `vvj_core`'s preprocess):
 *
 *   1. `views_view_vvja` preprocess — flatten the style plugin's
 *      options into template-friendly variables, sanitize custom
 *      SVG icons (defense in depth), rewrite per-row theme
 *      suggestions, then delegate to
 *      \Drupal\views\Hook\ViewsThemeHooks::preprocessViewsViewUnformatted().
 *
 *   2. `views_view_vvja_fields` preprocess — strip `<a>...</a>` from
 *      the FIRST field's rendered content. The trigger button must
 *      not contain a clickable `<a>` (nested `<a>` is invalid HTML5
 *      in deeplink mode; in button mode the inner link looks
 *      clickable but isn't — both are bad UX).
 */
final class VvjaPreprocessHooks {

  /**
   * SVG icon field machine names (sanitized at render time).
   *
   * @var list<string>
   */
  private const array SVG_FIELDS = [
    'svg_expand_content',
    'svg_collapse_content',
    'svg_expand_all',
    'svg_collapse_all',
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\views\Hook\ViewsThemeHooks $viewsThemeHooks
   *   Views row / field preprocess (replaces removed procedural
   *   template_preprocess_* functions in Drupal 12).
   * @param \Drupal\vvj_core\Service\SvgSanitizer|null $svgSanitizer
   *   Shared SVG sanitizer. Nullable to keep the container compilable
   *   during the v1 → v2 upgrade window when vvj_core is on disk but
   *   not yet enabled. vvja_update_10001 installs it; subsequent
   *   container rebuilds inject the real service.
   */
  public function __construct(
    private readonly ViewsThemeHooks $viewsThemeHooks,
    private readonly ?SvgSanitizer $svgSanitizer,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for views_view_vvja.
   *
   * @param array<string, mixed> $variables
   *   The template variables.
   */
  #[Hook('preprocess_views_view_vvja')]
  public function preprocessViewsViewVvja(array &$variables): void {
    $view = $variables['view'] ?? NULL;
    if (!($view instanceof ViewExecutable)) {
      return;
    }
    $stylePlugin = $view->style_plugin;
    if (!($stylePlugin instanceof Accordion)) {
      return;
    }
    /** @var \Drupal\vvja\Plugin\views\style\Accordion $handler */
    $handler = $stylePlugin;
    $options = $handler->options;

    $list_attributes = [
      'data-animation' => $options['animation'] ?? '',
      'data-unique-id' => $options['unique_id'] ?? '',
      'data-transition-speed' => $options['transition_speed'] ?? '',
      'data-accordion-item-width' => $options['accordion_item_width'] ?? '',
      'data-global-toggle' => !empty($options['global_toggle']) ? 'true' : 'false',
      'data-single-toggle' => !empty($options['single_toggle']) ? 'true' : 'false',
      'data-expand-default' => $options['expand_default'] ?? '',
      'data-enable-css' => !empty($options['enable_css']) ? 'true' : 'false',
      'data-exclusive-panel' => !empty($options['exclusive_panel']) ? 'true' : 'false',
    ];

    $variables['list_attributes'] = new Attribute(array_filter($list_attributes, static fn ($v): bool => $v !== ''));
    $variables['deeplink_enabled'] = $options['enable_deeplink'] ?? FALSE;
    $variables['deeplink_identifier'] = $options['deeplink_identifier'] ?? '';
    $variables['options'] = $options;

    // Sanitize custom SVG icons at render time (defense in depth).
    // During the v1 → v2 upgrade window before vvj_core is enabled,
    // the sanitizer is null — we strip SVGs entirely rather than emit
    // unsanitized markup.
    foreach (self::SVG_FIELDS as $field) {
      $svg = $options[$field] ?? '';
      $svgString = is_string($svg) ? $svg : '';
      if ($svgString === '') {
        continue;
      }
      if ($this->svgSanitizer === NULL) {
        $variables['options'][$field] = '';
        continue;
      }
      $sanitized = $this->svgSanitizer->sanitize($svgString);
      $variables['options'][$field] = $sanitized !== '' ? Markup::create($sanitized) : '';
    }

    $variables['settings'] = [
      'view_id' => $view->dom_id,
      'animation' => $options['animation'] ?? '',
      'unique_id' => $options['unique_id'] ?? '',
      'transition_speed' => $options['transition_speed'] ?? '',
      'accordion_item_width' => $options['accordion_item_width'] ?? 0,
      'pane_padding' => $options['pane_padding'] ?? 0,
      'global_toggle' => $options['global_toggle'] ?? FALSE,
      'single_toggle' => $options['single_toggle'] ?? FALSE,
      'expand_default' => $options['expand_default'] ?? '',
      'enable_css' => $options['enable_css'] ?? FALSE,
      'exclusive_panel' => $options['exclusive_panel'] ?? FALSE,
    ];

    // Rewrite per-row theme suggestions so per-row Twig overrides
    // resolve to VVJA-namespaced templates instead of generic
    // views_view_fields.
    $rows = $variables['rows'] ?? NULL;
    if (is_array($rows)) {
      foreach ($rows as $key => $row) {
        if (!is_array($row) || !isset($row['#theme']) || !is_array($row['#theme'])) {
          continue;
        }
        foreach ($row['#theme'] as $idx => $hook) {
          if (is_string($hook)) {
            $row['#theme'][$idx] = str_replace(
              'views_view_fields',
              'views_view_vvja_fields',
              $hook,
            );
          }
        }
        $rows[$key] = $row;
      }
      $variables['rows'] = $rows;
    }

    $this->viewsThemeHooks->preprocessViewsViewUnformatted($variables);
  }

  /**
   * Implements hook_preprocess_HOOK() for views_view_vvja_fields.
   *
   * Strips `<a>...</a>` from the FIRST field's rendered content. The
   * first field becomes the accordion trigger (a `<button>` or, in
   * deep-link mode, an `<a>`). A trigger element must not contain a
   * clickable `<a>` — see the class-level docblock for the full
   * rationale.
   *
   * @param array<string, mixed> $variables
   *   The template variables.
   */
  #[Hook('preprocess_views_view_vvja_fields')]
  public function preprocessViewsViewVvjaFields(array &$variables): void {
    $this->viewsThemeHooks->preprocessViewsViewFields($variables);

    if (empty($variables['fields']) || !is_array($variables['fields'])) {
      return;
    }
    $first_key = array_key_first($variables['fields']);
    if ($first_key === NULL) {
      return;
    }
    $field = $variables['fields'][$first_key];
    if (is_object($field) && isset($field->content)) {
      // $field is held by reference, so mutating it updates $variables.
      $field->content = self::stripAnchorTags($field->content);
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for views_view.
   *
   * Adds the legacy `vvj-accordion` class for v1 contract preservation.
   * vvj_core's preprocess adds `.vvj-component` and `.vvj-vvja`. v1
   * sites that styled `.vvj-accordion` keep working because we add
   * that legacy class here.
   *
   * @param array<string, mixed> $variables
   *   The template variables.
   */
  #[Hook('preprocess_views_view')]
  public function preprocessViewsView(array &$variables): void {
    $view = $variables['view'] ?? NULL;
    if (!($view instanceof ViewExecutable)) {
      return;
    }
    if (!($view->style_plugin instanceof Accordion)) {
      return;
    }
    $attributes = $variables['attributes'] ?? NULL;
    if (is_array($attributes)) {
      $classes = $attributes['class'] ?? [];
      if (!is_array($classes)) {
        $classes = [];
      }
      $classes[] = 'vvj-accordion';
      $attributes['class'] = $classes;
      $variables['attributes'] = $attributes;
    }
  }

  /**
   * Strip `<a>...</a>` wrappers from rendered field HTML.
   *
   * Removes opening `<a ...>` and closing `</a>` tags but keeps the
   * inner text and any other formatting tags untouched. The result is
   * marked safe for direct Twig output via `Markup::create()`.
   *
   * @param mixed $content
   *   Field's rendered content (string or `MarkupInterface` after
   *   ViewsThemeHooks field preprocess).
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   Stripped HTML, marked safe.
   */
  private static function stripAnchorTags(mixed $content): string|MarkupInterface {
    if (is_string($content)) {
      $html = $content;
    }
    elseif ($content instanceof \Stringable) {
      $html = (string) $content;
    }
    else {
      $html = '';
    }
    $stripped = preg_replace('#</?a(\s[^>]*)?>#i', '', $html);
    return Markup::create($stripped ?? $html);
  }

}
