# Changelog

All notable changes to the VVJA module are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and the project follows [Semantic
Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.0.0] — UNRELEASED

**Drop-in upgrade for 1.x sites.** v2 modernizes internals — OOP hooks,
AbortController-tracked listeners, PHP 8.3 typed constants, **`vvj_core`
foundation extraction** — while preserving every CSS rule, every option key,
every plugin ID, every theme hook, every Twig template name, and every JS
feature from 1.x verbatim. Run `composer update drupal/vvja` + `drush
cache:rebuild` and you're done.

### Added

- **`drupal/vvj_core` dependency.** Composer pulls it automatically. Provides
  the `VvjStylePluginBase` abstract class, shared services
  (`vvj_core.svg_sanitizer`, `vvj_core.token_resolver`,
  `vvj_core.unique_id_generator`, `vvj_core.breakpoint_registry`), shared
  `safe_html` Twig filter, cascade-layer CSS contract, and AAA-level focus
  indicators. Pattern modules consume the foundation instead of duplicating it
  10×.
- **Plugin LOC reduced from 759 → 411 (45% reduction).** Most of the boilerplate
  (form weights, warning, advanced/deeplinking/token-doc sections, library list,
  render(), validate(), unique-id generation, deeplink slug normalization) moved
  to `VvjStylePluginBase`. Only pattern-specific concerns (Behavior, Animation,
  Layout, Custom Icons sections + the 12 vvja-only options) live in the
  `Accordion` plugin.
- **Token resolution delegates to shared `vvj_core.token_resolver`.**
  `[vvja:field]` and `[vvja:field:plain]` semantics preserved; the same code now
  serves every VVJ pattern instead of being duplicated.
- **SVG sanitization delegates to shared `vvj_core.svg_sanitizer`.** Same
  whitelist parser, same threat model; one copy across the suite.
- **OOP hooks** under `src/Hook/` using the `#[Hook]` attribute pattern —
  replaces all procedural hooks in `vvja.module`. Constructor-injected via DI;
  no more `\Drupal::service()` calls.
- **APEX design-token consumption** — every CSS color / dimension / typography
  reads `var(--apex-token, fallback)` with system-color-keyword fallbacks
  (`Canvas`, `CanvasText`, `Highlight`). Looks beautiful in APEX themes;
  respectable in any other theme; Forced-Colors-mode compatible.
- **Cascade-layer self-declaration** — every component CSS file declares `@layer
  apex.foundation, apex.tokens, apex.layout, apex.components, apex.utilities;`
  at the top so layer order is predictable on non-APEX sites (idempotent on APEX
  sites).
- **All four mandatory media queries** — `prefers-reduced-motion`, `prefers-
  contrast: high`, `forced-colors: active`, `print` — present in every CSS file.
- **AbortController-tracked listeners** in JS — `detach('unload')` aborts every
  event listener for every accordion in the unload context, preventing memory
  leaks across AJAX page swaps.
- **Arrow-key navigation** between accordion triggers (Up / Down / Home / End)
  per W3C ARIA Authoring Practices Guide accordion pattern.
- **Drupal 11.3+ + PHP 8.3+ requirement** — typed class constants, readonly
  properties, first-class callable syntax, `string`/`int`/`array` constant
  types.
- **PHPStan-strict array shapes** (`list<T>`, `array<K, V>`, `array{key: type}`)
  on every method signature; zero `iterableValue` violations.
- **CustomEvent dispatching** on `document` with `vvja:` prefix (`vvja:ready`)
  for cross-behavior coordination — replaces global registry patterns.

### Changed

- **`vvja.module` is now a stub.** Hooks live in `src/Hook/*.php` classes with
  `#[Hook('hook_name')]` attributes. Procedural `function vvja_*()` hooks are
  removed. *No backwards-compat impact* — module-handler discovery resolves the
  new pattern transparently.
- **Internal class renames.** PHP classes adopt PSR-12 conventions:
  `ViewsVanillaJavascriptAccordion` → `Accordion`; `VVJATwigExtension` →
  `VvjaTwigExtension`. **Plugin ID (`views_vvja`) and service ID
  (`vvja.twig_extension`) are unchanged.** Anyone who subclassed the v1 PHP
  classes (rare) needs to update the parent class name.
- **CSS files preserved verbatim from v1.** Same paths (`css/vvja.css`,
  `css/vvja-style.css`, `css/vvja-admin.css`), same library contract
  (`vvja/vvja` always attached, `vvja/vvja-style` opt-in via the "Enable CSS
  Library" checkbox), same rules byte-for-byte. The earlier v2 attempt to split
  structural-vs-visual into a Single Directory Component was reverted after
  surfacing prop-validation errors in the Views UI on every form change — v1
  used a plain Twig template, v2 does the same.
- **JavaScript modernization.** Same behavior, ES2022 idioms, `let`/`const`,
  arrow functions, optional chaining, `???` nullish coalescing. AbortController
  + once() + native button keyboard handling.
- **Transliteration injected via DI** instead of `\Drupal::transliteration()`.

### Removed

- `src/SvgSanitizer.php` — moved to `drupal/vvj_core` as
  `vvj_core.svg_sanitizer` service. Code lifted verbatim; no behavior change.
- `src/Twig/VvjaTwigExtension.php` — `safe_html` filter moved to
  `drupal/vvj_core` as `vvj_core.twig_extension`. Same filter name, same
  behavior.
- Cross-cutting constants (TOKEN_PATTERN, DEEPLINK_*, transition-speed bounds,
  unique-ID range) — moved to `Drupal\vvj_core\Constants\ValidationBounds`.
  Pattern-specific accordion constants stay in `Drupal\vvja\VvjaConstants`.
- Procedural hooks in `vvja.module` (moved to OOP `#[Hook]` classes).

### Security

- **Defense-in-depth SVG sanitization** — admin-form validation + render-time
  re-sanitization unchanged from v1, but `SvgSanitizer` PHPDoc + array shapes
  tightened. Same whitelist parser, same threat model.

### Drupal core / PHP

- Now requires Drupal `^11.3 || ^12` (was `^10 || ^11`)
- Now requires PHP `>=8.3` (was unspecified)

### Migration guide (from 1.x)

For most sites: `composer update drupal/vvja` and `drush cache:rebuild` is
sufficient. Specifically:

| What you had | What stays the same in 2.0 |
|---|---|
| Existing views using "Views Vanilla JavaSc… | Continue working |
| Theme overrides of `views-view-vvja.html.twig` | Continue working |
| Custom SVG icons in any view | Re-validated on next save; no manual actio… |
| Deep-link slugs in any view | Continue working — same URL fragment shape |
| `[vvja:field]` token usage in Views text a… | Continue working — token system 

| What changed | Action you might take |
|---|---|
| You subclassed `Drupal\vvja\Plugin\views\s… | Update parent class name to `Dru
| You consumed `Drupal\vvja\SvgSanitizer` di… | Use `\Drupal::service` ` / `vali
| You attached `vvja/vvja-style` library directly (rare) | Continues to work |
| You override the visual via plain CSS in y… | Visual will look slightly differ

### Acknowledgments

v2 architecture target locked in [docs/planning/CONTRIB-
MODERNIZATION-V2.md](https://github.com/flashwebcenter/apex/blob/main/docs/planning/CONTRIB-
MODERNIZATION-V2.md) of the APEX project.

---

## [1.0.0 and earlier]

See git history. v1 architecture used procedural hooks, hardcoded colors,
separate `vvja-style` library, and was Drupal 10 / 11 compatible.
