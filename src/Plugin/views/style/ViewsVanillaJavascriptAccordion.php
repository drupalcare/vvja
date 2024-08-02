<?php

namespace Drupal\vvja\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
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
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['single_toggle'] = ['default' => TRUE];
    $options['global_toggle'] = ['default' => TRUE];
    $options['first_toggle'] = ['default' => TRUE];
    $options['animation'] = ['default' => 'vvja__animate_bottom'];
    $options['unique_id'] = ['default' => $this->generateUniqueId()];
    // New option for CSS library.
    $options['enable_css'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['enable_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CSS Library'),
      '#default_value' => $this->options['enable_css'],
      '#description' => $this->t('Check this box to include the CSS library for styling the accordion.'),
    ];

    $form['single_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Collapse or Expand'),
      '#default_value' => $this->options['single_toggle'],
      '#description' => $this->t('Check this box to show a toggle (+/-) button to open and close the accordion sections.'),
    ];

    $form['global_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Collapse or Expand All'),
      '#default_value' => $this->options['global_toggle'],
      '#description' => $this->t('Check this box to show a button that toggles all the accordions at once.'),
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
        'vvja__animate_top' => $this->t('Top'),
        'vvja__animate_bottom' => $this->t('Bottom'),
        'vvja__animate_left' => $this->t('Left'),
        'vvja__animate_right' => $this->t('Right'),
        'vvja__animate_zoom' => $this->t('Zoom'),
        'vvja__animate_opacity' => $this->t('Opacity'),
      ],
      '#default_value' => $this->options['animation'],
      '#description' => $this->t('Choose the animation type.'),
    ];

  }

  /**
   * Generates a unique numeric ID for the view display.
   */
  protected function generateUniqueId() {
    // 8 digit unique ID
    return random_int(10000000, 99999999);
  }

  /**
   * Renders the view with the 3D carousel style.
   *
   * @return array
   *   A render array for the 3D carousel.
   */
  public function render() {
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

    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
      '#unique_id' => $this->options['unique_id'],
      '#attached' => [
        'library' => $libraries,
      ],
    ];
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
