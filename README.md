# Views Vanilla JavaScript Accordion

## Overview

The **Views Vanilla JavaScript Accordion** module (machine name: `vvja`) is a
versatile and lightweight Views style plugin designed to render items in an
accordion format using vanilla JavaScript. This module improves the user
experience by providing smooth, collapsible navigation for content displayed
through Drupal Views, eliminating the need for external JavaScript libraries
like jQuery UI or Bootstrap.

## Features

- **Pure JavaScript**: Utilizes vanilla JavaScript for efficient performance
  and reduced dependencies, offering a modern approach to creating interactive
  content displays.
- **Toggle Options**: Choose between single toggle indicators for each section
  or a global toggle for expanding/collapsing all sections at once.
- **Expansion Modes**: Control which panels are open on page load: none (all
  collapsed), first panel only, or all panels expanded.
- **Exclusive Panel**: Allow only one panel to be open at a time. When a user
  opens a new panel, any currently open panel is automatically closed.
- **Animation Effects**: Choose from seven animation styles: None, Slide from
  Top, Bottom, Left, Right, Zoom, and Fade. Configurable transition speed
  from 0.1s (fast) to 2s (slow).
- **Layout Controls**: Fine-tune the spacing between panels (`panel_gap`) and
  the inner padding of panel content areas (`pane_padding`), both configurable
  in pixels.
- **Custom SVG Icons**: Replace the default expand/collapse icons with your own
  custom SVG markup for both individual panel toggles and the global
  expand/collapse all buttons. All SVG input is sanitized for XSS protection.
- **Deep Linking**: Enable shareable URLs that link directly to specific
  accordion panels with browser back/forward navigation support.
- **JavaScript API**: Control accordions programmatically from your own
  JavaScript via `Drupal.vvja`.
- **Custom Token System**: Use `[vvja:FIELD_NAME]` tokens in Views headers and
  footers to display field values from the first row.
- **CSS Custom Properties**: Theme via CSS variables (`--vvja-grid-gap`,
  `--vvja-pane-padding`, `--vvja-transition-speed`, `--vvja-box-width`) for
  easy customization without overriding selectors.
- **Expandable Cards**: Set a fixed width per accordion item to create card-
  style
  layouts, or use `0` for full-width traditional accordion.
- **Unique ID Generation**: Automatically generates a unique numeric ID for
  each view display to ensure consistency and prevent conflicts.
- **Custom element (v2)**: Front-end behavior runs inside a `<vvja-accordion>`
  element (lazy-hydrated via `vvj_core`). Every VVJ view wrapper also gets
  `.vvj-component` and `.vvj-vvja` from `vvj_core` for shared styling.
- **Responsive**: Automatically recalculates panel heights on viewport resize
  for consistent behavior across devices.
- **CSS Customization**: Easily customize the appearance of the accordion with
  optional CSS inclusion to match your site's design.

- **Drupal admin help**: full module documentation is embedded at
  `/admin/help/vvja` (reads this README via the help system).

## Accessibility

The **VVJA** module includes several accessibility features to ensure that your
accordion is usable by all visitors, including those using screen readers:

- **ARIA Roles and Properties**: Proper ARIA roles and properties provide
  context and state information to screen readers, including `aria-expanded`,
  `aria-controls`, `aria-labelledby`, and `aria-hidden`.
- **Keyboard Navigation**: Users can navigate through accordion sections using
  keyboard shortcuts (Arrow Up/Down, Home, End), ensuring full functionality
  without a mouse.
- **Focus Management**: Ensures that the currently displayed section is focused,
  providing a better experience for keyboard and screen reader users. Only one
  button is in the tab order at a time.
- **Focusable Element Management**: Links, buttons, and inputs inside hidden
  panels are automatically disabled (`tabindex="-1"`, `aria-disabled="true"`)
  to prevent keyboard trapping.
- **Auto ARIA Labels**: Button labels are automatically generated from button
  content or image alt text for screen reader context.

## Configuration

After installing and enabling the module, you can configure it through the
Views UI. Settings are organized into the following sections:

### Behavior Settings

1. **Single Toggle**: Show individual expand/collapse icons on each panel.
2. **Global Toggle**: Show an "Expand All / Collapse All" button above the
   accordion.
3. **Exclusive Panel**: Only one panel can be open at a time. Incompatible
   with Global Toggle and Expand All.
4. **Expand Default**: Choose which panels are open on page load: None,
   First, or All.

### Animation Settings

1. **Animation Type**: Choose the animation effect for panel transitions.
   Options: None, Slide from Top, Bottom, Left, Right, Zoom, and Fade.
2. **Transition Speed**: Value between 0.1 (fast) and 2 (slow) seconds.
   Default: 0.3s.

### Layout Settings

1. **Accordion Item Width**: Width in pixels for card-style layouts. Set to
   `0` for full-width (default).
2. **Content Padding**: Inner padding of panel content areas in pixels.
   Default: 16px.
3. **Panel Gap**: Spacing between accordion panels in pixels. Default: 16px.

### Custom Icons

1. **Panel Expand/Collapse Icons**: Custom SVG markup for each panel button.
2. **Global Expand/Collapse All Icons**: Custom SVG markup for the global
   toggle button.

### Deep Linking Settings

1. **Enable Deep Linking**: Generate shareable URLs with hash fragments.
2. **URL Identifier**: Short identifier used in the URL hash (e.g., "faqs"
   produces `#accordion-faqs-3`). Must start with a letter, lowercase,
   max 20 characters.

### Advanced Options

1. **Enable CSS**: Include the default VVJA stylesheet. Disable to use your
   own CSS exclusively.

## Token Support in Views Text Areas

In Views headers, footers, or empty text areas—when using *Global: Text area* or
*Global: Unfiltered text*—there is an option called **"Use replacement tokens
from the first row."**

The default Twig-style tokens (e.g., `{{ title }}` or `{{ field_image }}`)
**will not work** with the VVJA style. Instead, use the custom tokens provided
by VVJA:

**Examples:**

- `{{ title }}` → `[vvja:title]`  
- `{{ field_image }}` → `[vvja:field_image]`

To strip any HTML from the output, you can append `:plain` to the token:

- `[vvja:title:plain]`

These tokens pull data from the **first row** of the View result and are
designed to work with the VVJA rendering system.

v2 resolves tokens through the shared `vvj_core.token_resolver` service; the
`[vvja:field]` / `[vvja:field:plain]` syntax is unchanged from v1.

## Deep Linking

Enable deep linking in your accordion's Format Settings to create shareable URLs
for specific panels.

**Setup:**
1. Enable **Deep Linking** in Format Settings
2. Set **URL Identifier** (e.g., "faqs")
3. Accordion panel links become: `#accordion-faqs-3`

**Note:** Deep linking works with all accordion configurations (exclusive and
multi-panel modes).

## JavaScript API

Control accordions from your own JavaScript code:

### Available Methods

**Open specific panel:**
```javascript
Drupal.vvja.openPanel('faqs', 3);  // Open panel 3
```

**Close specific panel:**
```javascript
Drupal.vvja.closePanel('faqs', 2);  // Close panel 2
```

**Toggle specific panel:**
```javascript
Drupal.vvja.togglePanel('faqs', 1);  // Toggle panel 1
```

**Get open panels:**
```javascript
const openPanels = Drupal.vvja.getOpenPanels('faqs');  // Returns array like [1,
3, 5]
```

**Get total panels:**
```javascript
const total = Drupal.vvja.getTotalPanels('faqs');  // Returns total number of
panels
```

**Access accordion instance:**
```javascript
const instance = Drupal.vvja.getInstance('#vvja-12345');  // → <vvja-accordion>
```

### Complete Example: Custom Panel Control

```javascript
// Wait for Drupal behaviors to attach
Drupal.behaviors.myCustomAccordion = {
  attach: function(context, settings) {
    // Find your custom button
    const openAllBtn = once('open-all', '.my-open-all-button', context)[0];
    
    if (openAllBtn) {
      openAllBtn.addEventListener('click', function() {
        const total = Drupal.vvja.getTotalPanels('faqs');
        for (let i = 1; i <= total; i++) {
          Drupal.vvja.openPanel('faqs', i);
        }
      });
    }
  }
};
```

## Example Usage

1. Create a new view or edit an existing view.
2. Under the "Format" section, select "Views Vanilla JavaScript Accordion" as
   the style.
3. Configure the toggle settings, animation type, and CSS options as needed.
4. Save the view and see the accordion navigation in action on the front end.

**Sample view:** Optional config `views.view.vvja_example` (`config/optional/`)
installs when there is no ID conflict. Useful as a working reference.

## Additional Information

- **Dependencies**: Requires **Drupal core** (`views`, `filter`) and the
  **`vvj_core`** companion module (installed automatically via Composer when you
  require `drupal/vvja`). No other contributed projects are required.
- **Compatibility**: Targets **Drupal 11.3+** and **Drupal 12** with **PHP
  8.3+**, following Drupal security and modern OOP hook patterns.

## Installation

1. Download and install the module from
   [Drupal.org](https://www.drupal.org/project/vvja).
2. Enable the module through the Drupal admin interface or using Drush: 
   `drush en vvja`
3. Clear caches to ensure the new plugin is available: `drush cr`

## Troubleshooting

If you encounter any issues or have suggestions for improvements, please open
an issue in the module's issue queue on Drupal.org.

## Maintainers

- [Alaa Haddad](https://www.drupal.org/u/flashwebcenter)

## Support

For issues, feature requests, and general support, please use the 
[issue queue](https://www.drupal.org/project/issues/vvja).

## Running the tests

This module ships no `phpunit.xml` of its own, deliberately: its suites are
Kernel, Functional and FunctionalJavascript, which need a real Drupal, so a
module-local config would have to hard-code a relative path to `core` and would
break the moment the module is not physically inside a docroot (a symlinked
checkout, for instance). Drupal core's runner already does the job.

Run the fast suites from the docroot:

```bash
cd /path/to/drupal/web
export SIMPLETEST_DB="mysql://user:pass@localhost/dbname"
../vendor/bin/phpunit -c core modules/contrib/vvja/tests/src/Unit
../vendor/bin/phpunit -c core modules/contrib/vvja/tests/src/Kernel
```

The **Functional** and **FunctionalJavascript** suites also need
`SIMPLETEST_BASE_URL` pointing at a running web server, and the JavaScript ones
need chromedriver. Without those they error rather than fail, and the errors
name missing infrastructure rather than anything about this module, so don't
read them as test failures. The drupal.org pipeline provides both.

## License

This project is licensed under the 
[GNU General Public License, version 2 or
later](http://www.gnu.org/licenses/gpl-2.0.html).

---

## v2.0 architecture notes (for contributors)

v2.0 is a drop-in upgrade from 1.x. Every CSS rule, option key, plugin ID,
theme hook, Twig template name, JS behavior key, and `Drupal.vvja.*` public API
is preserved byte-identical to v1. v2 modernizes internals only:

- **OOP `#[Hook]` classes** under `src/Hook/` replace the procedural hooks in
  `vvja.module` (now a stub). Hook discovery is automatic via Drupal 11.3+
  attribute scanning.
- **Dependency injection** throughout. Every service consumed via constructor
  injection. Zero `\Drupal::service()` calls inside service code.
- **AbortController-tracked listeners**: `detach('unload')` aborts every event
  listener for an accordion instance, preventing leaks across AJAX page swaps.
- **PHP 8.3 typed constants** in `src/VvjaConstants.php` for compile-time type
  safety.
- **Strict types** (`declare(strict_types=1);`) on every PHP file.
- **Test suite** under
  `tests/src/{Unit,Kernel,Functional,FunctionalJavascript}/`.
- **Drupal 11.3+ / PHP 8.3+ requirement**: uses `ViewsThemeHooks` for row/field
  preprocess (removed procedural `template_preprocess_*` in Drupal 12); declare
  `core_version_requirement: ^11.3 || ^12` in the module info file.

For 1.x sites: `composer update drupal/vvja && drush cache:rebuild`. Existing
views, options, theme overrides, custom CSS, and JS API consumers continue
working unchanged.

---

This documentation adheres to Drupal best practices, providing a clear and
comprehensive guide for users to understand the module's functionality,
features, and setup. Users are encouraged to refer to the issue queue for
support and contributions.
