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
function q(c,a,g){var b,d={},e=null,h=null;void 0!==g&&(e=""+g);void 0!==a.key&&(e=""+a.key);void 0!==a.ref&&(h=a.ref);for(b in a)m.call(a,b)&&!p.hasOwnProperty(b)&&(d[b]=a[b]);if(c&&c.defaultProps)for(b in a=c.defaultProps,a)void 0===d[b]&&(d[b]=a[b]);return{$$typeof:k,type:c,key:e,ref:h,props:d,_owner:n.current}}__webpack_unused_export__=l;exports.jsx=q;exports.jsxs=q;


/***/ }),

/***/ 609:
/***/ ((module) => {

module.exports = window["React"];

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

;// external ["wp","element"]
const external_wp_element_namespaceObject = window["wp"]["element"];
;// external "lodash"
const external_lodash_namespaceObject = window["lodash"];
;// external ["wp","components"]
const external_wp_components_namespaceObject = window["wp"]["components"];
;// external ["wp","i18n"]
const external_wp_i18n_namespaceObject = window["wp"]["i18n"];
;// external "moment"
const external_moment_namespaceObject = window["moment"];
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_namespaceObject);
// EXTERNAL MODULE: ./node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__(848);
;// ./src/js/components/store-availability-popover/index.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */


const AVAILABILITY_ALL_DAY = '1';
const AVAILABILITY_SPECIFIC_HOURS = '2';
const DOES_NOT_REPEAT = '1';
const REPEATS_WEEKLY = '2';
const REPEATS_YEARLY = '3';
const REPEATS_SPECIFIC_DAYS = '4';
const REPEATS_SPECIFIC_DATES = '5';
function StoreAvailabilityPopover(props) {
  const [isPopoverVisible, setPopoverVisible] = (0,external_wp_element_namespaceObject.useState)(true);
  const [startDate, setStartDate] = (0,external_wp_element_namespaceObject.useState)(props.selectedDate || new Date());
  const [needsEndDate, setNeedsEndDate] = (0,external_wp_element_namespaceObject.useState)(false);
  const [endDate, setEndDate] = (0,external_wp_element_namespaceObject.useState)(props.selectedDate || new Date());
  const [isDatePickerVisible, setDatePickerVisible] = (0,external_wp_element_namespaceObject.useState)(false);
  const [availabilityTime, setAvailabilityTime] = (0,external_wp_element_namespaceObject.useState)(AVAILABILITY_ALL_DAY);
  const [availabilityTimeFrames, setAvailabilityTimeFrames] = (0,external_wp_element_namespaceObject.useState)([]);
  const [frequency, setFrequency] = (0,external_wp_element_namespaceObject.useState)(DOES_NOT_REPEAT);
  const [repeatDaysOfWeek, setRepeatDaysOfWeek] = (0,external_wp_element_namespaceObject.useState)();
  const selectedDayOfWeek = external_moment_default()(props.selectedDate).format('dddd');
  const selectedDay = external_moment_default()(props.selectedDate).format('MMMM D');
  function popoverContent() {
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "wb__sap",
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)("h4", {
        className: "wb__sap__heading",
        children: (0,external_wp_i18n_namespaceObject.__)('Edit Availability', 'woocommerce-bookings')
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "wb__sap__start-end-dates wb__sap__row",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.TextControl, {
          className: "wb__sap__start-date",
          label: (0,external_wp_i18n_namespaceObject.__)('Start Date', 'woocommerce-bookings'),
          onClick: selectStartDate,
          value: formatDate(startDate)
        }), !needsEndDate ? /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "wb__sap__add-end-date",
          onClick: selectEndDate,
          children: (0,external_wp_i18n_namespaceObject.__)('Add end date', 'woocommerce-bookings')
        }) : /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.TextControl, {
          className: "wb__sap__end-date",
          label: (0,external_wp_i18n_namespaceObject.__)('End Date', 'woocommerce-bookings'),
          onClick: selectEndDate,
          value: formatDate(endDate)
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "wb__sap__time wb__sap__row",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.SelectControl, {
          label: (0,external_wp_i18n_namespaceObject.__)('Time', 'woocommerce-bookings'),
          className: "wb__sap__time-selector",
          value: availabilityTime,
          onChange: changeAvailabilityTime,
          options: [{
            label: (0,external_wp_i18n_namespaceObject.__)('Available all day', 'woocommerce-bookings'),
            value: AVAILABILITY_ALL_DAY
          }, {
            label: (0,external_wp_i18n_namespaceObject.__)('Available at specific hours', 'woocommerce-bookings'),
            value: AVAILABILITY_SPECIFIC_HOURS
          }]
        }), isAvailableAtSpecificHours() && renderTimeFrames(availabilityTimeFrames, setAvailabilityTimeFrames)]
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "wb__sap__frequency wb__sap__row",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.SelectControl, {
          label: (0,external_wp_i18n_namespaceObject.__)('Frequency', 'woocommerce-bookings'),
          className: "wb__sap__frequency-selector",
          value: frequency,
          onChange: changeFrequency,
          options: [{
            label: (0,external_wp_i18n_namespaceObject.__)('Does not repeat', 'woocommerce-bookings'),
            value: DOES_NOT_REPEAT
          }, /* translators: %s: day of week */
          {
            label: (0,external_wp_i18n_namespaceObject.sprintf)((0,external_wp_i18n_namespaceObject.__)('Every %s', 'woocommerce-bookings'), selectedDayOfWeek),
            value: REPEATS_WEEKLY
          }, /* translators: %s: day of year */
          {
            label: (0,external_wp_i18n_namespaceObject.sprintf)((0,external_wp_i18n_namespaceObject.__)('Anually on %s', 'woocommerce-bookings'), selectedDay),
            value: REPEATS_YEARLY
          }, {
            label: (0,external_wp_i18n_namespaceObject.__)('On specific days of the week', 'woocommerce-bookings'),
            value: REPEATS_SPECIFIC_DAYS
          }, {
            label: (0,external_wp_i18n_namespaceObject.__)('On specific dates', 'woocommerce-bookings'),
            value: REPEATS_SPECIFIC_DATES
          }]
        }), doesRepeatSpecificDaysOfWeek() && /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          className: "wb__sap__frequency-days-of-week wb__sap__row",
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
            children: (0,external_wp_i18n_namespaceObject.__)('Repeats on:', 'woocommerce-bookings')
          }), /*#__PURE__*/(0,jsx_runtime.jsx)("ul", {
            className: "wb__sap__days-of-week-selector",
            children: daysOfWeekSelector(changeRepeatDayOfWeek, repeatDaysOfWeek)
          })]
        })]
      }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        className: "wb__sap__actions wb__sap__row",
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.Button, {
          className: "wb__sap__cancel",
          isTertiary: true,
          isLink: true,
          onClick: closePopover,
          children: (0,external_wp_i18n_namespaceObject.__)('Cancel', 'woocommerce-bookings')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.Button, {
          className: "wb__sap__apply",
          isDefault: true,
          isPrimary: true,
          children: (0,external_wp_i18n_namespaceObject.__)('Apply', 'woocommerce-bookings')
        })]
      })]
    });
  }
  function datePicker() {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.Popover, {
      className: "wb__sap_date-picker",
      position: "middle center",
      onClickOutside: () => setDatePickerVisible(false),
      focusOnMount: false,
      children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.DatePicker, {
        currentDate: startDate,
        onChange: date => {
          setDatePickerVisible(false);
          updateDate(date);
        }
      })
    });
  }
  function selectStartDate() {
    setDatePickerVisible(!isDatePickerVisible);
    updateDate = setStartDate;
  }
  function selectEndDate() {
    setNeedsEndDate(true);
    setDatePickerVisible(!isDatePickerVisible);
    updateDate = setEndDate;
  }
  function changeAvailabilityTime(newAvailabilityTime) {
    setAvailabilityTime(newAvailabilityTime);
    if (availabilityTimeFrames.length === 0) {
      availabilityTimeFrames.push(['0', '0']);
      setAvailabilityTimeFrames(availabilityTimeFrames);
    }
  }
  function isAvailableAtSpecificHours() {
    return availabilityTime === AVAILABILITY_SPECIFIC_HOURS;
  }
  function changeFrequency(newFrequency) {
    setFrequency(newFrequency);
  }
  function doesRepeatSpecificDaysOfWeek() {
    return frequency === REPEATS_SPECIFIC_DAYS;
  }
  function changeRepeatDayOfWeek(event) {
    const dayOfWeek = parseInt(event.target.value);
    let selectedDays = repeatDaysOfWeek || [];
    if (!selectedDays.includes(dayOfWeek)) {
      selectedDays.push(dayOfWeek);
    } else {
      selectedDays = selectedDays.filter(day => day !== dayOfWeek);
    }
    setRepeatDaysOfWeek(selectedDays.slice());
  }
  function closePopover() {
    if (!isDatePickerVisible) {
      setPopoverVisible(false);
    }
  }
  return isPopoverVisible && /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    className: "wb__sap__container",
    children: [/*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.Popover, {
      position: "middle center",
      onClickOutside: closePopover,
      focusOnMount: false,
      children: popoverContent()
    }), isDatePickerVisible && datePicker()]
  });
}
function formatDate(date) {
  return external_moment_default()(date).format('MMM DD, YYYY');
}
function renderTimeFrames(availabilityTimeFrames, setAvailabilityTimeFrames) {
  const timeFrames = [];
  function changeTimeFrame(timeFrameIndex, comboIndex) {
    return function (newTime) {
      availabilityTimeFrames[timeFrameIndex][comboIndex] = newTime;
      setAvailabilityTimeFrames(availabilityTimeFrames.slice());
    };
  }
  function addAnotherTimeFrame() {
    availabilityTimeFrames.push(['0', '0']);
    setAvailabilityTimeFrames(availabilityTimeFrames.slice());
  }
  for (var timeFrameIndex = 0; timeFrameIndex < availabilityTimeFrames.length; timeFrameIndex++) {
    const timeFrame = /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      className: "wb__sap__time-frame wb__sap__row",
      children: [hoursOfDayCombo(availabilityTimeFrames[timeFrameIndex][0], changeTimeFrame(timeFrameIndex, 0), timeFrameIndex, 0), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
        children: "\u2014"
      }), hoursOfDayCombo(availabilityTimeFrames[timeFrameIndex][1], changeTimeFrame(timeFrameIndex, 1), timeFrameIndex, 1)]
    }, timeFrameIndex);
    timeFrames.push(timeFrame);
  }
  timeFrames.push(/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
    className: "wb__sap__add-new-time-frame",
    onClick: addAnotherTimeFrame,
    children: (0,external_wp_i18n_namespaceObject.__)('Add more times', 'woocommerce-bookings')
  }, "add-more-times"));
  return timeFrames;
}
function hoursOfDayCombo(selectedTime, updateSelectedTime, timeFrameIndex, selectIndex) {
  const options = [];
  for (var hour = 0; hour < 24; hour++) {
    options.push({
      label: external_moment_default()().startOf('day').add(hour, 'hour').format('h:mma'),
      value: hour
    });
  }
  return /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.SelectControl, {
    value: selectedTime,
    onChange: updateSelectedTime,
    className: "wb__sap__time-frame-hours-selector",
    options: options
  }, `${timeFrameIndex}-${selectIndex}`);
}
function daysOfWeekSelector(changeRepeatDayOfWeek, repeatDaysOfWeek) {
  const daysOfWeek = [];
  for (var dayIndex = 0; dayIndex < 7; dayIndex++) {
    const day = external_moment_default()().startOf('week').add(dayIndex, 'days');
    const dayOfWeek = day.weekday();
    const dayName = day.format('dd');
    const selected = (repeatDaysOfWeek || []).includes(dayOfWeek);
    const classNames = ['wb__sap__day-of-week-button'];
    if (selected) {
      classNames.push('selected');
    }
    daysOfWeek.push(/*#__PURE__*/(0,jsx_runtime.jsx)("li", {
      children: /*#__PURE__*/(0,jsx_runtime.jsx)("button", {
        className: classNames.join(' '),
        onClick: changeRepeatDayOfWeek,
        value: dayOfWeek,
        "data-day": dayName,
        children: dayName
      })
    }, dayIndex));
  }
  return daysOfWeek;
}
/* harmony default export */ const store_availability_popover = (StoreAvailabilityPopover);
;// ./src/js/admin-store-availability.js
/* globals: wc_bookings_admin_calendar_js_params */

/**
 * External dependencies
 */



/**
 * Internal dependencies.
 */


let popoverContainer;
const storeAvailabilityDays = document.getElementsByClassName('wc-bookings__store-availability-day');
if (null !== storeAvailabilityDays) {
  (0,external_lodash_namespaceObject.forEach)(storeAvailabilityDays, storeAvailabilityDay => {
    storeAvailabilityDay.addEventListener('click', () => {
      const date = new Date(parseInt(storeAvailabilityDay.dataset.timestamp) * 1000 /* to milliseconds */);
      if (popoverContainer) {
        document.body.removeChild(popoverContainer);
      }
      popoverContainer = document.createElement('div');
      document.body.appendChild(popoverContainer);
      (0,external_wp_element_namespaceObject.render)(/*#__PURE__*/(0,jsx_runtime.jsx)(store_availability_popover, {
        selectedDate: date
      }), popoverContainer);
    });
  });
}
/******/ })()
;
//# sourceMappingURL=admin-store-availability.js.map