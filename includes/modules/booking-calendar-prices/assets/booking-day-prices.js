(function ($, hooks) {
  'use strict';

  if (!window.alquipressBookingDayPrices) {
    return;
  }

  var cache = {};

  function pad(num) {
    return num < 10 ? '0' + num : String(num);
  }

  function getCacheKey(productId, year, month) {
    return productId + ':' + year + ':' + month;
  }

  function fetchPrices(productId, year, month, done) {
    var key = getCacheKey(productId, year, month);
    if (cache[key]) {
      done(cache[key]);
      return;
    }

    $.ajax({
      url: alquipressBookingDayPrices.ajax_url,
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'alquipress_booking_day_prices',
        nonce: alquipressBookingDayPrices.nonce,
        product_id: productId,
        year: year,
        month: month
      }
    })
      .done(function (resp) {
        if (resp && resp.success && resp.data && resp.data.prices) {
          cache[key] = resp.data.prices;
          done(cache[key]);
          return;
        }
        cache[key] = {};
        done(cache[key]);
      })
      .fail(function () {
        cache[key] = {};
        done(cache[key]);
      });
  }

  function renderPrices($picker) {
    if (!$picker || !$picker.length) {
      return;
    }

    var durationUnit = $picker.data('duration-unit');
    if (durationUnit !== 'day' && durationUnit !== 'night') {
      return;
    }

    var $form = $picker.closest('form.cart');
    var productId = parseInt($form.find('.wc-booking-product-id').val(), 10);
    if (!productId) {
      return;
    }

    var inst = $.datepicker._getInst($picker[0]);
    if (!inst || typeof inst.drawYear === 'undefined') {
      return;
    }

    var year = inst.drawYear;
    var month = inst.drawMonth + 1;

    fetchPrices(productId, year, month, function (prices) {
      var $calendar = $picker.find('.ui-datepicker-calendar');
      if (!$calendar.length) {
        return;
      }

      $calendar.find('td').each(function () {
        var $td = $(this);
        $td.find('.alq-day-price').remove();

        if ($td.hasClass('ui-datepicker-other-month') || $td.hasClass('ui-datepicker-unselectable')) {
          return;
        }

        var $dayEl = $td.find('a, span').first();
        var day = parseInt($dayEl.text(), 10);
        if (!day) {
          return;
        }

        var key = year + '-' + pad(month) + '-' + pad(day);
        var price = prices[key];
        if (!price) {
          return;
        }

        $('<div class="alq-day-price" />').text(price).appendTo($td);
      });
    });
  }

  function bindPicker($picker) {
    if ($picker.data('alq-prices-bound')) {
      return;
    }

    $picker.data('alq-prices-bound', true);

    var timer = null;
    var observer = new MutationObserver(function () {
      if (timer) {
        return;
      }
      timer = window.setTimeout(function () {
        timer = null;
        renderPrices($picker);
      }, 50);
    });

    observer.observe($picker[0], { childList: true, subtree: true });
    renderPrices($picker);
  }

  function init() {
    $('.wc-bookings-date-picker .picker').each(function () {
      bindPicker($(this));
    });
  }

  $(document).ready(function () {
    window.setTimeout(init, 200);
  });

  if (hooks && hooks.addAction) {
    hooks.addAction('wc_bookings_date_picker_refreshed', 'alquipress/booking-day-prices', function (data) {
      if (data && data.date_picker) {
        bindPicker($(data.date_picker));
      }
    });
  }
})(jQuery, window.wp ? window.wp.hooks : null);
