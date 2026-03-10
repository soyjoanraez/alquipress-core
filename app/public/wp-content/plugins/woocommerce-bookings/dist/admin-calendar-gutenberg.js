/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ 20:
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

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
function q(c,a,g){var b,d={},e=null,h=null;void 0!==g&&(e=""+g);void 0!==a.key&&(e=""+a.key);void 0!==a.ref&&(h=a.ref);for(b in a)m.call(a,b)&&!p.hasOwnProperty(b)&&(d[b]=a[b]);if(c&&c.defaultProps)for(b in a=c.defaultProps,a)void 0===d[b]&&(d[b]=a[b]);return{$$typeof:k,type:c,key:e,ref:h,props:d,_owner:n.current}}exports.Fragment=l;exports.jsx=q;exports.jsxs=q;


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
;// external "moment"
const external_moment_namespaceObject = window["moment"];
var external_moment_default = /*#__PURE__*/__webpack_require__.n(external_moment_namespaceObject);
// EXTERNAL MODULE: ./node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__(848);
;// ./src/js/components/datepicker-popover-month/index.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


class DatePickerPopoverMonth extends external_wp_element_namespaceObject.Component {
  constructor(props) {
    super(props);
    this.state = {
      showPicker: false
    };
    this.handleChange = this.handleChange.bind(this);
    const picker = this;
    document.addEventListener('click', function (event) {
      let element = document.getElementById('wc-bookings-datepicker-toggle-month');
      if (event.target === element || element.contains(event.target)) {
        return;
      }
      if (event.target.closest('.wc-bookings-datepicker-popover')) {
        return;
      }
      picker.setState({
        showPicker: false
      });
    });
  }
  handleChange(datetime) {
    const date = datetime.substring(0, 10);
    document.location.search += '&calendar_day=' + date + '&view=day';
  }
  toggleVisibility() {
    const {
      showPicker
    } = this.state;
    this.setState({
      showPicker: !showPicker
    });
  }
  render() {
    const {
      showPicker
    } = this.state;
    const {
      year,
      month
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("a", {
        href: "#",
        onClick: e => {
          e.preventDefault();
          this.toggleVisibility();
        },
        id: "wc-bookings-datepicker-toggle-month",
        children: [month, " ", year, " ", /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          children: " \u25BE"
        })]
      }), showPicker && /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.Popover, {
        className: "wc-bookings-datepicker-popover",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.DateTimePicker, {
          onChange: this.handleChange,
          currentDate: external_moment_default()('01 ' + month + ' ' + year, 'DD MMM YYYY').toDate(),
          __nextRemoveHelpButton: true,
          __nextRemoveResetButton: true
        })
      })]
    });
  }
}
/* harmony default export */ const datepicker_popover_month = (DatePickerPopoverMonth);
;// ./src/js/components/datepicker-popover-day/index.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


class DatePickerPopoverDay extends external_wp_element_namespaceObject.Component {
  constructor(props) {
    super(props);
    this.state = {
      showPicker: false
    };
    this.handleChange = this.handleChange.bind(this);
    const picker = this;
    document.addEventListener('click', function (event) {
      let element = document.getElementById('wc-bookings-datepicker-toggle-day');
      if (event.target === element || element.contains(event.target)) {
        return;
      }
      if (event.target.closest('.wc-bookings-datepicker-popover')) {
        return;
      }
      picker.setState({
        showPicker: false
      });
    });
  }
  handleChange(datetime) {
    const date = datetime.substring(0, 10);
    document.location.search += '&calendar_day=' + date + '&view=day';
  }
  toggleVisibility() {
    const {
      showPicker
    } = this.state;
    this.setState({
      showPicker: !showPicker
    });
  }
  render() {
    const {
      showPicker
    } = this.state;
    const {
      year,
      month,
      day
    } = this.props;
    return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("a", {
        href: "#",
        onClick: e => {
          e.preventDefault();
          this.toggleVisibility();
        },
        id: "wc-bookings-datepicker-toggle-day",
        children: [day, " ", /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          children: " \u25BE"
        })]
      }), showPicker && /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.Popover, {
        className: "wc-bookings-datepicker-popover",
        children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.DateTimePicker, {
          onChange: this.handleChange,
          currentDate: external_moment_default()(Date.parse(day)).toDate(),
          __nextRemoveHelpButton: true,
          __nextRemoveResetButton: true
        })
      })]
    });
  }
}
/* harmony default export */ const datepicker_popover_day = (DatePickerPopoverDay);
;// external ["wp","i18n"]
const external_wp_i18n_namespaceObject = window["wp"]["i18n"];
;// ./src/js/components/calendar-popover/index.js
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


class BookingDetails extends external_wp_element_namespaceObject.Component {
  render() {
    const {
      booking
    } = this.props;
    const {
      orderMeta
    } = booking;
    let parsedOrderMeta = [];
    if (orderMeta) {
      parsedOrderMeta = JSON.parse(orderMeta);
    }
    return /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_element_namespaceObject.Fragment, {
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
          id: "event_detail_header",
          children: /*#__PURE__*/(0,jsx_runtime.jsx)("h3", {
            children: booking.title
          })
        }), /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          id: "event_detail_body",
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)("div", {
            id: "booking_status",
            children: booking.status && /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
              children: booking.status
            })
          }), /*#__PURE__*/(0,jsx_runtime.jsxs)("ul", {
            children: [booking.date && booking.time && /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
              className: "daily_popover_datetime_icon",
              children: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                children: /*#__PURE__*/(0,jsx_runtime.jsxs)("strong", {
                  children: [booking.date, /*#__PURE__*/(0,jsx_runtime.jsx)("br", {}), booking.time]
                })
              })
            }), booking.customer && /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
              className: "daily_popover_customer_icon",
              children: /*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
                children: [/*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
                  children: wc_bookings_admin_js_params.i18n_customer
                }), booking.customer]
              })
            }), booking.resource && /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
              className: "daily_popover_resource_icon",
              children: /*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
                children: [/*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
                  children: wc_bookings_admin_js_params.i18n_resource
                }), booking.resource.split(',').join('<br />')]
              })
            }), booking.persons && /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
              className: "daily_popover_persons_icon",
              children: /*#__PURE__*/(0,jsx_runtime.jsxs)("span", {
                children: [/*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
                  children: wc_bookings_admin_js_params.i18n_persons
                }), booking.persons]
              })
            }), parsedOrderMeta.map(({
              attrs,
              title
            }, index) => /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
              children: [/*#__PURE__*/(0,jsx_runtime.jsx)("li", {
                class: "daily_popover_group_title",
                children: /*#__PURE__*/(0,jsx_runtime.jsx)("strong", {
                  children: title
                })
              }), Object.keys(attrs).map(metaKey => /*#__PURE__*/(0,jsx_runtime.jsx)("li", {
                className: "daily_popover_order_meta_item",
                dangerouslySetInnerHTML: {
                  __html: `<strong>${metaKey}</strong>${attrs[metaKey]}`
                }
              }))]
            }))]
          })]
        }), booking.url && /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
          id: "event_detail_footer",
          children: [/*#__PURE__*/(0,jsx_runtime.jsx)("a", {
            href: booking.url,
            children: wc_bookings_admin_js_params.i18n_view_details
          }), " ", /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
            children: "\u2192"
          })]
        })]
      })
    });
  }
}
class CalendarPopover extends external_wp_element_namespaceObject.Component {
  constructor(props) {
    super(props);
    this.state = {
      showPicker: false
    };
  }
  getBookingData() {
    const attrs = this.props.element.attributes;
    const mapping = {
      'data-id': 'id',
      'data-status': 'status',
      'data-booking-title': 'title',
      'data-booking-url': 'url',
      'data-booking-date': 'date',
      'data-booking-time': 'time',
      'data-booking-customer': 'customer',
      'data-booking-resource': 'resource',
      'data-booking-persons': 'persons',
      'data-order-meta': 'orderMeta'
    };
    let booking = {};
    Object.entries(mapping).forEach(([attrName, propName]) => {
      booking[propName] = attrs.getNamedItem(attrName) ? attrs.getNamedItem(attrName).value : '';
    });

    // Sanitize URL.
    booking['url'] = booking['url'] ? booking['url'].trim() : '';
    if (!/^https?:\/\/[^\s'"<>]+$/gi.test(booking['url'])) {
      booking['url'] = '';
    }
    return booking;
  }
  toggleVisibility = e => {
    e.preventDefault();
    const {
      showPicker
    } = this.state;
    this.setState({
      showPicker: !showPicker
    });
  };
  closePopover = () => {
    this.setState({
      showPicker: false
    });
  };
  parseStyles = styles => styles.split(';').filter(style => style.split(':')[0] && style.split(':')[1]).map(style => [style.split(':')[0].trim().replace(/-./g, c => c.substr(1).toUpperCase()), style.split(':')[1].trim()]).reduce((styleObj, style) => ({
    ...styleObj,
    [style[0]]: style[1]
  }), {});
  render() {
    const {
      showPicker
    } = this.state;
    const {
      getAnchorRect,
      position
    } = this.props;
    const attr = this.props.element.attributes;
    const classes = attr.getNamedItem('data-classes') ? attr.getNamedItem('data-classes').value : '';
    const styles = attr.getNamedItem('data-style') ? this.parseStyles(attr.getNamedItem('data-style').value) : {};
    const booking = this.getBookingData();
    return /*#__PURE__*/(0,jsx_runtime.jsxs)(external_wp_element_namespaceObject.Fragment, {
      children: [/*#__PURE__*/(0,jsx_runtime.jsxs)("a", {
        href: booking.url,
        className: classes,
        style: styles,
        onClick: this.toggleVisibility,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "booking-calendar-booking-title",
          children: booking.customer + (booking.customer ? ', ' : '') + booking.title
        }), /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
          className: "booking-calendar-time-range",
          children: booking.time
        })]
      }), showPicker && /*#__PURE__*/(0,jsx_runtime.jsxs)(external_wp_components_namespaceObject.Popover, {
        className: "wc-bookings-calendar-popover",
        focusOnMount: 'firstElement',
        getAnchorRect: getAnchorRect,
        position: position,
        onFocusOutside: this.closePopover,
        children: [/*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_components_namespaceObject.Button, {
          className: "wc-bookings-calendar-popover-close",
          onClick: this.closePopover,
          children: (0,external_wp_i18n_namespaceObject.__)('Close', 'woocommerce-bookings')
        }), /*#__PURE__*/(0,jsx_runtime.jsx)(BookingDetails, {
          booking: booking
        })]
      })]
    });
  }
}
/* harmony default export */ const calendar_popover = (CalendarPopover);
;// ./src/js/admin-calendar-gutenberg.js
/* globals: wc_bookings_admin_calendar_js_params */

/**
 * External dependencies
 */



/**
 * Internal dependencies.
 */




const {
  default_month,
  default_year,
  default_day
} = wc_bookings_admin_calendar_js_params;
const getAnchorRect = popoverElement => popoverAnchorElement => {
  // set anchor tag if missing.
  if (!popoverAnchorElement || 'A' !== popoverAnchorElement.tagName) {
    popoverAnchorElement = popoverElement.querySelector('.wc-bookings-event-link');
  }
  const td = popoverElement.closest('td');
  const tableRect = popoverElement.closest('table').getBoundingClientRect();
  const content = popoverElement.querySelector('.components-popover__content') || popoverElement;
  const anchorRect = popoverAnchorElement.getBoundingClientRect();
  const anchorPoint = {
    x: anchorRect.x + td.clientWidth / 2,
    y: anchorRect.y
  };
  let x = anchorPoint.x;
  if (anchorPoint.x + content.clientWidth > tableRect.width + tableRect.x) {
    x = anchorPoint.x - content.clientWidth;
  }
  let y = anchorPoint.y;
  if (anchorPoint.y + content.clientHeight > document.body.clientHeight) {
    y = document.body.clientHeight - content.clientHeight;
  }
  return new DOMRect(x, y, 0, 0);
};
const popoverContainerMonth = document.getElementById('wc-bookings-datepicker-container-month');
if (null !== popoverContainerMonth) {
  const pickerMonth = /*#__PURE__*/(0,jsx_runtime.jsx)(datepicker_popover_month, {
    year: default_year,
    month: default_month
  });
  (0,external_wp_element_namespaceObject.render)(pickerMonth, popoverContainerMonth);
}
const popoverContainerDay = document.getElementById('wc-bookings-datepicker-container-day');
if (null !== popoverContainerDay) {
  const pickerDay = /*#__PURE__*/(0,jsx_runtime.jsx)(datepicker_popover_day, {
    year: default_year,
    month: default_month,
    day: default_day
  });
  (0,external_wp_element_namespaceObject.render)(pickerDay, popoverContainerDay);
}
const calendarEventMonth = document.getElementsByClassName('calendar_month_event');
if (null !== calendarEventMonth) {
  (0,external_lodash_namespaceObject.forEach)(calendarEventMonth, function (value) {
    (0,external_wp_element_namespaceObject.render)(/*#__PURE__*/(0,jsx_runtime.jsx)(calendar_popover, {
      getAnchorRect: getAnchorRect(value),
      element: value,
      position: 'bottom center'
    }), value);
  });
}
const calendarEventDay = document.getElementsByClassName('daily_view_booking');
if (null !== calendarEventDay) {
  (0,external_lodash_namespaceObject.forEach)(calendarEventDay, function (value) {
    (0,external_wp_element_namespaceObject.render)(/*#__PURE__*/(0,jsx_runtime.jsx)(calendar_popover, {
      element: value,
      position: 'bottom center'
    }), value.childNodes[0]);
  });
}
/******/ })()
;
//# sourceMappingURL=admin-calendar-gutenberg.js.map