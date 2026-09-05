<?php

declare(strict_types=1);

namespace Drupal\vvja\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\vvj_core\Constants\ValidationBounds;
use Drupal\vvj_core\Plugin\views\style\VvjStylePluginBase;
use Drupal\vvja\VvjaConstants;

/**
 * Renders Views rows as a vanilla-JavaScript accordion.
 *
 * @ingroup views_style_plugins
 *
 * Plugin ID `views_vvja` and theme hook `views_view_vvja` are LOCKED
 * for backwards compatibility with v1.x sites. The shared boilerplate
 * (form weights, warning, advanced/deeplinking/token-doc sections,
 * library list, render(), validate()) lives in
 * `\Drupal\vvj_core\Plugin\views\style\VvjStylePluginBase` — this
 * class only declares the accordion's pattern-specific options and
 * sections.
 */
#[ViewsStyle(
  id: 'views_vvja',
  title: new TranslatableMarkup('Views Vanilla JavaScript Accordion'),
  help: new TranslatableMarkup('Render items in an Accordion using vanilla JavaScript.'),
  theme: 'views_view_vvja',
  display_types: ['normal'],
)]
class Accordion extends VvjStylePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getModuleSlug(): string {
    return 'vvja';
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomElementTag(): string {
    return 'vvja-accordion';
  }

  /**
   * The "Enable CSS Library" checkbox description (v1 accordion wording).
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  protected function getEnableCssDescription(): TranslatableMarkup {
    return $this->t('Check this box to include the CSS library for styling the accordion. Uncheck if you want to provide your own custom styles.');
  }

  /**
   * The "Enable Deep Linking" checkbox description (v1 accordion wording).
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  protected function getDeeplinkEnableDescription(): TranslatableMarkup {
    return $this->t('Generate shareable links for each accordion panel that appear in the browser URL.');
  }

  /**
   * The deep-link "URL Identifier" field description (v1 accordion wording).
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  protected function getDeeplinkIdentifierDescription(): TranslatableMarkup {
    return $this->t('Short identifier used in panel links. Example: "faqs" creates links like #accordion-faqs-3. Will be automatically cleaned: converted to lowercase, spaces become hyphens, special characters removed.');
  }

  /**
   * {@inheritdoc}
   */
  protected function definePatternOptions(array $options): array {
    $options['single_toggle'] = ['default' => TRUE];
    $options['global_toggle'] = ['default' => TRUE];
    $options['expand_default'] = ['default' => VvjaConstants::DEFAULT_EXPAND];
    $options['transition_speed'] = ['default' => VvjaConstants::DEFAULT_TRANSITION_SPEED];
    $options['animation'] = ['default' => VvjaConstants::DEFAULT_ANIMATION];
    $options['exclusive_panel'] = ['default' => FALSE];
    $options['accordion_item_width'] = ['default' => VvjaConstants::DEFAULT_ACCORDION_ITEM_WIDTH];
    $options['pane_padding'] = ['default' => VvjaConstants::DEFAULT_PANE_PADDING];
    $options['panel_gap'] = ['default' => VvjaConstants::DEFAULT_PANEL_GAP];
    $options['svg_expand_content'] = ['default' => ''];
    $options['svg_collapse_content'] = ['default' => ''];
    $options['svg_expand_all'] = ['default' => ''];
    $options['svg_collapse_all'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPatternSections(array &$form): void {
    $this->buildBehaviorSection($form);
    $this->buildAnimationSection($form);
    $this->buildLayoutSection($form);
    $this->buildCustomIconsSection($form);
  }

  /**
   * Behavior settings — toggles, exclusive mode, default expand.
   *
   * @param array<int|string, mixed> $form
   *   The Views style options form, modified in place.
   */
  protected function buildBehaviorSection(array &$form): void {
    $form['behavior_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Behavior Settings'),
      '#open' => TRUE,
      '#weight' => -40,
    ];

    $form['behavior_section']['global_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Collapse or Expand All (Global)'),
      '#default_value' => $this->options['global_toggle'],
      '#description' => $this->t('Check this box to show a button that toggles all the accordions at once.'),
    ];

    $form['behavior_section']['exclusive_panel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Exclusive Mode'),
      '#default_value' => $this->options['exclusive_panel'],
      '#description' => $this->t('Allow only one panel to be open at a time. By default, multiple panels can be open simultaneously. Note: This feature is incompatible with the "Show Collapse or Expand All (Global)" option.'),
      '#states' => [
        'enabled' => [
          ':input[name="style_options[behavior_section][global_toggle]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['behavior_section']['single_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Collapse or Expand (Single)'),
      '#default_value' => $this->options['single_toggle'],
      '#description' => $this->t('Check this box to show a toggle (+/-) button to open and close the accordion sections.'),
    ];

    $form['behavior_section']['expand_default'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Expand Behavior'),
      '#options' => [
        VvjaConstants::EXPAND_NONE => $this->t('None'),
        VvjaConstants::EXPAND_FIRST => $this->t('Expand First Item'),
        VvjaConstants::EXPAND_ALL => $this->t('Expand All Items'),
      ],
      '#default_value' => $this->options['expand_default'],
      '#description' => $this->t('Select the initial expand state for accordion items on page load. Note: "Expand All Items" is incompatible with "Enable Exclusive Mode".'),
    ];
  }

  /**
   * Animation section — animation type + transition speed.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildAnimationSection(array &$form): void {
    $form['animation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation Settings'),
      '#open' => TRUE,
      '#weight' => -30,
    ];

    $form['animation_section']['animation'] = $this->buildAnimationSelect('animation');
    $form['animation_section']['animation']['#title'] = $this->t('Animation Type');
    $form['animation_section']['animation']['#description'] = $this->t('Choose the animation type for opening and closing accordion panels. Reduced-motion users see no animation regardless of this setting.');

    $form['animation_section']['transition_speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Transition Speed'),
      '#default_value' => $this->options['transition_speed'],
      '#description' => $this->t('Select the speed for the accordion transition. Enter a value between @min (fast) and @max (slow) to control the animation speed.', [
        '@min' => ValidationBounds::MIN_TRANSITION_SPEED,
        '@max' => ValidationBounds::MAX_TRANSITION_SPEED,
      ]),
      '#step' => 0.1,
      '#min' => ValidationBounds::MIN_TRANSITION_SPEED,
      '#max' => ValidationBounds::MAX_TRANSITION_SPEED,
      '#required' => TRUE,
    ];
  }

  /**
   * Layout section — item width, padding, gap.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildLayoutSection(array &$form): void {
    $form['layout_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout Settings'),
      '#open' => FALSE,
      '#weight' => -20,
    ];

    $form['layout_section']['accordion_item_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Expandable Card Width (px)'),
      '#default_value' => $this->options['accordion_item_width'],
      '#description' => $this->t('Set the width of each Expandable Card in pixels. You can use this setting to display items as Expandable Cards or as a list. If set to zero, each item will expand to the full available width, creating a list layout. Otherwise, specifying a width will display each item as an Expandable Card with the specified width.'),
      '#step' => 1,
      '#min' => VvjaConstants::MIN_ACCORDION_ITEM_WIDTH,
      '#required' => TRUE,
    ];

    $form['layout_section']['pane_padding'] = [
      '#type' => 'number',
      '#title' => $this->t('Content Padding (px)'),
      '#default_value' => $this->options['pane_padding'],
      '#description' => $this->t('Set the inner padding of each accordion panel content area in pixels.'),
      '#step' => 1,
      '#min' => VvjaConstants::MIN_PANE_PADDING,
      '#required' => TRUE,
    ];

    $form['layout_section']['panel_gap'] = [
      '#type' => 'number',
      '#title' => $this->t('Panel Gap (px)'),
      '#default_value' => $this->options['panel_gap'],
      '#description' => $this->t('Set the gap between accordion panels in pixels.'),
      '#step' => 1,
      '#min' => VvjaConstants::MIN_PANEL_GAP,
      '#required' => TRUE,
    ];
  }

  /**
   * Custom-SVG-icons section — admin pastes raw SVG markup.
   *
   * @param array<int|string, mixed> $form
   *   The form, modified in place.
   */
  protected function buildCustomIconsSection(array &$form): void {
    $google_url = 'https://fonts.google.com/icons';

    $form['icons_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom Icons'),
      '#open' => FALSE,
      '#weight' => -15,
      '#description' => $this->t('<p>Replace the default toggle icons with your own SVG markup. Leave empty to use the default icons. You can browse thousands of free SVG icons at <a href="@url" target="_blank">Google Material Symbols</a>.</p>
        <p><strong>How to use:</strong> Find an icon, click the SVG download or copy the SVG code, and paste it into the field below. For best results, use icons with <code>fill="currentColor"</code> so they inherit your theme colors.</p>
        <details><summary><strong>Security &amp; Sanitization</strong></summary>
        <p>All SVG input is validated and sanitized before saving. The following protections are applied:</p>
        <ul>
          <li><strong>Allowlisted elements only:</strong> svg, path, circle, rect, line, polyline, polygon, ellipse, g, defs, use, symbol, clippath, mask, lineargradient, radialgradient, stop, title, desc.</li>
          <li><strong>Allowlisted attributes only:</strong> Core attributes (id, class, xmlns), dimensions (viewBox, width, height, x, y, r, cx, cy, etc.), path data (d, points), presentation (fill, stroke, opacity, transform, etc.), and gradient attributes.</li>
          <li><strong>Blocked content:</strong> &lt;script&gt;, &lt;foreignObject&gt;, &lt;iframe&gt;, &lt;object&gt;, &lt;embed&gt; elements are rejected. All event handlers (onclick, onload, etc.) are stripped. javascript: and data: URIs are removed. External URL references are blocked — only internal fragment references (#id) are allowed.</li>
          <li><strong>XML validation:</strong> Input must be well-formed XML with &lt;svg&gt; as the root element.</li>
        </ul>
        </details>', ['@url' => $google_url]),
    ];

    foreach ($this->getSvgFieldDefinitions() as $key => $def) {
      $form['icons_section'][$key] = [
        '#type' => 'textarea',
        '#title' => $def['title'],
        '#default_value' => $this->options[$key] ?? '',
        '#description' => $def['description'],
        '#rows' => 3,
        '#placeholder' => '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="..."/></svg>',
        '#element_validate' => [[$this, 'validateSvgField']],
      ];
    }
  }

  /**
   * SVG field metadata.
   *
   * @return array<string, array{title: \Drupal\Core\StringTranslation\TranslatableMarkup, description: \Drupal\Core\StringTranslation\TranslatableMarkup}>
   *   Field key → label + description pairs for the SVG textareas.
   */
  protected function getSvgFieldDefinitions(): array {
    return [
      'svg_expand_content' => [
        'title' => $this->t('Expand Panel Icon'),
        'description' => $this->t('SVG icon shown when a panel can be expanded (opened).'),
      ],
      'svg_collapse_content' => [
        'title' => $this->t('Collapse Panel Icon'),
        'description' => $this->t('SVG icon shown when a panel can be collapsed (closed).'),
      ],
      'svg_expand_all' => [
        'title' => $this->t('Expand All Icon'),
        'description' => $this->t('SVG icon for the global "Expand All" button.'),
      ],
      'svg_collapse_all' => [
        'title' => $this->t('Collapse All Icon'),
        'description' => $this->t('SVG icon for the global "Collapse All" button.'),
      ],
    ];
  }

  /**
   * Validate + sanitize an SVG textarea field.
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateSvgField(array $element, FormStateInterface $form_state): void {
    $raw = $element['#value'] ?? '';
    $value = is_scalar($raw) ? trim((string) $raw) : '';
    if ($value === '') {
      return;
    }

    $errors = $this->svgSanitizer->validate($value);
    if (!empty($errors)) {
      $form_state->setError($element, $this->t('@title: @errors', [
        '@title' => $element['#title'],
        '@errors' => implode(' ', $errors),
      ]));
      return;
    }

    $sanitized = $this->svgSanitizer->sanitize($value);
    if ($sanitized === '') {
      $form_state->setError($element, $this->t('@title: The SVG could not be processed.', [
        '@title' => $element['#title'],
      ]));
      return;
    }

    $form_state->setValueForElement($element, $sanitized);
  }

  /**
   * {@inheritdoc}
   *
   * @param array<int|string, mixed> $form
   *   The Views style options form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::validateOptionsForm($form, $form_state);

    $values = $form_state->getValue('style_options');
    if (!is_array($values)) {
      return;
    }

    $behavior = $values['behavior_section'] ?? NULL;
    $behavior_form = $form['behavior_section'] ?? NULL;
    if (is_array($behavior) && is_array($behavior_form)) {
      $exclusive = !empty($behavior['exclusive_panel']);
      $exclusive_el = $behavior_form['exclusive_panel'] ?? NULL;
      if ($exclusive && !empty($behavior['global_toggle']) && is_array($exclusive_el)) {
        $form_state->setError(
          $exclusive_el,
          $this->t('Exclusive mode is incompatible with "Show Collapse or Expand All". Disable one.'),
        );
      }

      $expand = $behavior['expand_default'] ?? VvjaConstants::EXPAND_NONE;
      $expand_el = $behavior_form['expand_default'] ?? NULL;
      if ($exclusive && $expand === VvjaConstants::EXPAND_ALL && is_array($expand_el)) {
        $form_state->setError(
          $expand_el,
          $this->t('"Expand All Items" is incompatible with Exclusive Mode.'),
        );
      }
    }

    $animation = $values['animation_section'] ?? NULL;
    $animation_form = $form['animation_section'] ?? NULL;
    if (is_array($animation) && is_array($animation_form)) {
      $raw_speed = $animation['transition_speed'] ?? NULL;
      $speed_el = $animation_form['transition_speed'] ?? NULL;
      if ($raw_speed !== NULL && is_array($speed_el)) {
        $speed = is_numeric($raw_speed) ? (float) $raw_speed : 0.0;
        if ($speed < ValidationBounds::MIN_TRANSITION_SPEED
          || $speed > ValidationBounds::MAX_TRANSITION_SPEED) {
          $form_state->setError(
            $speed_el,
            $this->t('Transition speed must be between @min and @max seconds.', [
              '@min' => ValidationBounds::MIN_TRANSITION_SPEED,
              '@max' => ValidationBounds::MAX_TRANSITION_SPEED,
            ]),
          );
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param array<int|string, mixed> $form
   *   The Views style options form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('style_options');
    if (!is_array($values)) {
      $values = [];
    }
    $form_state->setValue('style_options', $this->flattenFormValues($values));
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Flatten the sectioned form values back into the persisted shape.
   *
   * @param array<int|string, mixed> $values
   *   The submitted form values keyed by section then by option name.
   *
   * @return array<string, mixed>
   *   Flattened option array suitable for $this->options.
   */
  protected function flattenFormValues(array $values): array {
    $flattened = [];

    $behavior = $values['behavior_section'] ?? NULL;
    if (is_array($behavior)) {
      $flattened['global_toggle'] = $behavior['global_toggle'] ?? TRUE;
      $flattened['exclusive_panel'] = $behavior['exclusive_panel'] ?? FALSE;
      $flattened['single_toggle'] = $behavior['single_toggle'] ?? TRUE;
      $flattened['expand_default'] = $behavior['expand_default'] ?? VvjaConstants::DEFAULT_EXPAND;
    }

    $animation = $values['animation_section'] ?? NULL;
    if (is_array($animation)) {
      $flattened['animation'] = $animation['animation'] ?? VvjaConstants::DEFAULT_ANIMATION;
      $flattened['transition_speed'] = $animation['transition_speed'] ?? VvjaConstants::DEFAULT_TRANSITION_SPEED;
    }

    $layout = $values['layout_section'] ?? NULL;
    if (is_array($layout)) {
      $flattened['accordion_item_width'] = $layout['accordion_item_width'] ?? VvjaConstants::DEFAULT_ACCORDION_ITEM_WIDTH;
      $flattened['pane_padding'] = $layout['pane_padding'] ?? VvjaConstants::DEFAULT_PANE_PADDING;
      $flattened['panel_gap'] = $layout['panel_gap'] ?? VvjaConstants::DEFAULT_PANEL_GAP;
    }

    $advanced = $values['advanced_section'] ?? NULL;
    if (is_array($advanced)) {
      $flattened['enable_css'] = $advanced['enable_css'] ?? TRUE;
    }

    $deeplink = $values['deeplink_section'] ?? NULL;
    if (is_array($deeplink)) {
      $flattened['enable_deeplink'] = $deeplink['enable_deeplink'] ?? FALSE;
      $flattened['deeplink_identifier'] = $deeplink['deeplink_identifier'] ?? '';
    }

    $icons = $values['icons_section'] ?? NULL;
    if (is_array($icons)) {
      $flattened['svg_expand_content'] = $icons['svg_expand_content'] ?? '';
      $flattened['svg_collapse_content'] = $icons['svg_collapse_content'] ?? '';
      $flattened['svg_expand_all'] = $icons['svg_expand_all'] ?? '';
      $flattened['svg_collapse_all'] = $icons['svg_collapse_all'] ?? '';
    }

    $flattened['unique_id'] = $this->options['unique_id'] ?? $this->generateUniqueId();

    return $flattened;
  }

}
