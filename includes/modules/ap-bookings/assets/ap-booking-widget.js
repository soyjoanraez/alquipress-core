/**
 * Alquipress — Frontend Booking Widget
 *
 * Monta el widget de reserva en la ficha del producto.
 * Depende del objeto `apBookingWidget` inyectado por PHP:
 *   - restBase   : URL base REST (wp-json/ap-bookings/v1)
 *   - nonce      : X-WP-Nonce
 *   - productId  : ID del producto
 *   - currency   : símbolo moneda
 *   - addToCartUrl: URL con nonce para hacer POST al carrito
 *   - monthNames : array 12 nombres de mes
 *   - dayNames   : array 7 días (L…D)
 *   - i18n       : { selectCheckin, selectCheckout, nightsLabel, ... }
 */
(function () {
  'use strict';

  const cfg = window.apBookingWidget || {};
  const REST = cfg.restBase || '/wp-json/ap-bookings/v1';
  const NONCE = cfg.nonce || '';
  const PID = parseInt(cfg.productId, 10) || 0;
  const CUR = cfg.currency || '€';
  const MONTHS = cfg.monthNames || ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  const DAYS   = cfg.dayNames  || ['L','M','X','J','V','S','D'];
  const i18n   = cfg.i18n || {};

  let viewYear, viewMonth;
  let calData = {};       // { 'YYYY-MM-DD': { status, price } }
  let selStart = null;
  let selEnd   = null;
  let guests   = 1;
  let priceData = null;   // último resultado de /price
  let loadingPrice = false;

  let $grid, $monthLbl, $breakdown, $cta, $availMsg, $guestsCount, $checkinHidden, $checkoutHidden, $guestsHidden;

  /* ── Boot ─────────────────────────────────────────────────────────────── */
  function boot() {
    const root = document.getElementById('ap-booking-widget-root');
    if (!root || !PID) return;

    const today = new Date();
    viewYear  = today.getFullYear();
    viewMonth = today.getMonth();

    root.innerHTML = buildHTML();
    cacheRefs(root);
    bindEvents(root);
    loadCalendar();
  }

  /* ── HTML ─────────────────────────────────────────────────────────────── */
  function buildHTML() {
    const addToCartUrl = cfg.addToCartUrl || '#';
    return `
    <div class="ap-bw">
      <div class="ap-bw-header">
        <span class="ap-bw-month" id="ap-bw-month"></span>
        <div class="ap-bw-nav">
          <button id="ap-bw-prev" aria-label="Mes anterior">&#8249;</button>
          <button id="ap-bw-next" aria-label="Mes siguiente">&#8250;</button>
        </div>
      </div>
      <div class="ap-bw-grid" id="ap-bw-grid"></div>

      <div class="ap-bw-guests">
        <label for="ap-bw-guests-label">Huéspedes</label>
        <button type="button" id="ap-bw-guests-minus" aria-label="Menos">−</button>
        <span class="ap-bw-guests-count" id="ap-bw-guests-count">1</span>
        <button type="button" id="ap-bw-guests-plus" aria-label="Más">+</button>
      </div>

      <div id="ap-bw-avail-msg" class="ap-bw-avail-msg" style="display:none"></div>

      <div id="ap-bw-breakdown" class="ap-bw-breakdown" style="display:none">
        <h4>Resumen de precio</h4>
        <div id="ap-bw-breakdown-rows"></div>
      </div>

      <form id="ap-bw-form" method="post" action="${esc(addToCartUrl)}">
        <input type="hidden" name="add-to-cart"  value="${PID}">
        <input type="hidden" name="quantity"      value="1">
        <input type="hidden" name="ap_checkin"    id="ap-bw-checkin-hidden"  value="">
        <input type="hidden" name="ap_checkout"   id="ap-bw-checkout-hidden" value="">
        <input type="hidden" name="ap_guests"     id="ap-bw-guests-hidden"   value="1">
        <button type="submit" class="ap-bw-cta" id="ap-bw-cta" disabled>
          Selecciona fechas
        </button>
      </form>
    </div>`;
  }

  function cacheRefs(c) {
    $grid          = c.querySelector('#ap-bw-grid');
    $monthLbl      = c.querySelector('#ap-bw-month');
    $breakdown     = c.querySelector('#ap-bw-breakdown');
    $cta           = c.querySelector('#ap-bw-cta');
    $availMsg      = c.querySelector('#ap-bw-avail-msg');
    $guestsCount   = c.querySelector('#ap-bw-guests-count');
    $checkinHidden  = c.querySelector('#ap-bw-checkin-hidden');
    $checkoutHidden = c.querySelector('#ap-bw-checkout-hidden');
    $guestsHidden   = c.querySelector('#ap-bw-guests-hidden');
  }

  function bindEvents(c) {
    c.querySelector('#ap-bw-prev').addEventListener('click', () => { prevMonth(); });
    c.querySelector('#ap-bw-next').addEventListener('click', () => { nextMonth(); });
    c.querySelector('#ap-bw-guests-minus').addEventListener('click', () => { if (guests > 1) { guests--; updateGuests(); } });
    c.querySelector('#ap-bw-guests-plus').addEventListener('click', () => { guests++; updateGuests(); });
  }

  function updateGuests() {
    $guestsCount.textContent = guests;
    $guestsHidden.value = guests;
    if (selStart && selEnd) fetchPrice();
  }

  /* ── Navigation ─────────────────────────────────────────────────────────── */
  function prevMonth() {
    if (viewMonth === 0) { viewMonth = 11; viewYear--; } else { viewMonth--; }
    loadCalendar();
  }
  function nextMonth() {
    if (viewMonth === 11) { viewMonth = 0; viewYear++; } else { viewMonth++; }
    loadCalendar();
  }

  /* ── Data ────────────────────────────────────────────────────────────────── */
  async function loadCalendar() {
    $monthLbl.textContent = `${MONTHS[viewMonth]} ${viewYear}`;
    $grid.innerHTML = `<div class="ap-bw-loading" style="grid-column:1/-1">Cargando…</div>`;

    const from = isoDate(viewYear, viewMonth, 1);
    const days  = new Date(viewYear, viewMonth + 1, 0).getDate();
    const to    = isoDate(viewYear, viewMonth, days);

    try {
      calData = await apiFetch(`${REST}/calendar?product_id=${PID}&from=${from}&to=${to}`) || {};
    } catch (e) { calData = {}; }

    renderGrid();
  }

  async function fetchPrice() {
    if (!selStart || !selEnd || loadingPrice) return;
    loadingPrice = true;
    $cta.disabled = true;
    $cta.textContent = 'Calculando…';
    $availMsg.style.display  = 'none';
    $breakdown.style.display = 'none';

    try {
      const data = await apiFetch(
        `${REST}/price?product_id=${PID}&checkin=${selStart}&checkout=${selEnd}&guests=${guests}`
      );
      priceData = data;
      renderBreakdown(data);
    } catch (e) {
      priceData = null;
      showAvailMsg('Error al calcular el precio. Inténtalo de nuevo.', false);
    } finally {
      loadingPrice = false;
    }
  }

  /* ── Grid ────────────────────────────────────────────────────────────────── */
  function renderGrid() {
    const today = isoToday();
    const firstDow = (new Date(viewYear, viewMonth, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

    let html = DAYS.map(d => `<div class="ap-bw-dow">${d}</div>`).join('');

    for (let i = 0; i < firstDow; i++) {
      html += `<div class="ap-bw-day ap-bw-empty"></div>`;
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const key  = isoDate(viewYear, viewMonth, d);
      const info = calData[key] || { status: 'free', price: 0 };
      const isPast    = key < today;
      const isToday   = key === today;
      const inRange   = selStart && selEnd && key > selStart && key < selEnd;
      const isStart   = key === selStart;
      const isEnd     = key === selEnd;
      const isBooked  = info.status === 'booked';
      const isBlocked = info.status === 'blocked';

      let cls = 'ap-bw-day';
      if (isPast)    cls += ' ap-bw-past';
      if (isToday)   cls += ' ap-bw-today';
      if (isBooked)  cls += ' ap-bw-booked';
      if (isBlocked) cls += ' ap-bw-blocked';
      if (inRange)   cls += ' ap-bw-in-range';
      if (isStart)   cls += ' ap-bw-sel-start';
      if (isEnd)     cls += ' ap-bw-sel-end';

      const priceHtml = (!isBooked && !isBlocked && info.price > 0)
        ? `<span class="ap-bw-dp">${CUR}${fmtN(info.price)}</span>`
        : '';

      html += `<div class="${cls}" data-date="${key}" tabindex="${isPast || isBooked || isBlocked ? -1 : 0}" role="button">
        <span class="ap-bw-dn">${d}</span>${priceHtml}
      </div>`;
    }

    $grid.innerHTML = html;

    $grid.querySelectorAll('.ap-bw-day:not(.ap-bw-past):not(.ap-bw-empty):not(.ap-bw-booked):not(.ap-bw-blocked)').forEach(cell => {
      cell.addEventListener('click', () => handleDayClick(cell.dataset.date));
    });
  }

  /* ── Selection ─────────────────────────────────────────────────────────── */
  function handleDayClick(date) {
    if (!selStart || selEnd || date < selStart) {
      // Start new selection
      selStart = date;
      selEnd   = null;
      priceData = null;
      resetBreakdown();
      $cta.textContent = 'Selecciona la fecha de salida';
      $cta.disabled = true;
      $checkinHidden.value  = date;
      $checkoutHidden.value = '';
    } else {
      selEnd = date;
      $checkoutHidden.value = date;
      $checkinHidden.value  = selStart;
      fetchPrice();
    }
    renderGrid();
  }

  /* ── Breakdown render ─────────────────────────────────────────────────── */
  function renderBreakdown(data) {
    if (!data.available) {
      showAvailMsg(data.message || 'Las fechas seleccionadas no están disponibles.', false);
      $cta.textContent = 'Fechas no disponibles';
      $cta.disabled = true;
      return;
    }

    const fmt = data.formatted || {};
    const fv  = (key, raw) => fmt[key] ? fmt[key] : `${CUR}${fmtN(raw)}`;

    let rows = `<div class="ap-bw-row"><span>${data.nights} noche${data.nights !== 1 ? 's' : ''} × ${fv('base_price', data.base_price)}</span><span class="amt">${fv('subtotal', data.subtotal)}</span></div>`;
    if (data.cleaning_fee > 0)     rows += `<div class="ap-bw-row"><span>Limpieza</span><span class="amt">${fv('cleaning_fee', data.cleaning_fee)}</span></div>`;
    if (data.laundry_fee > 0)      rows += `<div class="ap-bw-row"><span>Lavandería</span><span class="amt">${fv('laundry_fee', data.laundry_fee)}</span></div>`;
    if (data.security_deposit > 0) rows += `<div class="ap-bw-row"><span>Fianza (retención)</span><span class="amt">${fv('security_deposit', data.security_deposit)}</span></div>`;
    rows += `<div class="ap-bw-row is-total"><span>Total</span><span class="amt">${fv('total', data.total)}</span></div>`;

    document.getElementById('ap-bw-breakdown-rows').innerHTML = rows;
    $breakdown.style.display = '';
    $availMsg.style.display  = 'none';

    $cta.textContent = `Reservar — ${fv('total', data.total)}`;
    $cta.disabled = false;
  }

  function showAvailMsg(msg, isOk) {
    $availMsg.textContent  = msg;
    $availMsg.className    = `ap-bw-avail-msg ${isOk ? 'ok' : 'err'}`;
    $availMsg.style.display = '';
  }

  function resetBreakdown() {
    $breakdown.style.display = 'none';
    $availMsg.style.display  = 'none';
  }

  /* ── Utils ───────────────────────────────────────────────────────────────── */
  function isoDate(y, m, d) {
    return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
  }
  function isoToday() {
    const t = new Date();
    return isoDate(t.getFullYear(), t.getMonth(), t.getDate());
  }
  function fmtN(n) { return parseFloat(n).toFixed(0); }
  function esc(s)  { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  async function apiFetch(url, method = 'GET', body = null) {
    const opts = {
      method,
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
    };
    if (body && method !== 'GET') opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    if (!res.ok) throw new Error(res.statusText);
    return res.json();
  }

  /* ── Init ───────────────────────────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

})();
