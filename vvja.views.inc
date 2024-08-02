<?php

/**
 * @file
 * Contains the hook implementations for the custom Views style plugin.
 */

/**
 * Implements hook_views_data_alter().
 */
function vvja_views_data_alter(array &$data) {
  // Define the style plugin schema.
  $data['views_style_plugin']['views_vvja'] = [
    'type' => 'views_style',
    'label' => t('Views Vanilla JavaScript Accordion'),
    'mapping' => [
      'single_toggle' => [
        'type' => 'boolean',
        'label' => t('Show Collapse or Expand'),
      ],
      'global_toggle' => [
        'type' => 'boolean',
        'label' => t('Show Collapse or Expand All'),
      ],
      'first_toggle' => [
        'type' => 'boolean',
        'label' => t('Expand First Item'),
      ],
      'animation' => [
        'type' => 'string',
        'label' => t('Animation Type'),
      ],
      'unique_id' => [
        'type' => 'string',
        'label' => t('Unique ID for the view display'),
      ],
      'enable_css' => [
        'type' => 'boolean',
        'label' => t('Enable CSS Library'),
      ],
    ],
  ];
}
