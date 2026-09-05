<?php

declare(strict_types=1);

namespace Drupal\Tests\vvja\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * FunctionalJavascript tests for vvja accordion interaction and contract.
 */
#[Group('vvja')]
#[Group('vvj_javascript')]
#[Group('vvj_javascript')]
#[RunTestsInSeparateProcesses]
final class AccordionBehaviorTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = [
    'node',
    'block',
    'views',
    'vvja',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);

    foreach (range(1, 3) as $i) {
      Node::create([
        'type' => 'page',
        'title' => "Accordion Item $i",
        'body' => ['value' => "Body content for item $i", 'format' => 'plain_text'],
        'status' => 1,
      ])->save();
    }

    user_role_grant_permissions('anonymous', ['access content']);

    $this->addBlockDisplayToExampleView();
    $this->drupalPlaceBlock('views_block:vvja_example-block_1', [
      'region' => 'content',
    ]);
  }

  /**
   * Clicking an accordion trigger toggles aria-expanded on the button.
   *
   * THE FIRST PANEL STARTS OPEN, and this test used to assert it starts
   * closed — which is why it failed on a module that was behaving correctly.
   * The shipped example view sets `expand_default: first`, and the template
   * computes `is_expanded = all_expanded or (expand_default == 'first' and
   * key == 0)`. So the first button renders `aria-expanded="true"` by design.
   * The toggle is still exercised in both directions; it just starts from the
   * state the fixture actually produces.
   */
  public function testTriggerOpensAndClosesPanel(): void {
    $this->drupalGet('<front>');
    $session = $this->assertSession();

    $session->elementExists('css', '.vvja .vvja-button');
    $session->elementAttributeContains('css', '.vvja .vvja-button', 'aria-expanded', 'true');

    $page = $this->getSession()->getPage();
    $first = $page->find('css', '.vvja .vvja-button');
    $this->assertNotNull($first);
    $first->click();

    // Open -> closed.
    $this->assertJsCondition(
      "document.querySelector('.vvja .vvja-button').getAttribute('aria-expanded') === 'false'",
    );

    $first->click();

    // Closed -> open again.
    $this->assertJsCondition(
      "document.querySelector('.vvja .vvja-button').getAttribute('aria-expanded') === 'true'",
    );
  }

  /**
   * The global toggle expands all accordion panel triggers.
   */
  public function testGlobalToggleExpandsAllPanels(): void {
    $this->drupalGet('<front>');

    $page = $this->getSession()->getPage();
    $globalToggle = $page->find('css', '.vvja .global-toggle .button.group-toggle');
    $this->assertNotNull($globalToggle, 'Global toggle button is rendered.');

    $globalToggle->click();

    $this->assertJsCondition(
      "Array.from(document.querySelectorAll('.vvja .vvja-button'))"
      . ".every((b) => b.getAttribute('aria-expanded') === 'true')",
    );
  }

  /**
   * ArrowDown moves keyboard focus to the next accordion trigger.
   */
  public function testArrowKeyMovesFocusBetweenTriggers(): void {
    $this->drupalGet('<front>');

    $session = $this->getSession();
    $session->executeScript(
      "document.querySelector('.vvja .vvja-button').focus();",
    );
    // Send ArrowDown via key dispatch.
    $session->executeScript(
      "document.activeElement.dispatchEvent("
      . "new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true })"
      . ");",
    );

    $this->assertJsCondition(
      "document.activeElement === document.querySelectorAll('.vvja .vvja-button')[1]",
    );
  }

  /**
   * The Drupal.Vvj namespace loads and the accordion element registers.
   *
   * Guards the contract introduced when VVJ moved from ES module imports
   * to Drupal core's namespace + library-dependency-ordering pattern:
   * `vvj_core` populates `Drupal.Vvj.*` (base class + shared helpers) and
   * the accordion reads them off that namespace, with load order
   * guaranteed by the `vvja` library `dependencies:`. Independent of lazy
   * hydration, so a dependency-ordering regression fails here with a clear
   * signal rather than as a silent "click did nothing".
   */
  public function testVvjNamespaceAndCustomElementRegistration(): void {
    $this->drupalGet('<front>');

    // vvj_core element-base + keyboard-nav loaded first and populated the
    // shared namespace that the accordion element consumes.
    $this->assertJsCondition(
      "typeof Drupal.Vvj === 'object'"
      . " && typeof Drupal.Vvj.ElementBase === 'function'"
      . " && typeof Drupal.Vvj.handleArrowKeyNav === 'function'",
    );

    // <vvja-accordion> registered and upgraded to a subclass of the base —
    // proves the namespace handoff works end to end.
    $this->assertJsCondition(
      "typeof customElements.get('vvja-accordion') === 'function'",
    );
    $this->assertJsCondition(
      "document.querySelector('vvja-accordion') instanceof Drupal.Vvj.ElementBase",
    );
  }

  /**
   * Programmatically add a block display to the example view.
   */
  private function addBlockDisplayToExampleView(): void {
    $view = View::load('vvja_example');
    $this->assertNotNull($view, 'vvja_example view installs as optional config.');

    $displays = $view->get('display');
    $this->assertIsArray($displays);
    $displays['block_1'] = [
      'id' => 'block_1',
      'display_title' => 'Block',
      'display_plugin' => 'block',
      'position' => 1,
      'display_options' => [
        'display_extenders' => [],
      ],
    ];
    $view->set('display', $displays);
    $view->save();
  }

}
