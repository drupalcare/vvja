/**
 * @file
 * Handles form field visibility toggles in Views UI for VVJA.
 *
 * Filename:     admin-vvja.js
 * Website:      https://www.flashwebcenter.com
 * Developer:    Alaa Haddad https://www.alaahaddad.com.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Toggle deep link identifier field visibility.
   */
  Drupal.behaviors.vvjaDeeplinkToggle = {
    attach: function (context, settings) {
      const toggleCheckbox = once('vvja-deeplink-toggle', '[data-vvja-deeplink-toggle="true"]', context);

      if (!toggleCheckbox.length) {
        return;
      }

      toggleCheckbox.forEach(function(checkbox) {
        const wrapper = context.querySelector('[data-vvja-deeplink-field="true"]');

        if (!wrapper) {
          return;
        }

        if (checkbox.hasAttribute('data-vvja-processed')) {
          return;
        }
        checkbox.setAttribute('data-vvja-processed', 'true');

        const updateVisibility = function(isEnabled) {
          const input = wrapper.querySelector('input[type="text"]');

          if (isEnabled) {
            wrapper.classList.remove('hidden-element');
            wrapper.removeAttribute('aria-hidden');

            if (input) {
              input.removeAttribute('tabindex');
              input.setAttribute('required', 'required');
            }
          }
          else {
            wrapper.classList.add('hidden-element');
            wrapper.setAttribute('aria-hidden', 'true');

            if (input) {
              input.setAttribute('tabindex', '-1');
              input.removeAttribute('required');
            }
          }
        };

        updateVisibility(checkbox.checked);

        checkbox.addEventListener('change', function() {
          updateVisibility(this.checked);
        });
      });
    }
  };

})(Drupal, once);
