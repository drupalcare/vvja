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
    attach: function(context, settings) {
      // Utility function to get elements by ID
      const getElementsById = (id) => {
        const wrapper = context.querySelectorAll(`#${id} > .accordion-item`);
        const minusButton = context.querySelector(`#${id} > .global-toggle > button.b-minus`);
        const plusButton = context.querySelector(`#${id} > .global-toggle > button.b-plus`);
        return [wrapper, minusButton, plusButton];
      };

      // Function to update the tabindex based on visibility
      const updateTabindex = (button) => {
        if (window.getComputedStyle(button).display === 'none') {
          button.setAttribute('tabindex', '-1');
        } else {
          button.removeAttribute('tabindex');
        }
      };

      const toggleClassAndDisplay = (buttonSelector, addClass, removeClass, hideIndex, showIndex) => {
        once('toggleClassAndDisplay', buttonSelector, context).forEach((button) => {
          button.addEventListener('click', function(event) {
            const classId = event.target.closest('.accordion-inner').id;
            const [wrapper, minusButton, plusButton] = getElementsById(classId);
            wrapper.forEach((element) => {
              element.classList.remove(removeClass);
              element.classList.add(addClass);
              const isExpand = addClass === 'active';
              const toggleButton = element.querySelector('.accordion-button');
              const contentPane = element.querySelector('.accordion-pane');
              if (toggleButton) {
                toggleButton.setAttribute('aria-expanded', isExpand);
              }
              if (contentPane) {
                contentPane.setAttribute('aria-hidden', !isExpand);
              }
            });

            if (minusButton) {
              minusButton.style.display = hideIndex === 1 ? "none" : "block";
              updateTabindex(minusButton);
            }
            if (plusButton) {
              plusButton.style.display = showIndex === 1 ? "none" : "block";
              updateTabindex(plusButton);
            }
          });
        });
      };

      const toggleActiveState = (buttonSelector, activeClass, inactiveClass) => {
        once('toggleActiveState', buttonSelector, context).forEach((button) => {
          button.addEventListener('click', function(event) {
            const element = event.target.closest('.accordion-item');
            const section = event.target.closest('.accordion-inner'); // It should reference the individual panel's parent, not just any ancestor.
            const toggleButton = element.querySelector('.accordion-button');
            const contentPane = element.querySelector('.accordion-pane');

            const isCurrentlyActive = toggleButton.getAttribute('aria-expanded') === 'true';

            if (isCurrentlyActive) {
              element.classList.replace(activeClass, inactiveClass);
              toggleButton.setAttribute('aria-expanded', 'false');
              contentPane.setAttribute('aria-hidden', 'true');
            } else {
              element.classList.replace(inactiveClass, activeClass);
              toggleButton.setAttribute('aria-expanded', 'true');
              contentPane.setAttribute('aria-hidden', 'false');
            }

            // Ensure the correct display of expand/collapse all buttons
            const [_, minusButton, plusButton] = getElementsById(section.id);
            if (plusButton) {
              plusButton.style.display = isCurrentlyActive ? "block" : "none";
              updateTabindex(plusButton);
            }
            if (minusButton) {
              minusButton.style.display = isCurrentlyActive ? "none" : "block";
              updateTabindex(minusButton);
            }
          });
        });
      };

      once('expand-collapse-all', '.global-toggle > button', context).forEach((button) => {
        button.addEventListener('click', () => {
          const isExpand = button.classList.contains('b-plus');
          const accId = button.getAttribute('aria-controls');
          const elements = context.querySelectorAll(`#${accId} .accordion-item`);

          elements.forEach(element => {
            const toggleButton = element.querySelector('.accordion-button');
            if (toggleButton) {
              toggleButton.setAttribute('aria-expanded', isExpand);
            }
            const contentPane = element.querySelector('.accordion-pane');
            if (contentPane) {
              contentPane.setAttribute('aria-hidden', !isExpand);
            }
            element.classList.toggle('active', isExpand);
            element.classList.toggle('inactive', !isExpand);
          });

          // Update the display states of the global expand/collapse buttons
          const plusButton = context.querySelector(`#${accId} .b-plus`);
          const minusButton = context.querySelector(`#${accId} .b-minus`);
          if (plusButton) {
            plusButton.style.display = isExpand ? 'none' : 'block';
            updateTabindex(plusButton);
          }
          if (minusButton) {
            minusButton.style.display = isExpand ? 'block' : 'none';
            updateTabindex(minusButton);
          }
        });
      });

      // Initialize toggle functions
      toggleActiveState('.accordion-button', 'active', 'inactive');
      toggleClassAndDisplay('.global-toggle>button.b-plus', 'active', 'inactive', 2, 1);
      toggleClassAndDisplay('.global-toggle>button.b-minus', 'inactive', 'active', 1, 2);
    }
  };
})(Drupal, drupalSettings, once);
