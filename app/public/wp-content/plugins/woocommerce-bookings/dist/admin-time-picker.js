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
/******/ })()
;
//# sourceMappingURL=admin-time-picker.js.map