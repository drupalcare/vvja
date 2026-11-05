/**
 * @file
 * Shared by the vvja Pattern scripts: read the items, open one, follow
 * changes, and build the small buttons they add.
 *
 * Opening always goes through the element's own openPanel(), so the module
 * keeps its ARIA, exclusive mode and deep links. Following uses the shared
 * state the element writes (`vvj:change`) plus a click on the element, so a
 * panel that closes is seen too.
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

((Drupal, once) => {
  const api = {
    /**
     * @param {HTMLElement} root
     *   The `<vvja-accordion>` element.
     * @return {HTMLElement[]}
     *   Its items, in order.
     */
    items: (root) =>
      Array.from(root.querySelectorAll(':scope > .vvja-inner > .vvja-item')),

    /**
     * @param {HTMLElement} root
     *   The `<vvja-accordion>` element.
     * @return {number}
     *   The 0-based index of the first open item, or -1.
     */
    current: (root) =>
      api.items(root).findIndex((item) => item.classList.contains('opened')),

    /**
     * Opens one item; the index wraps around.
     *
     * @param {HTMLElement} root
     *   The `<vvja-accordion>` element.
     * @param {number} index
     *   The 0-based index.
     */
    open(root, index) {
      const total = api.items(root).length;
      if (!total || typeof root.openPanel !== 'function') {
        return;
      }
      root.openPanel((((index % total) + total) % total) + 1);
    },

    /**
     * Calls `cb(current)` once hydrated and after every change.
     *
     * @param {HTMLElement} root
     *   The `<vvja-accordion>` element.
     * @param {function(number): void} cb
     *   Receives the 0-based index of the open item, or -1.
     * @param {AbortSignal} signal
     *   Stops following when aborted.
     */
    follow(root, cb, signal) {
      let frame = 0;
      const run = () => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => cb(api.current(root)));
      };
      root.addEventListener('click', run, { signal });
      document.addEventListener(
        'vvj:change',
        (event) => {
          if (event.detail?.source === root) {
            run();
          }
        },
        { signal },
      );
      Drupal.Vvj.whenReady(root, run, signal);
      signal.addEventListener('abort', () => cancelAnimationFrame(frame), {
        once: true,
      });
    },

    /**
     * @param {string} name
     *   An icon name from vvja-pattern-base.css (prev, next, play...).
     * @return {HTMLSpanElement}
     *   A hidden icon that takes the text colour.
     */
    icon(name) {
      const span = document.createElement('span');
      span.className = `vvja-i vvja-i--${name}`;
      span.setAttribute('aria-hidden', 'true');
      return span;
    },

    /**
     * @param {string} className
     *   The button classes.
     * @param {string} label
     *   The accessible name (already translated).
     * @param {string} [iconName]
     *   An icon to put inside.
     * @return {HTMLButtonElement}
     *   The button.
     */
    button(className, label, iconName) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = className;
      button.setAttribute('aria-label', label);
      if (iconName) {
        button.appendChild(api.icon(iconName));
      }
      return button;
    },

    /**
     * Runs `setup(root, signal)` once per Pattern element; aborts on unload.
     *
     * @param {string} key
     *   The once() id.
     * @param {string} selector
     *   The Pattern class selector.
     * @param {function(HTMLElement, AbortSignal): void} setup
     *   Builds the Pattern's extras.
     * @return {Drupal~behavior}
     *   The behavior.
     */
    behavior(key, selector, setup) {
      const controllers = new WeakMap();
      return {
        attach(context) {
          once(key, selector, context).forEach((root) => {
            const controller = new AbortController();
            controllers.set(root, controller);
            setup(root, controller.signal);
          });
        },
        detach(context, settings, trigger) {
          if (trigger !== 'unload') {
            return;
          }
          once.remove(key, selector, context).forEach((root) => {
            controllers.get(root)?.abort();
            controllers.delete(root);
          });
        },
      };
    },
  };

  Drupal.VvjaPattern = api;
})(Drupal, once);
