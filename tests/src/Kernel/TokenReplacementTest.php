<?php

declare(strict_types=1);

namespace Drupal\Tests\vvja\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for vvja token definitions and replacement.
 */
#[Group('vvja')]
#[RunTestsInSeparateProcesses]
final class TokenReplacementTest extends KernelTestBase {

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

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();

    // The optional vvja_example view depends on field.storage.node.body;
    // create the storage + instance so it installs and nodes carry a body.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'body',
      // `text_long`, NOT `text_with_summary`. The type is incidental
      // here — this storage exists only so the example view's
      // `field.storage.node.body` dependency resolves — and
      // `text_with_summary` was REMOVED from core's text module on
      // Drupal 12. Measured on dev-main 2026-09-05:
      // TextWithSummaryItem.php is gone, TextLongItem.php remains.
      'type' => 'text_long',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'body',
      'bundle' => 'page',
      'label' => 'Body',
    ])->save();

    Node::create([
      'type' => 'page',
      'title' => 'First Page',
      'body' => ['value' => 'First page body.', 'format' => 'plain_text'],
    ])->save();

    $this->installConfig(['vvja']);
  }

  /**
   * Token info exposes the vvja type and its field tokens.
   */
  public function testTokenInfoExposesVvjaType(): void {
    /** @var \Drupal\Core\Utility\Token $token */
    $token = $this->container->get('token');
    $info = $token->getInfo();

    $this->assertArrayHasKey('types', $info);
    $types = $info['types'];
    $this->assertIsArray($types);
    $this->assertArrayHasKey('vvja', $types);

    $this->assertArrayHasKey('tokens', $info);
    $tokens = $info['tokens'];
    $this->assertIsArray($tokens);
    $this->assertArrayHasKey('vvja', $tokens);
  }

  /**
   * The plain title token returns stripped text without HTML.
   */
  public function testPlainTokenReturnsStrippedText(): void {
    $view = Views::getView('vvja_example');
    $this->assertNotNull($view);
    $view->setDisplay('default');
    $view->execute();

    /** @var \Drupal\Core\Utility\Token $token */
    $token = $this->container->get('token');
    // Field handlers render inside a render context; Views supplies one in
    // normal execution, so replicate it here.
    $renderer = $this->container->get('renderer');
    $result = $renderer->executeInRenderContext(
      new RenderContext(),
      static fn (): string => $token->replace('[vvja:title:plain]', ['view' => $view]),
    );

    $this->assertIsString($result);
    $this->assertSame('First Page', trim($result));
  }

  /**
   * The default title token returns rendered field markup.
   */
  public function testHtmlTokenReturnsRenderedMarkup(): void {
    $view = Views::getView('vvja_example');
    $this->assertNotNull($view);
    $view->setDisplay('default');
    $view->execute();

    /** @var \Drupal\Core\Utility\Token $token */
    $token = $this->container->get('token');
    $renderer = $this->container->get('renderer');
    $result = $renderer->executeInRenderContext(
      new RenderContext(),
      static fn (): string => $token->replace('[vvja:title]', ['view' => $view]),
    );

    $this->assertIsString($result);
    $this->assertStringContainsString('First Page', $result);
  }

  /**
   * Unknown vvja field tokens are left unreplaced.
   */
  public function testInvalidTokenIsIgnored(): void {
    $view = Views::getView('vvja_example');
    $this->assertNotNull($view);
    $view->setDisplay('default');
    $view->execute();

    /** @var \Drupal\Core\Utility\Token $token */
    $token = $this->container->get('token');
    // Unknown field — token engine returns the placeholder unchanged.
    $result = $token->replace('[vvja:nonexistent_field]', ['view' => $view]);

    $this->assertSame('[vvja:nonexistent_field]', $result);
  }

}
