<?php

declare(strict_types=1);

namespace Drupal\vvja\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\vvja\VvjaConstants;

/**
 * Style plugin to render items in an Accordion using vanilla JavaScript.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_vvja",
 *   title = @Translation("Views Vanilla JavaScript Accordion"),
 *   help = @Translation("Render items in an Accordion using vanilla JavaScript."),
 *   theme = "views_view_vvja",
 *   display_types = { "normal" }
 * )
 */
class ViewsVanillaJavascriptAccordion extends StylePluginBase {

  public const ANIMATION_NONE = 'none';
  public const ANIMATION_TOP = 'a-top';
  public const ANIMATION_BOTTOM = 'a-bottom';
  public const ANIMATION_LEFT = 'a-left';
  public const ANIMATION_RIGHT = 'a-right';
  public const ANIMATION_ZOOM = 'a-zoom';
  public const ANIMATION_FADE = 'a-fade';

  /**
   * Does the style plugin use a row plugin.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin use row classes.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Cached unique ID for this view display.
   *
   * @var int|null
   */
  protected ?int $cachedUniqueId = NULL;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['single_toggle'] = ['default' => TRUE];
    $options['global_toggle'] = ['default' => TRUE];
    $options['first_toggle'] = ['default' => TRUE];
    $options['transition_speed'] = ['default' => VvjaConstants::DEFAULT_TRANSITION_SPEED];
    $options['animation'] = ['default' => VvjaConstants::DEFAULT_ANIMATION];
    $options['unique_id'] = ['default' => $this->generateUniqueId()];
    $options['enable_css'] = ['default' => TRUE];
    $options['exclusive_panel'] = ['default' => FALSE];
    $options['accordion_item_width'] = ['default' => VvjaConstants::DEFAULT_ACCORDION_ITEM_WIDTH];
    $options['enable_deeplink'] = ['default' => FALSE];
    $options['deeplink_identifier'] = ['default' => ''];
    return $options;
  }

  /**
   * Get animation options.
   *
   * @return array
   *   An associative array of animation options.
   */
  protected function getAnimationOptions(): array {
    return [
      self::ANIMATION_NONE => $this->t('None'),
      self::ANIMATION_TOP => $this->t('Slide from Top'),
      self::ANIMATION_BOTTOM => $this->t('Slide from Bottom'),
      self::ANIMATION_LEFT => $this->t('Slide from Left'),
      self::ANIMATION_RIGHT => $this->t('Slide from Right'),
      self::ANIMATION_ZOOM => $this->t('Zoom'),
      self::ANIMATION_FADE => $this->t('Fade'),
    ];
  }

  /**
   * Enforce Fields row plugin requirement.
   *
   * This method ensures that the view always uses the Fields row plugin,
   * which is required for this style plugin to function properly.
   */
  protected function enforceFieldsRowPlugin(): void {
    if ($this->view && $this->view->rowPlugin && $this->view->rowPlugin->getPluginId() !== 'fields') {
      $this->view->display_handler->setOption('row', [
        'type' => 'fields',
        'options' => [],
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $this->enforceFieldsRowPlugin();
    $this->setDefaultElementWeights($form);
    $this->buildWarningMessage($form);
    $this->buildBehaviorSection($form);
    $this->buildAnimationSection($form);
    $this->buildLayoutSection($form);
    $this->buildDeepLinkingSection($form);
    $this->buildAdvancedSection($form);
    $this->buildTokenDocumentation($form);
    $this->attachFormAssets($form);
  }

  /**
   * Set weights for default Drupal form elements.
   *
   * @param array $form
   *   The form array.
   */
  protected function setDefaultElementWeights(array &$form): void {
    $default_elements = [
      'grouping' => -100,
      'row_class' => -90,
      'default_row_class' => -85,
      'uses_fields' => -80,
      'class' => -75,
      'wrapper_class' => -70,
    ];

    foreach ($default_elements as $element_key => $weight) {
      if (isset($form[$element_key])) {
        $form[$element_key]['#weight'] = $weight;
      }
    }
  }

  /**
   * Build warning message section.
   *
   * @param array $form
   *   The form array.
   */
  protected function buildWarningMessage(array &$form): void {
    // Don't show the message if we're already editing the example view.
    if ($this->view->storage->id() === 'vvja_example') {
      return;
    }

    $form['warning_message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--status">' . $this->t(
        'Note: The accordion component requires Fields as row style. To see an example, check the vvja_example view by clicking <a href="@url">here</a> to edit it.', [
          '@url' => Url::fromRoute('entity.view.edit_form', ['view' => 'vvja_example'])->toString(),
        ]
      ) . '</div>',
      '#weight' => -50,
    ];
  }
  /**
   * Build behavior configuration section.
   *
   * @param array $form
   *   The form array.
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

    $form['behavior_section']['first_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand First Item'),
      '#default_value' => $this->options['first_toggle'],
      '#description' => $this->t('Check this box to expand the first item in the accordion.'),
    ];
  }

  /**
   * Build animation configuration section.
   *
   * @param array $form
   *   The form array.
   */
  protected function buildAnimationSection(array &$form): void {
    $form['animation_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation Settings'),
      '#open' => TRUE,
      '#weight' => -30,
    ];

    $form['animation_section']['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation Type'),
      '#options' => $this->getAnimationOptions(),
      '#default_value' => $this->options['animation'],
      '#description' => $this->t('Choose the animation type for opening and closing accordion panels.'),
    ];

    $form['animation_section']['transition_speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Transition Speed'),
      '#default_value' => $this->options['transition_speed'],
      '#description' => $this->t('Select the speed for the accordion transition. Enter a value between @min (fast) and @max (slow) to control the animation speed.', [
        '@min' => VvjaConstants::MIN_TRANSITION_SPEED,
        '@max' => VvjaConstants::MAX_TRANSITION_SPEED,
      ]),
      '#step' => 0.1,
      '#min' => VvjaConstants::MIN_TRANSITION_SPEED,
      '#max' => VvjaConstants::MAX_TRANSITION_SPEED,
      '#required' => TRUE,
    ];
  }

  /**
   * Build layout configuration section.
   *
   * @param array $form
   *   The form array.
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
  }

  /**
   * Build deep linking configuration section.
   *
   * @param array $form
   *   The form array.
   */
  protected function buildDeepLinkingSection(array &$form): void {
    $form['deeplink_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Deep Linking Settings'),
      '#open' => FALSE,
      '#weight' => -5,
    ];

    $form['deeplink_section']['enable_deeplink'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Deep Linking'),
      '#description' => $this->t('Generate shareable links for each accordion panel that appear in the browser URL.'),
      '#default_value' => $this->options['enable_deeplink'] ?? FALSE,
      '#attributes' => [
        'data-vvja-deeplink-toggle' => 'true',
      ],
    ];

    $form['deeplink_section']['deeplink_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Identifier'),
      '#description' => $this->t('Short identifier used in panel links. Example: "faqs" creates links like #accordion-faqs-3. Will be automatically cleaned: converted to lowercase, spaces become hyphens, special characters removed.'),
      '#default_value' => $this->options['deeplink_identifier'] ?? '',
      '#maxlength' => VvjaConstants::DEEPLINK_IDENTIFIER_MAX_LENGTH,
      '#size' => 20,
      '#placeholder' => 'my-accordion',
      '#wrapper_attributes' => [
        'class' => ['deeplink-identifier-wrapper'],
        'data-vvja-deeplink-field' => 'true',
      ],
      '#element_validate' => [[$this, 'validateDeeplinkIdentifier']],
    ];
  }

  /**
   * Validates and sanitizes the deep link identifier field.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateDeeplinkIdentifier(array $element, FormStateInterface $form_state): void {
    $deeplink_values = $form_state->getValue(['style_options', 'deeplink_section']);
    $enable_deeplink = $deeplink_values['enable_deeplink'] ?? FALSE;
    $identifier = $deeplink_values['deeplink_identifier'] ?? '';

    if (!$enable_deeplink) {
      return;
    }

    if (empty($identifier)) {
      $form_state->setError($element, $this->t('URL Identifier is required when Deep Linking is enabled.'));
      return;
    }

    $transliteration = \Drupal::transliteration();
    $clean = $transliteration->transliterate($identifier, 'en');
    $clean = strtolower($clean);
    $clean = preg_replace('/[\s_]+/', '-', $clean);
    $clean = preg_replace('/[^a-z0-9-]/', '', $clean);
    $clean = preg_replace('/-+/', '-', $clean);
    $clean = trim($clean, '-');
    $clean = preg_replace('/^[0-9-]+/', '', $clean);

    if (empty($clean)) {
      $form_state->setError($element, $this->t('URL Identifier must contain at least one letter.'));
      return;
    }

    if (in_array($clean, VvjaConstants::DEEPLINK_RESERVED_WORDS, TRUE)) {
      $form_state->setError($element, $this->t('Please choose a more specific identifier. "@identifier" is a reserved word.', ['@identifier' => $clean]));
      return;
    }

    $form_state->setValue(['style_options', 'deeplink_section', 'deeplink_identifier'], $clean);
  }

  /**
   * Attach form-specific JavaScript and CSS assets.
   *
   * @param array $form
   *   The form array.
   */
  protected function attachFormAssets(array &$form): void {
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['#attached']['library'][] = 'vvja/vvja-admin';
  }

  /**
   * Build advanced options section.
   *
   * @param array $form
   *   The form array.
   */
  protected function buildAdvancedSection(array &$form): void {
    $form['advanced_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Options'),
      '#open' => FALSE,
      '#weight' => -10,
    ];

    $form['advanced_section']['enable_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CSS Library'),
      '#default_value' => $this->options['enable_css'],
      '#description' => $this->t('Check this box to include the CSS library for styling the accordion. Uncheck if you want to provide your own custom styles.'),
    ];
  }

  /**
   * Build token documentation section.
   *
   * @param array $form
   *   The form array.
   */
  protected function buildTokenDocumentation(array &$form): void {
    $form['token_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Token Documentation'),
      '#open' => FALSE,
      '#weight' => 100,
    ];

    $form['token_section']['description'] = [
      '#markup' => $this->t('<p>When using <em>Global: Text area</em> or <em>Global: Unfiltered text</em> in the Views header, footer, or empty text areas, the default Twig-style tokens (e.g., <code>{{ title }}</code>) will not work with the VVJA style plugin.</p>
        <p>Instead, use the custom VVJA token format to access field values from the <strong>first row</strong> of the View result:</p>
        <ul>
          <li><code>[vvja:field_name]</code> — The rendered output of the field (e.g., linked title, image, formatted text).</li>
          <li><code>[vvja:field_name:plain]</code> — A plain-text version of the field, with all HTML stripped.</li>
        </ul>
        <p>Examples:</p>
        <ul>
          <li><code>{{ title }}</code> ➜ <code>[vvja:title]</code></li>
          <li><code>{{ field_image }}</code> ➜ <code>[vvja:field_image]</code></li>
          <li><code>{{ body }}</code> ➜ <code>[vvja:body:plain]</code></li>
        </ul>
        <p>These tokens offer safe and flexible field output for dynamic headings, summaries, and fallback messages in VVJA-enabled Views.</p>'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::validateOptionsForm($form, $form_state);

    $values = $form_state->getValue('style_options');

    if (isset($values['behavior_section'])) {
      $behavior = $values['behavior_section'];

      if (!empty($behavior['exclusive_panel']) && !empty($behavior['global_toggle'])) {
        $form_state->setError(
          $form['behavior_section']['exclusive_panel'],
          $this->t('Exclusive mode is incompatible with "Show Collapse or Expand All" option. Please disable one of these features.')
        );
      }
    }

    if (isset($values['animation_section']['transition_speed'])) {
      $speed = $values['animation_section']['transition_speed'];
      if ($speed < VvjaConstants::MIN_TRANSITION_SPEED || $speed > VvjaConstants::MAX_TRANSITION_SPEED) {
        $form_state->setError(
          $form['animation_section']['transition_speed'],
          $this->t('Transition speed must be between @min and @max seconds.', [
            '@min' => VvjaConstants::MIN_TRANSITION_SPEED,
            '@max' => VvjaConstants::MAX_TRANSITION_SPEED,
          ])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('style_options');
    $flattened_values = $this->flattenFormValues($values);
    $form_state->setValue('style_options', $flattened_values);

    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Flatten nested form values to match original structure.
   *
   * @param array $values
   *   The nested form values.
   *
   * @return array
   *   The flattened values array.
   */
  protected function flattenFormValues(array $values): array {
    $flattened = [];

    if (isset($values['behavior_section'])) {
      $flattened['global_toggle'] = $values['behavior_section']['global_toggle'] ?? TRUE;
      $flattened['exclusive_panel'] = $values['behavior_section']['exclusive_panel'] ?? FALSE;
      $flattened['single_toggle'] = $values['behavior_section']['single_toggle'] ?? TRUE;
      $flattened['first_toggle'] = $values['behavior_section']['first_toggle'] ?? TRUE;
    }

    if (isset($values['animation_section'])) {
      $flattened['animation'] = $values['animation_section']['animation'] ?? VvjaConstants::DEFAULT_ANIMATION;
      $flattened['transition_speed'] = $values['animation_section']['transition_speed'] ?? VvjaConstants::DEFAULT_TRANSITION_SPEED;
    }

    if (isset($values['layout_section'])) {
      $flattened['accordion_item_width'] = $values['layout_section']['accordion_item_width'] ?? VvjaConstants::DEFAULT_ACCORDION_ITEM_WIDTH;
    }

    if (isset($values['advanced_section'])) {
      $flattened['enable_css'] = $values['advanced_section']['enable_css'] ?? TRUE;
    }

    if (isset($values['deeplink_section'])) {
      $flattened['enable_deeplink'] = $values['deeplink_section']['enable_deeplink'] ?? FALSE;
      $flattened['deeplink_identifier'] = $values['deeplink_section']['deeplink_identifier'] ?? '';
    }

    $flattened['unique_id'] = $this->options['unique_id'] ?? $this->generateUniqueId();

    return $flattened;
  }

  /**
   * Generates a unique numeric ID for the view display.
   *
   * @return int
   *   A unique ID between 10000000 and 99999999.
   *
   * @throws \Exception
   *   If an appropriate source of randomness cannot be found.
   */
  protected function generateUniqueId(): int {
    if ($this->cachedUniqueId !== NULL) {
      return $this->cachedUniqueId;
    }

    $this->cachedUniqueId = random_int(VvjaConstants::MIN_UNIQUE_ID, VvjaConstants::MAX_UNIQUE_ID);

    if ($this->cachedUniqueId < VvjaConstants::MIN_UNIQUE_ID) {
      $this->cachedUniqueId += VvjaConstants::MIN_UNIQUE_ID;
    }
    if ($this->cachedUniqueId > VvjaConstants::MAX_UNIQUE_ID) {
      $range = VvjaConstants::MAX_UNIQUE_ID - VvjaConstants::MIN_UNIQUE_ID + 1;
      $this->cachedUniqueId = $this->cachedUniqueId % $range + VvjaConstants::MIN_UNIQUE_ID;
    }

    return $this->cachedUniqueId;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $rows = [];

    if (!empty($this->view->result)) {
      foreach ($this->view->result as $row) {
        $rendered_row = $this->view->rowPlugin->render($row);
        if ($rendered_row !== NULL) {
          $rows[] = $rendered_row;
        }
      }
    }

    $libraries = $this->buildLibraryList();

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
      '#unique_id' => $this->options['unique_id'] ?? $this->generateUniqueId(),
      '#attached' => [
        'library' => $libraries,
      ],
    ];

    return $build;
  }

  /**
   * Build the list of libraries to attach.
   *
   * @return array
   *   An array of library names to attach.
   */
  protected function buildLibraryList(): array {
    $libraries = ['vvja/vvja'];

    if (!empty($this->options['enable_css'])) {
      $libraries[] = 'vvja/vvja-style';
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): array {
    $errors = parent::validate();

    if (!$this->usesFields()) {
      $errors[] = $this->t('Views Accordions requires Fields as row style');
    }

    return $errors;
  }

}
