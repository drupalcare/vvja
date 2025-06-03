<?php

namespace Drupal\vvja\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\style\StylePluginBase;

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

  /**
   * Does the style plugin use a row plugin.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options.
   *
   * @return array
   *   The default options.
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['single_toggle'] = ['default' => TRUE];
    $options['global_toggle'] = ['default' => TRUE];
    $options['first_toggle'] = ['default' => TRUE];
    $options['transition_speed'] = ['default' => '0.3'];
    $options['animation'] = ['default' => 'a-bottom'];
    $options['unique_id'] = ['default' => $this->generateUniqueId()];
    $options['enable_css'] = ['default' => TRUE];
    $options['exclusive_panel'] = ['default' => FALSE];
    $options['accordion_item_width'] = ['default' => 0];
    return $options;
  }

  /**
   * Builds the options form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['warning_message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--warning">' . $this->t('Note: The accordion component requires Fields as row style. To see an example, check the vvja_example view by clicking <a href="@url">here</a> to edit it.', [
        '@url' => Url::fromRoute('entity.view.edit_form', ['view' => 'vvja_example'])->toString(),
      ]) . '</div>',
    ];

    $form['enable_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CSS Library'),
      '#default_value' => $this->options['enable_css'],
      '#description' => $this->t('Check this box to include the CSS library for styling the accordion.'),
    ];

    $form['global_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Collapse or Expand All (Global)'),
      '#default_value' => $this->options['global_toggle'],
      '#description' => $this->t('Check this box to show a button that toggles all the accordions at once.'),
    ];

    $form['exclusive_panel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Exclusive Mode'),
      '#default_value' => $this->options['exclusive_panel'],
      '#description' => $this->t('Allow only one panel to be open at a time. By default, multiple panels can be open simultaneously. Note: This feature is incompatible with the "Show Collapse" or "Expand All (Global)" options.'),
      '#states' => [
        'enabled' => [
          ':input[name="style_options[global_toggle]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['single_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Collapse or Expand (Single)'),
      '#default_value' => $this->options['single_toggle'],
      '#description' => $this->t('Check this box to show a toggle (+/-) button to open and close the accordion sections.'),
    ];

    $form['first_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand First Item'),
      '#default_value' => $this->options['first_toggle'],
      '#description' => $this->t('Check this box to expand the first item in the accordion.'),
    ];

    $form['animation'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation Type'),
      '#options' => [
        'none' => $this->t('None'),
        'a-top' => $this->t('Slide from Top'),
        'a-bottom' => $this->t('Slide from Bottom'),
        'a-left' => $this->t('Slide from Left'),
        'a-right' => $this->t('Slide from Right'),
        'a-zoom' => $this->t('Zoom'),
        'a-fade' => $this->t('Fade'),
      ],
      '#default_value' => $this->options['animation'],
      '#description' => $this->t('Choose the animation type.'),
    ];

    $form['transition_speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Transition Speed'),
      '#default_value' => $this->options['transition_speed'],
      '#description' => $this->t('Select the speed for the accordion transition. Enter a value between 0.1 (fast) and 2 (slow) to control the animation speed.'),
      '#step' => 0.1,
      '#min' => 0.1,
      '#max' => 2,
      '#required' => TRUE,
    ];

    $form['accordion_item_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Expandable Card Width (px)'),
      '#default_value' => $this->options['accordion_item_width'],
      '#description' => $this->t('Set the width of each Expandable Card in pixels. You can use this setting to display items as Expandable Cards or as a list. If set to zero, each item will expand to the full available width, creating a list layout. Otherwise, specifying a width will display each item as an Expandable Card with the specified width.'),
      '#step' => 1,
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['vvja_token_info'] = [
      '#type' => 'details',
      '#title' => $this->t('VVJA Tokens'),
      '#open' => TRUE,
    ];

    $form['vvja_token_info']['description'] = [
      '#markup' => $this->t('<p>When using <em>Global: Text area</em> or <em>Global: Unfiltered text</em> in the Views header, footer, or empty text areas, the default Twig-style tokens (e.g., <code>{{ title }}</code>) will not work with the VVJA style plugin.</p>
        <p>Instead, use the custom VVJA token format to access field values from the <strong>first row</strong> of the View result:</p>
        <ul>
          <li><code>[vvja:field_name]</code> – The rendered output of the field (e.g., linked title, image, formatted text).</li>
          <li><code>[vvja:field_name:plain]</code> – A plain-text version of the field, with all HTML stripped.</li>
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
   * Generates a unique numeric ID for the view display.
   *
   * @return int
   *   A unique ID.
   *
   * @throws \Exception
   *   If an appropriate source of randomness cannot be found.
   */
  protected function generateUniqueId(): int {
    return random_int(10000000, 99999999);
  }

  /**
   * Renders the view with the accordion style.
   *
   * @return array
   *   A render array for the accordion.
   */
  public function render(): array {
    $rows = [];
    foreach ($this->view->result as $row) {
      $rows[] = $this->view->rowPlugin->render($row);
    }

    // Libraries to be attached.
    $libraries = [
    // Always include the JavaScript library.
      'vvja/vvja',
    ];

    // Conditionally include the CSS library based on the option.
    if ($this->options['enable_css']) {
      $libraries[] = 'vvja/vvja-style';
    }

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
      '#unique_id' => $this->options['unique_id'],
      '#attached' => [
        'library' => $libraries,
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    if (!$this->usesFields()) {
      $errors[] = $this->t('Views Accordions requires Fields as row style');
    }
    return $errors;
  }

}
