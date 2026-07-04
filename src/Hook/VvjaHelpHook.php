<?php

declare(strict_types=1);

namespace Drupal\vvja\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filter\FilterPluginManager;

/**
 * Help hook implementation.
 *
 * Renders the module's README.md on the help.page.vvja route. Falls
 * back to a `<pre>` block when the markdown contrib module isn't
 * enabled. Filter plugin manager is injected via the concrete class
 * (autowire-friendly) and is always available because `drupal:filter`
 * is a hard dependency of this module.
 */
final class VvjaHelpHook {

  use StringTranslationTrait;

  public function __construct(
    private readonly ModuleExtensionList $moduleExtensionList,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly FilterPluginManager $filterManager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Implements hook_help() for the VVJA help route.
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    if ($route_name !== 'help.page.vvja') {
      return NULL;
    }
    return $this->renderReadme();
  }

  /**
   * Render the module's README.md as HTML (markdown filter when present).
   */
  private function renderReadme(): string {
    $module_path = $this->moduleExtensionList->getPath('vvja');
    $readme_path = $module_path . '/README.md';
    $text = @file_get_contents($readme_path);

    if ($text === FALSE) {
      return (string) $this->t('README.md file not found.');
    }

    if (!$this->moduleHandler->moduleExists('markdown')) {
      return '<pre>' . Html::escape($text) . '</pre>';
    }

    $settings = $this->configFactory->get('markdown.settings')->getRawData();
    /** @var \Drupal\filter\Plugin\FilterInterface $filter */
    $filter = $this->filterManager->createInstance('markdown', ['settings' => $settings]);
    return $filter->process($text, 'en')->getProcessedText();
  }

}
