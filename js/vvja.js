/**
 * @file
 * Views Vanilla JacaScript Accordion.
 *
 * Filename:     vvja.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */
((Drupal, drupalSettings, once) => {
  'use strict';

  Drupal.behaviors.VVJAccordion = {
    attach: function (context, settings) {
      // Function to smoothly open an accordion pane
      function openPane(pane) {
        const panePadding = 32; // Total vertical padding (top + bottom)
        pane.style.maxHeight = `${pane.scrollHeight + panePadding}px`; // Add padding to maxHeight
        pane.style.padding = '16px'; // Apply padding when pane is open
        pane.setAttribute('aria-hidden', 'false');
      }

      // Function to smoothly close an accordion pane
      function closePane(pane) {
        pane.style.maxHeight = '0';
        pane.style.padding = '0 16px';
        pane.setAttribute('aria-hidden', 'true');
      }

      // Function to deactivate a button
      function deactivateButton(button) {
        button.classList.remove('active');
        button.setAttribute('tabindex', '-1');
        button.setAttribute('aria-selected', 'false');
        button.setAttribute('aria-expanded', 'false');
        updateToggleIndicator(button, false);
        button.parentElement.classList.remove('opened');
        button.parentElement.classList.add('closed');
      }

      // Function to activate a button
      function activateButton(button) {
        button.classList.add('active');
        button.removeAttribute('tabindex');
        button.setAttribute('aria-selected', 'true');
        button.setAttribute('aria-expanded', 'true');
        updateToggleIndicator(button, true);
        button.parentElement.classList.remove('closed');
        button.parentElement.classList.add('opened');
      }

      // Function to update the single toggle indicator
      function updateToggleIndicator(button, isOpen) {
        const toggle = button.querySelector('.single-toggle');
        if (toggle) {
          const plus = toggle.querySelector('.b-plus');
          const minus = toggle.querySelector('.b-minus');
          if (isOpen) {
            if (plus) plus.style.display = 'none';
            if (minus) minus.style.display = 'block';
          } else {
            if (plus) plus.style.display = 'block';
            if (minus) minus.style.display = 'none';
          }
        }
      }

      // Function to handle individual accordion clicks
      function handleAccordionClick(event) {
        const button = event.currentTarget;
        const pane = button.nextElementSibling;

        const isOpen = pane.style.maxHeight && pane.style.maxHeight !== '0px';

        if (isOpen) {
          closePane(pane);
          deactivateButton(button);
        } else {
          openPane(pane);
          activateButton(button);
        }
      }

      // Function to handle global toggle actions
      function handleGlobalToggle(container, action) {
        const panes = container.querySelectorAll('.accordion-pane');
        const buttons = container.querySelectorAll('.accordion-button');
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

      // Function to handle keyboard navigation
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

      // Function to update aria-labels for accordion buttons based on image alt text
      function updateAriaLabelsForAccordionButtons(accordionButtons) {
        accordionButtons.forEach(button => {
          const img = button.querySelector('img');
          if (img && img.alt) {
            button.setAttribute('aria-label', img.alt);
          } else {
            // If no image or alt, use button text as aria-label
            button.setAttribute('aria-label', button.textContent.trim() || 'Accordion Button');
          }
        });
      }

      // Disable links within accordion buttons
      function disableLinksInAccordionButtons(accordionButtons) {
        accordionButtons.forEach(button => {
          const links = button.querySelectorAll('a');
          links.forEach(link => {
            link.setAttribute('data-original-href', link.getAttribute('href'));
            link.removeAttribute('href');
            link.style.cursor = 'default';
            link.addEventListener('click', function(event) {
              event.preventDefault(); // Prevent link from navigating
            });
          });
        });
      }

      const accordionContainers = once('vvjaAccordion', '.accordion-inner', context);
      accordionContainers.forEach(container => {
        const accordionButtons = container.querySelectorAll('.accordion-button');

        // Initialize tabindex and ARIA attributes
        accordionButtons.forEach((button) => {
          if (!button.classList.contains('active')) {
            button.setAttribute('tabindex', '-1');
          }

          // Attach click event listener
          button.addEventListener('click', handleAccordionClick);
        });

        // Handle keyboard navigation
        handleKeyboardNavigation(accordionButtons);

        // Handle global toggle buttons
        const globalTogglePlus = container.querySelector('.global-toggle .b-plus');
        const globalToggleMinus = container.querySelector('.global-toggle .b-minus');

        if (globalTogglePlus && globalToggleMinus) {
          globalTogglePlus.addEventListener('click', () => {
            handleGlobalToggle(container, 'expand');
            globalTogglePlus.style.display = 'none';
            globalToggleMinus.style.display = 'block';
          });

          globalToggleMinus.addEventListener('click', () => {
            handleGlobalToggle(container, 'collapse');
            globalTogglePlus.style.display = 'block';
            globalToggleMinus.style.display = 'none';
          });
        }

        // Open the first item if first_toggle is true
        const firstToggle = container.closest('.vvja').dataset.firstToggle === 'true';
        if (firstToggle) {
          const firstButton = accordionButtons[0];
          const firstPane = firstButton.nextElementSibling;
          openPane(firstPane);
          activateButton(firstButton);
        }

        // Update aria-labels for accordion buttons based on content
        updateAriaLabelsForAccordionButtons(accordionButtons);

        // Disable links within accordion buttons
        disableLinksInAccordionButtons(accordionButtons);
      });
    }
  };
})(Drupal, drupalSettings, once);
