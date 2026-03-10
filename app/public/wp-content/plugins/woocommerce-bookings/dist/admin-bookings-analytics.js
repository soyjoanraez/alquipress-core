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

/***/ 609:
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ 846:
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {

var __webpack_unused_export__;
__webpack_unused_export__ = ({value:!0}),exports.A=_default;var _react=_interopRequireDefault(__webpack_require__(609)),_excluded=["size","onClick","icon","className"];function _interopRequireDefault(a){return a&&a.__esModule?a:{default:a}}function _extends(){return _extends=Object.assign?Object.assign.bind():function(a){for(var b,c=1;c<arguments.length;c++)for(var d in b=arguments[c],b)Object.prototype.hasOwnProperty.call(b,d)&&(a[d]=b[d]);return a},_extends.apply(this,arguments)}function _objectWithoutProperties(a,b){if(null==a)return{};var c,d,e=_objectWithoutPropertiesLoose(a,b);if(Object.getOwnPropertySymbols){var f=Object.getOwnPropertySymbols(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||Object.prototype.propertyIsEnumerable.call(a,c)&&(e[c]=a[c])}return e}function _objectWithoutPropertiesLoose(a,b){if(null==a)return{};var c,d,e={},f=Object.keys(a);for(d=0;d<f.length;d++)c=f[d],0<=b.indexOf(c)||(e[c]=a[c]);return e}function _default(a){var b=a.size,c=void 0===b?24:b,d=a.onClick,e=a.icon,f=a.className,g=_objectWithoutProperties(a,_excluded),h=["gridicon","gridicons-calendar",f,!!function isModulo18(a){return 0==a%18}(c)&&"needs-offset",!1,!1].filter(Boolean).join(" ");return _react["default"].createElement("svg",_extends({className:h,height:c,width:c,onClick:d},g,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24"}),_react["default"].createElement("g",null,_react["default"].createElement("path",{d:"M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 00-2 2v13a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2zm0 15H5V8h14v11z"})))}


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
var __webpack_exports__ = {};

;// external ["wp","i18n"]
const external_wp_i18n_namespaceObject = window["wp"]["i18n"];
;// external ["wp","hooks"]
const external_wp_hooks_namespaceObject = window["wp"]["hooks"];
;// external ["wp","element"]
const external_wp_element_namespaceObject = window["wp"]["element"];
;// ./node_modules/@wordpress/icons/build-module/icon/index.js
/**
 * WordPress dependencies
 */


/**
 * External dependencies
 */

/**
 * Return an SVG icon.
 *
 * @param props The component props.
 *
 * @return Icon component
 */
/* harmony default export */ const icon = ((0,external_wp_element_namespaceObject.forwardRef)(({
  icon,
  size = 24,
  ...props
}, ref) => {
  return (0,external_wp_element_namespaceObject.cloneElement)(icon, {
    width: size,
    height: size,
    ...props,
    ref
  });
}));
//# sourceMappingURL=index.js.map
;// external ["wp","primitives"]
const external_wp_primitives_namespaceObject = window["wp"]["primitives"];
// EXTERNAL MODULE: ./node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__(848);
;// ./node_modules/@wordpress/icons/build-module/library/chevron-right.js
/**
 * WordPress dependencies
 */


const chevronRight = /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_primitives_namespaceObject.SVG, {
  xmlns: "http://www.w3.org/2000/svg",
  viewBox: "0 0 24 24",
  children: /*#__PURE__*/(0,jsx_runtime.jsx)(external_wp_primitives_namespaceObject.Path, {
    d: "M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z"
  })
});
/* harmony default export */ const chevron_right = (chevronRight);
//# sourceMappingURL=chevron-right.js.map
// EXTERNAL MODULE: ./node_modules/gridicons/dist/calendar.js
var calendar = __webpack_require__(846);
;// ./src/js/admin-bookings-analytics.js
/**
 * External dependencies
 */





// Filter to add a new item in the Analytics dropdows.

(0,external_wp_hooks_namespaceObject.addFilter)('woocommerce_admin_products_report_filters', 'woocommerce-bookings/admin/analytics', (filterConfig, variationsConfig) => {
  let valueToPush = {};
  valueToPush["label"] = (0,external_wp_i18n_namespaceObject.__)('Bookable products', 'woocommerce-bookings');
  valueToPush["value"] = 'bookings';
  filterConfig.forEach(function (obj) {
    if (obj.filters) {
      obj.filters.push(valueToPush);
    }
  });
  return filterConfig;
}, 10);

// Filter to add a new item in the Product Type dropdows.
(0,external_wp_hooks_namespaceObject.addFilter)('experimental_woocommerce_tasklist_product_types', 'woocommerce-bookings', productTypes => [...productTypes, {
  key: 'booking',
  title: (0,external_wp_i18n_namespaceObject.__)('Bookable Product', 'woocommerce-bookings'),
  content: (0,external_wp_i18n_namespaceObject.__)('An item that can be rented or reserved. Variations include cost, availability, and number of persons.', 'woocommerce-bookings'),
  before: /*#__PURE__*/(0,jsx_runtime.jsx)(calendar/* default */.A, {}),
  after: /*#__PURE__*/(0,jsx_runtime.jsx)(icon, {
    icon: chevron_right
  })
}]);
/******/ })()
;
//# sourceMappingURL=admin-bookings-analytics.js.map