<?php

declare(strict_types=1);

namespace Drupal\Tests\vvja\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use Drupal\vvj_core\Service\SvgSanitizer;
use Drupal\vvj_core\Service\TokenResolver;
use PHPUnit\Framework\Attributes\Group;

/**
 * Confirms vvja installs, ships its example view, and appears in Views UI.
 */
#[Group('vvja')]
final class VvjaInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'vvja',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The module installs and ships its example view as optional config.
   */
  public function testModuleInstallsAndShipsExampleView(): void {
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('vvja'));
    $view = Views::getView('vvja_example');
    $this->assertNotNull($view, 'Example view installs as optional config.');
  }

  /**
   * The required vvj_core dependency is enabled and its services are wired.
   *
   * $modules lists only `vvja`, so vvj_core here is enabled purely via the
   * info.yml dependency — the same resolution a `composer require` performs.
   * This guards the v2 required-dependency contract: vvj_core is present and
   * the pattern's `@?vvj_core.*` nullable-injected services resolve to real
   * instances (not NULL) once vvj_core is enabled.
   *
   * The v1 -> v2 *update-hook* transition (`vvja_update_10001` enabling
   * vvj_core on `drush updb`) is a separate path that needs UpdatePathTestBase
   * + a v1 database fixture; see docs/planning/VVJ-V2-UPGRADE (README).
   */
  public function testVvjCoreDependencyEnabledAndWired(): void {
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('vvj_core'),
      'Installing vvja enables its required vvj_core dependency.',
    );

    $this->assertInstanceOf(
      SvgSanitizer::class,
      $this->container->get('vvj_core.svg_sanitizer'),
    );
    $this->assertInstanceOf(
      TokenResolver::class,
      $this->container->get('vvj_core.token_resolver'),
    );
  }

  /**
   * The style is reachable through the Views UI edit form.
   */
  public function testStyleAppearsInViewsUi(): void {
    $admin = $this->drupalCreateUser([
      'administer views',
      'access administration pages',
    ]);
    $this->assertInstanceOf(User::class, $admin);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/structure/views/view/vvja_example/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('VVJA Example');
  }

  /**
   * The module help page renders with the expected summary text.
   */
  public function testHelpPageRenders(): void {
    $admin = $this->drupalCreateUser([
      'access administration pages',
    ]);
    $this->assertInstanceOf(User::class, $admin);
    $this->drupalLogin($admin);

    $this->drupalGet('admin/help/vvja');
    $this->assertSession()->statusCodeEquals(200);
    // Markdown filter isn't enabled in the test, so we get the <pre>
    // fallback containing README content.
    $this->assertSession()->responseContains('Views Vanilla JavaScript Accordion');
  }

}
