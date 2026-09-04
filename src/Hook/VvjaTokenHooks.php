<?php

declare(strict_types=1);

namespace Drupal\vvja\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\vvj_core\Service\TokenResolver;
use Drupal\vvja\Plugin\views\style\Accordion;

/**
 * Token hooks — exposes per-row field tokens for VVJA-enabled Views.
 *
 * Token forms:
 *   [vvja:FIELD]        — rendered HTML (Xss::filterAdmin)
 *   [vvja:FIELD:plain]  — plain text (HTML stripped + entities decoded)
 *
 * Tokens read from the FIRST row of the view result so they're usable
 * in Views header / footer / empty-text Twig contexts. The actual
 * resolution logic lives in `vvj_core.token_resolver`; this class
 * registers the `vvja` namespace via hook_token_info() and delegates
 * resolution via hook_tokens().
 */
final class VvjaTokenHooks {

  use StringTranslationTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\vvj_core\Service\TokenResolver|null $tokenResolver
   *   Shared token resolver. Nullable to keep the container compilable
   *   during the v1 → v2 upgrade window when vvj_core is on disk but
   *   not yet enabled. vvja_update_10001 installs it; subsequent
   *   container rebuilds inject the real service.
   */
  public function __construct(
    private readonly ?TokenResolver $tokenResolver,
  ) {}

  /**
   * Implements hook_token_info().
   *
   * @return array<string, array<string, array<string, mixed>>>
   *   Token type + token definition arrays for the `vvja` namespace.
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    return [
      'types' => [
        'vvja' => [
          'name' => $this->t('VVJA Accordion'),
          'description' => $this->t('Tokens for accessing field values from the first row of a VVJA-enabled View.'),
          'needs-data' => 'view',
        ],
      ],
      'tokens' => [
        'vvja' => [
          '*' => [
            'name' => $this->t('Field token'),
            'description' => $this->t('Use [vvja:FIELD_NAME] for HTML output or [vvja:FIELD_NAME:plain] for plain text.'),
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_tokens() — delegates to the shared resolver.
   *
   * @param string $type
   *   Token type — only `vvja` is handled here; others short-circuit.
   * @param array<string, string> $tokens
   *   Token name → original placeholder map.
   * @param array<string, mixed> $data
   *   Token data; expects `view` key with a ViewExecutable.
   * @param array<string, mixed> $options
   *   Token resolution options.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   For propagating cache metadata.
   *
   * @return array<string, string|\Drupal\Component\Render\MarkupInterface>
   *   Map of original placeholder → resolved value.
   */
  #[Hook('tokens')]
  public function tokens(
    string $type,
    array $tokens,
    array $data,
    array $options,
    BubbleableMetadata $bubbleable_metadata,
  ): array {
    if ($type !== 'vvja' || $this->tokenResolver === NULL) {
      return [];
    }
    return $this->tokenResolver->resolve(
      'vvja',
      $tokens,
      $data,
      $bubbleable_metadata,
      Accordion::class,
    );
  }

}
