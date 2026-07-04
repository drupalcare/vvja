<?php

declare(strict_types=1);

namespace Drupal\Tests\vvja\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Views;
use Drupal\vvja\Plugin\views\style\Accordion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies the views_vvja style plugin loads + provides expected defaults.
 */
#[Group('vvja')]
#[RunTestsInSeparateProcesses]
final class AccordionStylePluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'views',
    'vvj_core',
    'vvja',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'filter', 'node']);

    // The optional vvja_example view depends on field.storage.node.body;
    // create it so installConfig(['vvja']) installs the example view.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'body',
      'type' => 'text_with_summary',
    ])->save();

    $this->installConfig(['vvja']);
  }

  /**
   * The style plugin is registered with the expected class and theme.
   */
  public function testStylePluginIsRegistered(): void {
    /** @var \Drupal\views\Plugin\ViewsPluginManager $manager */
    $manager = $this->container->get('plugin.manager.views.style');
    $definition = $manager->getDefinition('views_vvja');
    $this->assertIsArray($definition);

    $this->assertSame('Drupal\\vvja\\Plugin\\views\\style\\Accordion', $definition['class']);
    $this->assertSame('views_view_vvja', $definition['theme']);
  }

  /**
   * The example view loads and initializes the vvja style plugin.
   */
  public function testExampleViewLoadsWithVvjaStyle(): void {
    $view = Views::getView('vvja_example');
    $this->assertNotNull($view, 'vvja_example view ships as optional config.');

    $view->setDisplay('default');
    $view->initStyle();

    $this->assertInstanceOf(Accordion::class, $view->style_plugin);
    $this->assertSame('views_vvja', $view->style_plugin->getPluginId());
  }

  /**
   * The style plugin defines the expected option defaults.
   */
  public function testStyleDefinesExpectedOptionDefaults(): void {
    $view = Views::getView('vvja_example');
    $this->assertNotNull($view);
    $view->setDisplay('default');
    $view->initStyle();

    $options = $view->style_plugin->options;

    $this->assertArrayHasKey('global_toggle', $options);
    $this->assertArrayHasKey('single_toggle', $options);
    $this->assertArrayHasKey('expand_default', $options);
    $this->assertArrayHasKey('animation', $options);
    $this->assertArrayHasKey('transition_speed', $options);
    $this->assertArrayHasKey('accordion_item_width', $options);
    $this->assertArrayHasKey('pane_padding', $options);
    $this->assertArrayHasKey('panel_gap', $options);
    $this->assertArrayHasKey('exclusive_panel', $options);
    $this->assertArrayHasKey('enable_deeplink', $options);
    $this->assertArrayHasKey('deeplink_identifier', $options);
    $this->assertArrayHasKey('unique_id', $options);
  }

  /**
   * Rendering produces cache metadata and attaches the pattern library.
   */
  public function testRenderProducesCacheableOutput(): void {
    $view = Views::getView('vvja_example');
    $this->assertNotNull($view);
    $view->setDisplay('default');
    $view->execute();

    $build = $view->style_plugin->render();
    $this->assertIsArray($build);

    $this->assertArrayHasKey('#cache', $build);
    $cache = $build['#cache'];
    $this->assertIsArray($cache);
    $this->assertArrayHasKey('tags', $cache);
    $this->assertArrayHasKey('contexts', $cache);
    $this->assertArrayHasKey('max-age', $cache);

    $this->assertArrayHasKey('#attached', $build);
    $attached = $build['#attached'];
    $this->assertIsArray($attached);
    $this->assertArrayHasKey('library', $attached);
    $library = $attached['library'];
    $this->assertIsArray($library);
    $this->assertContains('vvja/vvja', $library);
  }

}
