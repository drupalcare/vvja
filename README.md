# Views Vanilla JavaScript Accordion

The **Views Vanilla JavaScript Accordion** module (machine name: `vvja`) is a
versatile and lightweight Views style plugin designed to render items in an
accordion format using vanilla JavaScript. This module improves the user
experience by providing smooth, collapsible navigation for content displayed
through Drupal Views, eliminating the need for external JavaScript libraries
like jQuery UI or Bootstrap.

- Project page: [drupal.org/project/vvja](https://www.drupal.org/project/vvja)
- Issue queue:
  [drupal.org/project/issues/vvja](https://www.drupal.org/project/issues/vvja)


## Table of contents

- Overview
- Requirements
- Installation
- Configuration
- Usage
- Patterns
- Upgrading from 1.x
- Troubleshooting
- Changelog
- Maintainers
- License


## Overview

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
- **Expandable Cards**: Set a fixed width per accordion item to create
  card-style
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


### Accessibility

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


## Requirements

- Drupal core `^11.3 || ^12`.
- PHP 8.3 or later.
- Drupal core modules Views and Filter.
- [VVJ Core](https://www.drupal.org/project/vvj_core) (`vvj_core`), installed
  automatically by Composer.


## Installation

1. Install with Composer: `composer require drupal/vvja:^2.0` (it pulls
   `drupal/vvj_core`), or download the module and `vvj_core` from
   [Drupal.org](https://www.drupal.org/project/vvja).
1. Enable it in the admin interface or with Drush: `drush en vvja`.
1. Clear caches so the new plugin is available: `drush cr`.


## Configuration

After installing and enabling the module, configure it in the Views UI. Settings
are organized into the following sections:


### Behavior settings

1. **Single Toggle**: Show individual expand/collapse icons on each panel.
1. **Global Toggle**: Show an "Expand All / Collapse All" button above the
   accordion.
1. **Exclusive Panel**: Only one panel can be open at a time. Incompatible
   with Global Toggle and Expand All.
1. **Expand Default**: Choose which panels are open on page load: None,
   First, or All.


### Animation settings

1. **Animation Type**: Choose the animation effect for panel transitions.
   Options: None, Slide from Top, Bottom, Left, Right, Zoom, and Fade.
1. **Transition Speed**: Value between 0.1 (fast) and 2 (slow) seconds.
   Default: 0.3s.


### Layout settings

1. **Accordion Item Width**: Width in pixels for card-style layouts. Set to
   `0` for full-width (default).
1. **Content Padding**: Inner padding of panel content areas in pixels.
   Default: 16px.
1. **Panel Gap**: Spacing between accordion panels in pixels. Default: 16px.


### Custom icons

1. **Panel Expand/Collapse Icons**: Custom SVG markup for each panel button.
1. **Global Expand/Collapse All Icons**: Custom SVG markup for the global
   toggle button.


### Deep linking settings

1. **Enable Deep Linking**: Generate shareable URLs with hash fragments.
1. **URL Identifier**: Short identifier used in the URL hash (e.g., "faqs"
   produces `#accordion-faqs-3`). Must start with a letter, lowercase,
   max 20 characters.


### Advanced options

1. **Enable CSS**: Include the default VVJA style sheet. Disable to use your
   own CSS exclusively.

For a ready-made starting point, choose a Pattern (see Patterns below).


## Usage


### Token support in Views text areas

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


### Deep linking

Enable deep linking in your accordion's Format Settings to create shareable URLs
for specific panels.

**Setup:**
1. Enable **Deep Linking** in Format Settings
1. Set **URL Identifier** (e.g., "faqs")
1. Accordion panel links become: `#accordion-faqs-3`

**Note:** Deep linking works with all accordion configurations (exclusive and
multi-panel modes).


### JavaScript API

Control accordions from your own JavaScript code:


#### Available methods

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


#### Complete example: custom panel control

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


### Example usage

1. Create a new view or edit an existing view.
1. Under the "Format" section, select "Views Vanilla JavaScript Accordion" as
   the style.
1. Configure the toggle settings, animation type, and CSS options as needed.
1. Save the view and see the accordion navigation in action on the front end.

**Sample view:** Optional config `views.view.vvja_example` (`config/optional/`)
installs when there is no ID conflict. Useful as a working reference.


## Patterns

A Pattern is a ready-made design for this format. One choice in the format
settings sets the layout, animation,
styling and deep linking together. Views Vanilla JavaScript Accordion ships 5
Patterns:

| Pattern | What it looks like | Demo page path |
|---|---|---|
| Spotlight | Photo panels side by side; the open one widens, with a bottom bar of segments and previous and next. | `vvj/vvja/spotlight` |
| Stage | Big numbered steps on the left and one photo stage on the right; it plays through the steps with a visible Pause. | `vvj/vvja/stage` |
| Answers | Text only: big questions on hairlines, a square chevron button, a bar on the open answer and Expand all. | `vvj/vvja/faq` |
| Cards | Portrait photo cards; the open card moves to the top, spans the row and has previous and next inside. | `vvj/vvja/cards` |
| Agenda | A numbered rail with a line and a dot per item, a copy-link button in each and a sticky dock of numbers. | `vvj/vvja/agenda` |

The demo pages exist only while the optional `vvj_patterns_demo` module is
enabled (see "Example views" below).


### Choosing a Pattern

1. Enable the shared Patterns base, which ships inside `vvj_core`:
   `drush en vvj_patterns`. The "Pattern" select
   appears in the format settings only while `vvj_patterns` is enabled.
1. Edit the view and open the format settings.
1. In the "Patterns" section, pick a Pattern in the "Pattern" select and save.
1. The Pattern's settings replace the layout, animation, styling and deep
   linking settings of the view. You can
   change them afterwards. Pick "None" to keep your own settings.
1. A list titled "To finish the <name> Pattern" shows what the view must supply:
   which fields, which markup
   classes and how many items. Follow it; a Pattern shows its finished design
   only when the view matches.


### Example views

Enable the optional `vvj_patterns_demo` module (`drush en vvj_patterns_demo`) to
see every Pattern with content.
It creates a "VVJ Slide" content type with five demo slides and pictures, plus
example views titled
"VVJ demo: <format>" for the VVJ format modules that are enabled, one page per
Pattern at the paths in the table
above. Uninstalling the demo module removes the slides and pictures it created.


## Upgrading from 1.x

v2.0 is a drop-in upgrade from 1.x. Every CSS rule, option key, plugin ID, theme
hook, Twig template name, JS behavior key and `Drupal.vvja.*` public API is
preserved. Stored option values are converted to the v2 shape by update 10002
(see [CHANGELOG.md](./CHANGELOG.md)).

For 1.x sites:
`composer update drupal/vvja && drush updb && drush cache:rebuild`. Existing
views, options, theme overrides, custom CSS and JS API consumers continue
working unchanged.


## Troubleshooting

| Symptom | Fix |
|---|---|
| The format is missing from the Views "Format" list | Enable `vvj_core` and `vvja`, then `drush cr`. |
| `Class "Drupal\vvja\..." not found` after deploying v2 code | Stale container cache: `drush cr`. If Drush will not bootstrap, clear `cache_container` in the database and retry. |
| `drush updb` errors before `vvj_core` is enabled | Run `drush en vvj_core -y`, then `drush updb -y && drush cr`. |
| No "Patterns" section in the format settings | Enable `vvj_patterns`: `drush en vvj_patterns`. |

Report bugs and request features in the
[issue queue](https://www.drupal.org/project/issues/vvja).


## Changelog

See [CHANGELOG.md](./CHANGELOG.md).


## Maintainers

- Alaa Haddad - [flashwebcenter](https://www.drupal.org/u/flashwebcenter)


## License

GPL-2.0-or-later. See the
[GNU General Public License, version 2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
