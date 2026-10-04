<?php

declare(strict_types=1);

namespace Drupal\Tests\vvja\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * A view saved by v1 is converted to the v2 option shape by vvja_update_10002.
 *
 * The fixture is written straight to config storage: no schema casting and
 * no save event, so it holds exactly what a v1 database holds. Strict schema
 * checking stays on and validates the CONVERTED view when the update saves
 * it. Measured on a real v1 database (solo.local) 2026-09-26.
 */
#[Group('vvja')]
#[RunTestsInSeparateProcesses]
final class VvjaLegacyOptionsUpgradeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = ['system', 'user', 'filter', 'views', 'vvj_core', 'vvja'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::moduleHandler()->loadInclude('vvja', 'install');
  }

  /**
   * The v1 first_toggle becomes expand_default, and types are cast.
   */
  public function testFirstToggleBecomesExpandDefaultAndTypesAreCast(): void {
    $this->saveLegacyView('legacy', [
      'first_toggle' => 1,
      'enable_css' => 1,
      'accordion_item_width' => '500',
      'transition_speed' => '0.5',
    ]);
    // CONTROL: the fixture really is drifted, or the checks below prove
    // nothing.
    $this->assertNotSame([], $this->driftOf('legacy'));

    $message = vvja_update_10002();
    $this->assertStringContainsString('Converted 1', $message);

    $options = $this->optionsOf('legacy');
    $this->assertArrayNotHasKey('first_toggle', $options);
    $this->assertSame('first', $options['expand_default']);
    $this->assertTrue($options['enable_css']);
    $this->assertSame(500, $options['accordion_item_width']);
    $this->assertSame(0.5, $options['transition_speed']);
    $this->assertSame([], $this->driftOf('legacy'));

    // Idempotent: a second run changes nothing.
    $this->assertStringContainsString('already', vvja_update_10002());
  }

  /**
   * A newer expand_default wins over the old first_toggle.
   */
  public function testExistingExpandDefaultIsKept(): void {
    $this->saveLegacyView('both', ['first_toggle' => 1, 'expand_default' => 'all']);
    vvja_update_10002();
    $options = $this->optionsOf('both');
    $this->assertSame('all', $options['expand_default']);
    $this->assertArrayNotHasKey('first_toggle', $options);
  }

  /**
   * Stores a view the way v1 left it: no schema casting.
   *
   * @param string $id
   *   View ID.
   * @param array<string, mixed> $options
   *   Style options as v1 stored them.
   */
  private function saveLegacyView(string $id, array $options): void {
    // Straight to storage: no casting, exactly as v1 left it. (The
    // trusted-data save() is deprecated in 11.4, removed in 13.)
    $this->container->get('config.storage')->write('views.view.' . $id, [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'id' => $id,
      'label' => $id,
      'module' => 'views',
      'description' => '',
      'tag' => '',
      'base_table' => 'users_field_data',
      'base_field' => 'uid',
      'display' => [
        'default' => [
          'id' => 'default',
          'display_title' => 'Default',
          'display_plugin' => 'default',
          'position' => 0,
          'display_options' => [
            'style' => ['type' => 'views_vvja', 'options' => $options],
          ],
        ],
      ],
    ]);
    $this->container->get('config.factory')->reset('views.view.' . $id);
  }

  /**
   * The stored style options of a view, read fresh.
   *
   * @param string $id
   *   View ID.
   *
   * @return array<mixed>
   *   The options.
   */
  private function optionsOf(string $id): array {
    $this->container->get('config.factory')->reset('views.view.' . $id);
    $options = $this->config('views.view.' . $id)->get('display.default.display_options.style.options');
    $this->assertIsArray($options);
    return $options;
  }

  /**
   * Drift the auditor reports for a view.
   *
   * @param string $id
   *   View ID.
   *
   * @return list<string>
   *   Drift descriptions.
   */
  private function driftOf(string $id): array {
    /** @var \Drupal\vvj_core\Service\VvjUpgradeAuditor $auditor */
    $auditor = $this->container->get('vvj_core.upgrade_auditor');
    return $auditor->detectDrift($id, ['views_vvja']);
  }

}
