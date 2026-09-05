/**
 * @file
 * VvjaAccordionElement — `<vvja-accordion>` custom element.
 *
 * Extends `Drupal.Vvj.ElementBase` from `vvj_core` for the standard
 * lifecycle (lazy IntersectionObserver hydration, AbortController-tracked
 * listeners, reduced-motion awareness, View Transitions helper, `vvj:*`
 * CustomEvent dispatch). The base class + shared helpers are pulled from
 * the `Drupal.Vvj` namespace — Drupal core's cross-file sharing pattern —
 * with load order guaranteed by the `vvja` library's `dependencies:`.
 *
 * The element IS the outer accordion wrapper — the `class="vvja"`
 * markup hasn't moved, just the tag name changed from `<div>` to
 * `<vvja-accordion>`. Theme overrides targeting `.vvja` keep working.
 *
 * Public methods exposed for `Drupal.vvja.*` API delegation:
 *   - openPanel(panelIndex)
 *   - closePanel(panelIndex)
 *   - togglePanel(panelIndex)
 *   - getOpenPanels()
 *   - getTotalPanels()
 *
 * @license SPDX-License-Identifier: GPL-2.0-or-later
 */

// phpcs:disable Drupal.NamingConventions.UpperCaseConstant
// phpcs:disable Generic.PHP.UpperCaseConstant
// phpcs:disable Squiz.WhiteSpace.OperatorSpacing

((Drupal) => {
  const { ElementBase, handleArrowKeyNav, wireDeeplink, writeDeeplinkHash } =
    Drupal.Vvj;

  class VvjaAccordionElement extends ElementBase {
    static get patternSlug() {
      return 'vvja';
    }

    // ---------------------------------------------------------------
    // Hydration — runs once when the element first becomes visible.
    // ---------------------------------------------------------------

    onHydrate() {
      /** @type {HTMLElement | null} */
      this._inner = this.querySelector(':scope > .vvja-inner');
      if (!this._inner) {
        return;
      }

      /** @type {NodeListOf<HTMLElement>} */
      this._buttons = this._inner.querySelectorAll('.vvja-button');
      this._isExclusive = this._inner.dataset.exclusive === 'true';

      this._wireTriggerButtons();
      this._wireGlobalToggle();
      this._applyExpandDefault();
      this._initAriaLabels();
      this._neutralizeNestedLinks();
      this._initFocusableElements();
      this._wireResize();
      this._wireDeeplink();
    }

    // ---------------------------------------------------------------
    // Public API — delegated to via `Drupal.vvja.openPanel(...)` etc.
    // ---------------------------------------------------------------

    /**
     * @param {number} panelIndex 1-based.
     * @return {boolean}
     */
    openPanel(panelIndex) {
      const button = this._getButton(panelIndex);
      if (!button) {
        return false;
      }
      const pane = button.nextElementSibling;
      if (!pane) {
        return false;
      }
      if (pane.getAttribute('aria-hidden') === 'false') {
        return true;
      }
      button.click();
      return true;
    }

    /**
     * @param {number} panelIndex 1-based.
     * @return {boolean}
     */
    closePanel(panelIndex) {
      const button = this._getButton(panelIndex);
      if (!button) {
        return false;
      }
      const pane = button.nextElementSibling;
      if (!pane) {
        return false;
      }
      if (pane.getAttribute('aria-hidden') === 'true') {
        return true;
      }
      button.click();
      return true;
    }

    /**
     * @param {number} panelIndex 1-based.
     * @return {boolean}
     */
    togglePanel(panelIndex) {
      const button = this._getButton(panelIndex);
      if (!button) {
        return false;
      }
      button.click();
      return true;
    }

    /**
     * @return {number[]} 1-based indices of currently-open panels.
     */
    getOpenPanels() {
      if (!this._inner) {
        return [];
      }
      const panes = this._inner.querySelectorAll('.vvja-pane');
      /** @type {number[]} */
      const open = [];
      panes.forEach((pane, index) => {
        if (pane.getAttribute('aria-hidden') === 'false') {
          open.push(index + 1);
        }
      });
      return open;
    }

    /**
     * @return {number}
     */
    getTotalPanels() {
      return this._buttons?.length ?? 0;
    }

    // ---------------------------------------------------------------
    // Per-button click + keyboard wiring.
    // ---------------------------------------------------------------

    _wireTriggerButtons() {
      if (!this._buttons) {
        return;
      }
      this._buttons.forEach((button, index) => {
        if (!button.classList.contains('active')) {
          button.setAttribute('tabindex', '-1');
        }
        button.addEventListener('click', this._handleAccordionClick, {
          signal: this.signal,
        });
        button.addEventListener(
          'keydown',
          (event) => {
            handleArrowKeyNav(event, this._buttons, index);
          },
          { signal: this.signal },
        );
      });
    }

    _wireGlobalToggle() {
      /** @type {HTMLButtonElement | null} */
      const globalToggleButton = this._inner?.querySelector(
        '.global-toggle .button.group-toggle',
      );
      if (!globalToggleButton) {
        return;
      }
      this._globalToggleButton = globalToggleButton;
      globalToggleButton.addEventListener(
        'click',
        () => {
          const isExpanded =
            globalToggleButton.getAttribute('aria-expanded') === 'true';
          this._handleGlobalToggle(isExpanded ? 'collapse' : 'expand');
          this._updateGlobalToggleState();
        },
        { signal: this.signal },
      );
    }

    _applyExpandDefault() {
      if (!this._inner || !this._buttons) {
        return;
      }
      const expandDefault = this._inner.dataset.expandDefault || 'none';
      if (expandDefault === 'first' && this._buttons.length > 0) {
        const firstPane = this._buttons[0].nextElementSibling;
        if (firstPane) {
          this._openPane(firstPane);
          this._activateButton(this._buttons[0]);
        }
      } else if (expandDefault === 'all') {
        this._handleGlobalToggle('expand');
        this._updateGlobalToggleState();
      }
    }

    // ---------------------------------------------------------------
    // Pane open/close mechanics.
    // ---------------------------------------------------------------

    /** @param {Element} pane */
    _openPane(pane) {
      if (!pane) {
        return;
      }
      /** @type {HTMLElement} */
      const p = /** @type {HTMLElement} */ (pane);
      p.style.maxHeight = `${p.scrollHeight}px`;
      p.setAttribute('aria-hidden', 'false');
      this._toggleFocusableElements(p, false);
      p.addEventListener(
        'transitionend',
        () => {
          if (p.getAttribute('aria-hidden') === 'false') {
            p.style.maxHeight = 'none';
          }
        },
        { once: true },
      );
    }

    /** @param {Element} pane */
    _closePane(pane) {
      if (!pane) {
        return;
      }
      /** @type {HTMLElement} */
      const p = /** @type {HTMLElement} */ (pane);
      p.style.maxHeight = `${p.scrollHeight}px`;
      requestAnimationFrame(() => {
        p.style.maxHeight = '0';
      });
      p.setAttribute('aria-hidden', 'true');
      this._toggleFocusableElements(p, true);
    }

    /** @param {HTMLElement} button */
    _activateButton(button) {
      button.classList.add('active');
      button.removeAttribute('tabindex');
      button.setAttribute('aria-expanded', 'true');
      this._updateToggleIndicator(button, true);
      button.parentElement?.classList.remove('closed');
      button.parentElement?.classList.add('opened');
    }

    /** @param {HTMLElement} button */
    _deactivateButton(button) {
      button.classList.remove('active');
      button.setAttribute('tabindex', '-1');
      button.setAttribute('aria-expanded', 'false');
      this._updateToggleIndicator(button, false);
      button.parentElement?.classList.remove('opened');
      button.parentElement?.classList.add('closed');
    }

    /**
     * @param {HTMLElement} button
     * @param {boolean} isOpen
     */
    _updateToggleIndicator(button, isOpen) {
      const toggle = button.querySelector('.single-toggle');
      if (!toggle) {
        return;
      }
      this._toggleAriaHidden(toggle.querySelector('.b-plus'), isOpen);
      this._toggleAriaHidden(toggle.querySelector('.b-minus'), !isOpen);
    }

    /**
     * @param {Element|null} element
     * @param {boolean} isHidden
     */
    _toggleAriaHidden(element, isHidden) {
      if (!element) {
        return;
      }
      element.setAttribute('aria-hidden', isHidden ? 'true' : 'false');
      element.classList.toggle('vvja-icon-hidden', isHidden);
    }

    /**
     * @param {HTMLElement} pane
     * @param {boolean} shouldDisable
     */
    _toggleFocusableElements(pane, shouldDisable) {
      const focusables = pane.querySelectorAll(
        'a, button, input, select, textarea, [tabindex]',
      );
      focusables.forEach((/** @type {HTMLElement} */ element) => {
        if (shouldDisable) {
          element.setAttribute('tabindex', '-1');
          element.setAttribute('aria-disabled', 'true');
          if ('disabled' in element) {
            /** @type {HTMLInputElement} */ (element).disabled = true;
          }
        } else {
          element.removeAttribute('tabindex');
          element.removeAttribute('aria-disabled');
          if ('disabled' in element) {
            /** @type {HTMLInputElement} */ (element).disabled = false;
          }
        }
      });
    }

    // ---------------------------------------------------------------
    // Click handlers.
    // ---------------------------------------------------------------

    _handleAccordionClick = (event) => {
      /** @type {HTMLElement} */
      const button = /** @type {HTMLElement} */ (event.currentTarget);
      const pane = button.nextElementSibling;
      if (!pane || !this._inner) {
        return;
      }
      const allButtons = this._inner.querySelectorAll('.vvja-button');

      /** @type {HTMLElement} */
      const p = /** @type {HTMLElement} */ (pane);
      const isOpen = !!p.style.maxHeight && p.style.maxHeight !== '0px';

      this.withTransition(() => {
        if (this._isExclusive) {
          allButtons.forEach((other) => {
            if (other !== button) {
              const otherPane = other.nextElementSibling;
              if (otherPane) {
                this._closePane(otherPane);
              }
              this._deactivateButton(/** @type {HTMLElement} */ (other));
            }
          });
          if (isOpen) {
            this._closePane(p);
            this._deactivateButton(button);
          } else {
            this._openPane(p);
            this._activateButton(button);
          }
        } else {
          if (isOpen) {
            this._closePane(p);
            this._deactivateButton(button);
          } else {
            this._openPane(p);
            this._activateButton(button);
          }
          this._updateGlobalToggleState();
        }
      });

      this._updateDeepLinkHash(p);
    };

    /** @param {'expand' | 'collapse'} action */
    _handleGlobalToggle(action) {
      if (!this._inner) {
        return;
      }
      const buttons = this._inner.querySelectorAll('.vvja-button');
      if (action === 'expand') {
        buttons.forEach((/** @type {HTMLElement} */ button) => {
          const pane = button.nextElementSibling;
          if (pane) {
            this._openPane(pane);
            this._activateButton(button);
          }
        });
      } else {
        const panes = this._inner.querySelectorAll('.vvja-pane');
        panes.forEach((pane) => this._closePane(pane));
        buttons.forEach((/** @type {HTMLElement} */ button) =>
          this._deactivateButton(button),
        );
      }
    }

    _updateGlobalToggleState() {
      const toggle = this._globalToggleButton;
      if (!toggle || !this._inner) {
        return;
      }
      const panes = Array.from(this._inner.querySelectorAll('.vvja-pane'));
      const allOpen =
        panes.length > 0 &&
        panes.every((p) => p.getAttribute('aria-hidden') === 'false');
      const allClosed =
        panes.length > 0 &&
        panes.every((p) => p.getAttribute('aria-hidden') === 'true');
      if (allOpen) {
        toggle.setAttribute('aria-expanded', 'true');
        this._toggleAriaHidden(toggle.querySelector('.b-plus'), true);
        this._toggleAriaHidden(toggle.querySelector('.b-minus'), false);
      } else if (allClosed) {
        toggle.setAttribute('aria-expanded', 'false');
        this._toggleAriaHidden(toggle.querySelector('.b-plus'), false);
        this._toggleAriaHidden(toggle.querySelector('.b-minus'), true);
      } else {
        toggle.setAttribute('aria-expanded', 'false');
        this._toggleAriaHidden(toggle.querySelector('.b-plus'), false);
        this._toggleAriaHidden(toggle.querySelector('.b-minus'), true);
      }
    }

    // ---------------------------------------------------------------
    // ARIA fallback labels + nested-link neutralization (v1 features).
    // ---------------------------------------------------------------

    _initAriaLabels() {
      this._buttons?.forEach((/** @type {HTMLElement} */ button) => {
        const img = button.querySelector('img');
        if (img && img.alt) {
          button.setAttribute('aria-label', img.alt);
          return;
        }
        if (button.hasAttribute('aria-label')) {
          return;
        }
        const labelElement = button.querySelector('.button-label');
        button.setAttribute(
          'aria-label',
          labelElement?.textContent?.trim() || 'Accordion Button',
        );
      });
    }

    _neutralizeNestedLinks() {
      this._buttons?.forEach((button) => {
        const links = button.querySelectorAll('a');
        links.forEach((link) => {
          link.setAttribute(
            'data-original-href',
            link.getAttribute('href') ?? '',
          );
          link.removeAttribute('href');
          link.style.cursor = 'pointer';
          link.addEventListener(
            'click',
            (event) => {
              event.preventDefault();
              event.stopPropagation();
            },
            { signal: this.signal },
          );
        });
      });
    }

    _initFocusableElements() {
      if (!this._inner) {
        return;
      }
      const panes = this._inner.querySelectorAll('.vvja-pane');
      panes.forEach((pane) => {
        const isHidden = pane.getAttribute('aria-hidden') === 'true';
        this._toggleFocusableElements(
          /** @type {HTMLElement} */ (pane),
          isHidden,
        );
      });
    }

    // ---------------------------------------------------------------
    // Resize: recompute open-pane maxHeight on viewport-width change.
    // ---------------------------------------------------------------

    _wireResize() {
      let resizeTimeout;
      let lastResizeWidth = window.innerWidth;
      const onResize = () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
          if (
            window.innerWidth > 768 &&
            Math.abs(lastResizeWidth - window.innerWidth) > 30
          ) {
            if (!this._inner) {
              return;
            }
            const openPanes = this._inner.querySelectorAll(
              '.vvja-pane[aria-hidden="false"]',
            );
            openPanes.forEach((/** @type {HTMLElement} */ pane) => {
              pane.style.maxHeight = `${pane.scrollHeight}px`;
            });
            lastResizeWidth = window.innerWidth;
          }
        }, 100);
      };
      window.addEventListener('resize', onResize, { signal: this.signal });
    }

    // ---------------------------------------------------------------
    // Deep linking.
    // ---------------------------------------------------------------

    _wireDeeplink() {
      if (!this._inner) {
        return;
      }
      const enabled = this._inner.dataset.deeplinkEnabled === 'true';
      const id = this._inner.dataset.deeplinkId;
      if (!enabled || !id) {
        return;
      }
      wireDeeplink(this, 'accordion', id, this.signal, (panelIndex) => {
        // Defer one tick so post-hydration state has settled.
        setTimeout(() => this.openPanel(panelIndex), 100);
      });
    }

    /** @param {HTMLElement} pane */
    _updateDeepLinkHash(pane) {
      if (!this._inner) {
        return;
      }
      const enabled = this._inner.dataset.deeplinkEnabled === 'true';
      const id = this._inner.dataset.deeplinkId;
      if (!enabled || !id) {
        return;
      }
      const panelIndex = pane.dataset.index;
      if (!panelIndex) {
        return;
      }
      if (pane.getAttribute('aria-hidden') === 'false') {
        writeDeeplinkHash('accordion', id, Number.parseInt(panelIndex, 10));
      }
    }

    // ---------------------------------------------------------------
    // Helpers.
    // ---------------------------------------------------------------

    /**
     * @param {number} panelIndex 1-based.
     * @return {HTMLElement | null}
     */
    _getButton(panelIndex) {
      if (
        !this._buttons ||
        panelIndex < 1 ||
        panelIndex > this._buttons.length
      ) {
        return null;
      }
      return this._buttons[panelIndex - 1];
    }
  }

  if (!customElements.get('vvja-accordion')) {
    customElements.define('vvja-accordion', VvjaAccordionElement);
  }
})(Drupal);
