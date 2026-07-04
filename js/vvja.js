/**
 * @file
 * Views Vanilla JavaScript Accordion — VVJA v2 compat shim.
 *
 * The accordion's behavior is fully encapsulated in the
 * `<vvja-accordion>` custom element (see `vvja-accordion-element.js`).
 * This file exists for two backwards-compat reasons:
 *
 *   1. v1 sites referenced `Drupal.behaviors.VVJAccordion` from custom
 *      code. Registering a no-op behavior under that key keeps any
 *      such code from crashing.
 *
 *   2. v1 sites used the public API `Drupal.vvja.openPanel(id, n)`,
 *      `closePanel`, `togglePanel`, `getOpenPanels`, `getTotalPanels`,
 *      `getInstance`. Those methods now delegate to the matching
 *      methods on the custom element instance.
 *
 * The custom element handles its own lifecycle via
 * `connectedCallback()` + IntersectionObserver lazy hydration —
 * `Drupal.behaviors.attach()` doesn't drive its setup.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

// phpcs:disable Drupal.NamingConventions.UpperCaseConstant
// phpcs:disable Generic.PHP.UpperCaseConstant
// phpcs:disable Squiz.WhiteSpace.OperatorSpacing

((Drupal, drupalSettings, once) => {
  'use strict';

  Drupal.behaviors.VVJAccordion = {
    attach(context) {
      // No-op marker. Custom element hydrates itself via
      // connectedCallback() → IntersectionObserver. once() guards
      // against repeated attach calls running side-effects (none here).
      once('vvja-accordion-marker', 'vvja-accordion', context);
    },
    detach() {
      // disconnectedCallback on the custom element aborts every
      // signal-bound listener. Nothing for the behavior to clean up.
    },
  };

  /**
   * Resolve a deeplink-id, CSS selector, or DOM node to the matching
   * `<vvja-accordion>` custom element.
   *
   * @param {string|Element} identifier
   *   Either a deeplink-id (e.g. `'faqs'`), a CSS selector
   *   (e.g. `'#vvja-12345'`), or an existing element.
   * @returns {Element|null}
   *   The matched `<vvja-accordion>` element, or null.
   */
  function getElement(identifier) {
    if (identifier instanceof Element) {
      return identifier.closest('vvja-accordion');
    }
    if (typeof identifier !== 'string') { return null;
    }
    // First: try to match by deeplink-id data attribute.
    let el = document.querySelector(`vvja-accordion[data-deeplink-id="${identifier}"]`);
    if (el) { return el;
    }
    // Second: try as a CSS selector (id or class).
    el = document.querySelector(identifier);
    if (el) { return el.closest('vvja-accordion') || el;
    }
    return null;
  }

  Drupal.vvja = Drupal.vvja || {};

  Drupal.vvja.openPanel = (identifier, panelIndex) => {
    const el = getElement(identifier);
    if (!el || typeof el.openPanel !== 'function') {
      console.warn(`VVJA: Accordion "${identifier}" not found or not yet hydrated`);
      return false;
    }
    return el.openPanel(panelIndex);
  };

  Drupal.vvja.closePanel = (identifier, panelIndex) => {
    const el = getElement(identifier);
    if (!el || typeof el.closePanel !== 'function') {
      console.warn(`VVJA: Accordion "${identifier}" not found or not yet hydrated`);
      return false;
    }
    return el.closePanel(panelIndex);
  };

  Drupal.vvja.togglePanel = (identifier, panelIndex) => {
    const el = getElement(identifier);
    if (!el || typeof el.togglePanel !== 'function') {
      console.warn(`VVJA: Accordion "${identifier}" not found or not yet hydrated`);
      return false;
    }
    return el.togglePanel(panelIndex);
  };

  Drupal.vvja.getOpenPanels = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.getOpenPanels !== 'function') { return null;
    }
    return el.getOpenPanels();
  };

  Drupal.vvja.getTotalPanels = (identifier) => {
    const el = getElement(identifier);
    if (!el || typeof el.getTotalPanels !== 'function') { return null;
    }
    return el.getTotalPanels();
  };

  Drupal.vvja.getInstance = (containerOrSelector) => getElement(containerOrSelector);

})(Drupal, drupalSettings, once);
