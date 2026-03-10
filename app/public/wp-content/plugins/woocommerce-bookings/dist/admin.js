/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ 20:
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;
/**
 * @license React
 * react-jsx-runtime.production.min.js
 *
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var f=__webpack_require__(609),k=Symbol.for("react.element"),l=Symbol.for("react.fragment"),m=Object.prototype.hasOwnProperty,n=f.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,p={key:!0,ref:!0,__self:!0,__source:!0};
function q(c,a,g){var b,d={},e=null,h=null;void 0!==g&&(e=""+g);void 0!==a.key&&(e=""+a.key);void 0!==a.ref&&(h=a.ref);for(b in a)m.call(a,b)&&!p.hasOwnProperty(b)&&(d[b]=a[b]);if(c&&c.defaultProps)for(b in a=c.defaultProps,a)void 0===d[b]&&(d[b]=a[b]);return{$$typeof:k,type:c,key:e,ref:h,props:d,_owner:n.current}}__webpack_unused_export__=l;exports.jsx=q;__webpack_unused_export__=q;


/***/ }),

/***/ 428:
/***/ ((module) => {

module.exports = window["jQuery"];

/***/ }),

/***/ 609:
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ 723:
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ 848:
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {



if (true) {
  module.exports = __webpack_require__(20);
} else // removed by dead control flow
{}


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

;// external ["wp","element"]
const external_wp_element_namespaceObject = window["wp"]["element"];
;// external ["wp","components"]
const external_wp_components_namespaceObject = window["wp"]["components"];
// EXTERNAL MODULE: external "jQuery"
var external_jQuery_ = __webpack_require__(428);
var external_jQuery_default = /*#__PURE__*/__webpack_require__.n(external_jQuery_);
// EXTERNAL MODULE: ./node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__(848);
;// ./src/js/writepanel.js




external_jQuery_default()(document).ready(function ($) {
  $('#bookings_availability, #bookings_pricing, .bookings_extension').on('change', '.wc_booking_availability_type select, .wc_booking_pricing_type select', function () {
    var value = $(this).val();
    var tr = $(this).closest('tr');
    var row = $(tr);

    // cleanup
    row.find('.from_date, .from_day_of_week, .from_month, .from_week, .from_time, .from').hide();
    row.find('.to_date, .to_day_of_week, .to_month, .to_week, .to_time, .to').hide();
    row.find('.repeating-label').hide();
    row.find('.bookings-datetime-select-to').removeClass('bookings-datetime-select-both');
    row.find('.bookings-datetime-select-from').removeClass('bookings-datetime-select-both');
    row.find('.bookings-to-label-row .bookings-datetimerange-second-label').hide();
    row.find('.rrule').hide();
    if (value == 'custom') {
      row.find('.from_date, .to_date').show();
    }
    if (value == 'custom:daterange') {
      row.find('.from_time, .to_time').show();
      row.find('.from_date, .to_date').show();
      row.find('.bookings-datetime-select-to').addClass('bookings-datetime-select-both');
      row.find('.bookings-datetime-select-from').addClass('bookings-datetime-select-both');
      row.find('.bookings-to-label-row .bookings-datetimerange-second-label').show();
    }
    if (value == 'months') {
      row.find('.from_month, .to_month').show();
    }
    if (value == 'weeks') {
      row.find('.from_week, .to_week').show();
    }
    if (value == 'days') {
      row.find('.from_day_of_week, .to_day_of_week').show();
    }
    if (value.match("^time")) {
      row.find('.from_time, .to_time').show();
      // Show the date range as well if "time range for custom dates" is selected
      if ('time:range' === value) {
        row.find('.from_date, .to_date').show();
        row.find('.repeating-label').show();
        row.find('.bookings-datetime-select-to').addClass('bookings-datetime-select-both');
        row.find('.bookings-datetime-select-from').addClass('bookings-datetime-select-both');
        row.find('.bookings-to-label-row .bookings-datetimerange-second-label').show();
      }
    }
    if (value == 'persons' || value == 'duration' || value == 'blocks') {
      row.find('.from, .to').show();
    }
    if (value === 'rrule') {
      row.find('.rrule').show();
    }
    $('#availability_rows tr').show();
  });

  /**
   * Sanitizes the start and end dates when datepicker value
   * is set through on onSelect.
   *
   * If date comparison fails, it sets both the start and end dates
   * to the same date.
   *
   * @param {object} ref Reference to the input HTML DOM element.
   */
  function startEndDateSanitizer(ref) {
    const input = $(ref);
    const dateType = input.closest('div').attr('class');
    const todaysDate = new Date();
    let startDate,
      endDate = null;
    let inputComplement = null;
    switch (dateType) {
      case 'from_date':
        inputComplement = input.closest('tr').find('.to_date .date-picker');
        startDate = input.datepicker('getDate');
        endDate = inputComplement.datepicker('getDate');
        if (startDate < todaysDate) {
          input.datepicker('setDate', todaysDate);
        } else if (endDate && startDate > endDate) {
          inputComplement.datepicker('setDate', startDate);
        }
        break;
      case 'to_date':
        inputComplement = input.closest('tr').find('.from_date .date-picker');
        endDate = input.datepicker('getDate');
        startDate = inputComplement.datepicker('getDate');
        if (endDate < startDate) {
          input.datepicker('setDate', startDate);
        }
        break;
      default:
        break;
    }
  }
  $(document).ready(function () {
    // Destroy all instances of date picker under availability rows.
    $('#availability_rows .date-picker, #pricing_rows .date-picker, #rates_rows .date-picker').datepicker('destroy');
    initializeDatepickers();
  });
  $('body').on('row_added', function () {
    $('.wc_booking_availability_type select, .wc_booking_pricing_type select').trigger('change');
    initializeDatepickers();
  });

  /**
   * Initializes datepickers throughout bookings.
   */
  function initializeDatepickers() {
    $('#availability_rows .date-picker, #pricing_rows .date-picker, #rates_rows .date-picker, .booking_start_date_field').datepicker({
      dateFormat: 'yy-mm-dd',
      numberOfMonths: 1,
      showButtonPanel: true,
      showOn: 'button',
      buttonImage: wc_bookings_admin_js_params.calendar_image,
      buttonImageOnly: true,
      minDate: 0,
      firstDay: parseInt(wc_bookings_admin_js_params.start_of_week),
      onSelect: function () {
        startEndDateSanitizer(this);
      },
      beforeShow: function (input) {
        if ($(input).parent().hasClass('to_date')) {
          const from_date = $(input).closest('tr').find('.from_date .date-picker').datepicker('getDate');
          return {
            'minDate': from_date
          };
        } else {
          return {
            'minDate': 0
          };
        }
      }
    });
  }
  $(document).on('change', '#availability_rows .date-picker, #pricing_rows .date-picker', function () {
    startEndDateSanitizer(this);
  });
  $('body').on('woocommerce-product-type-change', function (event, type) {
    if ('booking' !== type) {
      $('#_wc_booking_has_persons').prop('checked', false);
      $('#_wc_booking_has_resources').prop('checked', false);
    }
    wc_bookings_trigger_change_events();
  });
  function wc_bookings_trigger_change_events() {
    $('.wc_booking_availability_type select, .wc_booking_pricing_type select, #_wc_booking_duration_type, #_wc_booking_user_can_cancel, #_wc_booking_duration_unit, #_wc_booking_has_persons, #_wc_booking_has_resources, #_wc_booking_has_person_types, #_wc_booking_has_restricted_days').trigger('change');
  }
  $('input#_virtual').on('change', function () {
    wc_bookings_trigger_change_events();
  });
  $('#_wc_booking_duration_type').on('change', function () {
    if ($(this).val() == 'customer') {
      $('#min_max_duration').show();
    } else {
      $('#min_max_duration').hide();
    }
  });

  // limit hourly duration up to 24.
  $('#_wc_booking_duration, #_wc_booking_duration_unit, #_wc_booking_default_date_availability').on('change', function () {
    limit_hourly_duration($(this));
  });
  $('#_wc_booking_duration_unit').on('change', function () {
    $('.availability_time, ._wc_booking_first_block_time_field').hide();
    $('#enable-range-picker').hide();
    $('._wc_booking_apply_adjacent_buffer_field').show();
    $('._wc_booking_buffer_period').show();
    switch ($(this).val()) {
      case 'hour':
      case 'minute':
        var unit_text = 'hour' === $(this).val() ? wc_bookings_admin_js_params.i18n_hours : wc_bookings_admin_js_params.i18n_minutes;
        $('._wc_booking_buffer_period_unit').text(unit_text);
        $('.availability_time, ._wc_booking_first_block_time_field').show();
        break;
      case 'month':
        $('#enable-range-picker').show();
        $('._wc_booking_buffer_period').hide();
        $('._wc_booking_apply_adjacent_buffer_field').hide();
        break;
      default:
        //day
        $('#enable-range-picker').show();
        $('._wc_booking_buffer_period_unit').text(wc_bookings_admin_js_params.i18n_days);
        break;
    }
  });
  $('#_wc_booking_user_can_cancel').on('change', function () {
    if ($(this).is(':checked')) {
      $('.form-field.booking-cancel-limit').show();
    } else {
      $('.form-field.booking-cancel-limit').hide();
    }
  });
  $('#_wc_booking_has_persons').on('change', function () {
    if ($(this).is(':checked')) {
      $('#persons-options, .bookings_persons_tab').show();
    } else {
      $('#persons-options, .bookings_persons_tab').hide();
    }
    $('ul.wc-tabs li:visible').eq(0).find('a').trigger('click');
  });
  $('#_wc_booking_has_person_types').on('change', function () {
    if ($(this).is(':checked')) {
      $('#persons-types').show();
    } else {
      $('#persons-types').hide();
    }
  });
  $('#_wc_booking_has_resources').on('change', function () {
    if ($(this).is(':checked')) {
      $('.bookings_resources_tab').show();
      $('._wc_booking_qty_field').addClass('blur').attr('data-note', wc_bookings_admin_js_params.i18n_max_booking_overwridden);
    } else {
      $('.bookings_resources_tab').hide();
      $('._wc_booking_qty_field').removeClass('blur');
    }
    $('ul.wc-tabs li:visible').eq(0).find('a').trigger('click');
  });
  $('#_wc_booking_has_restricted_days').on('change', function () {
    if ($(this).is(':checked')) {
      $('.booking-day-restriction').show();
    } else {
      $('.booking-day-restriction').hide();
    }
  });
  wc_bookings_trigger_change_events();
  $('#availability_rows, #pricing_rows').sortable({
    items: 'tr',
    cursor: 'move',
    axis: 'y',
    handle: '.sort',
    scrollSensitivity: 40,
    forcePlaceholderSize: true,
    helper: 'clone',
    opacity: 0.65,
    placeholder: 'wc-metabox-sortable-placeholder',
    start: function (event, ui) {
      ui.item.css('background-color', '#f6f6f6');
    },
    stop: function (event, ui) {
      ui.item.removeAttr('style');
      ui.item.show();

      // loop through each of the rows
      external_jQuery_default()(event.target).find('tr').each(function (rowIndex, item) {
        // update all the form field indexes in the current moved tr
        var fields = external_jQuery_default()(item).find('[name*="wc_booking"]');
        for (var i = 0; i < fields.length; i++) {
          var field = fields[i];
          var oldName = external_jQuery_default()(field).attr('name');
          var newName = oldName.replace(/[\d+]/g, rowIndex);
          if (newName !== oldName) {
            external_jQuery_default()(field).attr('name', newName);
          }
        }
      });
    }
  });
  $('.add_row').on('click', function (e) {
    var indexField = $(e.target).closest('table').find('#pricing_rows tr:last [name*=wc_booking_pricing_type]').attr('name');
    var newRowIndex = indexField ? parseInt(indexField.replace(/[^\[]+\[(.*)\]/, '$1')) + 1 : 0;
    var newRow = $(this).data('row');
    newRow = newRow.replace(/bookings_cost_js_index_replace/ig, newRowIndex.toString());
    // Clear out IDs.
    newRow = newRow.replace(/wc_booking_availability_id.+/, 'wc_booking_availability_id[]" value="" />');
    newRow = newRow.replace(/data-id=.+/, 'data-id="">');

    // Clear out title.
    newRow = newRow.replace(/wc_booking_availability_title.+/, 'wc_booking_availability_title[]" value="" style="border:1px solid #ddd;background-color:#fff;" />');

    // Clear out priority.
    newRow = newRow.replace(/wc_booking_availability_priority.+/, 'wc_booking_availability_priority[]" value="10" placeholder="10" />');
    var tableBody = $(this).closest('table').find('tbody');
    tableBody.append(newRow);
    $('body').trigger('row_added', tableBody.find('tr:last'));
    return false;
  });
  $('body').on('click', 'td.remove', function () {
    var row = $(this).parent('tr'),
      id = row.data('id');

    // Get current deleted list.
    var deleted = $('.wc-booking-availability-deleted').val();

    // Add to deleted list.
    deleted = deleted + ',' + id;
    $('.wc-booking-availability-deleted').val(deleted);
    row.remove();
  });
  $('#bookings_persons').on('change', 'input.person_name', function () {
    $(this).closest('.woocommerce_booking_person').find('span.person_name').text($(this).val());
  });

  // limit hourly duration up to 24.
  function limit_hourly_duration(_this) {
    const booking_duration = $('#_wc_booking_duration');
    if ('hour' === $('#_wc_booking_duration_unit').val() && 24 < booking_duration.val() && 'non-available' === $('#_wc_booking_default_date_availability').val()) {
      _this.parents('.form-field').addClass('limited-hours');
      let limited_hours_notice = _this.parents('._wc_booking_default_date_availability_field').length !== 0 ? wc_bookings_admin_js_params.i18n_limited_hours_in_gen_tab : wc_bookings_admin_js_params.i18n_limited_hours;
      _this.parents('.form-field').addClass('limited-hours').attr('data-note', limited_hours_notice);
      booking_duration.val(24);
    } else {
      $('.form-field').removeClass('limited-hours');
    }
  }

  // Add a person type
  external_jQuery_default()('#bookings_persons').on('click', 'button.add_person', function () {
    external_jQuery_default()('.woocommerce_bookable_persons').block({
      message: null
    });
    var loop = external_jQuery_default()('.woocommerce_booking_person').length;
    var data = {
      action: 'woocommerce_add_bookable_person',
      post_id: wc_bookings_admin_js_params.post,
      loop: loop,
      security: wc_bookings_admin_js_params.nonce_add_person
    };
    external_jQuery_default().post(wc_bookings_admin_js_params.ajax_url, data, function (response) {
      external_jQuery_default()('.woocommerce_bookable_persons').append(response).unblock();
      external_jQuery_default()('.woocommerce_bookable_persons #message').hide();
      $('.woocommerce_bookable_persons').sortable(persons_sortable_options);
      // Trigger change to set max attribute.
      $('#_wc_booking_max_persons_group').trigger('change');
    });
    return false;
  });

  // Remove a person type
  external_jQuery_default()('#bookings_persons').on('click', 'button.unlink_booking_person', function (e) {
    e.preventDefault();
    var answer = confirm(wc_bookings_admin_js_params.i18n_remove_person);
    if (answer) {
      var el = external_jQuery_default()(this).parent().parent();
      var person = external_jQuery_default()(this).attr('rel');
      if (person > 0) {
        external_jQuery_default()(el).block({
          message: null
        });
        var data = {
          action: 'woocommerce_unlink_bookable_person',
          person_id: person,
          security: wc_bookings_admin_js_params.nonce_unlink_person
        };
        external_jQuery_default().post(wc_bookings_admin_js_params.ajax_url, data, function (response) {
          external_jQuery_default()(el).fadeOut('300', function () {
            external_jQuery_default()(el).remove();
          });
        });
      } else {
        external_jQuery_default()(el).fadeOut('300', function () {
          external_jQuery_default()(el).remove();
        });
      }
    }
    return false;
  });
  var persons_sortable_options = {
    items: '.woocommerce_booking_person',
    cursor: 'move',
    axis: 'y',
    handle: 'h3',
    scrollSensitivity: 40,
    forcePlaceholderSize: true,
    helper: 'clone',
    opacity: 0.65,
    placeholder: 'wc-metabox-sortable-placeholder',
    start: function (event, ui) {
      ui.item.css('background-color', '#f6f6f6');
    },
    stop: function (event, ui) {
      ui.item.removeAttr('style');
      person_row_indexes();
    }
  };
  $('.woocommerce_bookable_persons').sortable(persons_sortable_options);
  function person_row_indexes() {
    $('.woocommerce_bookable_persons .woocommerce_booking_person').each(function (index, el) {
      $('.person_menu_order', el).val(parseInt($(el).index('.woocommerce_bookable_persons .woocommerce_booking_person'), 10));
    });
  }
  ;
  $('#bookings_resources').on('change', 'input.resource_name', function () {
    $(this).closest('.woocommerce_booking_resource').find('span.resource_name').text($(this).val());
  });

  // Add a resource
  external_jQuery_default()('#bookings_resources').on('click', 'button.add_resource', function () {
    var loop = external_jQuery_default()('.woocommerce_booking_resource').length;
    var add_resource_id = external_jQuery_default()('select.add_resource_id').val();
    var add_resource_name = '';
    if (!add_resource_id) {
      add_resource_name = prompt(wc_bookings_admin_js_params.i18n_new_resource_name);
      if (!add_resource_name) {
        return false;
      }
    }
    external_jQuery_default()('.woocommerce_bookable_resources').block({
      message: null
    });
    var data = {
      action: 'woocommerce_add_bookable_resource',
      post_id: wc_bookings_admin_js_params.post,
      loop: loop,
      add_resource_id: add_resource_id,
      add_resource_name: add_resource_name,
      security: wc_bookings_admin_js_params.nonce_add_resource
    };
    external_jQuery_default().post(wc_bookings_admin_js_params.ajax_url, data, function (response) {
      if (response.error) {
        alert(response.error);
      } else {
        external_jQuery_default()('.woocommerce_bookable_resources').append(response.html).unblock();
        external_jQuery_default()('.woocommerce_bookable_resources').sortable(resources_sortable_options);
        if (add_resource_id) {
          external_jQuery_default()('.add_resource_id').find('option[value=' + add_resource_id + ']').remove();
        }
      }
    });
    return false;
  });

  // Remove a resource
  external_jQuery_default()('#bookings_resources').on('click', 'button.remove_booking_resource', function (e) {
    e.preventDefault();
    var answer = confirm(wc_bookings_admin_js_params.i18n_remove_resource);
    if (answer) {
      var el = external_jQuery_default()(this).parent().parent();
      var resource = external_jQuery_default()(this).attr('rel');
      external_jQuery_default()(el).block({
        message: null,
        overlayCSS: {
          background: '#fff url(' + wc_bookings_admin_js_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
          opacity: 0.6
        }
      });
      var data = {
        action: 'woocommerce_remove_bookable_resource',
        post_id: wc_bookings_admin_js_params.post,
        resource_id: resource,
        security: wc_bookings_admin_js_params.nonce_delete_resource
      };
      external_jQuery_default().post(wc_bookings_admin_js_params.ajax_url, data, function (response) {
        external_jQuery_default()(el).fadeOut('300', function () {
          external_jQuery_default()(el).remove();
          var resource_id = external_jQuery_default()(el).find('input[name*=resource_id]').val();
          var resource_title = external_jQuery_default()(el).find('input[name*=resource_title]').val();
          external_jQuery_default()('select[name=add_resource_id]').append(external_jQuery_default()('<option>', {
            value: resource_id,
            text: resource_title
          }));
        });
      });
    }
    return false;
  });
  var resources_sortable_options = {
    items: '.woocommerce_booking_resource',
    cursor: 'move',
    axis: 'y',
    handle: 'h3',
    scrollSensitivity: 40,
    forcePlaceholderSize: true,
    helper: 'clone',
    opacity: 0.65,
    placeholder: 'wc-metabox-sortable-placeholder',
    start: function (event, ui) {
      ui.item.css('background-color', '#f6f6f6');
    },
    stop: function (event, ui) {
      ui.item.removeAttr('style');
      resource_row_indexes();
    }
  };
  $('.woocommerce_bookable_resources').sortable(resources_sortable_options);
  function resource_row_indexes() {
    $('.woocommerce_bookable_resources .woocommerce_booking_resource').each(function (index, el) {
      $('.resource_menu_order', el).val(parseInt($(el).index('.woocommerce_bookable_resources .woocommerce_booking_resource'), 10));
    });
  }

  // Set max attribute for the max persons field in person types.
  $('#bookings_persons').on('change', '#_wc_booking_max_persons_group', function () {
    const max_persons_group = $(this).val();
    if (max_persons_group && max_persons_group > 0) {
      $('.woocommerce_bookable_persons').find('input.person_max, input.person_min').each(function () {
        $(this).attr('max', max_persons_group);
      });
    } else {
      $('.woocommerce_bookable_persons').find('input.person_max, input.person_min').each(function () {
        $(this).removeAttr('max');
      });
    }
  });
  // Trigger change to set max attribute values of max for person types.
  $('#_wc_booking_max_persons_group').trigger('change');

  // Render the WordPress timepicker for the time input field inside "from_time" container.
  // This is needed for the initial load.
  // The timepicker is not rendered for the "google-event" rows.
  document.querySelectorAll(['#availability_rows tr:not(.google-event) .from_time', '#availability_rows tr:not(.google-event) .to_time', '#pricing_rows .from_time', '#pricing_rows .to_time',
  // This class must be applied to a direct parent of input[type="time"]
  // input[type="time"] must be the only child of the parent container
  '.wc-bookings-render-wp-time-picker'].join(', ')).forEach(el => {
    renderWPTimePicker(el);
  });

  // Render the WordPress timepicker for the time input field inside "from_time" container when a new row is added.
  $('body').on('row_added', function (e, jQueryRowSelector) {
    // Do not render the timepicker for the "google-event" rows.
    if (jQueryRowSelector.classList.contains('google-event')) {
      return;
    }
    if (jQueryRowSelector.closest('#availability_rows') || jQueryRowSelector.closest('#pricing_rows')) {
      jQueryRowSelector.querySelectorAll('.from_time, .to_time').forEach(el => {
        renderWPTimePicker(el);
      });
    }
  });

  /**
   * Should render the WordPress timepicker.
   *
   * @since 1.16.0
   * @param {HTMLElement} el The time input field container. div.from_time or div.to_time.
   *
   * @return {void}
   */
  function renderWPTimePicker(el) {
    const timeElement = el.querySelector('input[type="time"]');
    const timeIn24Hours = timeElement.value ? timeElement.value.split(':') :
    // Saved time
    ['00', '00']; // Default time.
    const currentTime = new Date();

    // Edit current time to set the time (hours, minutes) from the time input field.
    currentTime.setHours(timeIn24Hours[0]);
    currentTime.setMinutes(timeIn24Hours[1]);

    // This function handles WP Timpicker change event to update the hidden input field with the time.
    let setTimeAsValueToInputField = (timeFieldContainer, time) => {
      const date = new Date(time);
      const hours = date.getHours().toString().padStart(2, '0');
      const minutes = date.getMinutes().toString().padStart(2, '0');

      // Update the hidden input field.
      timeFieldContainer.querySelector('input:last-child').value = `${hours}:${minutes}`;
    };
    setTimeAsValueToInputField = setTimeAsValueToInputField.bind(null, el);

    // Replace time input with TimePicker.
    (0,external_wp_element_namespaceObject.render)(/*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.TimePicker, {
      currentTime: currentTime,
      is12Hour: '1' === wc_bookings_admin_js_params.time_in_12hours,
      onChange: setTimeAsValueToInputField,
      __nextRemoveHelpButton: true,
      __nextRemoveResetButton: true
    }), el);

    // Add hidden input to store the time value.
    el.insertAdjacentHTML('beforeend', `<input type="hidden" name="${timeElement.name}" value="${timeElement.value}" />`);
  }

  // When the publish button is clicked, check if there are invalid fields in the person types section.
  $('#publish').on('click', function (e) {
    // Find all invalid fields in the person types section
    var $invalidFields = $('.woocommerce_bookable_persons input:invalid');
    if ($invalidFields.length) {
      // Open the person types tab.
      $('.bookings_persons_tab a').click();

      // For each invalid field, expand its parent section if closed.
      $invalidFields.each(function () {
        var $personSection = $(this).closest('.woocommerce_booking_person');
        if ($personSection.hasClass('closed')) {
          $personSection.find('h3').click();
        }
      });

      // Focus the first invalid field.
      var $firstInvalid = $invalidFields.first();
      $firstInvalid.focus();
    }
  });

  // Prevent entering more than allowed persons in person_max and person_min fields
  $('#bookings_persons').on('input change', 'input.person_max, input.person_min', function () {
    var max = parseInt($(this).attr('max'), 10);
    var val = parseInt($(this).val(), 10);

    // Remove any previous warning
    $(this).next('.max-persons-warning').remove();
    if (max && val > max) {
      $(this).val(max);

      // Inline warning message.
      $(this).after('<span class="max-persons-warning" style="color:red; margin-left:5px; margin-top:10px; display:inline-block;">' + wc_bookings_admin_js_params.i18n_max_persons_warning + ' ' + max + '</span>');
    }
  });
});
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(428);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_0__);

jquery__WEBPACK_IMPORTED_MODULE_0___default()(function ($) {
  'use strict';

  if ('undefined' === window.wcTracks) {
    return;
  }
  var wc_bookings_tracks = {
    record_event: function (eventName, properties) {
      const prefix = 'bookings_';
      let baseProperties = {
        bookings_version: wc_bookings_admin_js_params.bookings_version,
        bookings_db_version: wc_bookings_admin_js_params.bookings_db_version
      };
      properties = {
        ...properties,
        ...baseProperties
      };
      const fullEventName = prefix + eventName;
      window.wcTracks.recordEvent(fullEventName, properties);
    },
    init: function () {
      var doc = $(document);
      doc.on('click', '.booking-calendar-booking-title, .booking-calendar-time-range', function () {
        wc_bookings_tracks.record_event('calendar_popover_clicked');
      });
      doc.on('click', '#wc-bookings-datepicker-toggle-day', function () {
        wc_bookings_tracks.record_event('calendar_datepicker_clicked');
      });
      doc.on('click', '.wc-bookings-datepicker-popover .CalendarDay', function () {
        wc_bookings_tracks.record_event('calendar_datepicker_date_changed');
      });
      doc.on('click', '.date-selector-popover .change-date.prev', function () {
        wc_bookings_tracks.record_event('calendar_change_date_prev_clicked');
      });
      doc.on('click', '.date-selector-popover .change-date.next', function () {
        wc_bookings_tracks.record_event('calendar_change_date_next_clicked');
      });
      doc.on('click', '.date-selector-popover .change-date.today', function () {
        wc_bookings_tracks.record_event('calendar_change_date_today_clicked');
      });
      doc.on('click', '.wc-bookings-calendar-popover #event_detail_footer a', function () {
        wc_bookings_tracks.record_event('calendar_popover_details_clicked');
      });
      doc.on('click', '#publish', function () {
        if (-1 < window.location.href.indexOf('post-new.php') && 'booking' === $('#product-type').val()) {
          wc_bookings_tracks.record_event('booking_product_published');
        }
      });

      // Templates.
      doc.on('wc_bookings_template_clicked', function (event, templateIndex, templateSlug) {
        wc_bookings_tracks.record_event('template_click', {
          product_template: templateSlug,
          source: window.pagenow || 'unknown'
        });
      });
      doc.on('click', '.bookings-templates a.start-from-scratch-link', function () {
        wc_bookings_tracks.record_event('template_click_blank');
      });
    }
  };
  wc_bookings_tracks.init();
});
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(723);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* globals wc_bookings_admin_js_params */
/**
 * This file has logic to handle lazy loading store availability rules.
 * Location of availability rules listing page: "Bookings -> Settings -> Store Availability".
 *
 * @since 1.15.69
 */

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById('bookings_settings');
  const lazyLoadAvailabilityRuleshiddenField = form ? form.querySelector('[name="can-lazy-load-availability-rules"]') : null;
  const canLazyLoadAvailabilityRules = lazyLoadAvailabilityRuleshiddenField ? parseInt(lazyLoadAvailabilityRuleshiddenField.value) : 0;
  if (!canLazyLoadAvailabilityRules) {
    return;
  }

  // Data about store availability rule types.
  const availability_rule_types = {
    current_list_type: new URLSearchParams(window.location.search).get('show'),
    GOOGLE_EVENT: 'google-events',
    AVAILABILITY_RULES: 'availability-rules'
  };
  const availability_rule = {
    // Data related to remaining (lazy loaded) store availability rules.
    remaining_availability_rules: {
      button: null,
      can_fetch: true,
      next_request_data: {},
      availability_rules_html: ''
    },
    /**
     * Bootstrap process.
     *
     * @since 1.15.69
     */
    init_lazy_load: () => {
      // Add lazy load feature only on Google events rules list page.
      if (availability_rule_types.GOOGLE_EVENT !== availability_rule_types.current_list_type) {
        return;
      }
      const data = {
        per_page: parseInt(form.querySelector('[name="availability-rules-per-page"]').value),
        step: 1,
        nonce: form.querySelector('[name="lazy_load_availability_rules_nonce"]').value,
        show: availability_rule_types.current_list_type
      };
      availability_rule.register_lazy_load_button();

      // Disable load more button till first batch of availability rules ready to render.
      availability_rule.remaining_availability_rules.button.disabled = true;
      availability_rule.fetch(data);
    },
    /**
     * Should insert availability rules html to table.
     *
     * @since 1.15.69
     * @param {string} html
     */
    insert_html: html => {
      const availability_rows = document.getElementById('availability_rows');
      availability_rows.insertAdjacentHTML('beforeend', html);
      jQuery('body').trigger('row_added', availability_rows.querySelector('tr:last-child'));
    },
    /**
     * Should add lazy load button.
     *
     * @since 1.15.69
     */
    register_lazy_load_button: () => {
      const submit_button_container = form.querySelector('p.submit');
      const button_title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Load more Google Calendar event rules', 'woocommerce-bookings');

      // Add load more button.
      submit_button_container.insertAdjacentHTML('beforeend', `<button class="button-secondary lazy-load-button" style="margin-left: 5px">${button_title}</button>`);

      // Cache button element for future use.
      availability_rule.remaining_availability_rules.button = submit_button_container.querySelector('.lazy-load-button');
      availability_rule.remaining_availability_rules.button.addEventListener('click', event => {
        const {
          button,
          can_fetch,
          next_request_data,
          availability_rules_html
        } = availability_rule.remaining_availability_rules;
        button.disabled = true;
        button.textContent = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Loading...', 'woocommerce-bookings');

        // Add availability HTML to dom.
        // Trigger event which attach/perform necessary actions on new table rows.
        window.setTimeout(() => {
          availability_rule.insert_html(availability_rules_html);
        }, 5);

        // Fetch next batch of availibility rules.
        if (can_fetch) {
          window.setTimeout(() => availability_rule.fetch(next_request_data), 10);
        }
        event.preventDefault();
      });
    },
    /**
     * Should fetch availability rules html.
     *
     * @since 1.15.69
     * @param {object} data
     */
    fetch: (data = null) => {
      const request = {
        method: 'POST'
      };
      const form_data = new FormData();

      // Prepare quest data.
      data.action = 'wc_bookings_lazy_load_availability_rules';
      for (const property in data) {
        form_data.append(property, data[property]);
      }
      request.body = form_data;
      fetch(`${wc_bookings_admin_js_params.ajax_url}`, request).then(response => response.json()).then(response => {
        if (response.success) {
          // Remove "Load more availability rules" button if all availability rules loaded.
          const {
            button
          } = availability_rule.remaining_availability_rules;
          if (!response.data.lazy_load_availability_rules) {
            availability_rule.insert_html(response.data.html);
            button.remove();
            return;
          }

          // Increment step.
          ++data.step;
          availability_rule.remaining_availability_rules.availability_rules_html = response.data.html;
          availability_rule.remaining_availability_rules.can_fetch = response.data.lazy_load_availability_rules;
          availability_rule.remaining_availability_rules.next_request_data = data;

          // Activate load more button to allow fetch next batch of availability rules.
          button.disabled = false;
          button.textContent = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Load more Google Calendar event rules', 'woocommerce-bookings');
        }
      });
    }
  };
  availability_rule.init_lazy_load();
});
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(723);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(428);
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_1__);
/* globals wc_bookings_admin_js_params */


jquery__WEBPACK_IMPORTED_MODULE_1___default()(function ($) {
  // Create a bookable product.
  $('.wc-use-templates').on('click', function (e) {
    e.preventDefault();
    const button = $(this);
    const dashicon = button.find('.dashicons');
    const template = button.parents('.template-loop-item');
    const dataIndex = template.data('template-index');
    const dataSlug = template.data('template-slug');

    // Return if already clicked.
    if (button.hasClass('active')) {
      return false;
    }

    // Show the loader inside the button.
    button.addClass('active');
    dashicon.addClass('dashicons-update');
    dashicon.addClass('spin');
    dashicon.css('display', 'inline-block');
    $.ajax({
      type: 'POST',
      url: wc_bookings_admin_js_params.ajax_url,
      data: {
        action: 'wc_bookings_get_product_template',
        index: dataIndex,
        slug: dataSlug,
        security: wc_bookings_admin_js_params.nonce.wc_bookings_get_product_template
      },
      success: function (result) {
        result = JSON.parse(result);

        // Remove the loader.
        button.removeClass('active');
        dashicon.removeClass('dashicons-update');
        dashicon.css('display', 'none');

        // Check for failure.
        if (!result.success) {
          alert(result.data); // eslint-disable-line no-alert

          return false;
        }
        if (0 === $('.template-item-popup-content .insertion-result').length) {
          // Creating the <p> element.
          const paragraph = document.createElement('p');
          paragraph.className = 'insertion-result';
          paragraph.innerHTML = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.sprintf)(/* translators: 1: Open anchor tag html 2: Close anchor tag html */
          (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('A bookable product is created using this template. Please %1$sclick here%2$s if you are not redirected automatically.', 'woocommerce-bookings'), `<a href="${result.data}">`, '</a>');
          $('.template-item-popup-content').append(paragraph);
        }

        // Reload to the newly created bookable product's edit screen.
        window.location.href = result.data;
      },
      error: function (jqXHR, exception) {
        if ('abort' === exception) {
          return; // Assuming the date is changed very quickly.
        }

        // Show the error.
        alert(jqXHR.responseText); // eslint-disable-line no-alert

        // Remove the loader.
        button.removeClass('active');
        dashicon.removeClass('dashicons-update');
      },
      dataType: 'html'
    });
  });

  // Open the popup when the button is clicked.
  $('.template-loop-item').on('click', function (e) {
    e.stopPropagation();
    const $body = $('body');

    // Stop if the arrow or the popup-inner side is clicked.
    if ($(e.target).closest('.item-popup-arrow').length !== 0 || $(e.target).closest('.template-item-popup-inner').length !== 0) {
      return false;
    }

    // Add overlay only if it doesn't exist.
    if (!$('#active-template-popup-overlay').length) {
      $body.prepend('<div id="active-template-popup-overlay"></div>');
    }
    $body.addClass('noscroll');
    $('.template-item-popup').fadeOut();
    $(this).find('.template-item-popup').addClass('active').fadeIn();
    $body.trigger('wc_bookings_template_clicked', [$(this).data('template-index'), $(this).data('template-slug'), $(this)]);
  });

  // Close the popup when clicked outside or a close button.
  $('.close-template-popup').on('click', function (e) {
    e.stopPropagation();
    $('.template-item-popup').fadeOut();
    $(this).parents('.template-item-popup').removeClass('active');
    $('#active-template-popup-overlay').remove();
    $('body').removeClass('noscroll');
  });
  $(document).on('click', function (e) {
    if ($(e.target).closest('.template-item-popup-inner').length === 0) {
      $('.close-template-popup').trigger('click');
    }
  });

  // Close popup on Esc key press.
  $(document).on('keyup', function (e) {
    const activePopupEl = $('.active.template-item-popup');

    // Return if popup is not open.
    if (!activePopupEl.length) {
      return;
    }
    switch (e.key) {
      case 'ArrowLeft':
        activePopupEl.find('.item-popup-arrow.left').trigger('click');
        break;
      case 'ArrowRight':
        activePopupEl.find('.item-popup-arrow.right').trigger('click');
        break;
      case 'Escape':
        activePopupEl.find('.close-template-popup').trigger('click');
        break;
    }
  });

  // On arrow click, show next/prev popup.
  $('.item-popup-arrow').on('click', function () {
    const direction = $(this).hasClass('left') ? 'left' : 'right';
    const currentPopupCard = $(this).closest('.template-loop-item');

    // Remove active class from all popup cards.
    currentPopupCard.find('.template-item-popup').removeClass('active');
    if ('left' === direction) {
      let prevPopupCard = currentPopupCard.prev();
      prevPopupCard = prevPopupCard.length ? prevPopupCard : currentPopupCard.siblings().last();
      prevPopupCard.trigger('click');
    } else {
      let nextPopupCard = currentPopupCard.next();
      nextPopupCard = nextPopupCard.length ? nextPopupCard : currentPopupCard.siblings().first();
      nextPopupCard.trigger('click');
    }
  });
});
})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
// extracted by mini-css-extract-plugin

})();

// This entry needs to be wrapped in an IIFE because it needs to be isolated against other entry modules.
(() => {
// extracted by mini-css-extract-plugin

})();

/******/ })()
;
//# sourceMappingURL=admin.js.map