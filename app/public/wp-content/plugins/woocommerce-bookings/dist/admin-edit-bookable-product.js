/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/************************************************************************/

;// external "jQuery"
const external_jQuery_namespaceObject = window["jQuery"];
var external_jQuery_default = /*#__PURE__*/__webpack_require__.n(external_jQuery_namespaceObject);
;// ./src/js/admin-edit-bookable-product.js
// External dependencies.

(function ($) {
  /**
   * Validate min-max duration.
   */
  validateDuration();

  /**
   * Validates if max duration is greater or equal to min duration.
   */
  function validateDuration() {
    const minDuration = $('#_wc_booking_min_duration');
    const maxDuration = $('#_wc_booking_max_duration');
    let minDurationInitialVal = minDuration.val();
    let maxDurationInitialVal = maxDuration.val();
    let error = '';
    let hasError = false;
    minDuration.add(maxDuration).on('input', function () {
      const minDurationVal = Number(minDuration.val());
      const maxDurationVal = Number(maxDuration.val());

      /**
       * Check if min duration <= max duration.
       */
      const isDurationComparisonValid = minDurationVal <= maxDurationVal;

      /**
       * Error type for invalid comparisons.
       */
      if ('_wc_booking_min_duration' === this.name) {
        error = 'wc_bookings_invalid_min_duration';
      } else {
        error = 'wc_bookings_invalid_max_duration';
      }
      if (isDurationComparisonValid) {
        removeErrorFromField($(this));
        hasError = false;
      } else {
        if ('' === maxDuration.val()) {
          return;
        }
        appendErrorToField($(this), wc_bookings_admin_edit_booking_params[error]);
        hasError = true;
      }
    });

    /**
     * Resets the fields to initial values if they have invalid value.
     */
    minDuration.add(maxDuration).on('blur', function () {
      const currentDurationField = $(this);
      if (hasError || '' === currentDurationField.val()) {
        currentDurationField.val(currentDurationField.is(minDuration) ? minDurationInitialVal : maxDurationInitialVal);
        currentDurationField.trigger('input');
      }
    });

    /**
     * Adds the error message under the input field with invalid value.
     *
     * @param {Object} fieldEl The jQuery object of the input field.
     * @param {string} error The error string.
     */
    function appendErrorToField(fieldEl, error) {
      if (fieldEl.next().hasClass('wc_bookings_error')) {
        return;
      }
      fieldEl.after(`<div class="wc_bookings_error">${error}</div>`);
    }

    /**
     * Removes the existing error field after the input value is corrected.
     *
     * @param {Object} fieldEl The jQuery object of the input field.
     */
    function removeErrorFromField(fieldEl) {
      if (fieldEl.next().hasClass('wc_bookings_error')) {
        fieldEl.next('.wc_bookings_error').remove();
      }
    }
  }
})((external_jQuery_default()));
/******/ })()
;
//# sourceMappingURL=admin-edit-bookable-product.js.map