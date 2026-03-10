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
;// ./src/js/user-my-account.js
/*global moment, wc_bookings_user_my_account_params*/

external_jQuery_default()(document).ready(function ($) {
  var localTimezone = moment.tz.guess();
  var shouldDisplayNotices = false;

  // Confirm booking cancellation.
  $('.booking-cancel').on('click', 'a.cancel', function () {
    return window.confirm(wc_bookings_user_my_account_params.cancel_confirmation);
  });

  /**
   * Callback that checks if we should convert or add timezone string to the booking time.
   *
   * Since admin does not know what timezone the user is in, it is not possible to set a proper
   * local timezone for the booking manually create by admin. If timezone conversion is enabled
   * and the user timezone is different then admin timezone the user will see wrong start and end times.
   *
   * Because the booking start and end time are filterable, it's possible for those times to use different
   * format. We validate the time and only convert times that use the same format as Bookings is using.
   *
   * Reference: https://github.com/woocommerce/woocommerce-bookings/issues/2650
   *
   * @param {element index } index
   * @param {jQuery element } element
   */
  var maybeConvertDateTime = function (index, element) {
    var elementTimezone = $(element).data('timezone');
    var elementTime = $(element).text().trim();
    if ($(element).data('allDay') === 'yes') {
      // Don't adjust for day availability type events.
      return;
    }
    if (elementTimezone === localTimezone) {
      return;
    }

    // If the format is changed, only add timezone string.
    if (!moment(elementTime, wc_bookings_user_my_account_params.datetime_format, true).isValid()) {
      $(element).text(elementTime + ' ' + elementTimezone);
      shouldDisplayNotices = true;
      return;
    }
    var formattedDate = moment // eslint-disable-line vars-on-top
    .tz(elementTime, wc_bookings_user_my_account_params.datetime_format, elementTimezone).tz(localTimezone).format(wc_bookings_user_my_account_params.datetime_format);
    $(element).text(formattedDate);
  };
  if (!wc_bookings_user_my_account_params.timezone_conversion) {
    // No conversion necessary, don't adjust the time display.
    return;
  }
  if (!localTimezone) {
    // Local timezone not found. We can't compare the bookings timezone to local - abort!
    return;
  }
  $('.my_account_bookings').find('tbody .booking-start-date').each(maybeConvertDateTime);
  $('.my_account_bookings').find('tbody .booking-end-date').each(maybeConvertDateTime);
  $('.woocommerce-order-details').find('.wc-booking-summary-list .booking-start-date').each(maybeConvertDateTime);
  $('.woocommerce-order-details').find('.wc-booking-summary-list .booking-end-date').each(maybeConvertDateTime);

  // Inform user about the display of timezones for some of the items.
  if (shouldDisplayNotices) {
    $('.bookings-my-account-notice').text(wc_bookings_user_my_account_params.timezone_notice + ' ' + localTimezone + '.').show();
  }
});
/******/ })()
;
//# sourceMappingURL=user-my-account.js.map