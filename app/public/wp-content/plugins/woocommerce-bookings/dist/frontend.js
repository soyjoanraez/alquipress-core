/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ 2:
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   f: () => (/* binding */ get_client_server_timezone_offset_hrs),
/* harmony export */   u: () => (/* binding */ display_error)
/* harmony export */ });
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(428);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* globals: booking_form_params */


/**
 * Returns the hour offset between the client and the server.
 *
 * @param {*} referenceDate at which to compute offset.
 * @return {number} Number of hours between server and client.
 */
function get_client_server_timezone_offset_hrs(referenceDate) {
  if (!booking_form_params.timezone_conversion) {
    return 0;
  }
  let reference_time = moment(referenceDate);
  const client_offset = reference_time.utcOffset();
  reference_time.tz(booking_form_params.server_timezone);
  const server_offset = reference_time.utcOffset();
  return (client_offset - server_offset) / 60.0;
}
function display_error(errorMessage = booking_form_params.i18n_request_failed, after = '#wc-bookings-booking-form') {
  // Remove 'active' class from old notices.
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('.woocommerce-error.wc-bookings-notice').removeClass('active');

  // Create a new error notice element.
  let errorMessageHTML = document.createElement('p');
  errorMessageHTML.setAttribute('class', 'woocommerce-error wc-bookings-notice active');
  errorMessageHTML.setAttribute('style', 'display: none;');
  errorMessageHTML.textContent = errorMessage;
  jquery__WEBPACK_IMPORTED_MODULE_0___default()(errorMessageHTML).insertAfter(after);

  // Show a new notice and hide old ones.
  jquery__WEBPACK_IMPORTED_MODULE_0___default()('.woocommerce-error.wc-bookings-notice.active').slideDown({
    complete: function () {
      jquery__WEBPACK_IMPORTED_MODULE_0___default()('.woocommerce-error.wc-bookings-notice:not(.active)').slideUp();
    }
  });
}

/***/ }),

/***/ 428:
/***/ ((module) => {

module.exports = window["jQuery"];

/***/ }),

/***/ 619:
/***/ ((module) => {

module.exports = window["wp"]["hooks"];

/***/ }),

/***/ 771:
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (/* binding */ HookApi)
/* harmony export */ });
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(619);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Global variable for booking. This uses to expose public methods.
 *
 * @use window.wc_bookings.hooks
 *
 * @since 1.15.79
 */
const HookApi = () => {
  return window.wc_bookings.hooks;
};

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
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
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(619);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__);
// Global variable for booking. This uses to expose public methods.


// This global param use to expose public methods.
window.wc_bookings = window.wc_bookings || {};
window.wc_bookings.hooks = (0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_0__.createHooks)();
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(428);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* global wc_bookings_booking_form */


/*
 * This script registers functions to wc_bookings_booking_form global object.
 * These functions return product setting value for specific booking form.
 *
 * @since 1.15.73
 */
jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).ready(function ($) {
  /**
   * Should return whether multiple booking forms exist on webpage.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {boolean}
   */
  wc_bookings_booking_form.is_multiple_booking_forms_exist = () => {
    return !!$('.wc-bookings-booking-form').closest('form').length;
  };

  /**
   * Should return booking form product id.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {string}
   */
  wc_bookings_booking_form.get_booking_product_id = element => {
    let form = element.closest('form');
    form = element instanceof (jquery__WEBPACK_IMPORTED_MODULE_0___default()) ? form.get(0) : form;
    return parseInt(form.querySelector('.wc-booking-product-id').value);
  };

  /**
   * Should return booking duration.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {string}
   */
  wc_bookings_booking_form.get_booking_duration = element => {
    let product_id = null;
    if (!wc_bookings_booking_form.is_multiple_booking_forms_exist()) {
      return window.wc_bookings_booking_form.booking_duration;
    }
    product_id = wc_bookings_booking_form.get_booking_product_id(element);
    return window[`wc_bookings_booking_form_${product_id}`]['booking_duration'];
  };

  /**
   * Should return booking duration type.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {string}
   */
  wc_bookings_booking_form.get_booking_duration_type = element => {
    let product_id = null;
    if (!wc_bookings_booking_form.is_multiple_booking_forms_exist()) {
      return window.wc_bookings_booking_form.booking_duration_type;
    }
    product_id = wc_bookings_booking_form.get_booking_product_id(element);
    return window[`wc_bookings_booking_form_${product_id}`]['duration_type'];
  };

  /**
   * Should return booking max duration.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {string}
   */
  wc_bookings_booking_form.get_booking_max_duration = element => {
    let product_id = null;
    if (!wc_bookings_booking_form.is_multiple_booking_forms_exist()) {
      return window.wc_bookings_booking_form.booking_max_duration;
    }
    product_id = wc_bookings_booking_form.get_booking_product_id(element);
    return window[`wc_bookings_booking_form_${product_id}`]['booking_max_duration'];
  };

  /**
   * Should return booking max duration.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {string}
   */
  wc_bookings_booking_form.get_booking_min_duration = element => {
    let product_id = null;
    if (!wc_bookings_booking_form.is_multiple_booking_forms_exist()) {
      return window.wc_bookings_booking_form.booking_min_duration;
    }
    product_id = wc_bookings_booking_form.get_booking_product_id(element);
    return window[`wc_bookings_booking_form_${product_id}`]['booking_min_duration'];
  };

  /**
   * Should return booking check availability against.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {string}
   */
  wc_bookings_booking_form.get_booking_check_availability_against = element => {
    let product_id = null;
    if (!wc_bookings_booking_form.is_multiple_booking_forms_exist()) {
      return window.wc_bookings_booking_form.check_availability_against;
    }
    product_id = wc_bookings_booking_form.get_booking_product_id(element);
    return window[`wc_bookings_booking_form_${product_id}`]['check_availability_against'];
  };

  /**
   * Should return booking default availability.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {string}
   */
  wc_bookings_booking_form.get_booking_default_availability = element => {
    let product_id = null;
    if (!wc_bookings_booking_form.is_multiple_booking_forms_exist()) {
      return window.wc_bookings_booking_form.default_availability;
    }
    product_id = wc_bookings_booking_form.get_booking_product_id(element);
    return window[`wc_bookings_booking_form_${product_id}`]['default_availability'];
  };

  /**
   * Should return booking duration unit.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {string}
   */
  wc_bookings_booking_form.get_booking_duration_unit = element => {
    let product_id = null;
    if (!wc_bookings_booking_form.is_multiple_booking_forms_exist()) {
      return window.wc_bookings_booking_form.duration_unit;
    }
    product_id = wc_bookings_booking_form.get_booking_product_id(element);
    return window[`wc_bookings_booking_form_${product_id}`]['duration_unit'];
  };

  /**
   * Should return booking resources assignment.
   *
   * @since 1.15.73
   *
   * @param {object} element
   * @return {string}
   */
  wc_bookings_booking_form.get_booking_resources_assignment = element => {
    let product_id = null;
    if (!wc_bookings_booking_form.is_multiple_booking_forms_exist()) {
      return window.wc_bookings_booking_form.resources_assignment;
    }
    product_id = wc_bookings_booking_form.get_booking_product_id(element);
    return window[`wc_bookings_booking_form_${product_id}`]['resources_assignment'];
  };

  /**
   * Should return booking resources IDs.
   *
   * @since 1.16.02
   *
   * @param {object} element
   * @return {array} The resource IDs.
   */
  wc_bookings_booking_form.get_booking_resource_ids = element => {
    let product_id = null;
    if (!wc_bookings_booking_form.is_multiple_booking_forms_exist()) {
      return window.wc_bookings_booking_form.resource_ids;
    }
    product_id = wc_bookings_booking_form.get_booking_product_id(element);
    return window[`wc_bookings_booking_form_${product_id}`]['resource_ids'];
  };

  /**
   * should return sanitized text
   *
   * @since 1.15.73
   *
   * @param {string} text
   *
   * @return {string}
   */
  wc_bookings_booking_form.sanitize_text = text => {
    const element = document.createElement('div');
    element.innerText = text;
    return element.innerHTML;
  };
});
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(428);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _bookings_lib__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(771);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).ready(function ($) {
  if (!window.console) {
    window.console = {
      log: function () {}
    };
  }
  let xhr = [];
  wc_bookings_booking_form.wc_booking_form = $('.wc-bookings-booking-form').closest('form');
  $('.wc-bookings-booking-form').on('change', 'input, select:not("#wc-bookings-form-start-time, #wc-bookings-form-end-time")', function (e) {
    var name = $(this).attr('name');
    const booking_duration_type = wc_bookings_booking_form.get_booking_duration_type($(this));
    const booking_duration_unit = wc_bookings_booking_form.get_booking_duration_unit($(this));
    const $form = $(this).closest('form');

    /**
     * Fire action when form internal changes.
     *
     * @since 1.15.79
     * @param {HTMLElement} field Changed field.
     */
    (0,_utils__WEBPACK_IMPORTED_MODULE_2__/* .HookApi */ .A)().doAction('wc_bookings_form_field_change', {
      field: $(this).get(0)
    });

    // If it's the resource dropdown, we refresh the datepicker so that the
    // calendar availability reflects the potential differences, where it
    // may differ for different resources.
    if ('wc_bookings_field_resource' === name) {
      // Clear selection as availability in another resource might not apply.
      wc_bookings_booking_form.wc_bookings_date_picker.clear_selection();
      // Re-init the picker.
      wc_bookings_booking_form.wc_bookings_date_picker.init();
      return;
    }

    // Don't do anything on date change for hour and minute durations.
    if ('wc_bookings_field_start_date_day' === name && ['hour', 'minute'].includes(booking_duration_unit)) {
      return;
    }

    // If start time is not set, don't do anything.
    if ('customer' === booking_duration_type && '0' === $form.find('#wc-bookings-form-start-time').val()) {
      return;
    }

    // If end time is not set, don't do anything.
    if ('customer' === booking_duration_type && '0' === $form.find('#wc-bookings-form-end-time').val()) {
      return;
    }
    let $fieldset = $form.find('fieldset');
    let $picker = $fieldset.find('.picker:eq(0)');
    if ($picker.data('is_range_picker_enabled')) {
      if ('wc_bookings_field_duration' !== name && -1 === name.indexOf('wc_bookings_field_persons')) {
        return;
      }
    }
    let index = $form.index(this);
    let isEmptyCalendarSelection = !$form.find("[name='wc_bookings_field_start_date_day']").val() && !$form.find('#wc_bookings_field_start_date').val();

    // Do not update if triggered by Product Addons and no date is selected.
    if (jquery__WEBPACK_IMPORTED_MODULE_0___default()(e.target).hasClass('addon') && isEmptyCalendarSelection) {
      return;
    }
    let required_fields = $form.find('input.required_for_calculation');
    let filled = true;
    $.each(required_fields, function (index, field) {
      let value = $(field).val();
      if (!value) {
        filled = false;
      }
    });
    if (!filled) {
      $form.find('.wc-bookings-booking-cost').hide();
      return;
    }

    /**
     * Fire action before calculate booking cost.
     *
     * @since 1.15.79
     */
    (0,_utils__WEBPACK_IMPORTED_MODULE_2__/* .HookApi */ .A)().doAction('wc_bookings_pre_calculte_booking_cost', {
      'field': $(this).get(0),
      'fieldset': $fieldset.get(0),
      'date_picker': $picker.get(0),
      'form': $form.get(0)
    });
    $form.find('.wc-bookings-booking-cost').block({
      message: null,
      overlayCSS: {
        background: '#fff',
        backgroundSize: '16px 16px',
        opacity: 0.6
      }
    }).show();
    xhr[index] = $.ajax({
      type: 'POST',
      url: booking_form_params.ajax_url,
      data: {
        action: 'wc_bookings_calculate_costs',
        form: $form.serialize()
      },
      success: function (code) {
        if (code.charAt(0) !== '{') {
          // eslint-disable-next-line
          console.log(code);
          code = '{' + code.split(/\{(.+)?/)[1];
        }
        let result = JSON.parse(code);
        if (result.result === 'ERROR') {
          $form.find('.wc-bookings-booking-cost').html(result.html);
          $form.find('.wc-bookings-booking-cost').unblock();
          $form.find('.wc-bookings-booking-cost').show();
          $form.find('.single_add_to_cart_button').addClass('disabled');
        } else if (result.result === 'SUCCESS') {
          $form.find('.wc-bookings-booking-cost').html(result.html);
          $form.find('.wc-bookings-booking-cost').unblock();
          $form.find('.single_add_to_cart_button').removeClass('disabled');
          if (booking_form_params.pao_active && 'true' !== booking_form_params.pao_pre_30 && typeof result.raw_price !== 'undefined') {
            $form.find('.wc-bookings-booking-cost').attr('data-raw-price', result.raw_price);
            $('form.cart').trigger('woocommerce-product-addons-update');
          }
        } else {
          $form.find('.wc-bookings-booking-cost').hide();
          $form.find('.single_add_to_cart_button').addClass('disabled');
          // eslint-disable-next-line
          console.log(code);
        }
        $(document.body).trigger('wc_booking_form_changed', [$form]);

        // Hide error notices.
        $('.woocommerce-error.wc-bookings-notice').slideUp();
      },
      error: function (jqXHR, exception) {
        if ('abort' === exception) {
          return; // Assuming the date is changed very quickly.
        }
        (0,_bookings_lib__WEBPACK_IMPORTED_MODULE_1__/* .display_error */ .u)();
        $form.find('.wc-bookings-booking-cost').hide();
        $form.find('.single_add_to_cart_button').addClass('disabled');
        if (booking_form_params.pao_active && 'true' !== booking_form_params.pao_pre_30) {
          $('form.cart').trigger('woocommerce-product-addons-update');
        }
      },
      dataType: "html"
    });
  }).each(function () {
    let button = $(this).closest('form').find('.single_add_to_cart_button');
    button.addClass('disabled');
  });
  $('.single_add_to_cart_button').on('click', function (event) {
    if ($(this).hasClass('disabled')) {
      // eslint-disable-next-line
      alert(booking_form_params.i18n_choose_options);
      event.preventDefault();
      return false;
    }
  });

  // Prevent custom booking creation if required slots are not selected.
  // Checking if price is visible, if not, slots are not selected.
  $('.add_custom_booking').on('click', function (event) {
    if (!$('.wc-bookings-booking-cost').is(':visible') || $('.wc-bookings-booking-cost .booking-error').is(':visible')) {
      // eslint-disable-next-line
      alert(booking_form_params.i18n_choose_options);
      event.preventDefault();
      return false;
    }
  });
  if ('true' === booking_form_params.pao_pre_30) {
    $('.wc-bookings-booking-form').parent().on('updated_addons', function () {
      $('.wc-bookings-booking-form').find('input').first().trigger('change');
    });
  }
  $('.wc-bookings-booking-form, .wc-bookings-booking-form-button').show().prop('disabled', false);
});
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {

// EXTERNAL MODULE: external "jQuery"
var external_jQuery_ = __webpack_require__(428);
var external_jQuery_default = /*#__PURE__*/__webpack_require__.n(external_jQuery_);
;// external "_"
const external_namespaceObject = window["_"];
var external_default = /*#__PURE__*/__webpack_require__.n(external_namespaceObject);
// EXTERNAL MODULE: ./src/js/bookings-lib.js
var bookings_lib = __webpack_require__(2);
;// ./node_modules/rrule/dist/esm/weekday.js
// =============================================================================
// Weekday
// =============================================================================
var ALL_WEEKDAYS = [
    'MO',
    'TU',
    'WE',
    'TH',
    'FR',
    'SA',
    'SU',
];
var Weekday = /** @class */ (function () {
    function Weekday(weekday, n) {
        if (n === 0)
            throw new Error("Can't create weekday with n == 0");
        this.weekday = weekday;
        this.n = n;
    }
    Weekday.fromStr = function (str) {
        return new Weekday(ALL_WEEKDAYS.indexOf(str));
    };
    // __call__ - Cannot call the object directly, do it through
    // e.g. RRule.TH.nth(-1) instead,
    Weekday.prototype.nth = function (n) {
        return this.n === n ? this : new Weekday(this.weekday, n);
    };
    // __eq__
    Weekday.prototype.equals = function (other) {
        return this.weekday === other.weekday && this.n === other.n;
    };
    // __repr__
    Weekday.prototype.toString = function () {
        var s = ALL_WEEKDAYS[this.weekday];
        if (this.n)
            s = (this.n > 0 ? '+' : '') + String(this.n) + s;
        return s;
    };
    Weekday.prototype.getJsWeekday = function () {
        return this.weekday === 6 ? 0 : this.weekday + 1;
    };
    return Weekday;
}());

//# sourceMappingURL=weekday.js.map
;// ./node_modules/rrule/dist/esm/helpers.js
// =============================================================================
// Helper functions
// =============================================================================

var isPresent = function (value) {
    return value !== null && value !== undefined;
};
var isNumber = function (value) {
    return typeof value === 'number';
};
var isWeekdayStr = function (value) {
    return typeof value === 'string' && ALL_WEEKDAYS.includes(value);
};
var isArray = Array.isArray;
/**
 * Simplified version of python's range()
 */
var range = function (start, end) {
    if (end === void 0) { end = start; }
    if (arguments.length === 1) {
        end = start;
        start = 0;
    }
    var rang = [];
    for (var i = start; i < end; i++)
        rang.push(i);
    return rang;
};
var clone = function (array) {
    return [].concat(array);
};
var repeat = function (value, times) {
    var i = 0;
    var array = [];
    if (isArray(value)) {
        for (; i < times; i++)
            array[i] = [].concat(value);
    }
    else {
        for (; i < times; i++)
            array[i] = value;
    }
    return array;
};
var toArray = function (item) {
    if (isArray(item)) {
        return item;
    }
    return [item];
};
function padStart(item, targetLength, padString) {
    if (padString === void 0) { padString = ' '; }
    var str = String(item);
    targetLength = targetLength >> 0;
    if (str.length > targetLength) {
        return String(str);
    }
    targetLength = targetLength - str.length;
    if (targetLength > padString.length) {
        padString += repeat(padString, targetLength / padString.length);
    }
    return padString.slice(0, targetLength) + String(str);
}
/**
 * Python like split
 */
var split = function (str, sep, num) {
    var splits = str.split(sep);
    return num
        ? splits.slice(0, num).concat([splits.slice(num).join(sep)])
        : splits;
};
/**
 * closure/goog/math/math.js:modulo
 * Copyright 2006 The Closure Library Authors.
 * The % operator in JavaScript returns the remainder of a / b, but differs from
 * some other languages in that the result will have the same sign as the
 * dividend. For example, -1 % 8 == -1, whereas in some other languages
 * (such as Python) the result would be 7. This function emulates the more
 * correct modulo behavior, which is useful for certain applications such as
 * calculating an offset index in a circular list.
 *
 * @param {number} a The dividend.
 * @param {number} b The divisor.
 * @return {number} a % b where the result is between 0 and b (either 0 <= x < b
 * or b < x <= 0, depending on the sign of b).
 */
var pymod = function (a, b) {
    var r = a % b;
    // If r and b differ in sign, add b to wrap the result to the correct sign.
    return r * b < 0 ? r + b : r;
};
/**
 * @see: <http://docs.python.org/library/functions.html#divmod>
 */
var divmod = function (a, b) {
    return { div: Math.floor(a / b), mod: pymod(a, b) };
};
var empty = function (obj) {
    return !isPresent(obj) || obj.length === 0;
};
/**
 * Python-like boolean
 *
 * @return {Boolean} value of an object/primitive, taking into account
 * the fact that in Python an empty list's/tuple's
 * boolean value is False, whereas in JS it's true
 */
var notEmpty = function (obj) {
    return !empty(obj);
};
/**
 * Return true if a value is in an array
 */
var includes = function (arr, val) {
    return notEmpty(arr) && arr.indexOf(val) !== -1;
};
//# sourceMappingURL=helpers.js.map
;// ./node_modules/rrule/dist/esm/dateutil.js

var datetime = function (y, m, d, h, i, s) {
    if (h === void 0) { h = 0; }
    if (i === void 0) { i = 0; }
    if (s === void 0) { s = 0; }
    return new Date(Date.UTC(y, m - 1, d, h, i, s));
};
/**
 * General date-related utilities.
 * Also handles several incompatibilities between JavaScript and Python
 *
 */
var MONTH_DAYS = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
/**
 * Number of milliseconds of one day
 */
var ONE_DAY = 1000 * 60 * 60 * 24;
/**
 * @see: <http://docs.python.org/library/datetime.html#datetime.MAXYEAR>
 */
var MAXYEAR = 9999;
/**
 * Python uses 1-Jan-1 as the base for calculating ordinals but we don't
 * want to confuse the JS engine with milliseconds > Number.MAX_NUMBER,
 * therefore we use 1-Jan-1970 instead
 */
var ORDINAL_BASE = datetime(1970, 1, 1);
/**
 * Python: MO-SU: 0 - 6
 * JS: SU-SAT 0 - 6
 */
var PY_WEEKDAYS = [6, 0, 1, 2, 3, 4, 5];
/**
 * py_date.timetuple()[7]
 */
var getYearDay = function (date) {
    var dateNoTime = new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
    return (Math.ceil((dateNoTime.valueOf() - new Date(date.getUTCFullYear(), 0, 1).valueOf()) /
        ONE_DAY) + 1);
};
var isLeapYear = function (year) {
    return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
};
var isDate = function (value) {
    return value instanceof Date;
};
var isValidDate = function (value) {
    return isDate(value) && !isNaN(value.getTime());
};
/**
 * @return {Number} the date's timezone offset in ms
 */
var tzOffset = function (date) {
    return date.getTimezoneOffset() * 60 * 1000;
};
/**
 * @see: <http://www.mcfedries.com/JavaScript/DaysBetween.asp>
 */
var daysBetween = function (date1, date2) {
    // The number of milliseconds in one day
    // Convert both dates to milliseconds
    var date1ms = date1.getTime();
    var date2ms = date2.getTime();
    // Calculate the difference in milliseconds
    var differencems = date1ms - date2ms;
    // Convert back to days and return
    return Math.round(differencems / ONE_DAY);
};
/**
 * @see: <http://docs.python.org/library/datetime.html#datetime.date.toordinal>
 */
var toOrdinal = function (date) {
    return daysBetween(date, ORDINAL_BASE);
};
/**
 * @see - <http://docs.python.org/library/datetime.html#datetime.date.fromordinal>
 */
var fromOrdinal = function (ordinal) {
    return new Date(ORDINAL_BASE.getTime() + ordinal * ONE_DAY);
};
var getMonthDays = function (date) {
    var month = date.getUTCMonth();
    return month === 1 && isLeapYear(date.getUTCFullYear())
        ? 29
        : MONTH_DAYS[month];
};
/**
 * @return {Number} python-like weekday
 */
var getWeekday = function (date) {
    return PY_WEEKDAYS[date.getUTCDay()];
};
/**
 * @see: <http://docs.python.org/library/calendar.html#calendar.monthrange>
 */
var monthRange = function (year, month) {
    var date = datetime(year, month + 1, 1);
    return [getWeekday(date), getMonthDays(date)];
};
/**
 * @see: <http://docs.python.org/library/datetime.html#datetime.datetime.combine>
 */
var combine = function (date, time) {
    time = time || date;
    return new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), time.getHours(), time.getMinutes(), time.getSeconds(), time.getMilliseconds()));
};
var dateutil_clone = function (date) {
    var dolly = new Date(date.getTime());
    return dolly;
};
var cloneDates = function (dates) {
    var clones = [];
    for (var i = 0; i < dates.length; i++) {
        clones.push(dateutil_clone(dates[i]));
    }
    return clones;
};
/**
 * Sorts an array of Date or Time objects
 */
var sort = function (dates) {
    dates.sort(function (a, b) {
        return a.getTime() - b.getTime();
    });
};
var timeToUntilString = function (time, utc) {
    if (utc === void 0) { utc = true; }
    var date = new Date(time);
    return [
        padStart(date.getUTCFullYear().toString(), 4, '0'),
        padStart(date.getUTCMonth() + 1, 2, '0'),
        padStart(date.getUTCDate(), 2, '0'),
        'T',
        padStart(date.getUTCHours(), 2, '0'),
        padStart(date.getUTCMinutes(), 2, '0'),
        padStart(date.getUTCSeconds(), 2, '0'),
        utc ? 'Z' : '',
    ].join('');
};
var untilStringToDate = function (until) {
    var re = /^(\d{4})(\d{2})(\d{2})(T(\d{2})(\d{2})(\d{2})Z?)?$/;
    var bits = re.exec(until);
    if (!bits)
        throw new Error("Invalid UNTIL value: ".concat(until));
    return new Date(Date.UTC(parseInt(bits[1], 10), parseInt(bits[2], 10) - 1, parseInt(bits[3], 10), parseInt(bits[5], 10) || 0, parseInt(bits[6], 10) || 0, parseInt(bits[7], 10) || 0));
};
var dateTZtoISO8601 = function (date, timeZone) {
    // date format for sv-SE is almost ISO8601
    var dateStr = date.toLocaleString('sv-SE', { timeZone: timeZone });
    // '2023-02-07 10:41:36'
    return dateStr.replace(' ', 'T') + 'Z';
};
var dateInTimeZone = function (date, timeZone) {
    var localTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    // Date constructor can only reliably parse dates in ISO8601 format
    var dateInLocalTZ = new Date(dateTZtoISO8601(date, localTimeZone));
    var dateInTargetTZ = new Date(dateTZtoISO8601(date, timeZone !== null && timeZone !== void 0 ? timeZone : 'UTC'));
    var tzOffset = dateInTargetTZ.getTime() - dateInLocalTZ.getTime();
    return new Date(date.getTime() - tzOffset);
};
//# sourceMappingURL=dateutil.js.map
;// ./node_modules/rrule/dist/esm/iterresult.js
/**
 * This class helps us to emulate python's generators, sorta.
 */
var IterResult = /** @class */ (function () {
    function IterResult(method, args) {
        this.minDate = null;
        this.maxDate = null;
        this._result = [];
        this.total = 0;
        this.method = method;
        this.args = args;
        if (method === 'between') {
            this.maxDate = args.inc
                ? args.before
                : new Date(args.before.getTime() - 1);
            this.minDate = args.inc ? args.after : new Date(args.after.getTime() + 1);
        }
        else if (method === 'before') {
            this.maxDate = args.inc ? args.dt : new Date(args.dt.getTime() - 1);
        }
        else if (method === 'after') {
            this.minDate = args.inc ? args.dt : new Date(args.dt.getTime() + 1);
        }
    }
    /**
     * Possibly adds a date into the result.
     *
     * @param {Date} date - the date isn't necessarly added to the result
     * list (if it is too late/too early)
     * @return {Boolean} true if it makes sense to continue the iteration
     * false if we're done.
     */
    IterResult.prototype.accept = function (date) {
        ++this.total;
        var tooEarly = this.minDate && date < this.minDate;
        var tooLate = this.maxDate && date > this.maxDate;
        if (this.method === 'between') {
            if (tooEarly)
                return true;
            if (tooLate)
                return false;
        }
        else if (this.method === 'before') {
            if (tooLate)
                return false;
        }
        else if (this.method === 'after') {
            if (tooEarly)
                return true;
            this.add(date);
            return false;
        }
        return this.add(date);
    };
    /**
     *
     * @param {Date} date that is part of the result.
     * @return {Boolean} whether we are interested in more values.
     */
    IterResult.prototype.add = function (date) {
        this._result.push(date);
        return true;
    };
    /**
     * 'before' and 'after' return only one date, whereas 'all'
     * and 'between' an array.
     *
     * @return {Date,Array?}
     */
    IterResult.prototype.getValue = function () {
        var res = this._result;
        switch (this.method) {
            case 'all':
            case 'between':
                return res;
            case 'before':
            case 'after':
            default:
                return (res.length ? res[res.length - 1] : null);
        }
    };
    IterResult.prototype.clone = function () {
        return new IterResult(this.method, this.args);
    };
    return IterResult;
}());
/* harmony default export */ const iterresult = (IterResult);
//# sourceMappingURL=iterresult.js.map
;// ./node_modules/tslib/tslib.es6.mjs
/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */
/* global Reflect, Promise, SuppressedError, Symbol, Iterator */

var extendStatics = function(d, b) {
  extendStatics = Object.setPrototypeOf ||
      ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
      function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
  return extendStatics(d, b);
};

function __extends(d, b) {
  if (typeof b !== "function" && b !== null)
      throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
  extendStatics(d, b);
  function __() { this.constructor = d; }
  d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
}

var __assign = function() {
  __assign = Object.assign || function __assign(t) {
      for (var s, i = 1, n = arguments.length; i < n; i++) {
          s = arguments[i];
          for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
      }
      return t;
  }
  return __assign.apply(this, arguments);
}

function __rest(s, e) {
  var t = {};
  for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
      t[p] = s[p];
  if (s != null && typeof Object.getOwnPropertySymbols === "function")
      for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
          if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i]))
              t[p[i]] = s[p[i]];
      }
  return t;
}

function __decorate(decorators, target, key, desc) {
  var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
  if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
  else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
  return c > 3 && r && Object.defineProperty(target, key, r), r;
}

function __param(paramIndex, decorator) {
  return function (target, key) { decorator(target, key, paramIndex); }
}

function __esDecorate(ctor, descriptorIn, decorators, contextIn, initializers, extraInitializers) {
  function accept(f) { if (f !== void 0 && typeof f !== "function") throw new TypeError("Function expected"); return f; }
  var kind = contextIn.kind, key = kind === "getter" ? "get" : kind === "setter" ? "set" : "value";
  var target = !descriptorIn && ctor ? contextIn["static"] ? ctor : ctor.prototype : null;
  var descriptor = descriptorIn || (target ? Object.getOwnPropertyDescriptor(target, contextIn.name) : {});
  var _, done = false;
  for (var i = decorators.length - 1; i >= 0; i--) {
      var context = {};
      for (var p in contextIn) context[p] = p === "access" ? {} : contextIn[p];
      for (var p in contextIn.access) context.access[p] = contextIn.access[p];
      context.addInitializer = function (f) { if (done) throw new TypeError("Cannot add initializers after decoration has completed"); extraInitializers.push(accept(f || null)); };
      var result = (0, decorators[i])(kind === "accessor" ? { get: descriptor.get, set: descriptor.set } : descriptor[key], context);
      if (kind === "accessor") {
          if (result === void 0) continue;
          if (result === null || typeof result !== "object") throw new TypeError("Object expected");
          if (_ = accept(result.get)) descriptor.get = _;
          if (_ = accept(result.set)) descriptor.set = _;
          if (_ = accept(result.init)) initializers.unshift(_);
      }
      else if (_ = accept(result)) {
          if (kind === "field") initializers.unshift(_);
          else descriptor[key] = _;
      }
  }
  if (target) Object.defineProperty(target, contextIn.name, descriptor);
  done = true;
};

function __runInitializers(thisArg, initializers, value) {
  var useValue = arguments.length > 2;
  for (var i = 0; i < initializers.length; i++) {
      value = useValue ? initializers[i].call(thisArg, value) : initializers[i].call(thisArg);
  }
  return useValue ? value : void 0;
};

function __propKey(x) {
  return typeof x === "symbol" ? x : "".concat(x);
};

function __setFunctionName(f, name, prefix) {
  if (typeof name === "symbol") name = name.description ? "[".concat(name.description, "]") : "";
  return Object.defineProperty(f, "name", { configurable: true, value: prefix ? "".concat(prefix, " ", name) : name });
};

function __metadata(metadataKey, metadataValue) {
  if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(metadataKey, metadataValue);
}

function __awaiter(thisArg, _arguments, P, generator) {
  function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
  return new (P || (P = Promise))(function (resolve, reject) {
      function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
      function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
      function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
      step((generator = generator.apply(thisArg, _arguments || [])).next());
  });
}

function __generator(thisArg, body) {
  var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g = Object.create((typeof Iterator === "function" ? Iterator : Object).prototype);
  return g.next = verb(0), g["throw"] = verb(1), g["return"] = verb(2), typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
  function verb(n) { return function (v) { return step([n, v]); }; }
  function step(op) {
      if (f) throw new TypeError("Generator is already executing.");
      while (g && (g = 0, op[0] && (_ = 0)), _) try {
          if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
          if (y = 0, t) op = [op[0] & 2, t.value];
          switch (op[0]) {
              case 0: case 1: t = op; break;
              case 4: _.label++; return { value: op[1], done: false };
              case 5: _.label++; y = op[1]; op = [0]; continue;
              case 7: op = _.ops.pop(); _.trys.pop(); continue;
              default:
                  if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                  if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                  if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                  if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                  if (t[2]) _.ops.pop();
                  _.trys.pop(); continue;
          }
          op = body.call(thisArg, _);
      } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
      if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
  }
}

var __createBinding = Object.create ? (function(o, m, k, k2) {
  if (k2 === undefined) k2 = k;
  var desc = Object.getOwnPropertyDescriptor(m, k);
  if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
  }
  Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
  if (k2 === undefined) k2 = k;
  o[k2] = m[k];
});

function __exportStar(m, o) {
  for (var p in m) if (p !== "default" && !Object.prototype.hasOwnProperty.call(o, p)) __createBinding(o, m, p);
}

function __values(o) {
  var s = typeof Symbol === "function" && Symbol.iterator, m = s && o[s], i = 0;
  if (m) return m.call(o);
  if (o && typeof o.length === "number") return {
      next: function () {
          if (o && i >= o.length) o = void 0;
          return { value: o && o[i++], done: !o };
      }
  };
  throw new TypeError(s ? "Object is not iterable." : "Symbol.iterator is not defined.");
}

function __read(o, n) {
  var m = typeof Symbol === "function" && o[Symbol.iterator];
  if (!m) return o;
  var i = m.call(o), r, ar = [], e;
  try {
      while ((n === void 0 || n-- > 0) && !(r = i.next()).done) ar.push(r.value);
  }
  catch (error) { e = { error: error }; }
  finally {
      try {
          if (r && !r.done && (m = i["return"])) m.call(i);
      }
      finally { if (e) throw e.error; }
  }
  return ar;
}

/** @deprecated */
function __spread() {
  for (var ar = [], i = 0; i < arguments.length; i++)
      ar = ar.concat(__read(arguments[i]));
  return ar;
}

/** @deprecated */
function __spreadArrays() {
  for (var s = 0, i = 0, il = arguments.length; i < il; i++) s += arguments[i].length;
  for (var r = Array(s), k = 0, i = 0; i < il; i++)
      for (var a = arguments[i], j = 0, jl = a.length; j < jl; j++, k++)
          r[k] = a[j];
  return r;
}

function __spreadArray(to, from, pack) {
  if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
      if (ar || !(i in from)) {
          if (!ar) ar = Array.prototype.slice.call(from, 0, i);
          ar[i] = from[i];
      }
  }
  return to.concat(ar || Array.prototype.slice.call(from));
}

function __await(v) {
  return this instanceof __await ? (this.v = v, this) : new __await(v);
}

function __asyncGenerator(thisArg, _arguments, generator) {
  if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
  var g = generator.apply(thisArg, _arguments || []), i, q = [];
  return i = Object.create((typeof AsyncIterator === "function" ? AsyncIterator : Object).prototype), verb("next"), verb("throw"), verb("return", awaitReturn), i[Symbol.asyncIterator] = function () { return this; }, i;
  function awaitReturn(f) { return function (v) { return Promise.resolve(v).then(f, reject); }; }
  function verb(n, f) { if (g[n]) { i[n] = function (v) { return new Promise(function (a, b) { q.push([n, v, a, b]) > 1 || resume(n, v); }); }; if (f) i[n] = f(i[n]); } }
  function resume(n, v) { try { step(g[n](v)); } catch (e) { settle(q[0][3], e); } }
  function step(r) { r.value instanceof __await ? Promise.resolve(r.value.v).then(fulfill, reject) : settle(q[0][2], r); }
  function fulfill(value) { resume("next", value); }
  function reject(value) { resume("throw", value); }
  function settle(f, v) { if (f(v), q.shift(), q.length) resume(q[0][0], q[0][1]); }
}

function __asyncDelegator(o) {
  var i, p;
  return i = {}, verb("next"), verb("throw", function (e) { throw e; }), verb("return"), i[Symbol.iterator] = function () { return this; }, i;
  function verb(n, f) { i[n] = o[n] ? function (v) { return (p = !p) ? { value: __await(o[n](v)), done: false } : f ? f(v) : v; } : f; }
}

function __asyncValues(o) {
  if (!Symbol.asyncIterator) throw new TypeError("Symbol.asyncIterator is not defined.");
  var m = o[Symbol.asyncIterator], i;
  return m ? m.call(o) : (o = typeof __values === "function" ? __values(o) : o[Symbol.iterator](), i = {}, verb("next"), verb("throw"), verb("return"), i[Symbol.asyncIterator] = function () { return this; }, i);
  function verb(n) { i[n] = o[n] && function (v) { return new Promise(function (resolve, reject) { v = o[n](v), settle(resolve, reject, v.done, v.value); }); }; }
  function settle(resolve, reject, d, v) { Promise.resolve(v).then(function(v) { resolve({ value: v, done: d }); }, reject); }
}

function __makeTemplateObject(cooked, raw) {
  if (Object.defineProperty) { Object.defineProperty(cooked, "raw", { value: raw }); } else { cooked.raw = raw; }
  return cooked;
};

var __setModuleDefault = Object.create ? (function(o, v) {
  Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
  o["default"] = v;
};

var ownKeys = function(o) {
  ownKeys = Object.getOwnPropertyNames || function (o) {
    var ar = [];
    for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
    return ar;
  };
  return ownKeys(o);
};

function __importStar(mod) {
  if (mod && mod.__esModule) return mod;
  var result = {};
  if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
  __setModuleDefault(result, mod);
  return result;
}

function __importDefault(mod) {
  return (mod && mod.__esModule) ? mod : { default: mod };
}

function __classPrivateFieldGet(receiver, state, kind, f) {
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
  return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
}

function __classPrivateFieldSet(receiver, state, value, kind, f) {
  if (kind === "m") throw new TypeError("Private method is not writable");
  if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a setter");
  if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot write private member to an object whose class did not declare it");
  return (kind === "a" ? f.call(receiver, value) : f ? f.value = value : state.set(receiver, value)), value;
}

function __classPrivateFieldIn(state, receiver) {
  if (receiver === null || (typeof receiver !== "object" && typeof receiver !== "function")) throw new TypeError("Cannot use 'in' operator on non-object");
  return typeof state === "function" ? receiver === state : state.has(receiver);
}

function __addDisposableResource(env, value, async) {
  if (value !== null && value !== void 0) {
    if (typeof value !== "object" && typeof value !== "function") throw new TypeError("Object expected.");
    var dispose, inner;
    if (async) {
      if (!Symbol.asyncDispose) throw new TypeError("Symbol.asyncDispose is not defined.");
      dispose = value[Symbol.asyncDispose];
    }
    if (dispose === void 0) {
      if (!Symbol.dispose) throw new TypeError("Symbol.dispose is not defined.");
      dispose = value[Symbol.dispose];
      if (async) inner = dispose;
    }
    if (typeof dispose !== "function") throw new TypeError("Object not disposable.");
    if (inner) dispose = function() { try { inner.call(this); } catch (e) { return Promise.reject(e); } };
    env.stack.push({ value: value, dispose: dispose, async: async });
  }
  else if (async) {
    env.stack.push({ async: true });
  }
  return value;
}

var _SuppressedError = typeof SuppressedError === "function" ? SuppressedError : function (error, suppressed, message) {
  var e = new Error(message);
  return e.name = "SuppressedError", e.error = error, e.suppressed = suppressed, e;
};

function __disposeResources(env) {
  function fail(e) {
    env.error = env.hasError ? new _SuppressedError(e, env.error, "An error was suppressed during disposal.") : e;
    env.hasError = true;
  }
  var r, s = 0;
  function next() {
    while (r = env.stack.pop()) {
      try {
        if (!r.async && s === 1) return s = 0, env.stack.push(r), Promise.resolve().then(next);
        if (r.dispose) {
          var result = r.dispose.call(r.value);
          if (r.async) return s |= 2, Promise.resolve(result).then(next, function(e) { fail(e); return next(); });
        }
        else s |= 1;
      }
      catch (e) {
        fail(e);
      }
    }
    if (s === 1) return env.hasError ? Promise.reject(env.error) : Promise.resolve();
    if (env.hasError) throw env.error;
  }
  return next();
}

function __rewriteRelativeImportExtension(path, preserveJsx) {
  if (typeof path === "string" && /^\.\.?\//.test(path)) {
      return path.replace(/\.(tsx)$|((?:\.d)?)((?:\.[^./]+?)?)\.([cm]?)ts$/i, function (m, tsx, d, ext, cm) {
          return tsx ? preserveJsx ? ".jsx" : ".js" : d && (!ext || !cm) ? m : (d + ext + "." + cm.toLowerCase() + "js");
      });
  }
  return path;
}

/* harmony default export */ const tslib_es6 = ({
  __extends,
  __assign,
  __rest,
  __decorate,
  __param,
  __esDecorate,
  __runInitializers,
  __propKey,
  __setFunctionName,
  __metadata,
  __awaiter,
  __generator,
  __createBinding,
  __exportStar,
  __values,
  __read,
  __spread,
  __spreadArrays,
  __spreadArray,
  __await,
  __asyncGenerator,
  __asyncDelegator,
  __asyncValues,
  __makeTemplateObject,
  __importStar,
  __importDefault,
  __classPrivateFieldGet,
  __classPrivateFieldSet,
  __classPrivateFieldIn,
  __addDisposableResource,
  __disposeResources,
  __rewriteRelativeImportExtension,
});

;// ./node_modules/rrule/dist/esm/callbackiterresult.js


/**
 * IterResult subclass that calls a callback function on each add,
 * and stops iterating when the callback returns false.
 */
var CallbackIterResult = /** @class */ (function (_super) {
    __extends(CallbackIterResult, _super);
    function CallbackIterResult(method, args, iterator) {
        var _this = _super.call(this, method, args) || this;
        _this.iterator = iterator;
        return _this;
    }
    CallbackIterResult.prototype.add = function (date) {
        if (this.iterator(date, this._result.length)) {
            this._result.push(date);
            return true;
        }
        return false;
    };
    return CallbackIterResult;
}(iterresult));
/* harmony default export */ const callbackiterresult = (CallbackIterResult);
//# sourceMappingURL=callbackiterresult.js.map
;// ./node_modules/rrule/dist/esm/nlp/i18n.js
// =============================================================================
// i18n
// =============================================================================
var ENGLISH = {
    dayNames: [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ],
    monthNames: [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    ],
    tokens: {
        SKIP: /^[ \r\n\t]+|^\.$/,
        number: /^[1-9][0-9]*/,
        numberAsText: /^(one|two|three)/i,
        every: /^every/i,
        'day(s)': /^days?/i,
        'weekday(s)': /^weekdays?/i,
        'week(s)': /^weeks?/i,
        'hour(s)': /^hours?/i,
        'minute(s)': /^minutes?/i,
        'month(s)': /^months?/i,
        'year(s)': /^years?/i,
        on: /^(on|in)/i,
        at: /^(at)/i,
        the: /^the/i,
        first: /^first/i,
        second: /^second/i,
        third: /^third/i,
        nth: /^([1-9][0-9]*)(\.|th|nd|rd|st)/i,
        last: /^last/i,
        for: /^for/i,
        'time(s)': /^times?/i,
        until: /^(un)?til/i,
        monday: /^mo(n(day)?)?/i,
        tuesday: /^tu(e(s(day)?)?)?/i,
        wednesday: /^we(d(n(esday)?)?)?/i,
        thursday: /^th(u(r(sday)?)?)?/i,
        friday: /^fr(i(day)?)?/i,
        saturday: /^sa(t(urday)?)?/i,
        sunday: /^su(n(day)?)?/i,
        january: /^jan(uary)?/i,
        february: /^feb(ruary)?/i,
        march: /^mar(ch)?/i,
        april: /^apr(il)?/i,
        may: /^may/i,
        june: /^june?/i,
        july: /^july?/i,
        august: /^aug(ust)?/i,
        september: /^sep(t(ember)?)?/i,
        october: /^oct(ober)?/i,
        november: /^nov(ember)?/i,
        december: /^dec(ember)?/i,
        comma: /^(,\s*|(and|or)\s*)+/i,
    },
};
/* harmony default export */ const i18n = (ENGLISH);
//# sourceMappingURL=i18n.js.map
;// ./node_modules/rrule/dist/esm/nlp/totext.js



// =============================================================================
// Helper functions
// =============================================================================
/**
 * Return true if a value is in an array
 */
var contains = function (arr, val) {
    return arr.indexOf(val) !== -1;
};
var defaultGetText = function (id) { return id.toString(); };
var defaultDateFormatter = function (year, month, day) { return "".concat(month, " ").concat(day, ", ").concat(year); };
/**
 *
 * @param {RRule} rrule
 * Optional:
 * @param {Function} gettext function
 * @param {Object} language definition
 * @constructor
 */
var ToText = /** @class */ (function () {
    function ToText(rrule, gettext, language, dateFormatter) {
        if (gettext === void 0) { gettext = defaultGetText; }
        if (language === void 0) { language = i18n; }
        if (dateFormatter === void 0) { dateFormatter = defaultDateFormatter; }
        this.text = [];
        this.language = language || i18n;
        this.gettext = gettext;
        this.dateFormatter = dateFormatter;
        this.rrule = rrule;
        this.options = rrule.options;
        this.origOptions = rrule.origOptions;
        if (this.origOptions.bymonthday) {
            var bymonthday = [].concat(this.options.bymonthday);
            var bynmonthday = [].concat(this.options.bynmonthday);
            bymonthday.sort(function (a, b) { return a - b; });
            bynmonthday.sort(function (a, b) { return b - a; });
            // 1, 2, 3, .., -5, -4, -3, ..
            this.bymonthday = bymonthday.concat(bynmonthday);
            if (!this.bymonthday.length)
                this.bymonthday = null;
        }
        if (isPresent(this.origOptions.byweekday)) {
            var byweekday = !isArray(this.origOptions.byweekday)
                ? [this.origOptions.byweekday]
                : this.origOptions.byweekday;
            var days = String(byweekday);
            this.byweekday = {
                allWeeks: byweekday.filter(function (weekday) {
                    return !weekday.n;
                }),
                someWeeks: byweekday.filter(function (weekday) {
                    return Boolean(weekday.n);
                }),
                isWeekdays: days.indexOf('MO') !== -1 &&
                    days.indexOf('TU') !== -1 &&
                    days.indexOf('WE') !== -1 &&
                    days.indexOf('TH') !== -1 &&
                    days.indexOf('FR') !== -1 &&
                    days.indexOf('SA') === -1 &&
                    days.indexOf('SU') === -1,
                isEveryDay: days.indexOf('MO') !== -1 &&
                    days.indexOf('TU') !== -1 &&
                    days.indexOf('WE') !== -1 &&
                    days.indexOf('TH') !== -1 &&
                    days.indexOf('FR') !== -1 &&
                    days.indexOf('SA') !== -1 &&
                    days.indexOf('SU') !== -1,
            };
            var sortWeekDays = function (a, b) {
                return a.weekday - b.weekday;
            };
            this.byweekday.allWeeks.sort(sortWeekDays);
            this.byweekday.someWeeks.sort(sortWeekDays);
            if (!this.byweekday.allWeeks.length)
                this.byweekday.allWeeks = null;
            if (!this.byweekday.someWeeks.length)
                this.byweekday.someWeeks = null;
        }
        else {
            this.byweekday = null;
        }
    }
    /**
     * Test whether the rrule can be fully converted to text.
     *
     * @param {RRule} rrule
     * @return {Boolean}
     */
    ToText.isFullyConvertible = function (rrule) {
        var canConvert = true;
        if (!(rrule.options.freq in ToText.IMPLEMENTED))
            return false;
        if (rrule.origOptions.until && rrule.origOptions.count)
            return false;
        for (var key in rrule.origOptions) {
            if (contains(['dtstart', 'tzid', 'wkst', 'freq'], key))
                return true;
            if (!contains(ToText.IMPLEMENTED[rrule.options.freq], key))
                return false;
        }
        return canConvert;
    };
    ToText.prototype.isFullyConvertible = function () {
        return ToText.isFullyConvertible(this.rrule);
    };
    /**
     * Perform the conversion. Only some of the frequencies are supported.
     * If some of the rrule's options aren't supported, they'll
     * be omitted from the output an "(~ approximate)" will be appended.
     *
     * @return {*}
     */
    ToText.prototype.toString = function () {
        var gettext = this.gettext;
        if (!(this.options.freq in ToText.IMPLEMENTED)) {
            return gettext('RRule error: Unable to fully convert this rrule to text');
        }
        this.text = [gettext('every')];
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        this[RRule.FREQUENCIES[this.options.freq]]();
        if (this.options.until) {
            this.add(gettext('until'));
            var until = this.options.until;
            this.add(this.dateFormatter(until.getUTCFullYear(), this.language.monthNames[until.getUTCMonth()], until.getUTCDate()));
        }
        else if (this.options.count) {
            this.add(gettext('for'))
                .add(this.options.count.toString())
                .add(this.plural(this.options.count) ? gettext('times') : gettext('time'));
        }
        if (!this.isFullyConvertible())
            this.add(gettext('(~ approximate)'));
        return this.text.join('');
    };
    ToText.prototype.HOURLY = function () {
        var gettext = this.gettext;
        if (this.options.interval !== 1)
            this.add(this.options.interval.toString());
        this.add(this.plural(this.options.interval) ? gettext('hours') : gettext('hour'));
    };
    ToText.prototype.MINUTELY = function () {
        var gettext = this.gettext;
        if (this.options.interval !== 1)
            this.add(this.options.interval.toString());
        this.add(this.plural(this.options.interval)
            ? gettext('minutes')
            : gettext('minute'));
    };
    ToText.prototype.DAILY = function () {
        var gettext = this.gettext;
        if (this.options.interval !== 1)
            this.add(this.options.interval.toString());
        if (this.byweekday && this.byweekday.isWeekdays) {
            this.add(this.plural(this.options.interval)
                ? gettext('weekdays')
                : gettext('weekday'));
        }
        else {
            this.add(this.plural(this.options.interval) ? gettext('days') : gettext('day'));
        }
        if (this.origOptions.bymonth) {
            this.add(gettext('in'));
            this._bymonth();
        }
        if (this.bymonthday) {
            this._bymonthday();
        }
        else if (this.byweekday) {
            this._byweekday();
        }
        else if (this.origOptions.byhour) {
            this._byhour();
        }
    };
    ToText.prototype.WEEKLY = function () {
        var gettext = this.gettext;
        if (this.options.interval !== 1) {
            this.add(this.options.interval.toString()).add(this.plural(this.options.interval) ? gettext('weeks') : gettext('week'));
        }
        if (this.byweekday && this.byweekday.isWeekdays) {
            if (this.options.interval === 1) {
                this.add(this.plural(this.options.interval)
                    ? gettext('weekdays')
                    : gettext('weekday'));
            }
            else {
                this.add(gettext('on')).add(gettext('weekdays'));
            }
        }
        else if (this.byweekday && this.byweekday.isEveryDay) {
            this.add(this.plural(this.options.interval) ? gettext('days') : gettext('day'));
        }
        else {
            if (this.options.interval === 1)
                this.add(gettext('week'));
            if (this.origOptions.bymonth) {
                this.add(gettext('in'));
                this._bymonth();
            }
            if (this.bymonthday) {
                this._bymonthday();
            }
            else if (this.byweekday) {
                this._byweekday();
            }
            if (this.origOptions.byhour) {
                this._byhour();
            }
        }
    };
    ToText.prototype.MONTHLY = function () {
        var gettext = this.gettext;
        if (this.origOptions.bymonth) {
            if (this.options.interval !== 1) {
                this.add(this.options.interval.toString()).add(gettext('months'));
                if (this.plural(this.options.interval))
                    this.add(gettext('in'));
            }
            else {
                // this.add(gettext('MONTH'))
            }
            this._bymonth();
        }
        else {
            if (this.options.interval !== 1) {
                this.add(this.options.interval.toString());
            }
            this.add(this.plural(this.options.interval)
                ? gettext('months')
                : gettext('month'));
        }
        if (this.bymonthday) {
            this._bymonthday();
        }
        else if (this.byweekday && this.byweekday.isWeekdays) {
            this.add(gettext('on')).add(gettext('weekdays'));
        }
        else if (this.byweekday) {
            this._byweekday();
        }
    };
    ToText.prototype.YEARLY = function () {
        var gettext = this.gettext;
        if (this.origOptions.bymonth) {
            if (this.options.interval !== 1) {
                this.add(this.options.interval.toString());
                this.add(gettext('years'));
            }
            else {
                // this.add(gettext('YEAR'))
            }
            this._bymonth();
        }
        else {
            if (this.options.interval !== 1) {
                this.add(this.options.interval.toString());
            }
            this.add(this.plural(this.options.interval) ? gettext('years') : gettext('year'));
        }
        if (this.bymonthday) {
            this._bymonthday();
        }
        else if (this.byweekday) {
            this._byweekday();
        }
        if (this.options.byyearday) {
            this.add(gettext('on the'))
                .add(this.list(this.options.byyearday, this.nth, gettext('and')))
                .add(gettext('day'));
        }
        if (this.options.byweekno) {
            this.add(gettext('in'))
                .add(this.plural(this.options.byweekno.length)
                ? gettext('weeks')
                : gettext('week'))
                .add(this.list(this.options.byweekno, undefined, gettext('and')));
        }
    };
    ToText.prototype._bymonthday = function () {
        var gettext = this.gettext;
        if (this.byweekday && this.byweekday.allWeeks) {
            this.add(gettext('on'))
                .add(this.list(this.byweekday.allWeeks, this.weekdaytext, gettext('or')))
                .add(gettext('the'))
                .add(this.list(this.bymonthday, this.nth, gettext('or')));
        }
        else {
            this.add(gettext('on the')).add(this.list(this.bymonthday, this.nth, gettext('and')));
        }
        // this.add(gettext('DAY'))
    };
    ToText.prototype._byweekday = function () {
        var gettext = this.gettext;
        if (this.byweekday.allWeeks && !this.byweekday.isWeekdays) {
            this.add(gettext('on')).add(this.list(this.byweekday.allWeeks, this.weekdaytext));
        }
        if (this.byweekday.someWeeks) {
            if (this.byweekday.allWeeks)
                this.add(gettext('and'));
            this.add(gettext('on the')).add(this.list(this.byweekday.someWeeks, this.weekdaytext, gettext('and')));
        }
    };
    ToText.prototype._byhour = function () {
        var gettext = this.gettext;
        this.add(gettext('at')).add(this.list(this.origOptions.byhour, undefined, gettext('and')));
    };
    ToText.prototype._bymonth = function () {
        this.add(this.list(this.options.bymonth, this.monthtext, this.gettext('and')));
    };
    ToText.prototype.nth = function (n) {
        n = parseInt(n.toString(), 10);
        var nth;
        var gettext = this.gettext;
        if (n === -1)
            return gettext('last');
        var npos = Math.abs(n);
        switch (npos) {
            case 1:
            case 21:
            case 31:
                nth = npos + gettext('st');
                break;
            case 2:
            case 22:
                nth = npos + gettext('nd');
                break;
            case 3:
            case 23:
                nth = npos + gettext('rd');
                break;
            default:
                nth = npos + gettext('th');
        }
        return n < 0 ? nth + ' ' + gettext('last') : nth;
    };
    ToText.prototype.monthtext = function (m) {
        return this.language.monthNames[m - 1];
    };
    ToText.prototype.weekdaytext = function (wday) {
        var weekday = isNumber(wday) ? (wday + 1) % 7 : wday.getJsWeekday();
        return ((wday.n ? this.nth(wday.n) + ' ' : '') +
            this.language.dayNames[weekday]);
    };
    ToText.prototype.plural = function (n) {
        return n % 100 !== 1;
    };
    ToText.prototype.add = function (s) {
        this.text.push(' ');
        this.text.push(s);
        return this;
    };
    ToText.prototype.list = function (arr, callback, finalDelim, delim) {
        var _this = this;
        if (delim === void 0) { delim = ','; }
        if (!isArray(arr)) {
            arr = [arr];
        }
        var delimJoin = function (array, delimiter, finalDelimiter) {
            var list = '';
            for (var i = 0; i < array.length; i++) {
                if (i !== 0) {
                    if (i === array.length - 1) {
                        list += ' ' + finalDelimiter + ' ';
                    }
                    else {
                        list += delimiter + ' ';
                    }
                }
                list += array[i];
            }
            return list;
        };
        callback =
            callback ||
                function (o) {
                    return o.toString();
                };
        var realCallback = function (arg) {
            return callback && callback.call(_this, arg);
        };
        if (finalDelim) {
            return delimJoin(arr.map(realCallback), delim, finalDelim);
        }
        else {
            return arr.map(realCallback).join(delim + ' ');
        }
    };
    return ToText;
}());
/* harmony default export */ const totext = (ToText);
//# sourceMappingURL=totext.js.map
;// ./node_modules/rrule/dist/esm/nlp/parsetext.js


// =============================================================================
// Parser
// =============================================================================
var Parser = /** @class */ (function () {
    function Parser(rules) {
        this.done = true;
        this.rules = rules;
    }
    Parser.prototype.start = function (text) {
        this.text = text;
        this.done = false;
        return this.nextSymbol();
    };
    Parser.prototype.isDone = function () {
        return this.done && this.symbol === null;
    };
    Parser.prototype.nextSymbol = function () {
        var best;
        var bestSymbol;
        this.symbol = null;
        this.value = null;
        do {
            if (this.done)
                return false;
            var rule = void 0;
            best = null;
            for (var name_1 in this.rules) {
                rule = this.rules[name_1];
                var match = rule.exec(this.text);
                if (match) {
                    if (best === null || match[0].length > best[0].length) {
                        best = match;
                        bestSymbol = name_1;
                    }
                }
            }
            if (best != null) {
                this.text = this.text.substr(best[0].length);
                if (this.text === '')
                    this.done = true;
            }
            if (best == null) {
                this.done = true;
                this.symbol = null;
                this.value = null;
                return;
            }
        } while (bestSymbol === 'SKIP');
        this.symbol = bestSymbol;
        this.value = best;
        return true;
    };
    Parser.prototype.accept = function (name) {
        if (this.symbol === name) {
            if (this.value) {
                var v = this.value;
                this.nextSymbol();
                return v;
            }
            this.nextSymbol();
            return true;
        }
        return false;
    };
    Parser.prototype.acceptNumber = function () {
        return this.accept('number');
    };
    Parser.prototype.expect = function (name) {
        if (this.accept(name))
            return true;
        throw new Error('expected ' + name + ' but found ' + this.symbol);
    };
    return Parser;
}());
function parseText(text, language) {
    if (language === void 0) { language = i18n; }
    var options = {};
    var ttr = new Parser(language.tokens);
    if (!ttr.start(text))
        return null;
    S();
    return options;
    function S() {
        // every [n]
        ttr.expect('every');
        var n = ttr.acceptNumber();
        if (n)
            options.interval = parseInt(n[0], 10);
        if (ttr.isDone())
            throw new Error('Unexpected end');
        switch (ttr.symbol) {
            case 'day(s)':
                options.freq = RRule.DAILY;
                if (ttr.nextSymbol()) {
                    AT();
                    F();
                }
                break;
            // FIXME Note: every 2 weekdays != every two weeks on weekdays.
            // DAILY on weekdays is not a valid rule
            case 'weekday(s)':
                options.freq = RRule.WEEKLY;
                options.byweekday = [RRule.MO, RRule.TU, RRule.WE, RRule.TH, RRule.FR];
                ttr.nextSymbol();
                AT();
                F();
                break;
            case 'week(s)':
                options.freq = RRule.WEEKLY;
                if (ttr.nextSymbol()) {
                    ON();
                    AT();
                    F();
                }
                break;
            case 'hour(s)':
                options.freq = RRule.HOURLY;
                if (ttr.nextSymbol()) {
                    ON();
                    F();
                }
                break;
            case 'minute(s)':
                options.freq = RRule.MINUTELY;
                if (ttr.nextSymbol()) {
                    ON();
                    F();
                }
                break;
            case 'month(s)':
                options.freq = RRule.MONTHLY;
                if (ttr.nextSymbol()) {
                    ON();
                    F();
                }
                break;
            case 'year(s)':
                options.freq = RRule.YEARLY;
                if (ttr.nextSymbol()) {
                    ON();
                    F();
                }
                break;
            case 'monday':
            case 'tuesday':
            case 'wednesday':
            case 'thursday':
            case 'friday':
            case 'saturday':
            case 'sunday':
                options.freq = RRule.WEEKLY;
                var key = ttr.symbol
                    .substr(0, 2)
                    .toUpperCase();
                options.byweekday = [RRule[key]];
                if (!ttr.nextSymbol())
                    return;
                // TODO check for duplicates
                while (ttr.accept('comma')) {
                    if (ttr.isDone())
                        throw new Error('Unexpected end');
                    var wkd = decodeWKD();
                    if (!wkd) {
                        throw new Error('Unexpected symbol ' + ttr.symbol + ', expected weekday');
                    }
                    options.byweekday.push(RRule[wkd]);
                    ttr.nextSymbol();
                }
                AT();
                MDAYs();
                F();
                break;
            case 'january':
            case 'february':
            case 'march':
            case 'april':
            case 'may':
            case 'june':
            case 'july':
            case 'august':
            case 'september':
            case 'october':
            case 'november':
            case 'december':
                options.freq = RRule.YEARLY;
                options.bymonth = [decodeM()];
                if (!ttr.nextSymbol())
                    return;
                // TODO check for duplicates
                while (ttr.accept('comma')) {
                    if (ttr.isDone())
                        throw new Error('Unexpected end');
                    var m = decodeM();
                    if (!m) {
                        throw new Error('Unexpected symbol ' + ttr.symbol + ', expected month');
                    }
                    options.bymonth.push(m);
                    ttr.nextSymbol();
                }
                ON();
                F();
                break;
            default:
                throw new Error('Unknown symbol');
        }
    }
    function ON() {
        var on = ttr.accept('on');
        var the = ttr.accept('the');
        if (!(on || the))
            return;
        do {
            var nth = decodeNTH();
            var wkd = decodeWKD();
            var m = decodeM();
            // nth <weekday> | <weekday>
            if (nth) {
                // ttr.nextSymbol()
                if (wkd) {
                    ttr.nextSymbol();
                    if (!options.byweekday)
                        options.byweekday = [];
                    options.byweekday.push(RRule[wkd].nth(nth));
                }
                else {
                    if (!options.bymonthday)
                        options.bymonthday = [];
                    options.bymonthday.push(nth);
                    ttr.accept('day(s)');
                }
                // <weekday>
            }
            else if (wkd) {
                ttr.nextSymbol();
                if (!options.byweekday)
                    options.byweekday = [];
                options.byweekday.push(RRule[wkd]);
            }
            else if (ttr.symbol === 'weekday(s)') {
                ttr.nextSymbol();
                if (!options.byweekday) {
                    options.byweekday = [RRule.MO, RRule.TU, RRule.WE, RRule.TH, RRule.FR];
                }
            }
            else if (ttr.symbol === 'week(s)') {
                ttr.nextSymbol();
                var n = ttr.acceptNumber();
                if (!n) {
                    throw new Error('Unexpected symbol ' + ttr.symbol + ', expected week number');
                }
                options.byweekno = [parseInt(n[0], 10)];
                while (ttr.accept('comma')) {
                    n = ttr.acceptNumber();
                    if (!n) {
                        throw new Error('Unexpected symbol ' + ttr.symbol + '; expected monthday');
                    }
                    options.byweekno.push(parseInt(n[0], 10));
                }
            }
            else if (m) {
                ttr.nextSymbol();
                if (!options.bymonth)
                    options.bymonth = [];
                options.bymonth.push(m);
            }
            else {
                return;
            }
        } while (ttr.accept('comma') || ttr.accept('the') || ttr.accept('on'));
    }
    function AT() {
        var at = ttr.accept('at');
        if (!at)
            return;
        do {
            var n = ttr.acceptNumber();
            if (!n) {
                throw new Error('Unexpected symbol ' + ttr.symbol + ', expected hour');
            }
            options.byhour = [parseInt(n[0], 10)];
            while (ttr.accept('comma')) {
                n = ttr.acceptNumber();
                if (!n) {
                    throw new Error('Unexpected symbol ' + ttr.symbol + '; expected hour');
                }
                options.byhour.push(parseInt(n[0], 10));
            }
        } while (ttr.accept('comma') || ttr.accept('at'));
    }
    function decodeM() {
        switch (ttr.symbol) {
            case 'january':
                return 1;
            case 'february':
                return 2;
            case 'march':
                return 3;
            case 'april':
                return 4;
            case 'may':
                return 5;
            case 'june':
                return 6;
            case 'july':
                return 7;
            case 'august':
                return 8;
            case 'september':
                return 9;
            case 'october':
                return 10;
            case 'november':
                return 11;
            case 'december':
                return 12;
            default:
                return false;
        }
    }
    function decodeWKD() {
        switch (ttr.symbol) {
            case 'monday':
            case 'tuesday':
            case 'wednesday':
            case 'thursday':
            case 'friday':
            case 'saturday':
            case 'sunday':
                return ttr.symbol.substr(0, 2).toUpperCase();
            default:
                return false;
        }
    }
    function decodeNTH() {
        switch (ttr.symbol) {
            case 'last':
                ttr.nextSymbol();
                return -1;
            case 'first':
                ttr.nextSymbol();
                return 1;
            case 'second':
                ttr.nextSymbol();
                return ttr.accept('last') ? -2 : 2;
            case 'third':
                ttr.nextSymbol();
                return ttr.accept('last') ? -3 : 3;
            case 'nth':
                var v = parseInt(ttr.value[1], 10);
                if (v < -366 || v > 366)
                    throw new Error('Nth out of range: ' + v);
                ttr.nextSymbol();
                return ttr.accept('last') ? -v : v;
            default:
                return false;
        }
    }
    function MDAYs() {
        ttr.accept('on');
        ttr.accept('the');
        var nth = decodeNTH();
        if (!nth)
            return;
        options.bymonthday = [nth];
        ttr.nextSymbol();
        while (ttr.accept('comma')) {
            nth = decodeNTH();
            if (!nth) {
                throw new Error('Unexpected symbol ' + ttr.symbol + '; expected monthday');
            }
            options.bymonthday.push(nth);
            ttr.nextSymbol();
        }
    }
    function F() {
        if (ttr.symbol === 'until') {
            var date = Date.parse(ttr.text);
            if (!date)
                throw new Error('Cannot parse until date:' + ttr.text);
            options.until = new Date(date);
        }
        else if (ttr.accept('for')) {
            options.count = parseInt(ttr.value[0], 10);
            ttr.expect('number');
            // ttr.expect('times')
        }
    }
}
//# sourceMappingURL=parsetext.js.map
;// ./node_modules/rrule/dist/esm/types.js
var Frequency;
(function (Frequency) {
    Frequency[Frequency["YEARLY"] = 0] = "YEARLY";
    Frequency[Frequency["MONTHLY"] = 1] = "MONTHLY";
    Frequency[Frequency["WEEKLY"] = 2] = "WEEKLY";
    Frequency[Frequency["DAILY"] = 3] = "DAILY";
    Frequency[Frequency["HOURLY"] = 4] = "HOURLY";
    Frequency[Frequency["MINUTELY"] = 5] = "MINUTELY";
    Frequency[Frequency["SECONDLY"] = 6] = "SECONDLY";
})(Frequency || (Frequency = {}));
function freqIsDailyOrGreater(freq) {
    return freq < Frequency.HOURLY;
}
//# sourceMappingURL=types.js.map
;// ./node_modules/rrule/dist/esm/nlp/index.js





/* !
 * rrule.js - Library for working with recurrence rules for calendar dates.
 * https://github.com/jakubroztocil/rrule
 *
 * Copyright 2010, Jakub Roztocil and Lars Schoning
 * Licenced under the BSD licence.
 * https://github.com/jakubroztocil/rrule/blob/master/LICENCE
 *
 */
/**
 *
 * Implementation of RRule.fromText() and RRule::toText().
 *
 *
 * On the client side, this file needs to be included
 * when those functions are used.
 *
 */
// =============================================================================
// fromText
// =============================================================================
/**
 * Will be able to convert some of the below described rules from
 * text format to a rule object.
 *
 *
 * RULES
 *
 * Every ([n])
 * day(s)
 * | [weekday], ..., (and) [weekday]
 * | weekday(s)
 * | week(s)
 * | month(s)
 * | [month], ..., (and) [month]
 * | year(s)
 *
 *
 * Plus 0, 1, or multiple of these:
 *
 * on [weekday], ..., (or) [weekday] the [monthday], [monthday], ... (or) [monthday]
 *
 * on [weekday], ..., (and) [weekday]
 *
 * on the [monthday], [monthday], ... (and) [monthday] (day of the month)
 *
 * on the [nth-weekday], ..., (and) [nth-weekday] (of the month/year)
 *
 *
 * Plus 0 or 1 of these:
 *
 * for [n] time(s)
 *
 * until [date]
 *
 * Plus (.)
 *
 *
 * Definitely no supported for parsing:
 *
 * (for year):
 * in week(s) [n], ..., (and) [n]
 *
 * on the [yearday], ..., (and) [n] day of the year
 * on day [yearday], ..., (and) [n]
 *
 *
 * NON-TERMINALS
 *
 * [n]: 1, 2 ..., one, two, three ..
 * [month]: January, February, March, April, May, ... December
 * [weekday]: Monday, ... Sunday
 * [nth-weekday]: first [weekday], 2nd [weekday], ... last [weekday], ...
 * [monthday]: first, 1., 2., 1st, 2nd, second, ... 31st, last day, 2nd last day, ..
 * [date]:
 * - [month] (0-31(,) ([year])),
 * - (the) 0-31.(1-12.([year])),
 * - (the) 0-31/(1-12/([year])),
 * - [weekday]
 *
 * [year]: 0000, 0001, ... 01, 02, ..
 *
 * Definitely not supported for parsing:
 *
 * [yearday]: first, 1., 2., 1st, 2nd, second, ... 366th, last day, 2nd last day, ..
 *
 * @param {String} text
 * @return {Object, Boolean} the rule, or null.
 */
var fromText = function (text, language) {
    if (language === void 0) { language = i18n; }
    return new RRule(parseText(text, language) || undefined);
};
var common = [
    'count',
    'until',
    'interval',
    'byweekday',
    'bymonthday',
    'bymonth',
];
totext.IMPLEMENTED = [];
totext.IMPLEMENTED[Frequency.HOURLY] = common;
totext.IMPLEMENTED[Frequency.MINUTELY] = common;
totext.IMPLEMENTED[Frequency.DAILY] = ['byhour'].concat(common);
totext.IMPLEMENTED[Frequency.WEEKLY] = common;
totext.IMPLEMENTED[Frequency.MONTHLY] = common;
totext.IMPLEMENTED[Frequency.YEARLY] = ['byweekno', 'byyearday'].concat(common);
// =============================================================================
// Export
// =============================================================================
var toText = function (rrule, gettext, language, dateFormatter) {
    return new totext(rrule, gettext, language, dateFormatter).toString();
};
var isFullyConvertible = totext.isFullyConvertible;

//# sourceMappingURL=index.js.map
;// ./node_modules/rrule/dist/esm/datetime.js




var Time = /** @class */ (function () {
    function Time(hour, minute, second, millisecond) {
        this.hour = hour;
        this.minute = minute;
        this.second = second;
        this.millisecond = millisecond || 0;
    }
    Time.prototype.getHours = function () {
        return this.hour;
    };
    Time.prototype.getMinutes = function () {
        return this.minute;
    };
    Time.prototype.getSeconds = function () {
        return this.second;
    };
    Time.prototype.getMilliseconds = function () {
        return this.millisecond;
    };
    Time.prototype.getTime = function () {
        return ((this.hour * 60 * 60 + this.minute * 60 + this.second) * 1000 +
            this.millisecond);
    };
    return Time;
}());

var DateTime = /** @class */ (function (_super) {
    __extends(DateTime, _super);
    function DateTime(year, month, day, hour, minute, second, millisecond) {
        var _this = _super.call(this, hour, minute, second, millisecond) || this;
        _this.year = year;
        _this.month = month;
        _this.day = day;
        return _this;
    }
    DateTime.fromDate = function (date) {
        return new this(date.getUTCFullYear(), date.getUTCMonth() + 1, date.getUTCDate(), date.getUTCHours(), date.getUTCMinutes(), date.getUTCSeconds(), date.valueOf() % 1000);
    };
    DateTime.prototype.getWeekday = function () {
        return getWeekday(new Date(this.getTime()));
    };
    DateTime.prototype.getTime = function () {
        return new Date(Date.UTC(this.year, this.month - 1, this.day, this.hour, this.minute, this.second, this.millisecond)).getTime();
    };
    DateTime.prototype.getDay = function () {
        return this.day;
    };
    DateTime.prototype.getMonth = function () {
        return this.month;
    };
    DateTime.prototype.getYear = function () {
        return this.year;
    };
    DateTime.prototype.addYears = function (years) {
        this.year += years;
    };
    DateTime.prototype.addMonths = function (months) {
        this.month += months;
        if (this.month > 12) {
            var yearDiv = Math.floor(this.month / 12);
            var monthMod = pymod(this.month, 12);
            this.month = monthMod;
            this.year += yearDiv;
            if (this.month === 0) {
                this.month = 12;
                --this.year;
            }
        }
    };
    DateTime.prototype.addWeekly = function (days, wkst) {
        if (wkst > this.getWeekday()) {
            this.day += -(this.getWeekday() + 1 + (6 - wkst)) + days * 7;
        }
        else {
            this.day += -(this.getWeekday() - wkst) + days * 7;
        }
        this.fixDay();
    };
    DateTime.prototype.addDaily = function (days) {
        this.day += days;
        this.fixDay();
    };
    DateTime.prototype.addHours = function (hours, filtered, byhour) {
        if (filtered) {
            // Jump to one iteration before next day
            this.hour += Math.floor((23 - this.hour) / hours) * hours;
        }
        for (;;) {
            this.hour += hours;
            var _a = divmod(this.hour, 24), dayDiv = _a.div, hourMod = _a.mod;
            if (dayDiv) {
                this.hour = hourMod;
                this.addDaily(dayDiv);
            }
            if (empty(byhour) || includes(byhour, this.hour))
                break;
        }
    };
    DateTime.prototype.addMinutes = function (minutes, filtered, byhour, byminute) {
        if (filtered) {
            // Jump to one iteration before next day
            this.minute +=
                Math.floor((1439 - (this.hour * 60 + this.minute)) / minutes) * minutes;
        }
        for (;;) {
            this.minute += minutes;
            var _a = divmod(this.minute, 60), hourDiv = _a.div, minuteMod = _a.mod;
            if (hourDiv) {
                this.minute = minuteMod;
                this.addHours(hourDiv, false, byhour);
            }
            if ((empty(byhour) || includes(byhour, this.hour)) &&
                (empty(byminute) || includes(byminute, this.minute))) {
                break;
            }
        }
    };
    DateTime.prototype.addSeconds = function (seconds, filtered, byhour, byminute, bysecond) {
        if (filtered) {
            // Jump to one iteration before next day
            this.second +=
                Math.floor((86399 - (this.hour * 3600 + this.minute * 60 + this.second)) /
                    seconds) * seconds;
        }
        for (;;) {
            this.second += seconds;
            var _a = divmod(this.second, 60), minuteDiv = _a.div, secondMod = _a.mod;
            if (minuteDiv) {
                this.second = secondMod;
                this.addMinutes(minuteDiv, false, byhour, byminute);
            }
            if ((empty(byhour) || includes(byhour, this.hour)) &&
                (empty(byminute) || includes(byminute, this.minute)) &&
                (empty(bysecond) || includes(bysecond, this.second))) {
                break;
            }
        }
    };
    DateTime.prototype.fixDay = function () {
        if (this.day <= 28) {
            return;
        }
        var daysinmonth = monthRange(this.year, this.month - 1)[1];
        if (this.day <= daysinmonth) {
            return;
        }
        while (this.day > daysinmonth) {
            this.day -= daysinmonth;
            ++this.month;
            if (this.month === 13) {
                this.month = 1;
                ++this.year;
                if (this.year > MAXYEAR) {
                    return;
                }
            }
            daysinmonth = monthRange(this.year, this.month - 1)[1];
        }
    };
    DateTime.prototype.add = function (options, filtered) {
        var freq = options.freq, interval = options.interval, wkst = options.wkst, byhour = options.byhour, byminute = options.byminute, bysecond = options.bysecond;
        switch (freq) {
            case Frequency.YEARLY:
                return this.addYears(interval);
            case Frequency.MONTHLY:
                return this.addMonths(interval);
            case Frequency.WEEKLY:
                return this.addWeekly(interval, wkst);
            case Frequency.DAILY:
                return this.addDaily(interval);
            case Frequency.HOURLY:
                return this.addHours(interval, filtered, byhour);
            case Frequency.MINUTELY:
                return this.addMinutes(interval, filtered, byhour, byminute);
            case Frequency.SECONDLY:
                return this.addSeconds(interval, filtered, byhour, byminute, bysecond);
        }
    };
    return DateTime;
}(Time));

//# sourceMappingURL=datetime.js.map
;// ./node_modules/rrule/dist/esm/parseoptions.js







function initializeOptions(options) {
    var invalid = [];
    var keys = Object.keys(options);
    // Shallow copy for options and origOptions and check for invalid
    for (var _i = 0, keys_1 = keys; _i < keys_1.length; _i++) {
        var key = keys_1[_i];
        if (!includes(defaultKeys, key))
            invalid.push(key);
        if (isDate(options[key]) && !isValidDate(options[key])) {
            invalid.push(key);
        }
    }
    if (invalid.length) {
        throw new Error('Invalid options: ' + invalid.join(', '));
    }
    return __assign({}, options);
}
function parseOptions(options) {
    var opts = __assign(__assign({}, DEFAULT_OPTIONS), initializeOptions(options));
    if (isPresent(opts.byeaster))
        opts.freq = RRule.YEARLY;
    if (!(isPresent(opts.freq) && RRule.FREQUENCIES[opts.freq])) {
        throw new Error("Invalid frequency: ".concat(opts.freq, " ").concat(options.freq));
    }
    if (!opts.dtstart)
        opts.dtstart = new Date(new Date().setMilliseconds(0));
    if (!isPresent(opts.wkst)) {
        opts.wkst = RRule.MO.weekday;
    }
    else if (isNumber(opts.wkst)) {
        // cool, just keep it like that
    }
    else {
        opts.wkst = opts.wkst.weekday;
    }
    if (isPresent(opts.bysetpos)) {
        if (isNumber(opts.bysetpos))
            opts.bysetpos = [opts.bysetpos];
        for (var i = 0; i < opts.bysetpos.length; i++) {
            var v = opts.bysetpos[i];
            if (v === 0 || !(v >= -366 && v <= 366)) {
                throw new Error('bysetpos must be between 1 and 366,' + ' or between -366 and -1');
            }
        }
    }
    if (!(Boolean(opts.byweekno) ||
        notEmpty(opts.byweekno) ||
        notEmpty(opts.byyearday) ||
        Boolean(opts.bymonthday) ||
        notEmpty(opts.bymonthday) ||
        isPresent(opts.byweekday) ||
        isPresent(opts.byeaster))) {
        switch (opts.freq) {
            case RRule.YEARLY:
                if (!opts.bymonth)
                    opts.bymonth = opts.dtstart.getUTCMonth() + 1;
                opts.bymonthday = opts.dtstart.getUTCDate();
                break;
            case RRule.MONTHLY:
                opts.bymonthday = opts.dtstart.getUTCDate();
                break;
            case RRule.WEEKLY:
                opts.byweekday = [getWeekday(opts.dtstart)];
                break;
        }
    }
    // bymonth
    if (isPresent(opts.bymonth) && !isArray(opts.bymonth)) {
        opts.bymonth = [opts.bymonth];
    }
    // byyearday
    if (isPresent(opts.byyearday) &&
        !isArray(opts.byyearday) &&
        isNumber(opts.byyearday)) {
        opts.byyearday = [opts.byyearday];
    }
    // bymonthday
    if (!isPresent(opts.bymonthday)) {
        opts.bymonthday = [];
        opts.bynmonthday = [];
    }
    else if (isArray(opts.bymonthday)) {
        var bymonthday = [];
        var bynmonthday = [];
        for (var i = 0; i < opts.bymonthday.length; i++) {
            var v = opts.bymonthday[i];
            if (v > 0) {
                bymonthday.push(v);
            }
            else if (v < 0) {
                bynmonthday.push(v);
            }
        }
        opts.bymonthday = bymonthday;
        opts.bynmonthday = bynmonthday;
    }
    else if (opts.bymonthday < 0) {
        opts.bynmonthday = [opts.bymonthday];
        opts.bymonthday = [];
    }
    else {
        opts.bynmonthday = [];
        opts.bymonthday = [opts.bymonthday];
    }
    // byweekno
    if (isPresent(opts.byweekno) && !isArray(opts.byweekno)) {
        opts.byweekno = [opts.byweekno];
    }
    // byweekday / bynweekday
    if (!isPresent(opts.byweekday)) {
        opts.bynweekday = null;
    }
    else if (isNumber(opts.byweekday)) {
        opts.byweekday = [opts.byweekday];
        opts.bynweekday = null;
    }
    else if (isWeekdayStr(opts.byweekday)) {
        opts.byweekday = [Weekday.fromStr(opts.byweekday).weekday];
        opts.bynweekday = null;
    }
    else if (opts.byweekday instanceof Weekday) {
        if (!opts.byweekday.n || opts.freq > RRule.MONTHLY) {
            opts.byweekday = [opts.byweekday.weekday];
            opts.bynweekday = null;
        }
        else {
            opts.bynweekday = [[opts.byweekday.weekday, opts.byweekday.n]];
            opts.byweekday = null;
        }
    }
    else {
        var byweekday = [];
        var bynweekday = [];
        for (var i = 0; i < opts.byweekday.length; i++) {
            var wday = opts.byweekday[i];
            if (isNumber(wday)) {
                byweekday.push(wday);
                continue;
            }
            else if (isWeekdayStr(wday)) {
                byweekday.push(Weekday.fromStr(wday).weekday);
                continue;
            }
            if (!wday.n || opts.freq > RRule.MONTHLY) {
                byweekday.push(wday.weekday);
            }
            else {
                bynweekday.push([wday.weekday, wday.n]);
            }
        }
        opts.byweekday = notEmpty(byweekday) ? byweekday : null;
        opts.bynweekday = notEmpty(bynweekday) ? bynweekday : null;
    }
    // byhour
    if (!isPresent(opts.byhour)) {
        opts.byhour = opts.freq < RRule.HOURLY ? [opts.dtstart.getUTCHours()] : null;
    }
    else if (isNumber(opts.byhour)) {
        opts.byhour = [opts.byhour];
    }
    // byminute
    if (!isPresent(opts.byminute)) {
        opts.byminute =
            opts.freq < RRule.MINUTELY ? [opts.dtstart.getUTCMinutes()] : null;
    }
    else if (isNumber(opts.byminute)) {
        opts.byminute = [opts.byminute];
    }
    // bysecond
    if (!isPresent(opts.bysecond)) {
        opts.bysecond =
            opts.freq < RRule.SECONDLY ? [opts.dtstart.getUTCSeconds()] : null;
    }
    else if (isNumber(opts.bysecond)) {
        opts.bysecond = [opts.bysecond];
    }
    return { parsedOptions: opts };
}
function buildTimeset(opts) {
    var millisecondModulo = opts.dtstart.getTime() % 1000;
    if (!freqIsDailyOrGreater(opts.freq)) {
        return [];
    }
    var timeset = [];
    opts.byhour.forEach(function (hour) {
        opts.byminute.forEach(function (minute) {
            opts.bysecond.forEach(function (second) {
                timeset.push(new Time(hour, minute, second, millisecondModulo));
            });
        });
    });
    return timeset;
}
//# sourceMappingURL=parseoptions.js.map
;// ./node_modules/rrule/dist/esm/parsestring.js





function parseString(rfcString) {
    var options = rfcString
        .split('\n')
        .map(parseLine)
        .filter(function (x) { return x !== null; });
    return __assign(__assign({}, options[0]), options[1]);
}
function parseDtstart(line) {
    var options = {};
    var dtstartWithZone = /DTSTART(?:;TZID=([^:=]+?))?(?::|=)([^;\s]+)/i.exec(line);
    if (!dtstartWithZone) {
        return options;
    }
    var tzid = dtstartWithZone[1], dtstart = dtstartWithZone[2];
    if (tzid) {
        options.tzid = tzid;
    }
    options.dtstart = untilStringToDate(dtstart);
    return options;
}
function parseLine(rfcString) {
    rfcString = rfcString.replace(/^\s+|\s+$/, '');
    if (!rfcString.length)
        return null;
    var header = /^([A-Z]+?)[:;]/.exec(rfcString.toUpperCase());
    if (!header) {
        return parseRrule(rfcString);
    }
    var key = header[1];
    switch (key.toUpperCase()) {
        case 'RRULE':
        case 'EXRULE':
            return parseRrule(rfcString);
        case 'DTSTART':
            return parseDtstart(rfcString);
        default:
            throw new Error("Unsupported RFC prop ".concat(key, " in ").concat(rfcString));
    }
}
function parseRrule(line) {
    var strippedLine = line.replace(/^RRULE:/i, '');
    var options = parseDtstart(strippedLine);
    var attrs = line.replace(/^(?:RRULE|EXRULE):/i, '').split(';');
    attrs.forEach(function (attr) {
        var _a = attr.split('='), key = _a[0], value = _a[1];
        switch (key.toUpperCase()) {
            case 'FREQ':
                options.freq = Frequency[value.toUpperCase()];
                break;
            case 'WKST':
                options.wkst = Days[value.toUpperCase()];
                break;
            case 'COUNT':
            case 'INTERVAL':
            case 'BYSETPOS':
            case 'BYMONTH':
            case 'BYMONTHDAY':
            case 'BYYEARDAY':
            case 'BYWEEKNO':
            case 'BYHOUR':
            case 'BYMINUTE':
            case 'BYSECOND':
                var num = parseNumber(value);
                var optionKey = key.toLowerCase();
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                options[optionKey] = num;
                break;
            case 'BYWEEKDAY':
            case 'BYDAY':
                options.byweekday = parseWeekday(value);
                break;
            case 'DTSTART':
            case 'TZID':
                // for backwards compatibility
                var dtstart = parseDtstart(line);
                options.tzid = dtstart.tzid;
                options.dtstart = dtstart.dtstart;
                break;
            case 'UNTIL':
                options.until = untilStringToDate(value);
                break;
            case 'BYEASTER':
                options.byeaster = Number(value);
                break;
            default:
                throw new Error("Unknown RRULE property '" + key + "'");
        }
    });
    return options;
}
function parseNumber(value) {
    if (value.indexOf(',') !== -1) {
        var values = value.split(',');
        return values.map(parseIndividualNumber);
    }
    return parseIndividualNumber(value);
}
function parseIndividualNumber(value) {
    if (/^[+-]?\d+$/.test(value)) {
        return Number(value);
    }
    return value;
}
function parseWeekday(value) {
    var days = value.split(',');
    return days.map(function (day) {
        if (day.length === 2) {
            // MO, TU, ...
            return Days[day]; // wday instanceof Weekday
        }
        // -1MO, +3FR, 1SO, 13TU ...
        var parts = day.match(/^([+-]?\d{1,2})([A-Z]{2})$/);
        if (!parts || parts.length < 3) {
            throw new SyntaxError("Invalid weekday string: ".concat(day));
        }
        var n = Number(parts[1]);
        var wdaypart = parts[2];
        var wday = Days[wdaypart].weekday;
        return new Weekday(wday, n);
    });
}
//# sourceMappingURL=parsestring.js.map
;// ./node_modules/rrule/dist/esm/datewithzone.js

var DateWithZone = /** @class */ (function () {
    function DateWithZone(date, tzid) {
        if (isNaN(date.getTime())) {
            throw new RangeError('Invalid date passed to DateWithZone');
        }
        this.date = date;
        this.tzid = tzid;
    }
    Object.defineProperty(DateWithZone.prototype, "isUTC", {
        get: function () {
            return !this.tzid || this.tzid.toUpperCase() === 'UTC';
        },
        enumerable: false,
        configurable: true
    });
    DateWithZone.prototype.toString = function () {
        var datestr = timeToUntilString(this.date.getTime(), this.isUTC);
        if (!this.isUTC) {
            return ";TZID=".concat(this.tzid, ":").concat(datestr);
        }
        return ":".concat(datestr);
    };
    DateWithZone.prototype.getTime = function () {
        return this.date.getTime();
    };
    DateWithZone.prototype.rezonedDate = function () {
        if (this.isUTC) {
            return this.date;
        }
        return dateInTimeZone(this.date, this.tzid);
    };
    return DateWithZone;
}());

//# sourceMappingURL=datewithzone.js.map
;// ./node_modules/rrule/dist/esm/optionstostring.js





function optionsToString(options) {
    var rrule = [];
    var dtstart = '';
    var keys = Object.keys(options);
    var defaultKeys = Object.keys(DEFAULT_OPTIONS);
    for (var i = 0; i < keys.length; i++) {
        if (keys[i] === 'tzid')
            continue;
        if (!includes(defaultKeys, keys[i]))
            continue;
        var key = keys[i].toUpperCase();
        var value = options[keys[i]];
        var outValue = '';
        if (!isPresent(value) || (isArray(value) && !value.length))
            continue;
        switch (key) {
            case 'FREQ':
                outValue = RRule.FREQUENCIES[options.freq];
                break;
            case 'WKST':
                if (isNumber(value)) {
                    outValue = new Weekday(value).toString();
                }
                else {
                    outValue = value.toString();
                }
                break;
            case 'BYWEEKDAY':
                /*
                  NOTE: BYWEEKDAY is a special case.
                  RRule() deconstructs the rule.options.byweekday array
                  into an array of Weekday arguments.
                  On the other hand, rule.origOptions is an array of Weekdays.
                  We need to handle both cases here.
                  It might be worth change RRule to keep the Weekdays.
        
                  Also, BYWEEKDAY (used by RRule) vs. BYDAY (RFC)
        
                  */
                key = 'BYDAY';
                outValue = toArray(value)
                    .map(function (wday) {
                    if (wday instanceof Weekday) {
                        return wday;
                    }
                    if (isArray(wday)) {
                        return new Weekday(wday[0], wday[1]);
                    }
                    return new Weekday(wday);
                })
                    .toString();
                break;
            case 'DTSTART':
                dtstart = buildDtstart(value, options.tzid);
                break;
            case 'UNTIL':
                outValue = timeToUntilString(value, !options.tzid);
                break;
            default:
                if (isArray(value)) {
                    var strValues = [];
                    for (var j = 0; j < value.length; j++) {
                        strValues[j] = String(value[j]);
                    }
                    outValue = strValues.toString();
                }
                else {
                    outValue = String(value);
                }
        }
        if (outValue) {
            rrule.push([key, outValue]);
        }
    }
    var rules = rrule
        .map(function (_a) {
        var key = _a[0], value = _a[1];
        return "".concat(key, "=").concat(value.toString());
    })
        .join(';');
    var ruleString = '';
    if (rules !== '') {
        ruleString = "RRULE:".concat(rules);
    }
    return [dtstart, ruleString].filter(function (x) { return !!x; }).join('\n');
}
function buildDtstart(dtstart, tzid) {
    if (!dtstart) {
        return '';
    }
    return 'DTSTART' + new DateWithZone(new Date(dtstart), tzid).toString();
}
//# sourceMappingURL=optionstostring.js.map
;// ./node_modules/rrule/dist/esm/cache.js



function argsMatch(left, right) {
    if (Array.isArray(left)) {
        if (!Array.isArray(right))
            return false;
        if (left.length !== right.length)
            return false;
        return left.every(function (date, i) { return date.getTime() === right[i].getTime(); });
    }
    if (left instanceof Date) {
        return right instanceof Date && left.getTime() === right.getTime();
    }
    return left === right;
}
var Cache = /** @class */ (function () {
    function Cache() {
        this.all = false;
        this.before = [];
        this.after = [];
        this.between = [];
    }
    /**
     * @param {String} what - all/before/after/between
     * @param {Array,Date} value - an array of dates, one date, or null
     * @param {Object?} args - _iter arguments
     */
    Cache.prototype._cacheAdd = function (what, value, args) {
        if (value) {
            value = value instanceof Date ? dateutil_clone(value) : cloneDates(value);
        }
        if (what === 'all') {
            this.all = value;
        }
        else {
            args._value = value;
            this[what].push(args);
        }
    };
    /**
     * @return false - not in the cache
     * @return null  - cached, but zero occurrences (before/after)
     * @return Date  - cached (before/after)
     * @return []    - cached, but zero occurrences (all/between)
     * @return [Date1, DateN] - cached (all/between)
     */
    Cache.prototype._cacheGet = function (what, args) {
        var cached = false;
        var argsKeys = args ? Object.keys(args) : [];
        var findCacheDiff = function (item) {
            for (var i = 0; i < argsKeys.length; i++) {
                var key = argsKeys[i];
                if (!argsMatch(args[key], item[key])) {
                    return true;
                }
            }
            return false;
        };
        var cachedObject = this[what];
        if (what === 'all') {
            cached = this.all;
        }
        else if (isArray(cachedObject)) {
            // Let's see whether we've already called the
            // 'what' method with the same 'args'
            for (var i = 0; i < cachedObject.length; i++) {
                var item = cachedObject[i];
                if (argsKeys.length && findCacheDiff(item))
                    continue;
                cached = item._value;
                break;
            }
        }
        if (!cached && this.all) {
            // Not in the cache, but we already know all the occurrences,
            // so we can find the correct dates from the cached ones.
            var iterResult = new iterresult(what, args);
            for (var i = 0; i < this.all.length; i++) {
                if (!iterResult.accept(this.all[i]))
                    break;
            }
            cached = iterResult.getValue();
            this._cacheAdd(what, cached, args);
        }
        return isArray(cached)
            ? cloneDates(cached)
            : cached instanceof Date
                ? dateutil_clone(cached)
                : cached;
    };
    return Cache;
}());

//# sourceMappingURL=cache.js.map
;// ./node_modules/rrule/dist/esm/masks.js


// =============================================================================
// Date masks
// =============================================================================
// Every mask is 7 days longer to handle cross-year weekly periods.
var M365MASK = __spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray([], repeat(1, 31), true), repeat(2, 28), true), repeat(3, 31), true), repeat(4, 30), true), repeat(5, 31), true), repeat(6, 30), true), repeat(7, 31), true), repeat(8, 31), true), repeat(9, 30), true), repeat(10, 31), true), repeat(11, 30), true), repeat(12, 31), true), repeat(1, 7), true);
var M366MASK = __spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray([], repeat(1, 31), true), repeat(2, 29), true), repeat(3, 31), true), repeat(4, 30), true), repeat(5, 31), true), repeat(6, 30), true), repeat(7, 31), true), repeat(8, 31), true), repeat(9, 30), true), repeat(10, 31), true), repeat(11, 30), true), repeat(12, 31), true), repeat(1, 7), true);
var M28 = range(1, 29);
var M29 = range(1, 30);
var M30 = range(1, 31);
var M31 = range(1, 32);
var MDAY366MASK = __spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray([], M31, true), M29, true), M31, true), M30, true), M31, true), M30, true), M31, true), M31, true), M30, true), M31, true), M30, true), M31, true), M31.slice(0, 7), true);
var MDAY365MASK = __spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray([], M31, true), M28, true), M31, true), M30, true), M31, true), M30, true), M31, true), M31, true), M30, true), M31, true), M30, true), M31, true), M31.slice(0, 7), true);
var NM28 = range(-28, 0);
var NM29 = range(-29, 0);
var NM30 = range(-30, 0);
var NM31 = range(-31, 0);
var NMDAY366MASK = __spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray([], NM31, true), NM29, true), NM31, true), NM30, true), NM31, true), NM30, true), NM31, true), NM31, true), NM30, true), NM31, true), NM30, true), NM31, true), NM31.slice(0, 7), true);
var NMDAY365MASK = __spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray(__spreadArray([], NM31, true), NM28, true), NM31, true), NM30, true), NM31, true), NM30, true), NM31, true), NM31, true), NM30, true), NM31, true), NM30, true), NM31, true), NM31.slice(0, 7), true);
var M366RANGE = [0, 31, 60, 91, 121, 152, 182, 213, 244, 274, 305, 335, 366];
var M365RANGE = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334, 365];
var WDAYMASK = (function () {
    var wdaymask = [];
    for (var i = 0; i < 55; i++)
        wdaymask = wdaymask.concat(range(7));
    return wdaymask;
})();

//# sourceMappingURL=masks.js.map
;// ./node_modules/rrule/dist/esm/iterinfo/yearinfo.js




function rebuildYear(year, options) {
    var firstyday = datetime(year, 1, 1);
    var yearlen = isLeapYear(year) ? 366 : 365;
    var nextyearlen = isLeapYear(year + 1) ? 366 : 365;
    var yearordinal = toOrdinal(firstyday);
    var yearweekday = getWeekday(firstyday);
    var result = __assign(__assign({ yearlen: yearlen, nextyearlen: nextyearlen, yearordinal: yearordinal, yearweekday: yearweekday }, baseYearMasks(year)), { wnomask: null });
    if (empty(options.byweekno)) {
        return result;
    }
    result.wnomask = repeat(0, yearlen + 7);
    var firstwkst;
    var wyearlen;
    var no1wkst = (firstwkst = pymod(7 - yearweekday + options.wkst, 7));
    if (no1wkst >= 4) {
        no1wkst = 0;
        // Number of days in the year, plus the days we got
        // from last year.
        wyearlen = result.yearlen + pymod(yearweekday - options.wkst, 7);
    }
    else {
        // Number of days in the year, minus the days we
        // left in last year.
        wyearlen = yearlen - no1wkst;
    }
    var div = Math.floor(wyearlen / 7);
    var mod = pymod(wyearlen, 7);
    var numweeks = Math.floor(div + mod / 4);
    for (var j = 0; j < options.byweekno.length; j++) {
        var n = options.byweekno[j];
        if (n < 0) {
            n += numweeks + 1;
        }
        if (!(n > 0 && n <= numweeks)) {
            continue;
        }
        var i = void 0;
        if (n > 1) {
            i = no1wkst + (n - 1) * 7;
            if (no1wkst !== firstwkst) {
                i -= 7 - firstwkst;
            }
        }
        else {
            i = no1wkst;
        }
        for (var k = 0; k < 7; k++) {
            result.wnomask[i] = 1;
            i++;
            if (result.wdaymask[i] === options.wkst)
                break;
        }
    }
    if (includes(options.byweekno, 1)) {
        // Check week number 1 of next year as well
        // orig-TODO : Check -numweeks for next year.
        var i = no1wkst + numweeks * 7;
        if (no1wkst !== firstwkst)
            i -= 7 - firstwkst;
        if (i < yearlen) {
            // If week starts in next year, we
            // don't care about it.
            for (var j = 0; j < 7; j++) {
                result.wnomask[i] = 1;
                i += 1;
                if (result.wdaymask[i] === options.wkst)
                    break;
            }
        }
    }
    if (no1wkst) {
        // Check last week number of last year as
        // well. If no1wkst is 0, either the year
        // started on week start, or week number 1
        // got days from last year, so there are no
        // days from last year's last week number in
        // this year.
        var lnumweeks = void 0;
        if (!includes(options.byweekno, -1)) {
            var lyearweekday = getWeekday(datetime(year - 1, 1, 1));
            var lno1wkst = pymod(7 - lyearweekday.valueOf() + options.wkst, 7);
            var lyearlen = isLeapYear(year - 1) ? 366 : 365;
            var weekst = void 0;
            if (lno1wkst >= 4) {
                lno1wkst = 0;
                weekst = lyearlen + pymod(lyearweekday - options.wkst, 7);
            }
            else {
                weekst = yearlen - no1wkst;
            }
            lnumweeks = Math.floor(52 + pymod(weekst, 7) / 4);
        }
        else {
            lnumweeks = -1;
        }
        if (includes(options.byweekno, lnumweeks)) {
            for (var i = 0; i < no1wkst; i++)
                result.wnomask[i] = 1;
        }
    }
    return result;
}
function baseYearMasks(year) {
    var yearlen = isLeapYear(year) ? 366 : 365;
    var firstyday = datetime(year, 1, 1);
    var wday = getWeekday(firstyday);
    if (yearlen === 365) {
        return {
            mmask: M365MASK,
            mdaymask: MDAY365MASK,
            nmdaymask: NMDAY365MASK,
            wdaymask: WDAYMASK.slice(wday),
            mrange: M365RANGE,
        };
    }
    return {
        mmask: M366MASK,
        mdaymask: MDAY366MASK,
        nmdaymask: NMDAY366MASK,
        wdaymask: WDAYMASK.slice(wday),
        mrange: M366RANGE,
    };
}
//# sourceMappingURL=yearinfo.js.map
;// ./node_modules/rrule/dist/esm/iterinfo/monthinfo.js


function rebuildMonth(year, month, yearlen, mrange, wdaymask, options) {
    var result = {
        lastyear: year,
        lastmonth: month,
        nwdaymask: [],
    };
    var ranges = [];
    if (options.freq === RRule.YEARLY) {
        if (empty(options.bymonth)) {
            ranges = [[0, yearlen]];
        }
        else {
            for (var j = 0; j < options.bymonth.length; j++) {
                month = options.bymonth[j];
                ranges.push(mrange.slice(month - 1, month + 1));
            }
        }
    }
    else if (options.freq === RRule.MONTHLY) {
        ranges = [mrange.slice(month - 1, month + 1)];
    }
    if (empty(ranges)) {
        return result;
    }
    // Weekly frequency won't get here, so we may not
    // care about cross-year weekly periods.
    result.nwdaymask = repeat(0, yearlen);
    for (var j = 0; j < ranges.length; j++) {
        var rang = ranges[j];
        var first = rang[0];
        var last = rang[1] - 1;
        for (var k = 0; k < options.bynweekday.length; k++) {
            var i = void 0;
            var _a = options.bynweekday[k], wday = _a[0], n = _a[1];
            if (n < 0) {
                i = last + (n + 1) * 7;
                i -= pymod(wdaymask[i] - wday, 7);
            }
            else {
                i = first + (n - 1) * 7;
                i += pymod(7 - wdaymask[i] + wday, 7);
            }
            if (first <= i && i <= last)
                result.nwdaymask[i] = 1;
        }
    }
    return result;
}
//# sourceMappingURL=monthinfo.js.map
;// ./node_modules/rrule/dist/esm/iterinfo/easter.js
function easter(y, offset) {
    if (offset === void 0) { offset = 0; }
    var a = y % 19;
    var b = Math.floor(y / 100);
    var c = y % 100;
    var d = Math.floor(b / 4);
    var e = b % 4;
    var f = Math.floor((b + 8) / 25);
    var g = Math.floor((b - f + 1) / 3);
    var h = Math.floor(19 * a + b - d - g + 15) % 30;
    var i = Math.floor(c / 4);
    var k = c % 4;
    var l = Math.floor(32 + 2 * e + 2 * i - h - k) % 7;
    var m = Math.floor((a + 11 * h + 22 * l) / 451);
    var month = Math.floor((h + l - 7 * m + 114) / 31);
    var day = ((h + l - 7 * m + 114) % 31) + 1;
    var date = Date.UTC(y, month - 1, day + offset);
    var yearStart = Date.UTC(y, 0, 1);
    return [Math.ceil((date - yearStart) / (1000 * 60 * 60 * 24))];
}
//# sourceMappingURL=easter.js.map
;// ./node_modules/rrule/dist/esm/iterinfo/index.js







// =============================================================================
// Iterinfo
// =============================================================================
var Iterinfo = /** @class */ (function () {
    // eslint-disable-next-line no-empty-function
    function Iterinfo(options) {
        this.options = options;
    }
    Iterinfo.prototype.rebuild = function (year, month) {
        var options = this.options;
        if (year !== this.lastyear) {
            this.yearinfo = rebuildYear(year, options);
        }
        if (notEmpty(options.bynweekday) &&
            (month !== this.lastmonth || year !== this.lastyear)) {
            var _a = this.yearinfo, yearlen = _a.yearlen, mrange = _a.mrange, wdaymask = _a.wdaymask;
            this.monthinfo = rebuildMonth(year, month, yearlen, mrange, wdaymask, options);
        }
        if (isPresent(options.byeaster)) {
            this.eastermask = easter(year, options.byeaster);
        }
    };
    Object.defineProperty(Iterinfo.prototype, "lastyear", {
        get: function () {
            return this.monthinfo ? this.monthinfo.lastyear : null;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "lastmonth", {
        get: function () {
            return this.monthinfo ? this.monthinfo.lastmonth : null;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "yearlen", {
        get: function () {
            return this.yearinfo.yearlen;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "yearordinal", {
        get: function () {
            return this.yearinfo.yearordinal;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "mrange", {
        get: function () {
            return this.yearinfo.mrange;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "wdaymask", {
        get: function () {
            return this.yearinfo.wdaymask;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "mmask", {
        get: function () {
            return this.yearinfo.mmask;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "wnomask", {
        get: function () {
            return this.yearinfo.wnomask;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "nwdaymask", {
        get: function () {
            return this.monthinfo ? this.monthinfo.nwdaymask : [];
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "nextyearlen", {
        get: function () {
            return this.yearinfo.nextyearlen;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "mdaymask", {
        get: function () {
            return this.yearinfo.mdaymask;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(Iterinfo.prototype, "nmdaymask", {
        get: function () {
            return this.yearinfo.nmdaymask;
        },
        enumerable: false,
        configurable: true
    });
    Iterinfo.prototype.ydayset = function () {
        return [range(this.yearlen), 0, this.yearlen];
    };
    Iterinfo.prototype.mdayset = function (_, month) {
        var start = this.mrange[month - 1];
        var end = this.mrange[month];
        var set = repeat(null, this.yearlen);
        for (var i = start; i < end; i++)
            set[i] = i;
        return [set, start, end];
    };
    Iterinfo.prototype.wdayset = function (year, month, day) {
        // We need to handle cross-year weeks here.
        var set = repeat(null, this.yearlen + 7);
        var i = toOrdinal(datetime(year, month, day)) - this.yearordinal;
        var start = i;
        for (var j = 0; j < 7; j++) {
            set[i] = i;
            ++i;
            if (this.wdaymask[i] === this.options.wkst)
                break;
        }
        return [set, start, i];
    };
    Iterinfo.prototype.ddayset = function (year, month, day) {
        var set = repeat(null, this.yearlen);
        var i = toOrdinal(datetime(year, month, day)) - this.yearordinal;
        set[i] = i;
        return [set, i, i + 1];
    };
    Iterinfo.prototype.htimeset = function (hour, _, second, millisecond) {
        var _this = this;
        var set = [];
        this.options.byminute.forEach(function (minute) {
            set = set.concat(_this.mtimeset(hour, minute, second, millisecond));
        });
        sort(set);
        return set;
    };
    Iterinfo.prototype.mtimeset = function (hour, minute, _, millisecond) {
        var set = this.options.bysecond.map(function (second) { return new Time(hour, minute, second, millisecond); });
        sort(set);
        return set;
    };
    Iterinfo.prototype.stimeset = function (hour, minute, second, millisecond) {
        return [new Time(hour, minute, second, millisecond)];
    };
    Iterinfo.prototype.getdayset = function (freq) {
        switch (freq) {
            case Frequency.YEARLY:
                return this.ydayset.bind(this);
            case Frequency.MONTHLY:
                return this.mdayset.bind(this);
            case Frequency.WEEKLY:
                return this.wdayset.bind(this);
            case Frequency.DAILY:
                return this.ddayset.bind(this);
            default:
                return this.ddayset.bind(this);
        }
    };
    Iterinfo.prototype.gettimeset = function (freq) {
        switch (freq) {
            case Frequency.HOURLY:
                return this.htimeset.bind(this);
            case Frequency.MINUTELY:
                return this.mtimeset.bind(this);
            case Frequency.SECONDLY:
                return this.stimeset.bind(this);
        }
    };
    return Iterinfo;
}());
/* harmony default export */ const iterinfo = (Iterinfo);
//# sourceMappingURL=index.js.map
;// ./node_modules/rrule/dist/esm/iter/poslist.js


function buildPoslist(bysetpos, timeset, start, end, ii, dayset) {
    var poslist = [];
    for (var j = 0; j < bysetpos.length; j++) {
        var daypos = void 0;
        var timepos = void 0;
        var pos = bysetpos[j];
        if (pos < 0) {
            daypos = Math.floor(pos / timeset.length);
            timepos = pymod(pos, timeset.length);
        }
        else {
            daypos = Math.floor((pos - 1) / timeset.length);
            timepos = pymod(pos - 1, timeset.length);
        }
        var tmp = [];
        for (var k = start; k < end; k++) {
            var val = dayset[k];
            if (!isPresent(val))
                continue;
            tmp.push(val);
        }
        var i = void 0;
        if (daypos < 0) {
            i = tmp.slice(daypos)[0];
        }
        else {
            i = tmp[daypos];
        }
        var time = timeset[timepos];
        var date = fromOrdinal(ii.yearordinal + i);
        var res = combine(date, time);
        // XXX: can this ever be in the array?
        // - compare the actual date instead?
        if (!includes(poslist, res))
            poslist.push(res);
    }
    sort(poslist);
    return poslist;
}
//# sourceMappingURL=poslist.js.map
;// ./node_modules/rrule/dist/esm/iter/index.js









function iter(iterResult, options) {
    var dtstart = options.dtstart, freq = options.freq, interval = options.interval, until = options.until, bysetpos = options.bysetpos;
    var count = options.count;
    if (count === 0 || interval === 0) {
        return emitResult(iterResult);
    }
    var counterDate = DateTime.fromDate(dtstart);
    var ii = new iterinfo(options);
    ii.rebuild(counterDate.year, counterDate.month);
    var timeset = makeTimeset(ii, counterDate, options);
    for (;;) {
        var _a = ii.getdayset(freq)(counterDate.year, counterDate.month, counterDate.day), dayset = _a[0], start = _a[1], end = _a[2];
        var filtered = removeFilteredDays(dayset, start, end, ii, options);
        if (notEmpty(bysetpos)) {
            var poslist = buildPoslist(bysetpos, timeset, start, end, ii, dayset);
            for (var j = 0; j < poslist.length; j++) {
                var res = poslist[j];
                if (until && res > until) {
                    return emitResult(iterResult);
                }
                if (res >= dtstart) {
                    var rezonedDate = rezoneIfNeeded(res, options);
                    if (!iterResult.accept(rezonedDate)) {
                        return emitResult(iterResult);
                    }
                    if (count) {
                        --count;
                        if (!count) {
                            return emitResult(iterResult);
                        }
                    }
                }
            }
        }
        else {
            for (var j = start; j < end; j++) {
                var currentDay = dayset[j];
                if (!isPresent(currentDay)) {
                    continue;
                }
                var date = fromOrdinal(ii.yearordinal + currentDay);
                for (var k = 0; k < timeset.length; k++) {
                    var time = timeset[k];
                    var res = combine(date, time);
                    if (until && res > until) {
                        return emitResult(iterResult);
                    }
                    if (res >= dtstart) {
                        var rezonedDate = rezoneIfNeeded(res, options);
                        if (!iterResult.accept(rezonedDate)) {
                            return emitResult(iterResult);
                        }
                        if (count) {
                            --count;
                            if (!count) {
                                return emitResult(iterResult);
                            }
                        }
                    }
                }
            }
        }
        if (options.interval === 0) {
            return emitResult(iterResult);
        }
        // Handle frequency and interval
        counterDate.add(options, filtered);
        if (counterDate.year > MAXYEAR) {
            return emitResult(iterResult);
        }
        if (!freqIsDailyOrGreater(freq)) {
            timeset = ii.gettimeset(freq)(counterDate.hour, counterDate.minute, counterDate.second, 0);
        }
        ii.rebuild(counterDate.year, counterDate.month);
    }
}
function isFiltered(ii, currentDay, options) {
    var bymonth = options.bymonth, byweekno = options.byweekno, byweekday = options.byweekday, byeaster = options.byeaster, bymonthday = options.bymonthday, bynmonthday = options.bynmonthday, byyearday = options.byyearday;
    return ((notEmpty(bymonth) && !includes(bymonth, ii.mmask[currentDay])) ||
        (notEmpty(byweekno) && !ii.wnomask[currentDay]) ||
        (notEmpty(byweekday) && !includes(byweekday, ii.wdaymask[currentDay])) ||
        (notEmpty(ii.nwdaymask) && !ii.nwdaymask[currentDay]) ||
        (byeaster !== null && !includes(ii.eastermask, currentDay)) ||
        ((notEmpty(bymonthday) || notEmpty(bynmonthday)) &&
            !includes(bymonthday, ii.mdaymask[currentDay]) &&
            !includes(bynmonthday, ii.nmdaymask[currentDay])) ||
        (notEmpty(byyearday) &&
            ((currentDay < ii.yearlen &&
                !includes(byyearday, currentDay + 1) &&
                !includes(byyearday, -ii.yearlen + currentDay)) ||
                (currentDay >= ii.yearlen &&
                    !includes(byyearday, currentDay + 1 - ii.yearlen) &&
                    !includes(byyearday, -ii.nextyearlen + currentDay - ii.yearlen)))));
}
function rezoneIfNeeded(date, options) {
    return new DateWithZone(date, options.tzid).rezonedDate();
}
function emitResult(iterResult) {
    return iterResult.getValue();
}
function removeFilteredDays(dayset, start, end, ii, options) {
    var filtered = false;
    for (var dayCounter = start; dayCounter < end; dayCounter++) {
        var currentDay = dayset[dayCounter];
        filtered = isFiltered(ii, currentDay, options);
        if (filtered)
            dayset[currentDay] = null;
    }
    return filtered;
}
function makeTimeset(ii, counterDate, options) {
    var freq = options.freq, byhour = options.byhour, byminute = options.byminute, bysecond = options.bysecond;
    if (freqIsDailyOrGreater(freq)) {
        return buildTimeset(options);
    }
    if ((freq >= RRule.HOURLY &&
        notEmpty(byhour) &&
        !includes(byhour, counterDate.hour)) ||
        (freq >= RRule.MINUTELY &&
            notEmpty(byminute) &&
            !includes(byminute, counterDate.minute)) ||
        (freq >= RRule.SECONDLY &&
            notEmpty(bysecond) &&
            !includes(bysecond, counterDate.second))) {
        return [];
    }
    return ii.gettimeset(freq)(counterDate.hour, counterDate.minute, counterDate.second, counterDate.millisecond);
}
//# sourceMappingURL=index.js.map
;// ./node_modules/rrule/dist/esm/rrule.js











// =============================================================================
// RRule
// =============================================================================
var Days = {
    MO: new Weekday(0),
    TU: new Weekday(1),
    WE: new Weekday(2),
    TH: new Weekday(3),
    FR: new Weekday(4),
    SA: new Weekday(5),
    SU: new Weekday(6),
};
var DEFAULT_OPTIONS = {
    freq: Frequency.YEARLY,
    dtstart: null,
    interval: 1,
    wkst: Days.MO,
    count: null,
    until: null,
    tzid: null,
    bysetpos: null,
    bymonth: null,
    bymonthday: null,
    bynmonthday: null,
    byyearday: null,
    byweekno: null,
    byweekday: null,
    bynweekday: null,
    byhour: null,
    byminute: null,
    bysecond: null,
    byeaster: null,
};
var defaultKeys = Object.keys(DEFAULT_OPTIONS);
/**
 *
 * @param {Options?} options - see <http://labix.org/python-dateutil/#head-cf004ee9a75592797e076752b2a889c10f445418>
 * - The only required option is `freq`, one of RRule.YEARLY, RRule.MONTHLY, ...
 * @constructor
 */
var RRule = /** @class */ (function () {
    function RRule(options, noCache) {
        if (options === void 0) { options = {}; }
        if (noCache === void 0) { noCache = false; }
        // RFC string
        this._cache = noCache ? null : new Cache();
        // used by toString()
        this.origOptions = initializeOptions(options);
        var parsedOptions = parseOptions(options).parsedOptions;
        this.options = parsedOptions;
    }
    RRule.parseText = function (text, language) {
        return parseText(text, language);
    };
    RRule.fromText = function (text, language) {
        return fromText(text, language);
    };
    RRule.fromString = function (str) {
        return new RRule(RRule.parseString(str) || undefined);
    };
    RRule.prototype._iter = function (iterResult) {
        return iter(iterResult, this.options);
    };
    RRule.prototype._cacheGet = function (what, args) {
        if (!this._cache)
            return false;
        return this._cache._cacheGet(what, args);
    };
    RRule.prototype._cacheAdd = function (what, value, args) {
        if (!this._cache)
            return;
        return this._cache._cacheAdd(what, value, args);
    };
    /**
     * @param {Function} iterator - optional function that will be called
     * on each date that is added. It can return false
     * to stop the iteration.
     * @return Array containing all recurrences.
     */
    RRule.prototype.all = function (iterator) {
        if (iterator) {
            return this._iter(new callbackiterresult('all', {}, iterator));
        }
        var result = this._cacheGet('all');
        if (result === false) {
            result = this._iter(new iterresult('all', {}));
            this._cacheAdd('all', result);
        }
        return result;
    };
    /**
     * Returns all the occurrences of the rrule between after and before.
     * The inc keyword defines what happens if after and/or before are
     * themselves occurrences. With inc == True, they will be included in the
     * list, if they are found in the recurrence set.
     *
     * @return Array
     */
    RRule.prototype.between = function (after, before, inc, iterator) {
        if (inc === void 0) { inc = false; }
        if (!isValidDate(after) || !isValidDate(before)) {
            throw new Error('Invalid date passed in to RRule.between');
        }
        var args = {
            before: before,
            after: after,
            inc: inc,
        };
        if (iterator) {
            return this._iter(new callbackiterresult('between', args, iterator));
        }
        var result = this._cacheGet('between', args);
        if (result === false) {
            result = this._iter(new iterresult('between', args));
            this._cacheAdd('between', result, args);
        }
        return result;
    };
    /**
     * Returns the last recurrence before the given datetime instance.
     * The inc keyword defines what happens if dt is an occurrence.
     * With inc == True, if dt itself is an occurrence, it will be returned.
     *
     * @return Date or null
     */
    RRule.prototype.before = function (dt, inc) {
        if (inc === void 0) { inc = false; }
        if (!isValidDate(dt)) {
            throw new Error('Invalid date passed in to RRule.before');
        }
        var args = { dt: dt, inc: inc };
        var result = this._cacheGet('before', args);
        if (result === false) {
            result = this._iter(new iterresult('before', args));
            this._cacheAdd('before', result, args);
        }
        return result;
    };
    /**
     * Returns the first recurrence after the given datetime instance.
     * The inc keyword defines what happens if dt is an occurrence.
     * With inc == True, if dt itself is an occurrence, it will be returned.
     *
     * @return Date or null
     */
    RRule.prototype.after = function (dt, inc) {
        if (inc === void 0) { inc = false; }
        if (!isValidDate(dt)) {
            throw new Error('Invalid date passed in to RRule.after');
        }
        var args = { dt: dt, inc: inc };
        var result = this._cacheGet('after', args);
        if (result === false) {
            result = this._iter(new iterresult('after', args));
            this._cacheAdd('after', result, args);
        }
        return result;
    };
    /**
     * Returns the number of recurrences in this set. It will have go trough
     * the whole recurrence, if this hasn't been done before.
     */
    RRule.prototype.count = function () {
        return this.all().length;
    };
    /**
     * Converts the rrule into its string representation
     *
     * @see <http://www.ietf.org/rfc/rfc2445.txt>
     * @return String
     */
    RRule.prototype.toString = function () {
        return optionsToString(this.origOptions);
    };
    /**
     * Will convert all rules described in nlp:ToText
     * to text.
     */
    RRule.prototype.toText = function (gettext, language, dateFormatter) {
        return toText(this, gettext, language, dateFormatter);
    };
    RRule.prototype.isFullyConvertibleToText = function () {
        return isFullyConvertible(this);
    };
    /**
     * @return a RRule instance with the same freq and options
     * as this one (cache is not cloned)
     */
    RRule.prototype.clone = function () {
        return new RRule(this.origOptions);
    };
    // RRule class 'constants'
    RRule.FREQUENCIES = [
        'YEARLY',
        'MONTHLY',
        'WEEKLY',
        'DAILY',
        'HOURLY',
        'MINUTELY',
        'SECONDLY',
    ];
    RRule.YEARLY = Frequency.YEARLY;
    RRule.MONTHLY = Frequency.MONTHLY;
    RRule.WEEKLY = Frequency.WEEKLY;
    RRule.DAILY = Frequency.DAILY;
    RRule.HOURLY = Frequency.HOURLY;
    RRule.MINUTELY = Frequency.MINUTELY;
    RRule.SECONDLY = Frequency.SECONDLY;
    RRule.MO = Days.MO;
    RRule.TU = Days.TU;
    RRule.WE = Days.WE;
    RRule.TH = Days.TH;
    RRule.FR = Days.FR;
    RRule.SA = Days.SA;
    RRule.SU = Days.SU;
    RRule.parseString = parseString;
    RRule.optionsToString = optionsToString;
    return RRule;
}());

//# sourceMappingURL=rrule.js.map
;// ./node_modules/rrule/dist/esm/iterset.js



function iterSet(iterResult, _rrule, _exrule, _rdate, _exdate, tzid) {
    var _exdateHash = {};
    var _accept = iterResult.accept;
    function evalExdate(after, before) {
        _exrule.forEach(function (rrule) {
            rrule.between(after, before, true).forEach(function (date) {
                _exdateHash[Number(date)] = true;
            });
        });
    }
    _exdate.forEach(function (date) {
        var zonedDate = new DateWithZone(date, tzid).rezonedDate();
        _exdateHash[Number(zonedDate)] = true;
    });
    iterResult.accept = function (date) {
        var dt = Number(date);
        if (isNaN(dt))
            return _accept.call(this, date);
        if (!_exdateHash[dt]) {
            evalExdate(new Date(dt - 1), new Date(dt + 1));
            if (!_exdateHash[dt]) {
                _exdateHash[dt] = true;
                return _accept.call(this, date);
            }
        }
        return true;
    };
    if (iterResult.method === 'between') {
        evalExdate(iterResult.args.after, iterResult.args.before);
        iterResult.accept = function (date) {
            var dt = Number(date);
            if (!_exdateHash[dt]) {
                _exdateHash[dt] = true;
                return _accept.call(this, date);
            }
            return true;
        };
    }
    for (var i = 0; i < _rdate.length; i++) {
        var zonedDate = new DateWithZone(_rdate[i], tzid).rezonedDate();
        if (!iterResult.accept(new Date(zonedDate.getTime())))
            break;
    }
    _rrule.forEach(function (rrule) {
        iter(iterResult, rrule.options);
    });
    var res = iterResult._result;
    sort(res);
    switch (iterResult.method) {
        case 'all':
        case 'between':
            return res;
        case 'before':
            return ((res.length && res[res.length - 1]) || null);
        case 'after':
        default:
            return ((res.length && res[0]) || null);
    }
}
//# sourceMappingURL=iterset.js.map
;// ./node_modules/rrule/dist/esm/rrulestr.js






/**
 * RRuleStr
 * To parse a set of rrule strings
 */
var rrulestr_DEFAULT_OPTIONS = {
    dtstart: null,
    cache: false,
    unfold: false,
    forceset: false,
    compatible: false,
    tzid: null,
};
function parseInput(s, options) {
    var rrulevals = [];
    var rdatevals = [];
    var exrulevals = [];
    var exdatevals = [];
    var parsedDtstart = parseDtstart(s);
    var dtstart = parsedDtstart.dtstart;
    var tzid = parsedDtstart.tzid;
    var lines = splitIntoLines(s, options.unfold);
    lines.forEach(function (line) {
        var _a;
        if (!line)
            return;
        var _b = breakDownLine(line), name = _b.name, parms = _b.parms, value = _b.value;
        switch (name.toUpperCase()) {
            case 'RRULE':
                if (parms.length) {
                    throw new Error("unsupported RRULE parm: ".concat(parms.join(',')));
                }
                rrulevals.push(parseString(line));
                break;
            case 'RDATE':
                var _c = (_a = /RDATE(?:;TZID=([^:=]+))?/i.exec(line)) !== null && _a !== void 0 ? _a : [], rdateTzid = _c[1];
                if (rdateTzid && !tzid) {
                    tzid = rdateTzid;
                }
                rdatevals = rdatevals.concat(parseRDate(value, parms));
                break;
            case 'EXRULE':
                if (parms.length) {
                    throw new Error("unsupported EXRULE parm: ".concat(parms.join(',')));
                }
                exrulevals.push(parseString(value));
                break;
            case 'EXDATE':
                exdatevals = exdatevals.concat(parseRDate(value, parms));
                break;
            case 'DTSTART':
                break;
            default:
                throw new Error('unsupported property: ' + name);
        }
    });
    return {
        dtstart: dtstart,
        tzid: tzid,
        rrulevals: rrulevals,
        rdatevals: rdatevals,
        exrulevals: exrulevals,
        exdatevals: exdatevals,
    };
}
function buildRule(s, options) {
    var _a = parseInput(s, options), rrulevals = _a.rrulevals, rdatevals = _a.rdatevals, exrulevals = _a.exrulevals, exdatevals = _a.exdatevals, dtstart = _a.dtstart, tzid = _a.tzid;
    var noCache = options.cache === false;
    if (options.compatible) {
        options.forceset = true;
        options.unfold = true;
    }
    if (options.forceset ||
        rrulevals.length > 1 ||
        rdatevals.length ||
        exrulevals.length ||
        exdatevals.length) {
        var rset_1 = new RRuleSet(noCache);
        rset_1.dtstart(dtstart);
        rset_1.tzid(tzid || undefined);
        rrulevals.forEach(function (val) {
            rset_1.rrule(new RRule(groomRruleOptions(val, dtstart, tzid), noCache));
        });
        rdatevals.forEach(function (date) {
            rset_1.rdate(date);
        });
        exrulevals.forEach(function (val) {
            rset_1.exrule(new RRule(groomRruleOptions(val, dtstart, tzid), noCache));
        });
        exdatevals.forEach(function (date) {
            rset_1.exdate(date);
        });
        if (options.compatible && options.dtstart)
            rset_1.rdate(dtstart);
        return rset_1;
    }
    var val = rrulevals[0] || {};
    return new RRule(groomRruleOptions(val, val.dtstart || options.dtstart || dtstart, val.tzid || options.tzid || tzid), noCache);
}
function rrulestr(s, options) {
    if (options === void 0) { options = {}; }
    return buildRule(s, rrulestr_initializeOptions(options));
}
function groomRruleOptions(val, dtstart, tzid) {
    return __assign(__assign({}, val), { dtstart: dtstart, tzid: tzid });
}
function rrulestr_initializeOptions(options) {
    var invalid = [];
    var keys = Object.keys(options);
    var defaultKeys = Object.keys(rrulestr_DEFAULT_OPTIONS);
    keys.forEach(function (key) {
        if (!includes(defaultKeys, key))
            invalid.push(key);
    });
    if (invalid.length) {
        throw new Error('Invalid options: ' + invalid.join(', '));
    }
    return __assign(__assign({}, rrulestr_DEFAULT_OPTIONS), options);
}
function extractName(line) {
    if (line.indexOf(':') === -1) {
        return {
            name: 'RRULE',
            value: line,
        };
    }
    var _a = split(line, ':', 1), name = _a[0], value = _a[1];
    return {
        name: name,
        value: value,
    };
}
function breakDownLine(line) {
    var _a = extractName(line), name = _a.name, value = _a.value;
    var parms = name.split(';');
    if (!parms)
        throw new Error('empty property name');
    return {
        name: parms[0].toUpperCase(),
        parms: parms.slice(1),
        value: value,
    };
}
function splitIntoLines(s, unfold) {
    if (unfold === void 0) { unfold = false; }
    s = s && s.trim();
    if (!s)
        throw new Error('Invalid empty string');
    // More info about 'unfold' option
    // Go head to http://www.ietf.org/rfc/rfc2445.txt
    if (!unfold) {
        return s.split(/\s/);
    }
    var lines = s.split('\n');
    var i = 0;
    while (i < lines.length) {
        // TODO
        var line = (lines[i] = lines[i].replace(/\s+$/g, ''));
        if (!line) {
            lines.splice(i, 1);
        }
        else if (i > 0 && line[0] === ' ') {
            lines[i - 1] += line.slice(1);
            lines.splice(i, 1);
        }
        else {
            i += 1;
        }
    }
    return lines;
}
function validateDateParm(parms) {
    parms.forEach(function (parm) {
        if (!/(VALUE=DATE(-TIME)?)|(TZID=)/.test(parm)) {
            throw new Error('unsupported RDATE/EXDATE parm: ' + parm);
        }
    });
}
function parseRDate(rdateval, parms) {
    validateDateParm(parms);
    return rdateval.split(',').map(function (datestr) { return untilStringToDate(datestr); });
}
//# sourceMappingURL=rrulestr.js.map
;// ./node_modules/rrule/dist/esm/rruleset.js







function createGetterSetter(fieldName) {
    var _this = this;
    return function (field) {
        if (field !== undefined) {
            _this["_".concat(fieldName)] = field;
        }
        if (_this["_".concat(fieldName)] !== undefined) {
            return _this["_".concat(fieldName)];
        }
        for (var i = 0; i < _this._rrule.length; i++) {
            var field_1 = _this._rrule[i].origOptions[fieldName];
            if (field_1) {
                return field_1;
            }
        }
    };
}
var RRuleSet = /** @class */ (function (_super) {
    __extends(RRuleSet, _super);
    /**
     *
     * @param {Boolean?} noCache
     * The same stratagy as RRule on cache, default to false
     * @constructor
     */
    function RRuleSet(noCache) {
        if (noCache === void 0) { noCache = false; }
        var _this = _super.call(this, {}, noCache) || this;
        _this.dtstart = createGetterSetter.apply(_this, ['dtstart']);
        _this.tzid = createGetterSetter.apply(_this, ['tzid']);
        _this._rrule = [];
        _this._rdate = [];
        _this._exrule = [];
        _this._exdate = [];
        return _this;
    }
    RRuleSet.prototype._iter = function (iterResult) {
        return iterSet(iterResult, this._rrule, this._exrule, this._rdate, this._exdate, this.tzid());
    };
    /**
     * Adds an RRule to the set
     *
     * @param {RRule}
     */
    RRuleSet.prototype.rrule = function (rrule) {
        _addRule(rrule, this._rrule);
    };
    /**
     * Adds an EXRULE to the set
     *
     * @param {RRule}
     */
    RRuleSet.prototype.exrule = function (rrule) {
        _addRule(rrule, this._exrule);
    };
    /**
     * Adds an RDate to the set
     *
     * @param {Date}
     */
    RRuleSet.prototype.rdate = function (date) {
        _addDate(date, this._rdate);
    };
    /**
     * Adds an EXDATE to the set
     *
     * @param {Date}
     */
    RRuleSet.prototype.exdate = function (date) {
        _addDate(date, this._exdate);
    };
    /**
     * Get list of included rrules in this recurrence set.
     *
     * @return List of rrules
     */
    RRuleSet.prototype.rrules = function () {
        return this._rrule.map(function (e) { return rrulestr(e.toString()); });
    };
    /**
     * Get list of excluded rrules in this recurrence set.
     *
     * @return List of exrules
     */
    RRuleSet.prototype.exrules = function () {
        return this._exrule.map(function (e) { return rrulestr(e.toString()); });
    };
    /**
     * Get list of included datetimes in this recurrence set.
     *
     * @return List of rdates
     */
    RRuleSet.prototype.rdates = function () {
        return this._rdate.map(function (e) { return new Date(e.getTime()); });
    };
    /**
     * Get list of included datetimes in this recurrence set.
     *
     * @return List of exdates
     */
    RRuleSet.prototype.exdates = function () {
        return this._exdate.map(function (e) { return new Date(e.getTime()); });
    };
    RRuleSet.prototype.valueOf = function () {
        var result = [];
        if (!this._rrule.length && this._dtstart) {
            result = result.concat(optionsToString({ dtstart: this._dtstart }));
        }
        this._rrule.forEach(function (rrule) {
            result = result.concat(rrule.toString().split('\n'));
        });
        this._exrule.forEach(function (exrule) {
            result = result.concat(exrule
                .toString()
                .split('\n')
                .map(function (line) { return line.replace(/^RRULE:/, 'EXRULE:'); })
                .filter(function (line) { return !/^DTSTART/.test(line); }));
        });
        if (this._rdate.length) {
            result.push(rdatesToString('RDATE', this._rdate, this.tzid()));
        }
        if (this._exdate.length) {
            result.push(rdatesToString('EXDATE', this._exdate, this.tzid()));
        }
        return result;
    };
    /**
     * to generate recurrence field such as:
     * DTSTART:19970902T010000Z
     * RRULE:FREQ=YEARLY;COUNT=2;BYDAY=TU
     * RRULE:FREQ=YEARLY;COUNT=1;BYDAY=TH
     */
    RRuleSet.prototype.toString = function () {
        return this.valueOf().join('\n');
    };
    /**
     * Create a new RRuleSet Object completely base on current instance
     */
    RRuleSet.prototype.clone = function () {
        var rrs = new RRuleSet(!!this._cache);
        this._rrule.forEach(function (rule) { return rrs.rrule(rule.clone()); });
        this._exrule.forEach(function (rule) { return rrs.exrule(rule.clone()); });
        this._rdate.forEach(function (date) { return rrs.rdate(new Date(date.getTime())); });
        this._exdate.forEach(function (date) { return rrs.exdate(new Date(date.getTime())); });
        return rrs;
    };
    return RRuleSet;
}(RRule));

function _addRule(rrule, collection) {
    if (!(rrule instanceof RRule)) {
        throw new TypeError(String(rrule) + ' is not RRule instance');
    }
    if (!includes(collection.map(String), String(rrule))) {
        collection.push(rrule);
    }
}
function _addDate(date, collection) {
    if (!(date instanceof Date)) {
        throw new TypeError(String(date) + ' is not Date instance');
    }
    if (!includes(collection.map(Number), Number(date))) {
        collection.push(date);
        sort(collection);
    }
}
function rdatesToString(param, rdates, tzid) {
    var isUTC = !tzid || tzid.toUpperCase() === 'UTC';
    var header = isUTC ? "".concat(param, ":") : "".concat(param, ";TZID=").concat(tzid, ":");
    var dateString = rdates
        .map(function (rdate) { return timeToUntilString(rdate.valueOf(), isUTC); })
        .join(',');
    return "".concat(header).concat(dateString);
}
//# sourceMappingURL=rruleset.js.map
;// ./node_modules/rrule/dist/esm/index.js
/* !
 * rrule.js - Library for working with recurrence rules for calendar dates.
 * https://github.com/jakubroztocil/rrule
 *
 * Copyright 2010, Jakub Roztocil and Lars Schoning
 * Licenced under the BSD licence.
 * https://github.com/jakubroztocil/rrule/blob/master/LICENCE
 *
 * Based on:
 * python-dateutil - Extensions to the standard Python datetime module.
 * Copyright (c) 2003-2011 - Gustavo Niemeyer <gustavo@niemeyer.net>
 * Copyright (c) 2012 - Tomi Pieviläinen <tomi.pievilainen@iki.fi>
 * https://github.com/jakubroztocil/rrule/blob/master/LICENCE
 *
 */






//# sourceMappingURL=index.js.map
// EXTERNAL MODULE: ./src/js/utils.js
var utils = __webpack_require__(771);
;// ./src/js/date-picker.js
/* eslint-disable no-var */
/* globals: wc_bookings_booking_form, booking_form_params */

/**
 * External dependencies
 */



/**
 * Internal dependencies
 */




// globally accessible for tests
let wc_bookings_date_picker = {};
external_jQuery_default()(function ($) {
  let defaultDate;
  var wc_bookings_locale = window.navigator.userLanguage || window.navigator.language,
    wc_bookings_timeout = 0,
    currentDateRange = {},
    wc_bookings_date_picker = {
      init: function () {
        $('body').on('click', '.wc-bookings-date-picker legend', this.toggle_calendar);
        $('body').on('click', '.booking_date_year, .booking_date_month, .booking_date_day', this.open_calendar);
        $('body').on('input', '.booking_date_year, .booking_date_month, .booking_date_day', this.input_date_trigger);
        $('body').on('keyup', '.booking_date_year, .booking_date_month, .booking_date_day', this.input_date_keypress);
        $('body').on('keyup', '.booking_to_date_year, .booking_to_date_month, .booking_to_date_day', this.input_date_keypress);
        $('body').on('change', '.booking_to_date_year, .booking_to_date_month, .booking_to_date_day', this.input_date_trigger);
        $('.wc-bookings-date-picker legend').show();
        $('.wc-bookings-date-picker').each(function () {
          var form = $(this).closest('form'),
            picker = form.find('.picker'),
            fieldset = $(this).closest('fieldset');
          wc_bookings_date_picker.date_picker_init(picker);
          if (picker.data('display') == 'always_visible') {
            $('.wc-bookings-date-picker-date-fields', fieldset).hide();
          } else {
            picker.hide();
          }
          if (picker.data('is_range_picker_enabled')) {
            form.find('p.wc_bookings_field_duration').hide();
            form.find('.wc_bookings_field_start_date legend span.label').text('always_visible' !== picker.data('display') ? booking_form_params.i18n_dates : booking_form_params.i18n_start_date);
          }
        });
      },
      calc_duration: function (picker) {
        var form = picker.closest('form'),
          fieldSet = picker.closest('fieldset'),
          unit = picker.data('durationUnit');
        setTimeout(function () {
          var days = 1,
            e_year = parseInt(fieldSet.find('input.booking_to_date_year').val(), 10),
            e_month = parseInt(fieldSet.find('input.booking_to_date_month').val(), 10),
            e_day = parseInt(fieldSet.find('input.booking_to_date_day').val(), 10),
            s_year = parseInt(fieldSet.find('input.booking_date_year').val(), 10),
            s_month = parseInt(fieldSet.find('input.booking_date_month').val(), 10),
            s_day = parseInt(fieldSet.find('input.booking_date_day').val(), 10);
          if (e_year && e_month >= 0 && e_day && s_year && s_month >= 0 && s_day) {
            var s_date = new Date(Date.UTC(s_year, s_month - 1, s_day)),
              e_date = new Date(Date.UTC(e_year, e_month - 1, e_day));
            days = Math.floor((e_date.getTime() - s_date.getTime()) / (1000 * 60 * 60 * 24));
            if ('day' === unit) {
              days = days + 1;
            }
          }
          form.find('#wc_bookings_field_duration').val(days).trigger('change');
        });
      },
      open_calendar: function () {
        const $picker = $(this).closest('fieldset').find('.picker:eq(0)');
        wc_bookings_date_picker.date_picker_init($picker);
        $picker.slideDown();
      },
      toggle_calendar: function () {
        const $picker = $(this).closest('fieldset').find('.picker:eq(0)');
        wc_bookings_date_picker.date_picker_init($picker);
        $picker.slideToggle();
      },
      input_date_keypress: function () {
        var $fieldset = $(this).closest('fieldset'),
          $picker = $fieldset.find('.picker:eq(0)');
        if ($picker.data('is_range_picker_enabled')) {
          clearTimeout(wc_bookings_timeout);
          wc_bookings_timeout = setTimeout(wc_bookings_date_picker.calc_duration($picker), 800);
        }
      },
      clear_selection: function () {
        const form_containers = $('.wc-bookings-booking-form');

        // If we use setDate on the picker it will shift the selected month to the current one.

        form_containers.each((index, form_container) => {
          const $form_container = $(form_container);
          const form = $form_container.closest('form');

          // Clear selection
          form.find('input.booking_date_year, input.booking_date_month, input.booking_date_day, input#wc_bookings_field_start_date').val('').trigger('change');
          form.find('.ui-state-active').removeClass('ui-state-active');

          /**
           * @see https://github.com/woocommerce/woocommerce-bookings/pull/3277#issuecomment-1115969788
           * for the reasoning behind the following.
           */
          if (['hour', 'minute'].includes(wc_bookings_booking_form.get_booking_duration_unit(form))) {
            form.find('.wc-bookings-booking-form .block-picker').html(`<li>${wc_bookings_booking_form.sanitize_text(wc_bookings_booking_form.default_blocks_area_text)}</li>`);
          } else {
            // Reset block picker.
            wc_bookings_booking_form.time_picker_reset_selected(form.find('.block-picker'));
          }
          form.find('.wc-bookings-booking-cost').hide();
        });
      },
      input_date_trigger: function () {
        var $fieldset = $(this).closest('fieldset'),
          $picker = $fieldset.find('.picker:eq(0)'),
          $form = $(this).closest('form'),
          year = parseInt($fieldset.find('input.booking_date_year').val(), 10),
          month = parseInt($fieldset.find('input.booking_date_month').val(), 10),
          day = parseInt($fieldset.find('input.booking_date_day').val(), 10);
        if (year && month && day) {
          var date = new Date(year, month - 1, day);
          $picker.datepicker("setDate", date);
          if ($picker.data('is_range_picker_enabled')) {
            var to_year = parseInt($fieldset.find('input.booking_to_date_year').val(), 10),
              to_month = parseInt($fieldset.find('input.booking_to_date_month').val(), 10),
              to_day = parseInt($fieldset.find('input.booking_to_date_day').val(), 10);
            var to_date = new Date(to_year, to_month - 1, to_day);
            if (!to_date || to_date < date) {
              $fieldset.find('input.booking_to_date_year').val('').addClass('error');
              $fieldset.find('input.booking_to_date_month').val('').addClass('error');
              $fieldset.find('input.booking_to_date_day').val('').addClass('error');
            } else {
              $fieldset.find('input').removeClass('error');
            }
          }
          $fieldset.triggerHandler('date-selected', date);
        }
      },
      select_date_trigger: function (date) {
        var fieldset = $(this).closest('fieldset'),
          picker = fieldset.find('.picker:eq(0)'),
          form = $(this).closest('form'),
          parsed_date = date.split('-'),
          start_or_end_date = picker.data('start_or_end_date');
        if (!picker.data('is_range_picker_enabled') || !start_or_end_date) {
          start_or_end_date = 'start';
        }
        if (picker.data('is_range_picker_enabled') && start_or_end_date === 'end') {
          var start_date = wc_bookings_date_picker.get_input_date(fieldset, '');
          var moment_date = moment(date);
          if (start_date && moment_date.isBefore(start_date)) {
            start_or_end_date = 'start';
          }
        }

        // End date selected
        if (start_or_end_date === 'end') {
          // Set min date to default
          picker.data('min_date', picker.data('o_min_date'));

          // Set fields
          fieldset.find('input.booking_to_date_year').val(parsed_date[0]);
          fieldset.find('input.booking_to_date_month').val(parsed_date[1]);
          fieldset.find('input.booking_to_date_day').val(parsed_date[2]).trigger('change');

          // Calc duration
          if (picker.data('is_range_picker_enabled')) {
            wc_bookings_date_picker.calc_duration(picker);
          }

          // Next click will be start date
          picker.data('start_or_end_date', 'start');
          if (picker.data('is_range_picker_enabled')) {
            form.find('.wc_bookings_field_start_date legend span.label').text('always_visible' !== picker.data('display') ? booking_form_params.i18n_dates : booking_form_params.i18n_clear_date_selection);
          }
          if ('always_visible' !== picker.data('display')) {
            $(this).hide();
          }
          // Start date selected
        } else {
          // Set min date to today
          if (picker.data('is_range_picker_enabled')) {
            // Store the original min date if it is not already set.
            if (typeof picker.data('o_min_date') === 'undefined') {
              picker.data('o_min_date', picker.data('min_date'));
            }
            picker.data('min_date', date);
          }

          // Set fields
          fieldset.find('input.booking_to_date_year').val('');
          fieldset.find('input.booking_to_date_month').val('');
          fieldset.find('input.booking_to_date_day').val('');
          fieldset.find('input.booking_date_year').val(parsed_date[0]);
          fieldset.find('input.booking_date_month').val(parsed_date[1]);
          fieldset.find('input.booking_date_day').val(parsed_date[2]).trigger('change');

          // Calc duration
          if (picker.data('is_range_picker_enabled')) {
            wc_bookings_date_picker.calc_duration(picker);
          }

          // Next click will be end date
          picker.data('start_or_end_date', 'end');
          if (picker.data('is_range_picker_enabled')) {
            form.find('.wc_bookings_field_start_date legend span.label').text(booking_form_params.i18n_end_date);
          }
          if ('always_visible' !== picker.data('display') && !picker.data('is_range_picker_enabled')) {
            $(this).hide();
          }
        }
        fieldset.triggerHandler('date-selected', date, start_or_end_date);

        /**
         * Fire action after end date select.
         *
         * @param {object} fieldset Field.
         *
         * @since 1.15.79
         */
        (0,utils/* HookApi */.A)().doAction('wc_bookings_date_selected', {
          'fieldset': fieldset.get(0),
          'date_picker': picker.get(0)
        });
      },
      date_picker_init: function (element) {
        var WC_DatePicker = new WC_Bookings_DatePicker(element);
        const min_date = typeof WC_DatePicker.get_data_attr('o_min_date') !== 'undefined' ? WC_DatePicker.get_data_attr('o_min_date') : WC_DatePicker.get_data_attr('min_date');
        /*
         * This prevents the calendar resetting to the current date when re-initializing.
         *
         * The defaultDate is set to the current date when the datepicker is initialized.
         * As the user navigates from month to month, the defaultDate is updated to the
         * first of the month the user has navigated to.
         *
         * If the resource is updated, this allows the date picker to refresh without
         * changing the month back to the current month.
         */
        if (typeof defaultDate === 'undefined') {
          defaultDate = WC_DatePicker.get_data_attr('default_date');
        }
        WC_DatePicker.set_default_params({
          onSelect: wc_bookings_date_picker.select_date_trigger,
          minDate: min_date,
          maxDate: WC_DatePicker.get_data_attr('max_date'),
          defaultDate: defaultDate,
          closeText: WC_DatePicker.get_custom_data('closeText'),
          currentText: WC_DatePicker.get_custom_data('currentText'),
          prevText: WC_DatePicker.get_custom_data('prevText'),
          nextText: WC_DatePicker.get_custom_data('nextText'),
          monthNames: WC_DatePicker.get_custom_data('monthNames'),
          monthNamesShort: WC_DatePicker.get_custom_data('monthNamesShort'),
          dayNames: WC_DatePicker.get_custom_data('dayNames'),
          dayNamesShort: WC_DatePicker.get_custom_data('dayNamesShort'),
          dayNamesMin: WC_DatePicker.get_custom_data('dayNamesMin'),
          firstDay: booking_form_params.client_firstday ? moment().localeData().firstDayOfWeek() : WC_DatePicker.get_custom_data('firstDay'),
          isRTL: WC_DatePicker.get_custom_data('isRTL'),
          beforeShowDay: WC_DatePicker.maybe_load_from_cache.bind(WC_DatePicker),
          onChangeMonthYear: function (year, month) {
            this.get_data(year, month).done(function () {
              element.datepicker('refresh');
            });
            defaultDate = new Date(year, month - 1, 1);
          }.bind(WC_DatePicker)
        });
        WC_DatePicker.create();
        wc_bookings_booking_form.get_day_attributes = WC_DatePicker.maybe_load_from_cache.bind(WC_DatePicker);
      },
      refresh_datepicker: function () {
        var $picker = $('.wc-bookings-date-picker').find('.picker:eq(0)');
        $picker.datepicker('refresh');

        /**
         * Fire action after date picker is refreshed.
         *
         * @param {object} $picker jQuery object of the date picker.
         * @since 1.15.79
         */
        (0,utils/* HookApi */.A)().doAction('wc_bookings_date_picker_refreshed', {
          'date_picker': $picker
        });
      },
      get_input_date: function (fieldset, where) {
        var year = fieldset.find('input.booking_' + where + 'date_year'),
          month = fieldset.find('input.booking_' + where + 'date_month'),
          day = fieldset.find('input.booking_' + where + 'date_day');
        if (0 !== year.val().length && 0 !== month.val().length && 0 !== day.val().length) {
          return year.val() + '-' + month.val() + '-' + day.val();
        } else {
          return '';
        }
      },
      get_number_of_days: function (defaultNumberOfDays, $form, $picker, wc_bookings_booking_form) {
        var number_of_days = defaultNumberOfDays;
        var wcbf = wc_bookings_booking_form;
        if ($form.find('#wc_bookings_field_duration').length > 0 && wcbf.duration_unit != 'minute' && wcbf.duration_unit != 'hour' && !$picker.data('is_range_picker_enabled')) {
          var user_duration = $form.find('#wc_bookings_field_duration').val();
          number_of_days = number_of_days * user_duration;
        }
        if (number_of_days < 1) {
          number_of_days = 1;
        }
        return number_of_days;
      },
      is_blocks_bookable: function (args) {
        var bookable = args.default_availability;

        // Loop all the days we need to check for this block.
        for (var i = 0; i < args.number_of_days; i++) {
          var the_date = new Date(args.start_date);
          the_date.setDate(the_date.getDate() + i);
          var year = the_date.getFullYear(),
            month = the_date.getMonth() + 1,
            day = the_date.getDate(),
            day_of_week = the_date.getDay();

          // Sunday is 0, Monday is 1, and so on.
          if (day_of_week === 0) {
            day_of_week = 7;
          }

          // Is resource available in current date?
          // Note: resource_id = 0 is product's availability rules.
          // Each resource rules also contains product's rules.
          var resource_args = {
            date: the_date,
            default_availability: args.default_availability
          };
          var resource_rules = args.availability[args.resource_id];

          /*
           * If the resource ID is zero, the check is for the availability rules of the product.
           *
           * If the availability rules are only checked against the start date then we can bypass the
           * checks for the second and subsequent days in the block.
           */
          if (args.resource_id === 0 && wc_bookings_booking_form.check_availability_against === 'start' && i > 0) {
            bookable = true;
          } else {
            bookable = wc_bookings_date_picker.is_resource_available_on_date(resource_args, resource_rules);
          }

          // In case of automatic assignment we want to make sure at least
          // one resource is available.
          if ('automatic' === args.resources_assignment) {
            var automatic_resource_args = $.extend({
              availability: args.availability,
              fully_booked_days: args.fully_booked_days
            }, resource_args);
            bookable = wc_bookings_date_picker.has_available_resource(automatic_resource_args);
          }
          if (!bookable) {
            return 'not_bookable_by_rules';
          }

          // Fully booked in entire block?
          var ymdIndex = year + '-' + month + '-' + day;
          if (args.fully_booked_days[ymdIndex]) {
            // If product does not have any resources, mark as bookable false.
            // OR if a product with customer defined resources found in an array, mark as bookable false.
            // This conditions fixes issues #2881 & #3453.
            if (this.bookingsData.fully_booked_days[ymdIndex][0] && 0 === args.resource_ids.length || 0 !== args.resource_id && this.bookingsData.fully_booked_days[ymdIndex][args.resource_id]) {
              bookable = false;
            }
          }
          if (!bookable) {
            break;
          }
        }
        return bookable;
      },
      rrule_cache: {},
      /**
       * Checks rules against date to check if there are any available minutes on the date.
       *
       * Depending on client/server timezone offset, prev/next day will be checked for available
       * minutes and then offsetted accordingly for timezone.
       *
       * @param args
       * @param rules array of rules in order from lowest override power to highest.
       *
       * @returns bool
       */
      is_resource_available_on_date: function (args, rules) {
        if ('object' !== typeof args || 'object' !== typeof rules) {
          return false;
        }
        const server_offset = (0,bookings_lib/* get_client_server_timezone_offset_hrs */.f)(args.date);
        const durationUnit = $('[data-duration-unit]').data('duration-unit');
        let availableMinutes;

        // For days, skip the timezone conversion.
        if (server_offset === 0 || 'day' === durationUnit) {
          availableMinutes = this.get_available_minutes_on_date_for_rule(args, rules);
        } else {
          const {
            date: currentDate,
            ...argsNoDate
          } = args;
          const currentDayMinutes = this.get_available_minutes_on_date_for_rule({
            ...argsNoDate,
            date: currentDate
          }, rules);
          if (server_offset < 0) {
            const nextDayDate = new Date(currentDate);
            nextDayDate.setDate(currentDate.getDate() + 1);
            const nextDayMinutes = this.get_available_minutes_on_date_for_rule({
              ...argsNoDate,
              date: nextDayDate
            }, rules).map(m => m + 1440);
            availableMinutes = currentDayMinutes.concat(nextDayMinutes);
          } else {
            const prevDayDate = new Date(currentDate);
            prevDayDate.setDate(currentDate.getDate() - 1);
            const prevDayMinutes = this.get_available_minutes_on_date_for_rule({
              ...argsNoDate,
              date: prevDayDate
            }, rules).map(m => m - 1440);
            availableMinutes = prevDayMinutes.concat(currentDayMinutes);
          }
          // Offset minutes for timezone.
          availableMinutes = availableMinutes.map(m => m + server_offset * 60);
          // Filter out minutes that are not on the current day.
          availableMinutes = availableMinutes.filter(m => m > 0 && m < 1440);
        }
        return !external_default().isEmpty(availableMinutes);
      },
      /**
       * Goes through all the rules and applies them to get array of available minutes
       *
       * Rules are recursively applied. Rules later array will override rules earlier in the array if
       * applicable to the block being checked.
       *
       * @param args
       * @param rules array of rules in order from lowest override power to highest.
       *
       * @returns array
       */
      get_available_minutes_on_date_for_rule: function (args, rules) {
        var defaultAvailability = args.default_availability,
          year = args.date.getFullYear(),
          month = args.date.getMonth() + 1,
          // months start at 0
          day = args.date.getDate(),
          day_of_week = args.date.getDay(),
          week = wc_bookings_date_picker.get_week_number(args.date);

        // Sunday is 0, Monday is 1, and so on.
        if (day_of_week === 0) {
          day_of_week = 7;
        }
        let minutesAvailableForDay = [];

        // `args.fully_booked_days` and `args.resource_id` only available
        // when checking 'automatic' resource assignment.
        if (args.fully_booked_days && args.fully_booked_days[year + '-' + month + '-' + day] && args.fully_booked_days[year + '-' + month + '-' + day][args.resource_id]) {
          return minutesAvailableForDay;
        }
        var minutesForADay = external_default().range(1, 1440, 1);
        // Ensure that the minutes are set when the all slots are available by default.
        if (defaultAvailability) {
          minutesAvailableForDay = minutesForADay;
        }
        $.each(rules, function (index, rule) {
          var type = rule['type'],
            range = rule['range'],
            minutesAvailableForTime;
          try {
            switch (type) {
              case 'months':
                if (typeof range[month] != 'undefined') {
                  if (range[month]) {
                    minutesAvailableForDay = minutesForADay;
                  } else {
                    minutesAvailableForDay = [];
                  }
                  return true; // go to the next rule
                }
                break;
              case 'weeks':
                if (typeof range[week] != 'undefined') {
                  if (range[week]) {
                    minutesAvailableForDay = minutesForADay;
                  } else {
                    minutesAvailableForDay = [];
                  }
                  return true; // go to the next rule
                }
                break;
              case 'days':
                if (typeof range[day_of_week] != 'undefined') {
                  if (range[day_of_week]) {
                    minutesAvailableForDay = minutesForADay;
                  } else {
                    minutesAvailableForDay = [];
                  }
                  return true; // go to the next rule
                }
                break;
              case 'custom':
                if (typeof range[year][month][day] != 'undefined') {
                  if (range[year][month][day]) {
                    minutesAvailableForDay = minutesForADay;
                  } else {
                    minutesAvailableForDay = [];
                  }
                  return true; // go to the next rule
                }
                break;
              case 'rrule':
                const is_all_day = -1 === range.from.indexOf(':');
                const current_date = moment.utc(args.date);
                const current_date_sod = current_date.clone().startOf('day');
                const from_date = moment.utc(range.from);
                const to_date = moment.utc(range.to);
                const duration = moment.duration(to_date.diff(from_date));
                const rrule = rrulestr(range.rrule, {
                  dtstart: from_date.toDate()
                });
                const cache_key = index + currentDateRange.startDate + currentDateRange.endDate;
                if (typeof wc_bookings_date_picker.rrule_cache[cache_key] === 'undefined') {
                  wc_bookings_date_picker.rrule_cache[cache_key] = rrule.between(moment.utc(currentDateRange.startDate).subtract(duration).subtract(1, 'days').toDate(), moment.utc(currentDateRange.endDate).subtract(duration).add(1, 'days').toDate(), true).map(occurrence => new moment(occurrence));
                }
                wc_bookings_date_picker.rrule_cache[cache_key].forEach(occurrence => {
                  const occurrence_sod = occurrence.clone().startOf('day');
                  const end_occurrence = occurrence.clone().add(duration);
                  const end_occurrence_sod = end_occurrence.clone().startOf('day');
                  if (current_date_sod.isSameOrAfter(occurrence_sod) && current_date_sod.isBefore(end_occurrence_sod)) {
                    if (is_all_day) {
                      minutesAvailableForDay = range.rule ? minutesForADay : [];
                    } else if (current_date_sod.isSame(occurrence_sod)) {
                      const minutesFromStartOfDay = moment.duration(occurrence.diff(occurrence_sod)).asMinutes();
                      minutesAvailableForTime = external_default().range(minutesFromStartOfDay, minutesFromStartOfDay + duration.asMinutes(), 1);
                      if (range.rule) {
                        minutesAvailableForDay = external_default().union(minutesAvailableForDay, minutesAvailableForTime);
                      } else {
                        minutesAvailableForDay = external_default().difference(minutesAvailableForDay, minutesAvailableForTime);
                      }
                    } else if (current_date_sod.isAfter(occurrence_sod) && current_date_sod.isBefore(end_occurrence_sod)) {
                      // Event is a multi-day event with start and end time but current day is fully inside the start day and end days
                      minutesAvailableForDay = range.rule ? minutesForADay : [];
                    } else if (current_date_sod.isSame(end_occurrence_sod)) {
                      // Event is multi-day and current day is the last day of event. Find how many minutes there are before end time.
                      minutesAvailableForTime = external_default().range(1, moment.duration(end_occurrence.diff(end_occurrence_sod)).asMinutes(), 1);
                      if (range.rule) {
                        minutesAvailableForDay = external_default().union(minutesAvailableForDay, minutesAvailableForTime);
                      } else {
                        minutesAvailableForDay = external_default().difference(minutesAvailableForDay, minutesAvailableForTime);
                      }
                    }
                  }
                });
                break;
              case 'time':
              case 'time:1':
              case 'time:2':
              case 'time:3':
              case 'time:4':
              case 'time:5':
              case 'time:6':
              case 'time:7':
                if (day_of_week === range.day || 0 === range.day) {
                  var fromHour = parseInt(range.from.split(':')[0]);
                  var fromMinute = parseInt(range.from.split(':')[1]);
                  var toHour = parseInt(range.to.split(':')[0]);
                  var toMinute = parseInt(range.to.split(':')[1]);

                  // each minute in the day gets a number from 1 to 1440
                  var fromMinuteNumber = fromMinute + fromHour * 60;
                  var toMinuteNumber = toMinute + toHour * 60;
                  minutesAvailableForTime = external_default().range(fromMinuteNumber, toMinuteNumber, 1);
                  if (range.rule) {
                    minutesAvailableForDay = external_default().union(minutesAvailableForDay, minutesAvailableForTime);
                  } else {
                    minutesAvailableForDay = external_default().difference(minutesAvailableForDay, minutesAvailableForTime);
                  }
                  return true;
                }
                break;
              case 'time:range':
              case 'custom:daterange':
                range = range[year][month][day];
                var fromHour = parseInt(range.from.split(':')[0]);
                var fromMinute = parseInt(range.from.split(':')[1]);
                var toHour = parseInt(range.to.split(':')[0]);
                var toMinute = parseInt(range.to.split(':')[1]);

                // each minute in the day gets a number from 1 to 1440
                var fromMinuteNumber = fromMinute + fromHour * 60;
                var toMinuteNumber = toMinute + toHour * 60;
                minutesAvailableForTime = external_default().range(fromMinuteNumber, toMinuteNumber, 1);
                if (range.rule) {
                  minutesAvailableForDay = external_default().union(minutesAvailableForDay, minutesAvailableForTime);
                } else {
                  minutesAvailableForDay = external_default().difference(minutesAvailableForDay, minutesAvailableForTime);
                }
                break;
            }
          } catch (err) {
            return true; // go to the next rule
          }
        });
        return minutesAvailableForDay;
      },
      get_week_number: function (date) {
        return moment(date).format('W');
      },
      has_available_resource: function (args) {
        for (var resource_id in args.availability) {
          resource_id = parseInt(resource_id, 10);

          // Skip resource_id '0' that has been performed before.
          if (0 === resource_id) {
            continue;
          }
          var resource_rules = args.availability[resource_id];
          args.resource_id = resource_id;
          if (wc_bookings_date_picker.is_resource_available_on_date(args, resource_rules)) {
            return true;
          }
        }
        return false;
      }
    };

  /**
   * Represents a jQuery UI DatePicker.
   *
   * @constructor
   * @version 1.10.11
   * @since   1.10.11
   * @param   {object} element - jQuery object for the picker that was initialized.
   * @param   {object} opts - Optional arguments.
   */
  var WC_Bookings_DatePicker = function WC_Bookings_DatePicker(element) {
    this.$picker = $(element);
    this.$form = this.$picker.closest('form, .cart');
    this.customData = {};
    this.opts = {
      cache: false
    };
    this.cache = {
      data: {},
      attributes: {}
    };
    $.each(wc_bookings_booking_form, function (key, val) {
      this.customData[key] = val;
    }.bind(this));
    $.each(booking_form_params, function (key, val) {
      this.customData[key] = val;
    }.bind(this));
    if (this.customData.cache_ajax_requests && ('true' == this.customData.cache_ajax_requests.toLowerCase() || 'false' == this.customData.cache_ajax_requests.toLowerCase())) {
      this.opts.cache = 'true' == this.customData.cache_ajax_requests.toLowerCase();
    }

    // Multiple global objects get defined on client side when more than one booking product is used per page.
    // For this reason, the localized param value is set to the last defined global object which creates issue with booking calendars.
    // And the global object always refer to the recently loaded booking product. To fix issue, we are adding product specific settings to customData.
    // https://github.com/woocommerce/woocommerce-bookings/issues/1636
    this.customData.product_id = wc_bookings_booking_form.get_booking_product_id(element);
    this.customData.booking_duration = wc_bookings_booking_form.get_booking_duration(element);
    this.customData.booking_min_duration = wc_bookings_booking_form.get_booking_min_duration(element);
    this.customData.booking_max_duration = wc_bookings_booking_form.get_booking_max_duration(element);
    this.customData.check_availability_against = wc_bookings_booking_form.get_booking_check_availability_against(element);
    this.customData.default_availability = wc_bookings_booking_form.get_booking_default_availability(element);
    this.customData.duration_type = wc_bookings_booking_form.get_booking_duration_type(element);
    this.customData.booking_duration_type = wc_bookings_booking_form.get_booking_duration_type(element);
    this.customData.duration_unit = wc_bookings_booking_form.get_booking_duration_unit(element);
    this.customData.resources_assignment = wc_bookings_booking_form.get_booking_resources_assignment(element);
    this.customData.resource_ids = wc_bookings_booking_form.get_booking_resource_ids(element);
    if (!this.$picker.length) {
      return;
    }
  };

  /**
   * Creates the DatePicker referenced by initializing the first data call.
   *
   * @version 1.10.11
   * @since   1.10.11
   */
  WC_Bookings_DatePicker.prototype.create = function create() {
    var year = parseInt(this.$form.find('input.booking_date_year').val(), 10);
    var month = parseInt(this.$form.find('input.booking_date_month').val(), 10);
    var day = parseInt(this.$form.find('input.booking_date_day').val(), 10);
    this.$picker.empty().removeClass('hasDatepicker').datepicker(this.get_default_params());
    $('.ui-datepicker-current-day').removeClass('ui-datepicker-current-day');
    if (year && month && day) {
      this.$picker.datepicker('setDate', new Date(year, month - 1, day));
    }
    var picker_month = this.$picker.datepicker('getDate').getMonth() + 1;
    var picker_year = this.$picker.datepicker('getDate').getFullYear();
    this.get_data(picker_year, picker_month).done(function () {
      wc_bookings_date_picker.refresh_datepicker();
    });
  };

  /**
   * If caching is being requested beforeShowDay will use this method to load styles from cache if available.
   *
   * @version 1.10.11
   * @since   1.10.11
   * @param   {object} date - Date to apply attributes to.
   */
  WC_Bookings_DatePicker.prototype.maybe_load_from_cache = function maybe_load_from_cache(date) {
    var cacheKey = date.getTime();
    var defaultClass = '1' === this.customData.default_availability ? 'bookable' : 'not-bookable';
    var attributes = [false, defaultClass, ''];
    var cachedAttributes = this.cache.attributes[cacheKey];
    if (cachedAttributes) {
      cachedAttributes = [cachedAttributes.selectable, cachedAttributes.class.join(' '), cachedAttributes.title];
    } else if (this.bookingsData) {
      var attrs = this.getDateElementAttributes(date);
      attributes = [attrs.selectable, attrs.class.join(' '), attrs.title];
    }
    return cachedAttributes || attributes;
  };

  /**
   * Returns the default parameters.
   *
   * @version 1.10.11
   * @since   1.10.11
   */
  WC_Bookings_DatePicker.prototype.get_default_params = function get_default_params() {
    return this.defaultParams || {};
  };

  /**
   * Set and override the default parameters.
   *
   * @version 1.10.11
   * @since   1.10.11
   * @param   {object} params - Parameters to be set or overridden.
   */
  WC_Bookings_DatePicker.prototype.set_default_params = function set_default_params(params) {
    var _defaultParams = {
      showWeek: false,
      showOn: false,
      numberOfMonths: 1,
      showButtonPanel: false,
      showOtherMonths: true,
      selectOtherMonths: true,
      gotoCurrent: true,
      dateFormat: $.datepicker.ISO_8601
    };
    if (typeof params !== 'object') {
      throw new Error('Cannot set params with typeof ' + typeof params);
    }
    this.defaultParams = $.extend(_defaultParams, params) || {};
  };

  /**
   * Get the data from the server for a block of time.
   *
   * @since   1.10.11
   * @param   {string} year - Year being requested.
   * @param   {string} month - Month being requested.
   * @returns {object} Deferred object to be resolved after the http request
   */
  WC_Bookings_DatePicker.prototype.get_data = function get_data(year, month) {
    /**
     * Overlay styles when jQuery.block is called to block the DOM.
     */
    var blockUIOverlayCSS = {
      background: '#fff',
      opacity: 0.6
    };

    /**
     * Get a date range based on the start date.
     *
     * @since   1.10.11
     * @param   {string} startDate - Optional start date to get the date range from.
     * @returns {object} Object referencing the start date and end date for the range calculated.
     */
    var get_date_range = function get_date_range(startDate) {
      if (!startDate) {
        startDate = new Date([year, month, '01'].join('/'));
      }
      var range = this.get_number_of_days_in_month(month);
      return this.get_padded_date_range(startDate, range);
    }.bind(this);
    var deferred = $.Deferred();
    var dateRange = get_date_range();
    var cacheKey = dateRange.startDate.getTime() + '-' + dateRange.endDate.getTime();
    if (this.opts.cache && this.cache.data[cacheKey]) {
      deferred.resolveWith(this, [dateRange, this.cache.data[cacheKey]]);
    } else {
      var resource_id = parseInt(this.$form.find('select#wc_bookings_field_resource').val(), 10) || 0;
      var params = {
        'product_id': this.get_custom_data('product_id'),
        'wc-ajax': 'wc_bookings_find_booked_day_blocks',
        'security': this.$form.data('nonce'),
        'resource_id': resource_id
      };
      this.$picker.block({
        message: null,
        overlayCSS: blockUIOverlayCSS
      });
      if (booking_form_params.timezone_conversion) {
        params.timezone_offset = (0,bookings_lib/* get_client_server_timezone_offset_hrs */.f)(dateRange.startDate);
        $('#timezone_offset').val(params.timezone_offset);
      }
      params.min_date = moment(dateRange.startDate).format('YYYY-MM-DD');
      params.max_date = moment(dateRange.endDate).format('YYYY-MM-DD');
      $('#min_date').val(params.min_date);
      $('#max_date').val(params.max_date);
      $.ajax({
        context: this,
        url: wc_bookings_date_picker_args.ajax_url,
        method: 'GET',
        data: params
      }).done(function (data) {
        if ('old_availability' in data && data.old_availability) {
          if (0 === $('#old_availability_served').length) {
            let old_availability = document.createElement('p');
            old_availability.setAttribute('id', 'old_availability_served');
            old_availability.textContent = booking_form_params.i18n_old_availability;
            $(old_availability).insertBefore('#wc-bookings-booking-form');
          }
        } else {
          $('#old_availability_served').remove();
        }
        this.bookingsData = this.bookingsData || {};
        $.each(data, function (key, val) {
          if (Array.isArray(val) || typeof val === 'object') {
            var emptyType = Array.isArray(val) ? [] : {};
            this.bookingsData[key] = this.bookingsData[key] || emptyType;
            $.extend(this.bookingsData[key], val);
          } else {
            this.bookingsData[key] = val;
          }
        }.bind(this));
        wc_bookings_booking_form.wc_bookings_date_picker.bookingsData = this.bookingsData;
        this.cache.data[cacheKey] = data;
        if (!year && !month && this.bookingsData.min_date) {
          dateRange = get_date_range(this.get_default_date(this.bookingsData.min_date));
        }
        deferred.resolveWith(this, [dateRange, data]);
        this.$picker.unblock();
      }.bind(this));
    }
    return deferred;
  };

  /**
   * Gets the default date
   *
   * @version 1.10.11
   * @since   1.10.11
   * @returns {Date}  Default date
   */
  WC_Bookings_DatePicker.prototype.get_default_date = function get_default_date(minBookableDate) {
    var defaultDate;
    var defaultDateFromData = this.$picker.data('default_date').split('-');
    // We change the day to be 31, as default_date defaults to the current day,
    // but we want to go as far as to the end of the current month.
    defaultDateFromData[2] = '31';
    var modifier = 1;

    // If for some reason the default_date didn't get or set incorrectly we should
    // try to fix it even though it may be indicative somewith else has gone wrong
    // on the backend.
    defaultDate = defaultDateFromData.length !== 3 ? new Date() : new Date(defaultDateFromData);

    // The server will sometimes return a min_bookable_date with the data request
    // If that happens we need to modify the default date to start from this
    // modified date.
    if (minBookableDate) {
      switch (minBookableDate.unit) {
        case 'month':
          modifier = 30;
          break;
        case 'week':
          modifier = 7;
          break;
      }
      modifier = modifier * minBookableDate.value;
      defaultDate.setDate(defaultDate.getDate() + modifier);
    }
    return defaultDate;
  };

  /**
   * Get number of days in a month
   *
   * @version 1.10.11
   * @since   1.10.11
   * @param   {number} [ month = currentMonth ] - The month in a 1 based index to get the number of days for.
   * @returns {number} Number of days in the month.
   */
  WC_Bookings_DatePicker.prototype.get_number_of_days_in_month = function get_number_of_days_in_month(month) {
    var currentDate = this.get_default_date();
    month = month || currentDate.getMonth() + 1;
    return new Date(currentDate.getFullYear(), month, 0).getDate();
  };

  /**
   * Get custom data that was set by the server prior to rendering the client.
   *
   * @version 1.10.11
   * @since   1.10.11
   * @param   {string} key - Custom data attribute to get.
   */
  WC_Bookings_DatePicker.prototype.get_custom_data = function get_custom_data(key) {
    if (!key) {
      return;
    }
    return this.customData[key] || null;
  };

  /**
   * Get data attribute set on the $picker element.
   *
   * @version 1.10.11
   * @since   1.10.11
   * @param   {string} attr - Data attribute to get.
   */
  WC_Bookings_DatePicker.prototype.get_data_attr = function get_data_attr(attr) {
    if (!attr) {
      return;
    }
    return this.$picker.data(attr);
  };

  /**
   * Gets a date range with a padding in days on either side of the range.
   *
   * @version 1.10.11
   * @since   1.10.11
   * @param   {Date}   date - Date to start from.
   * @param   {number} rangeInDays - Number of days to build for the range.
   * @param   {number} padInDays - Number of days to pad on either side of the range.
   */
  WC_Bookings_DatePicker.prototype.get_padded_date_range = function get_padded_date_range(date, rangeInDays, padInDays) {
    date = date || this.get_default_date();
    rangeInDays = rangeInDays || 30;
    padInDays = padInDays || 7;
    var currentDate = new Date();
    var isCurrentDayToday = date < currentDate;
    var startDate = new Date(date.setDate(isCurrentDayToday ? currentDate.getDate() : '01')); // We dont go back further than today
    var endDate = new Date(startDate.getTime());
    startDate.setDate(startDate.getDate() - (isCurrentDayToday ? 0 : padInDays)); // No reason to pad the left if the date is today
    endDate.setDate(endDate.getDate() + (rangeInDays + padInDays));
    if (startDate < currentDate) {
      startDate = currentDate;
    }
    return {
      startDate: startDate,
      endDate: endDate
    };
  };

  /**
   * Gets the date element attributes. This was formerly called is_bookable but changed names to more accurately reflect its new purpose.
   *
   * @version 1.10.11
   * @since   1.10.11
   * @param   {Date}   key - Date to get the element attributes for.
   * @returns {object} Attributes computed for the date.
   */
  WC_Bookings_DatePicker.prototype.getDateElementAttributes = function getDateElementAttributes(date) {
    var attributes = {
      class: [],
      title: '',
      selectable: true
    };
    var moment_date = moment(date);
    var resource_id = this.$form.find('select#wc_bookings_field_resource').val() > 0 ? this.$form.find('select#wc_bookings_field_resource').val() : 0;
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    var day_of_week = date.getDay();
    var ymdIndex = year + '-' + month + '-' + day;
    var today = new Date();

    // Unavailable days?
    if (this.bookingsData.unavailable_days && this.bookingsData.unavailable_days[ymdIndex] && this.bookingsData.unavailable_days[ymdIndex][resource_id]) {
      attributes.title = booking_form_params.i18n_date_unavailable;
      attributes.selectable = false;
      attributes.class.push('not_bookable');
    }

    // Unavailable days when the resources is automatically assigned.
    if (wc_bookings_booking_form.resources_assignment === 'automatic' && this.bookingsData.unavailable_days && this.bookingsData.unavailable_days[ymdIndex] && Object.keys(this.bookingsData.unavailable_days[ymdIndex]).length === wc_bookings_booking_form.resource_ids.length) {
      attributes.title = booking_form_params.i18n_date_unavailable;
      attributes.selectable = false;
      attributes.class.push('not_bookable');
      attributes.class.push('not_bookable_by_resources');
    }

    // Buffer days?
    if (this.bookingsData.buffer_days && this.bookingsData.buffer_days[ymdIndex]) {
      // If "resources_assignment" is set to customer selected, check if the selected resource has a buffer day for a particular date.
      // If "resources_assignment" is set to "automatic", make the date not bookable if it is a buffer day for all available resources.
      if (this.bookingsData.buffer_days[ymdIndex][0] || this.bookingsData.buffer_days[ymdIndex][resource_id] || 'automatic' === this.customData.resources_assignment && Object.keys(this.bookingsData.buffer_days[ymdIndex]).length === this.customData.resource_ids.length) {
        attributes.title = booking_form_params.i18n_date_unavailable;
        attributes.selectable = false;
        attributes.class.push('not_bookable');
      }
    }

    // Restricted days?
    if (this.bookingsData.restricted_days && undefined === this.bookingsData.restricted_days[day_of_week]) {
      attributes.title = booking_form_params.i18n_date_unavailable;
      attributes.selectable = false;
      attributes.class.push('not_bookable');
    }
    if (moment_date.isBefore(today, 'day')) {
      attributes.title = booking_form_params.i18n_date_unavailable;
      attributes.selectable = false;
      attributes.class.push('not_bookable');
    }
    var number_of_days = wc_bookings_date_picker.get_number_of_days(this.customData.booking_duration, this.$form, this.$picker, wc_bookings_booking_form);
    var block_args = {
      start_date: date,
      number_of_days: wc_bookings_booking_form.check_availability_against === 'start' ? 1 : number_of_days,
      fully_booked_days: this.bookingsData.fully_booked_days,
      availability: this.bookingsData.availability_rules,
      default_availability: this.customData.default_availability,
      resource_id: resource_id,
      resource_ids: this.customData.resource_ids,
      resources_assignment: this.customData.resources_assignment
    };
    var bookable = wc_bookings_date_picker.is_blocks_bookable(block_args);
    if ('not_bookable_by_rules' === bookable) {
      attributes.class.push('not_bookable_by_rules');
      bookable = false;
    }

    // Fully booked?
    if (this.bookingsData.fully_booked_days[ymdIndex]) {
      // If product does not have any resources, mark as fully booked if found in the array.
      // OR if a product with customer defined resources found in an array, mark as fully booked.
      // This conditions fixes issues #2881 & #3453.
      if (this.bookingsData.fully_booked_days[ymdIndex][0] && 0 === this.customData.resource_ids.length || 0 !== resource_id && this.bookingsData.fully_booked_days[ymdIndex][resource_id]) {
        attributes.title = booking_form_params.i18n_date_fully_booked;
        attributes.selectable = false;
        attributes.class.push('fully_booked');
        return attributes;
      } else if ('automatic' === this.customData.resources_assignment) {
        attributes.class.push('partial_booked');
      }
    }

    // Apply partially booked CSS class.
    if (this.bookingsData.partially_booked_days && this.bookingsData.partially_booked_days[ymdIndex]) {
      if ('automatic' === this.customData.resources_assignment || this.bookingsData.partially_booked_days[ymdIndex][0] || this.bookingsData.partially_booked_days[ymdIndex][resource_id]) {
        attributes.class.push('partial_booked');
      }
    }

    // Calculate end date to check 'in-range' or not.
    var fieldset = this.$picker.closest('fieldset');
    var start_date = $.datepicker.parseDate($.datepicker.ISO_8601, wc_bookings_date_picker.get_input_date(fieldset, ''));
    var end_date;
    if (this.$picker.data('is_range_picker_enabled')) {
      end_date = $.datepicker.parseDate($.datepicker.ISO_8601, wc_bookings_date_picker.get_input_date(fieldset, 'to_'));
    } else if (start_date && number_of_days > 1) {
      // We only want to do this for days, and number_of_days will
      // be 1 if the duration day is something different
      end_date = new Date(start_date);
      end_date.setDate(end_date.getDate() + (number_of_days - 1));
    }

    // Add bookable-range CSS to all days in the range
    const inRange = start_date && (moment_date.isSame(start_date, 'day') || end_date && moment_date.isSameOrAfter(start_date, 'day') && moment_date.isSameOrBefore(end_date, 'day'));

    // Add bookable-range CSS to all days in the range
    if (inRange) {
      attributes.class.push('bookable-range');
      // Add either selection-start-date or selection-end-date CSS to the first/last day only
      if (moment_date.isSame(start_date, 'day')) {
        attributes.class.push('selection-start-date');
      } else if (moment_date.isSame(end_date, 'day')) {
        attributes.class.push('selection-end-date');
      }
    }
    if (!bookable) {
      attributes.title = booking_form_params.i18n_date_unavailable;
      attributes.selectable = bookable;
      if (0 === resource_id) {
        attributes.class.push([this.bookingsData.fully_booked_days[ymdIndex] ? 'fully_booked' : 'not_bookable']);
      } else if (this.bookingsData.fully_booked_days[ymdIndex] && this.bookingsData.fully_booked_days[ymdIndex][resource_id]) {
        attributes.class.push([this.bookingsData.fully_booked_days[ymdIndex][resource_id] ? 'fully_booked' : 'not_bookable']);
      }
    } else {
      if (attributes.class.indexOf('partial_booked') > -1) {
        attributes.title = booking_form_params.i18n_date_partially_booked;
      } else if ('' === attributes.title) {
        attributes.title = booking_form_params.i18n_date_available;
      }
      if (!inRange) {
        attributes.class.push('bookable');
      }
    }

    /**
     * Filter date element attributes.
     *
     * @param {object} attributes Attributes for the date element.
     * @param {object} this.bookingsData Booking data.
     * @param {object} this.$picker Date picker.
     *
     * @since 1.15.79
     */
    attributes = (0,utils/* HookApi */.A)().applyFilters('wc_bookings_date_picker_get_day_attributes', attributes, {
      booking_data: this.bookingsData,
      custom_data: this.customData,
      date_picker: this.$picker,
      resource_id,
      date
    });
    return attributes;
  };
  moment.locale(wc_bookings_locale);
  wc_bookings_date_picker.init();
  // use global object added by wp_localize_script
  wc_bookings_booking_form.wc_bookings_date_picker = wc_bookings_date_picker;
});
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(428);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/**
 * External dependencies
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).ready(function ($) {
  var startSel = null,
    endSel = null;
  $('.wc-bookings-booking-form').closest('form').each((index, form) => {
    const $form = $(form);
    if ($form.find('.picker').data('is_range_picker_enabled')) {
      $form.find('p.wc_bookings_field_duration').hide();
    }
  });
  $('.block-picker').on('click', 'a', function () {
    const bookingform = $(this).closest('form');
    const $picker = bookingform.find('.picker');

    // Enable this event for month duration only to prevent unwanted ajax calls.
    if (!bookingform.find('[name="wc_bookings_field_start_date_yearmonth"]').length) {
      return false;
    }
    var value = $(this).data('value');
    var target = $(this).closest('div').find('input');
    var range;
    var value = $(this).data('value');
    var block_picker = $(this).closest('ul');
    set_selected_month(block_picker, value);
    if (!$picker.data('is_range_picker_enabled')) {
      // If we are not using range picker just trigger change for AJAX request.
      target.val(value).trigger('change');
      return;
    }

    // Unselect everything and set duration to default value.
    $(this).parent().siblings().children('a').removeClass('selected');
    $('#wc_bookings_field_duration').val(1);
    if (startSel && endSel) {
      // If we have both start and end selection, reset end.
      startSel = $(this).parent();
      endSel = null;
    } else if (startSel) {
      value = startSel.find('a').data('value');
      // If we have start selection, we set end selection and do calculation.
      endSel = $(this).parent();
      if (startSel.is(endSel)) {
        // Not a range, just a single entry.
        range = startSel;
      } else {
        // There is a range so select every entry inclusive.
        range = startSel.nextUntil(endSel).addBack();
        range = range.add(range.last().next());
      }
      range.children('a').addClass('selected');

      // Set duration and date and trigger change for AJAX request.
      $('#wc_bookings_field_duration').val(range.length);
    } else {
      startSel = $(this).parent();
    }
    target.val(value).trigger('change');
  });
  var max_width = 0;
  var max_height = 0;
  $('.block-picker a').each(function () {
    var width = $(this).width();
    var height = $(this).height();
    if (width > max_width) {
      max_width = width;
    }
    if (height > max_height) {
      max_height = height;
    }
  });
  $('.block-picker a').width(max_width);
  $('.block-picker a').height(max_height);
  function set_selected_month(block_picker, value) {
    var submit_button = block_picker.closest('form').find('.wc-bookings-booking-form-button');
    if (undefined === value) {
      submit_button.addClass('disabled');
      return;
    }
    var selected_block = block_picker.find('[data-value="' + value + '"]');
    if (undefined === selected_block.data('value')) {
      submit_button.addClass('disabled');
      return;
    }
    block_picker.closest('ul').find('a').removeClass('selected');
    selected_block.addClass('selected');
    submit_button.removeClass('disabled');
  }
});
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(428);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _bookings_lib__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(2);
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */

jquery__WEBPACK_IMPORTED_MODULE_0___default()(document).ready(function ($) {
  var local_timezone = moment.tz.guess() || booking_form_params.server_timezone;
  if (booking_form_params.timezone_conversion) {
    $('.wc-bookings-date-picker-timezone').text(local_timezone.replace('_', ' '));
    $('[name="wc_bookings_field_start_date_local_timezone"]').val(local_timezone);
  }
  $('.block-picker').on('click', 'a', function () {
    const bookingform = $(this).closest('form');

    // Don't enable this event for month duration.
    if (bookingform.find('[name="wc_bookings_field_start_date_yearmonth"]').length) {
      return false;
    }
    var value = $(this).data('value');
    var block_picker = $(this).closest('ul');
    set_selected_time(block_picker, value);
    return false;
  });
  function set_selected_time(block_picker, value) {
    var submit_button = block_picker.closest('form').find('.wc-bookings-booking-form-button');
    if (undefined === value) {
      submit_button.addClass('disabled');
      return;
    }
    var selected_block = block_picker.find('[data-value="' + value + '"]');
    if (undefined === selected_block.data('value')) {
      submit_button.addClass('disabled');
      return;
    }
    var target = block_picker.closest('div').find('input');
    target.val(value).trigger('change');
    block_picker.closest('ul').find('a').removeClass('selected');
    selected_block.addClass('selected');
    submit_button.removeClass('disabled');
  }
  function time_picker_reset_selected(block_picker) {
    block_picker.closest('ul').find('a').removeClass('selected');
  }
  wc_bookings_booking_form.time_picker_reset_selected = time_picker_reset_selected;
  $('.wc-bookings-booking-form').on('change', '#wc-bookings-form-start-time', function () {
    var value = $(this).val(),
      bookingForm = $(this).closest('form'),
      id = $(this).parents('.wc-bookings-start-time-container').data('productId'),
      blocks = $(this).parents('.wc-bookings-start-time-container').data('blocks'),
      formField = $(this).parents('.form-field').eq(0);

    // Disable end time dropdown.
    $('#wc-bookings-form-end-time').attr('disabled', 'disabled');

    // Set the end time dropdown opacity.
    $('#wc-bookings-form-end-time').css('opacity', '0.5');

    // Hide the cost as a reset.
    $(this).closest('form').find('.wc-bookings-booking-cost').html('').hide();
    if ('0' === value) {
      $(this).closest('form').find('.wc-bookings-booking-form-button').addClass('disabled');
      return false;
    }

    // Disable the Book button.
    bookingForm.find('.wc-bookings-booking-form-button').addClass('disabled');
    var resource_id = bookingForm.find('#wc_bookings_field_resource').val();
    var year_str = bookingForm.find('input.booking_date_year').val();
    var year = parseInt(year_str, 10);
    var month_str = bookingForm.find('input.booking_date_month').val();
    var month = parseInt(month_str, 10);
    var day_str = bookingForm.find('input.booking_date_day').val();
    var day = parseInt(day_str, 10);
    var person_qty = bookingForm.find('#wc_bookings_field_persons').val();
    var date_str = year_str + '-' + month_str + '-' + day_str;
    if (!year || !month || !day) {
      return;
    }
    xhr = $.ajax({
      type: 'POST',
      url: booking_form_params.ajax_url,
      data: {
        action: 'wc_bookings_get_end_time_html',
        security: booking_form_params.nonce.get_end_time_html,
        start_date_time: value,
        product_id: id,
        blocks: blocks,
        resource_id: resource_id,
        person_qty
      },
      success: function (response) {
        bookingForm.find('.wc-bookings-end-time-container').replaceWith(response);
        offset_block_times_for_end_time(date_str);
        formField.find('input.required_for_calculation').val(value);
      },
      dataType: "html"
    });
    return false;
  });
  $('.wc-bookings-booking-form').on('change', '#wc-bookings-form-end-time', function () {
    // Hide the cost as a reset.
    $('.wc-bookings-booking-cost').html('').hide();
    var value = $(this).val(),
      block_picker = $(this);
    $(this).parents('.wc-bookings-booking-form').find('.wc_bookings_field_duration').val(value);
    var submit_button = block_picker.closest('form').find('.wc-bookings-booking-form-button');
    if (undefined === value || '0' === value || '0' === $(this).parents('.wc-bookings-booking-form').find('#wc-bookings-form-start-time').val()) {
      submit_button.addClass('disabled');
      return;
    }
    block_picker.parents('.form-field').eq(0).find('input.required_for_calculation').trigger('change');
    submit_button.removeClass('disabled');
  });
  function set_selected_customer_time(block_picker, value) {
    var submit_button = block_picker.closest('form').find('.wc-bookings-booking-form-button');
    if (undefined === value || '0' === value) {
      submit_button.addClass('disabled');
      return;
    }
    var target = block_picker.parents('.form-field').eq(0).find('input.required_for_calculation');
    target.val(value).trigger('change');
    submit_button.removeClass('disabled');
  }
  $('.wc_bookings_field_duration').on('change', function () {
    if (!['hour', 'minute'].includes(wc_bookings_booking_form.get_booking_duration_unit($(this)))) {
      return;
    }
    show_available_time_blocks(this);
  });
  $('#wc_bookings_field_resource').on('change', function () {
    const duration_unit = wc_bookings_booking_form.get_booking_duration_unit($(this));
    if ('month' === duration_unit) {
      show_available_month_blocks(this);
    } else if (!['hour', 'minute'].includes(duration_unit)) {
      return;
    }
    time_picker_reset_selected($('.wc-bookings-booking-form').find('.block-picker'));
  });
  $('.wc-bookings-booking-form fieldset').on('date-selected', function () {
    $('.wc_bookings_field_duration').val(1);
    if (!['hour', 'minute'].includes(wc_bookings_booking_form.get_booking_duration_unit($(this)))) {
      return;
    }
    show_available_time_blocks(this);
  });
  var xhr;
  function show_available_month_blocks(element) {
    var $form = $(element).closest('form');
    var form_val = $form.serialize();
    var block_picker = $(element).closest('div').find('.block-picker');
    block_picker.closest('div').block({
      message: null,
      overlayCSS: {
        background: '#fff',
        backgroundSize: '16px 16px',
        opacity: 0.6
      }
    }).show();
    var xhr = $.ajax({
      type: 'POST',
      url: booking_form_params.ajax_url,
      data: {
        action: 'wc_bookings_get_booking_blocks',
        form: form_val,
        security: booking_form_params.nonce.show_available_month_blocks
      },
      success: function (result) {
        result = JSON.parse(result);

        // Check for failure.
        if (!result.success) {
          alert(result.data);
          return false;
        }
        block_picker.closest('div').unblock();
        $('.block-picker').html(result.data);
      },
      error: function (jqXHR, exception) {
        if ('abort' === exception) {
          return; // Assuming the date is changed very quickly.
        }
        (0,_bookings_lib__WEBPACK_IMPORTED_MODULE_1__/* .display_error */ .u)();
      },
      dataType: 'html'
    });
  }
  function show_available_time_blocks(element) {
    var $form = $(element).closest('form');
    var fieldset = $(element).closest('div').find('fieldset');
    var block_picker = $(element).closest('div').find('.block-picker');
    var selected_block = block_picker.find('.selected');
    var year_str = fieldset.find('input.booking_date_year').val();
    var year = parseInt(year_str, 10);
    var month_str = fieldset.find('input.booking_date_month').val();
    var month = parseInt(month_str, 10);
    var day_str = fieldset.find('input.booking_date_day').val();
    var day = parseInt(day_str, 10);
    var date_str = year_str + '-' + month_str + '-' + day_str;
    if (!year || !month || !day) {
      return;
    }

    // clear blocks
    block_picker.closest('div').find('input').val('').trigger('change');
    block_picker.closest('div').block({
      message: null,
      overlayCSS: {
        background: '#fff',
        backgroundSize: '16px 16px',
        opacity: 0.6
      }
    }).show();
    $form.find('.wc-bookings-booking-cost').html('').hide();

    // Get blocks via ajax
    if (xhr) xhr.abort();
    var form_val = $form.serialize();

    /*
     * Get previous/next day in addition to current day based on server/client timezone offset.
     * This will give the client enough blocks to fill out 24 hours of blocks in its timezone.
     */
    var server_offset = (0,_bookings_lib__WEBPACK_IMPORTED_MODULE_1__/* .get_client_server_timezone_offset_hrs */ .f)(date_str);
    if (server_offset < 0) {
      form_val += '&get_next_day=true';
    } else if (server_offset > 0) {
      form_val += '&get_prev_day=true';
    }
    xhr = $.ajax({
      type: 'POST',
      url: booking_form_params.ajax_url,
      data: {
        action: 'wc_bookings_get_blocks',
        form: form_val
      },
      success: function (code) {
        block_picker.html(code);
        resize_blocks();
        offset_block_times(date_str, $(element));
        block_picker.closest('div').unblock();
        set_selected_time(block_picker, selected_block.data('value'));

        // Hide error notices.
        $('.woocommerce-error.wc-bookings-notice').slideUp();
      },
      error: function (jqXHR, exception) {
        if ('abort' === exception) {
          return; // Assuming the date is changed very quickly.
        }
        (0,_bookings_lib__WEBPACK_IMPORTED_MODULE_1__/* .display_error */ .u)();

        // Remove loading spin and refresh the datepicker.
        $('.blockOverlay').remove();
        wc_bookings_booking_form.wc_bookings_date_picker.clear_selection();
        wc_bookings_booking_form.wc_bookings_date_picker.refresh_datepicker();
      },
      dataType: 'html'
    });
  }
  function resize_blocks() {
    var max_width = 0;
    var max_height = 0;
    $('.block-picker a').each(function () {
      var width = $(this).width();
      var height = $(this).height();
      if (width > max_width) {
        max_width = width;
      }
      if (height > max_height) {
        max_height = height;
      }
    });
    $('.block-picker a').width(max_width);
    $('.block-picker a').height(max_height);
  }
  function offset_block_times_for_end_time(date_str) {
    if (!booking_form_params.timezone_conversion) {
      return;
    }
    var from = moment.tz(date_str, local_timezone);
    var to = moment(from);
    var element = '.block-picker #wc-bookings-form-end-time > option';
    to.add(1, 'days');
    $(element).each(function () {
      var block_time = $(this).data('value');
      if ('undefined' === typeof block_time || '0' == block_time) {
        return true;
      }
      var server_offset = (0,_bookings_lib__WEBPACK_IMPORTED_MODULE_1__/* .get_client_server_timezone_offset_hrs */ .f)(date_str);
      var client_local_time = moment.tz(block_time, booking_form_params.server_timezone);
      var duration_display = $(this).data('durationDisplay');
      client_local_time.add(server_offset, 'hours');
      $(this).text(client_local_time.format(booking_form_params.server_time_format) + duration_display);
    });
  }
  function offset_block_times(date_str, element) {
    if (!booking_form_params.timezone_conversion) {
      return;
    }
    const booking_duration_type = wc_bookings_booking_form.get_booking_duration_type(element);
    const booking_duration_unit = wc_bookings_booking_form.get_booking_duration_unit(element);
    var from = moment.tz(date_str, local_timezone);
    var to = moment(from);
    var selector = '.block-picker .block a';
    to.add(1, 'days');
    if ('customer' === booking_duration_type && ['hour', 'minute'].includes(booking_duration_unit)) {
      selector = '.block-picker #wc-bookings-form-start-time > option';
    }
    $(selector).each(function () {
      if ('.block-picker #wc-bookings-form-start-time > option' === selector) {
        var block_time = $(this).val();
      } else {
        var block_time = $(this).attr('data-value'); // iso8061 format time string
      }
      if ('undefined' === typeof block_time || '0' == block_time) {
        return true;
      }
      var server_offset = (0,_bookings_lib__WEBPACK_IMPORTED_MODULE_1__/* .get_client_server_timezone_offset_hrs */ .f)(date_str);
      var server_local_time = moment.tz(block_time, booking_form_params.server_timezone);
      var client_local_time = moment.tz(block_time, booking_form_params.server_timezone);
      client_local_time.add(server_offset, 'hours');
      if (!server_local_time.isBetween(from, to, null, '[)')) {
        if ('.block-picker #wc-bookings-form-start-time > option' === selector) {
          // Delete any blocks outside of today.
          $(this).remove();
        } else {
          // Delete any blocks outside of today.
          $(this).parent().remove();
        }
      } else {
        if ('.block-picker #wc-bookings-form-start-time > option' === selector) {
          const block_time = moment($(this).val()).unix();
          const current_browser_time = moment().unix();
          if (block_time <= current_browser_time) {
            // Delete any blocks outside of right now in the past.
            $(this).remove();
          } else {
            $(this).text(server_local_time);
          }
        }
        $(this).text(client_local_time.format(booking_form_params.server_time_format));

        // If the block has existing bookings, show the remaining slots after the time.
        if ($(this).data('remaining')) {
          if ('.block-picker #wc-bookings-form-start-time > option' === selector) {
            $(this).append(' (' + $(this).data('remaining') + ')');
          } else {
            $(this).append(' <small class="booking-spaces-left">(' + $(this).data('remaining') + ')</small>');
          }
        }
      }
    });
  }
});
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
// extracted by mini-css-extract-plugin

})();

/******/ })()
;
//# sourceMappingURL=frontend.js.map