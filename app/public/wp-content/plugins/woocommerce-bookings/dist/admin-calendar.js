/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ 231:
/***/ ((module) => {

let processLayout = function (input, maxHeight, maxWidth) {
  let tree = [{
    id: 'root',
    start: 0,
    end: maxHeight,
    depth: -1,
    children: []
  }];

  // Siblings container wrapper, for more performant BFT
  let siblingIndex = {
    index: {},
    clear: function () {
      this.index = {};
    },
    add: function (node) {
      let level = this.index[node.depth] || (this.index[node.depth] = []);
      level.push(node);
    },
    get: function (depth) {
      return this.index[depth] || [];
    }
  };

  // Add a new node to the tree, while maintaining siblings index
  function append(node, item, depth) {
    item.depth = depth + 1;
    siblingIndex.add(item);
    node.children.push(item);
    item.parent = node;
    tree.push(item);
  }

  // Predicate for checking if two events overlap
  function overlaps(node, item) {
    /*eslint-disable */
    return item.start >= node.start && item.start < node.end || node.start == item.start || node.end == item.end;
    /*eslint-enable */
  }

  // returns true on successful tree placement
  function traverse(node, item, depth) {
    if (!overlaps(node, item)) {
      return false;
    }

    // Run BFT on siblings to check if there are any overlaps
    let list = siblingIndex.get(depth + 1);
    for (let key in list) {
      if (traverse(list[key], item, depth + 1)) {
        return true;
      }
    }

    // If there's an overlap, append the item under the current node
    append(node, item, depth);
    return true;
  }

  // Find max depth for each tree branch
  function setMaxDepth() {
    // Visit and set maxDepth on all of node's children
    let setChildren = function (node, depth) {
      node.maxDepth = depth;
      node.children.forEach(function (child) {
        setChildren(child, depth);
      });
    };

    // Only need the leaves from the lookup table
    let leaves = tree.filter(function (node) {
      return node.children.length === 0;
    });

    // Visit every leaf, find maxDepth, reach root and update the roots (root index)
    let roots = {},
      currentRoot;
    leaves.forEach(function (leaf) {
      let node = leaf,
        maxDepth = 0;
      while (1) {
        maxDepth = Math.max(maxDepth, node.depth);
        if (node.parent && node.parent.depth >= 0) {
          node = node.parent;
        } else {
          currentRoot = roots[node.id] || (roots[node.id] = node);
          currentRoot.maxDepth = Math.max(currentRoot.maxDepth, maxDepth);
          break;
        }
      }
    });

    // Traverse children (DFT)
    for (let rootId in roots) {
      setChildren(roots[rootId], roots[rootId].maxDepth);
    }
    tree.forEach(function (leaf) {
      let nextDepth = leaf.maxDepth + 1;
      let siblings = siblingIndex.get(nextDepth);
      while (siblings.length > 0) {
        for (let index in siblings) {
          if (overlaps(siblings[index], leaf)) {
            leaf.maxDepth = Math.max(leaf.maxDepth, siblings[index].maxDepth);
            return;
          }
        }
        siblings = siblingIndex.get(++nextDepth);
      }
    });
  }

  // Set widths for each node
  function setWidths() {
    // Calculate max depth first.
    setMaxDepth();

    // Then calculate and set width for each node
    tree.forEach(function (node) {
      node.width = maxWidth / (node.maxDepth + 1);
    });
  }

  // Format lookup table for result
  // eslint-disable-next-line
  function format(tree) {
    // eslint-disable-next-line
    return tree.map(function (node) {
      return {
        id: node.id,
        top: node.start,
        left: node.width * node.depth,
        width: node.width,
        height: node.height
      };
    });
  }
  siblingIndex.clear();

  // Fill in helpers.
  input = input.map(function (node) {
    // Make sure height is at least 15 pixels. Otherwise, adjust `end`
    // for proper calculations to account for it.
    if (node.end - node.start < 22) {
      node.end = node.start + 22;
    }
    return {
      id: node.id,
      start: node.start,
      end: node.end,
      height: node.end - node.start,
      width: 0,
      children: [],
      depth: 0,
      maxDepth: 0
    };
  }).sort(function (a, b) {
    // Sort by start time, then length
    return a.start === b.start ? b.end - a.end : a.start - b.start;
  });
  let root = tree[0];
  input.forEach(function (item) {
    traverse(root, item, -1);
  });
  setWidths();
  tree.shift();
  return tree.map(function (node) {
    return {
      id: node.id,
      top: node.start,
      left: node.width * node.depth,
      width: node.width,
      height: node.height
    };
  });
};
module.exports.q = processLayout;

/***/ }),

/***/ 415:
/***/ ((__unused_webpack_module, __unused_webpack___webpack_exports__, __webpack_require__) => {

"use strict";

;// external "jQuery"
const external_jQuery_namespaceObject = window["jQuery"];
var external_jQuery_default = /*#__PURE__*/__webpack_require__.n(external_jQuery_namespaceObject);
// EXTERNAL MODULE: ./src/js/admin-calendar-layout.js
var admin_calendar_layout = __webpack_require__(231);
;// ./src/js/admin-calendar.js
/**
 * External dependencies
 */


/**
 * Internal dependencies
 */


/**
 * Popper implementation
 */
external_jQuery_default()(function ($) {
  /**
   * Scrolls to first booking on day view.
   *
   * @since 1.13.0
   */
  function scrollToFirstBooking() {
    // Day view and atleast one booking.
    if ($('.calendar_days').length && $('.daily_view_booking').length) {
      let previousStartValue, smallestStartValueIndex;

      // Gather all start values.
      $('.daily_view_booking').each(function (index) {
        let currentStartValue = parseInt($(this).data('bookingStart'), 10);
        if ('undefined' === typeof previousStartValue) {
          previousStartValue = currentStartValue;
        }
        if (currentStartValue <= previousStartValue) {
          smallestStartValueIndex = index;
        }
        previousStartValue = currentStartValue;
      });

      /*
       * Keep running at interval until first booking
       * offset is found to prevent race condition.
       */
      let findFirstBooking = setInterval(function () {
        let firstBookingTopOffset = parseInt($('.daily_view_booking').eq(smallestStartValueIndex).offset().top, 10);
        if (firstBookingTopOffset > 0) {
          $('html, body').animate({
            scrollTop: firstBookingTopOffset - 110
          }, 600);
          clearInterval(findFirstBooking);
        }
      }, 100);

      /*
       * Fallback timeout after 5 seconds to
       * clear the interval so it doesn't continue
       * running indefinitely.
       */
      setTimeout(function () {
        clearInterval(findFirstBooking);
      }, 5000);
    }
  }

  /*
  * Add select class on mouseenter and mouseleave events for each event with the same
  * event( Bookings booking or Google Calendar event ) ID
  */
  $('.calendar_month_event').on('mouseenter', function () {
    let id = this.dataset["id"];
    let id_class = '.calendar_event_id_' + id;
    $(id_class).find('a.wc-bookings-event-link').addClass("calendar_month_event_selected");
  }).on('mouseleave', function () {
    let id = this.dataset["id"];
    let id_class = '.calendar_event_id_' + id;
    $(id_class).find('a.wc-bookings-event-link').removeClass("calendar_month_event_selected");
  });
  let input = $.map($('.daily_view_booking'), function (el) {
    return {
      id: $(el).data('bookingId'),
      start: $(el).data('bookingStart'),
      end: $(el).data('bookingEnd')
    };
  });
  let totalCalendarHeight = 1968; // definded by design
  let calendarHeightInMinutes = 1440; // 60 minutes * 24 hours
  let scale = totalCalendarHeight / calendarHeightInMinutes;
  (0,admin_calendar_layout/* processLayout */.q)(input, 1968, 100).forEach(function (node) {
    let $el = $('*[data-booking-id="' + node.id + '"]');
    $el.css({
      'top': 'calc(' + node.top * scale + 'px + 2px )'
    });
    $el.css({
      'height': 'calc(' + node.height * scale + 'px - 3px )'
    });
    $el.css('left', node.left + '%');
    // 12px come from the popper padding
    $el.css({
      'width': 'calc(' + node.width + '% - 13px )'
    });
    $el.show();
  }, scrollToFirstBooking());
  let global_availability = $.map($('.daily_view_global_availabiltiy'), function (el) {
    return {
      start: $(el).data('start'),
      end: $(el).data('end'),
      id: $(el).data('globalAvailabilityId')
    };
  });
  global_availability.forEach(function (range) {
    let $el = $('*[data-global-availability-id="' + range.id + '"]');
    $el.css({
      'top': 'calc(' + range.start * scale + 'px )'
    });
    $el.css({
      'height': 'calc(' + (range.end - range.start) * scale + 'px )'
    });
    $el.css('left', '-43px');
    $el.css({
      'width': 'calc(' + '100' + '% + 50px )'
    });
    $el.show();
  });
  $('.daily_view_booking').each(function (index, el) {
    let $el = $(el);

    // Account for the padding in the CSS
    $el.height($el.height() - 12);
  });

  // Scroll to today on schedule view.
  $(document).ready(function () {
    const $today_el = $('.wc-bookings-schedule-date.wc-booking-schedule-today');
    if ($today_el.length) {
      const today_offset = parseInt($today_el.offset().top, 10);
      $('html, body').animate({
        scrollTop: today_offset - 101 // today field/row height offset + floating header height
      }, 600);
    }
  });
});

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
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	__webpack_require__(415);
/******/ 	// This entry module is referenced by other modules so it can't be inlined
/******/ 	var __webpack_exports__ = __webpack_require__(231);
/******/ 	
/******/ })()
;
//# sourceMappingURL=admin-calendar.js.map