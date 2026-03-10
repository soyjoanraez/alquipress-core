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

;// external ["wc","wcBlocksRegistry"]
const external_wc_wcBlocksRegistry_namespaceObject = window["wc"]["wcBlocksRegistry"];
;// external ["wc","wcSettings"]
const external_wc_wcSettings_namespaceObject = window["wc"]["wcSettings"];
;// external ["wp","data"]
const external_wp_data_namespaceObject = window["wp"]["data"];
;// external ["wp","hooks"]
const external_wp_hooks_namespaceObject = window["wp"]["hooks"];
;// external ["wp","htmlEntities"]
const external_wp_htmlEntities_namespaceObject = window["wp"]["htmlEntities"];
// EXTERNAL MODULE: ./node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__(848);
;// ./src/payment-method/check-availability/index.js
/**
 * External dependencies
 */



/**
 * Internal dependencies
 */

const PAYMENT_METHOD_NAME = 'wc-bookings-gateway';
const settings = (0,external_wc_wcSettings_namespaceObject.getSetting)('wc-bookings-gateway_data', {});
const label = (0,external_wp_htmlEntities_namespaceObject.decodeEntities)(settings.title);
const orderButtonText = (0,external_wp_htmlEntities_namespaceObject.decodeEntities)(settings.order_button_text);

/**
 * Content component
 */
const Content = () => {
  return (0,external_wp_htmlEntities_namespaceObject.decodeEntities)(settings.description || '');
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = props => {
  const {
    PaymentMethodLabel
  } = props.components;
  return /*#__PURE__*/(0,jsx_runtime.jsx)(PaymentMethodLabel, {
    text: label
  });
};

/**
 * Bookings payment method config object.
 */
const bookingsPaymentMethod = {
  name: PAYMENT_METHOD_NAME,
  content: /*#__PURE__*/(0,jsx_runtime.jsx)(Content, {}),
  label: /*#__PURE__*/(0,jsx_runtime.jsx)(Label, {}),
  edit: /*#__PURE__*/(0,jsx_runtime.jsx)(Content, {}),
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports
  },
  placeOrderButtonLabel: orderButtonText
};
/* harmony default export */ const check_availability = (bookingsPaymentMethod);
;// ./src/payment-method/index.js
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */

const payment_method_settings = (0,external_wc_wcSettings_namespaceObject.getSetting)('wc-bookings-gateway_data', {});
const {
  PAYMENT_STORE_KEY
} = wc.wcBlocksData;
(0,external_wc_wcBlocksRegistry_namespaceObject.registerPaymentMethod)(check_availability);
if (payment_method_settings.is_enabled) {
  // Set the payment method as active when the checkout form is rendered.
  (0,external_wp_hooks_namespaceObject.addAction)('experimental__woocommerce_blocks-checkout-render-checkout-form', 'woocommerce-bookings-gateway', () => (0,external_wp_data_namespaceObject.dispatch)(PAYMENT_STORE_KEY).__internalSetActivePaymentMethod('wc-bookings-gateway'));
}
/******/ })()
;
//# sourceMappingURL=blocks.js.map