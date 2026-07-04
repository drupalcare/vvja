/**
 * @file
 * VVJA admin behavior — Views config-form deeplink toggle.
 *
 * v2.0 architecture:
 *   - Drupal.behaviors with attach/detach lifecycle.
 *   - once() for idempotent setup.
 *   - AbortController per checkbox so detach('unload') aborts every
 *     listener — prevents leaks across AJAX form rebuilds.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

// phpcs:disable Drupal.NamingConventions.UpperCaseConstant
// phpcs:disable Generic.PHP.UpperCaseConstant
// phpcs:disable Squiz.WhiteSpace.OperatorSpacing

((Drupal, once) => {
  'use strict';

  /**
   * Track AbortControllers per checkbox.
   *
   * @type {Map<HTMLElement, AbortController>}
   */
  const controllers = new Map();

  Drupal.behaviors.vvjaDeeplinkToggle = {
    attach(context) {
      const checkboxes = once(
        'vvja-deeplink-toggle',
        '[data-vvja-deeplink-toggle="true"]',
        context,
      );

      checkboxes.forEach((checkbox) => {
        const wrapper = (context.querySelector
          ? context
          : document
        ).querySelector('[data-vvja-deeplink-field="true"]');

        if (!wrapper) { return;
        }

        const controller = new AbortController();
        controllers.set(checkbox, controller);

        const update = (enabled) => {
          const input = wrapper.querySelector('input[type="text"]');

          wrapper.classList.toggle('hidden-element', !enabled);
          if (enabled) {
            wrapper.removeAttribute('aria-hidden');
            input?.removeAttribute('tabindex');
            input?.setAttribute('required', 'required');
          } else {
            wrapper.setAttribute('aria-hidden', 'true');
            input?.setAttribute('tabindex', '-1');
            input?.removeAttribute('required');
          }
        };

        update(checkbox.checked);
        checkbox.addEventListener(
          'change',
          (event) => update(event.currentTarget.checked),
          { signal: controller.signal },
        );
      });
    },

    detach(context, settings, trigger) {
      if (trigger !== 'unload') { return;
      }

      const checkboxes = context.querySelectorAll
        ? context.querySelectorAll('[data-vvja-deeplink-toggle="true"]')
        : [];

      checkboxes.forEach((checkbox) => {
        const controller = controllers.get(checkbox);
        if (controller) {
          controller.abort();
          controllers.delete(checkbox);
        }
      });
    },
  };
})(Drupal, once);
