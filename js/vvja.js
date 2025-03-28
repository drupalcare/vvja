/**
 * @file
 * Views Vanilla JavaScript Accordion.
 *
 * Filename:     vvja.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */

((Drupal, drupalSettings, once) => {
  'use strict';

  Drupal.behaviors.VVJAccordion = {
    attach: function (context, settings) {
      const accordionContainers = once('vvjAccordion', '.vvja > .vvja-inner', context);

      accordionContainers.forEach(container => {
        const accordionButtons = container.querySelectorAll('.vvja-button');

        once('vvjAccordionButtonInit', accordionButtons).forEach((button) => {
          if (!button.classList.contains('active')) {
            button.setAttribute('tabindex', '-1');
          }
          button.addEventListener('click', handleAccordionClick);
        });

        const globalToggleButton = container.querySelector('.global-toggle .button.group-toggle');

        if (globalToggleButton) {
          globalToggleButton.addEventListener('click', () => {
            const isExpanded = globalToggleButton.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
              handleGlobalToggle('collapse');
              globalToggleButton.setAttribute('aria-expanded', 'false');
              toggleAriaHidden(globalToggleButton.querySelector('.b-plus'), false);
              toggleAriaHidden(globalToggleButton.querySelector('.b-minus'), true);
            } else {
              handleGlobalToggle('expand');
              globalToggleButton.setAttribute('aria-expanded', 'true');
              toggleAriaHidden(globalToggleButton.querySelector('.b-plus'), true);
              toggleAriaHidden(globalToggleButton.querySelector('.b-minus'), false);
            }
          });
        }

        const firstToggle = container.dataset.firstToggle === 'true';
        if (firstToggle) {
          const firstButton = accordionButtons[0];
          const firstPane = firstButton.nextElementSibling;
          openPane(firstPane);
          activateButton(firstButton);
          toggleAriaHidden(firstButton.querySelector('.b-plus'), true);
          toggleAriaHidden(firstButton.querySelector('.b-minus'), false);
        }

        function updateGlobalToggleState(container) {
          const globalToggleButton = container.querySelector('.global-toggle .group-toggle');
          if (!globalToggleButton) return;

          const panes = container.querySelectorAll('.vvja-pane');
          const allOpen = Array.from(panes).every(pane => pane.getAttribute('aria-hidden') === 'false');
          const allClosed = Array.from(panes).every(pane => pane.getAttribute('aria-hidden') === 'true');

          if (allOpen) {
            globalToggleButton.setAttribute('aria-expanded', 'true');
            toggleAriaHidden(globalToggleButton.querySelector('.b-plus'), true);
            toggleAriaHidden(globalToggleButton.querySelector('.b-minus'), false);
          } else if (allClosed) {
            globalToggleButton.setAttribute('aria-expanded', 'false');
            toggleAriaHidden(globalToggleButton.querySelector('.b-plus'), false);
            toggleAriaHidden(globalToggleButton.querySelector('.b-minus'), true);
          } else {
            // Mixed state: keep as-is or default to collapse
            globalToggleButton.setAttribute('aria-expanded', 'false');
            toggleAriaHidden(globalToggleButton.querySelector('.b-plus'), false);
            toggleAriaHidden(globalToggleButton.querySelector('.b-minus'), true);
          }
        }

        function openPane(pane) {
          const panePadding = 32;
          pane.style.maxHeight = `${pane.scrollHeight + panePadding}px`;
          pane.style.padding = '16px';
          pane.setAttribute('aria-hidden', 'false');
          toggleFocusableElements(pane, false);

          // Optional: Let it resize naturally afterward
          pane.addEventListener('transitionend', () => {
            if (pane.getAttribute('aria-hidden') === 'false') {
              pane.style.maxHeight = 'none';
            }
          }, { once: true });
        }

        function closePane(pane) {
          pane.style.maxHeight = `${pane.scrollHeight}px`;
          requestAnimationFrame(() => {
            pane.style.maxHeight = '0';
            pane.style.padding = '0 16px';
          });
          pane.setAttribute('aria-hidden', 'true');
          toggleFocusableElements(pane, true);
        }

        function deactivateButton(button) {
          button.classList.remove('active');
          button.setAttribute('tabindex', '-1');
          button.setAttribute('aria-expanded', 'false');
          updateToggleIndicator(button, false);
          button.parentElement.classList.remove('opened');
          button.parentElement.classList.add('closed');
        }

        function activateButton(button) {
          button.classList.add('active');
          button.removeAttribute('tabindex');
          button.setAttribute('aria-expanded', 'true');
          updateToggleIndicator(button, true);
          button.parentElement.classList.remove('closed');
          button.parentElement.classList.add('opened');
        }

        function updateToggleIndicator(button, isOpen) {
          const toggle = button.querySelector('.single-toggle');
          if (toggle) {
            const plus = toggle.querySelector('.b-plus');
            const minus = toggle.querySelector('.b-minus');
            if (isOpen) {
              toggleAriaHidden(plus, true);
              toggleAriaHidden(minus, false);
            } else {
              toggleAriaHidden(plus, false);
              toggleAriaHidden(minus, true);
            }
          }
        }

        function toggleAriaHidden(element, isHidden) {
          if (element) {
            element.setAttribute('aria-hidden', isHidden.toString());
            element.classList.toggle('vvja-hidden', isHidden);
          }
        }

        function toggleFocusableElements(pane, shouldDisable) {
          const focusableElements = pane.querySelectorAll('a, button, input, select, textarea, [tabindex]');
          focusableElements.forEach(element => {
            if (shouldDisable) {
              element.setAttribute('tabindex', '-1');
              element.setAttribute('aria-disabled', 'true');
              element.disabled = true;
            } else {
              element.removeAttribute('tabindex');
              element.removeAttribute('aria-disabled');
              element.disabled = false;
            }
          });
        }

        function handleAccordionClick(event) {
          const button = event.currentTarget;
          const pane = button.nextElementSibling;
          const container = button.closest('.vvja-inner');
          const allButtons = container.querySelectorAll('.vvja-button');

          const isOpen = pane.style.maxHeight && pane.style.maxHeight !== '0px';

          // Check if exclusive mode is enabled via data attribute
          const isExclusive = container.dataset.exclusive === 'true';

          if (isExclusive) {
            // Exclusive mode: close all other panels
            allButtons.forEach(otherButton => {
              const otherPane = otherButton.nextElementSibling;

              if (otherButton !== button) {
                closePane(otherPane);
                deactivateButton(otherButton);
              }
            });

            if (isOpen) {
              closePane(pane);
              deactivateButton(button);
            } else {
              openPane(pane);
              activateButton(button);
            }

          } else {
            if (isOpen) {
              closePane(pane);
              deactivateButton(button);
            } else {
              openPane(pane);
              activateButton(button);
            }
            updateGlobalToggleState(container);
          }
        }

        function handleGlobalToggle(action) {
          const panes = container.querySelectorAll('.vvja-pane');
          const buttons = container.querySelectorAll('.vvja-button');
          if (action === 'expand') {
            buttons.forEach(button => {
              const pane = button.nextElementSibling;
              if (pane) {
                openPane(pane);
                activateButton(button);
              }
            });
          } else if (action === 'collapse') {
            panes.forEach(pane => closePane(pane));
            buttons.forEach(button => deactivateButton(button));
          }
        }

        function handleKeyboardNavigation(accordionButtons) {
          accordionButtons.forEach((button, index) => {
            button.addEventListener('keydown', (event) => {
              const currentIndex = index;
              let newIndex;

              switch (event.key) {
                case 'ArrowDown':
                  newIndex = (currentIndex + 1) % accordionButtons.length;
                  break;
                case 'ArrowUp':
                  newIndex = (currentIndex - 1 + accordionButtons.length) % accordionButtons.length;
                  break;
                case 'Home':
                  newIndex = 0;
                  break;
                case 'End':
                  newIndex = accordionButtons.length - 1;
                  break;
                default:
                  return;
              }

              accordionButtons.forEach(b => b.setAttribute('tabindex', '-1'));
              accordionButtons[newIndex].setAttribute('tabindex', '0');
              accordionButtons[newIndex].focus();
              event.preventDefault();
            });
          });
        }

        function updateAriaLabelsForAccordionButtons(accordionButtons) {
          accordionButtons.forEach(button => {
            const img = button.querySelector('img');
            if (img && img.alt) {
              button.setAttribute('aria-label', img.alt);
            } else if (button.hasAttribute('aria-label')) {
              return;
            } else {
              const labelElement = button.querySelector('.button-label');
              if (labelElement) {
                button.setAttribute('aria-label', labelElement.textContent.trim());
              } else {
                button.setAttribute('aria-label', 'Accordion Button');
              }
            }
          });
        }

        function disableLinksInAccordionButtons(accordionButtons) {
          accordionButtons.forEach(button => {
            const links = button.querySelectorAll('a');
            links.forEach(link => {
              link.setAttribute('data-original-href', link.getAttribute('href'));
              link.removeAttribute('href');
              link.style.cursor = 'pointer';
              link.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
              });
            });
          });
        }

        function initializeFocusableElements() {
          const panes = container.querySelectorAll('.vvja-pane');
          panes.forEach(pane => {
            const isHidden = pane.getAttribute('aria-hidden') === 'true';
            toggleFocusableElements(pane, isHidden);
          });
        }

        // Debounce function to limit resize event calls
        let resizeTimeout;
        let lastResizeWidth = window.innerWidth;
        function handleResizeDebounced() {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(() => {
            if (window.innerWidth > 768 && Math.abs(lastResizeWidth - window.innerWidth) > 30) {
              handleResize();
              lastResizeWidth = window.innerWidth;
            }
          }, 100);
        }

        // Adjust open panes on resize
        function handleResize() {
          const openPanes = container.querySelectorAll('.vvja-pane[aria-hidden="false"]');

          openPanes.forEach(pane => {
            const panePadding = 32; // Match the padding used in openPane()
            pane.style.maxHeight = `${pane.scrollHeight + panePadding}px`;
          });
        }

        // Add resize event listener
        window.addEventListener('resize', handleResizeDebounced);

        handleKeyboardNavigation(accordionButtons);
        updateAriaLabelsForAccordionButtons(accordionButtons);
        disableLinksInAccordionButtons(accordionButtons);
        initializeFocusableElements();
      });
    }
  };
})(Drupal, drupalSettings, once);
