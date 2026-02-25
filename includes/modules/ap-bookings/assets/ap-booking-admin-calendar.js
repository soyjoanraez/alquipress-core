/**
 * Alquipress — Admin Booking Calendar
 *
 * Monta el calendario interactivo de precios y disponibilidad en el panel
 * "Calendario & precios" del editor de propiedades.
 *
 * Config inyectada por PHP via wp_localize_script en el objeto `apBookingAdmin`:
 *   - restBase     : URL base de la REST API (wp-json/ap-bookings/v1)
 *   - nonce        : X-WP-Nonce
 *   - productId    : ID del producto actual
 *   - currency     : símbolo de moneda ('€')
 *   - monthNames   : array de 12 nombres de mes (localizado)
 *   - dayNames     : array de 7 días semana empezando en lunes (localizado)
 */
(function () {
  'use strict';

  /* ── Config ─────────────────────────────────────────────────────────────── */
  const cfg = window.apBookingAdmin || {};
  const REST   = cfg.restBase  || '/wp-json/ap-bookings/v1';
  const NONCE  = cfg.nonce     || '';
  const PID    = parseInt(cfg.productId, 10) || 0;
  const CUR    = cfg.currency  || '€';
  const MONTHS = cfg.monthNames || ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  const DAYS   = cfg.dayNames  || ['L','M','X','J','V','S','D'];

  /* ── State ──────────────────────────────────────────────────────────────── */
  let viewYear, viewMonth;          // mes visible
  let calendarData = {};            // { 'YYYY-MM-DD': { status, price } }
  let rules        = [];            // reglas de pricing cargadas
  let blocks       = [];            // bloques de disponibilidad
  let selStart     = null;          // 'YYYY-MM-DD'
  let selEnd       = null;
  let selMode      = 'rule';        // 'rule' | 'block'
  let editingRule  = null;          // objeto de regla en edición

  /* ── DOM refs ───────────────────────────────────────────────────────────── */
  let $grid, $monthLabel, $selInfo,
      $ruleForm, $blockForm,
      $rulesList, $blocksList,
      $toast;

  /* ─────────────────────────────────────────────────────────────────────────
   * Bootstrap
   * ───────────────────────────────────────────────────────────────────────── */
  function boot() {
    const container = document.getElementById('ap-booking-admin-calendar-root');
    if (!container || !PID) return;

    const today = new Date();
    viewYear  = today.getFullYear();
    viewMonth = today.getMonth(); // 0-indexed

    container.innerHTML = buildHTML();
    cacheRefs(container);
    bindEvents(container);
    loadAll();
  }

  /* ── HTML scaffold ──────────────────────────────────────────────────────── */
  function buildHTML() {
    return `
    <div class="ap-booking-admin-cal">
      <div class="ap-booking-admin-wrap">

        <!-- Columna izquierda: calendario -->
        <div class="ap-cal-main">
          <div class="ap-cal-header">
            <div class="ap-cal-nav">
              <button id="ap-cal-prev" title="Mes anterior">&#8249;</button>
              <span class="ap-cal-month-label" id="ap-cal-month-label"></span>
              <button id="ap-cal-next" title="Mes siguiente">&#8250;</button>
            </div>
            <button id="ap-cal-today" class="button button-small">Hoy</button>
          </div>
          <div class="ap-cal-grid" id="ap-cal-grid"></div>
          <div class="ap-cal-legend">
            <span><i class="leg-free"></i> Libre</span>
            <span><i class="leg-booked"></i> Reservado</span>
            <span><i class="leg-blocked"></i> Bloqueado</span>
            <span><i class="leg-rule"></i> Regla de precio</span>
          </div>
        </div>

        <!-- Columna derecha: panel lateral -->
        <div class="ap-cal-sidebar">

          <h3>Acciones</h3>
          <div class="ap-cal-action-bar">
            <button id="ap-mode-rule"  class="active">&#43; Regla precio</button>
            <button id="ap-mode-block">&#9632; Bloquear</button>
          </div>

          <div class="ap-cal-sel-info" id="ap-cal-sel-info">
            Haz clic en un día de inicio y luego en el de fin para seleccionar un rango.
          </div>

          <!-- Formulario regla -->
          <div class="ap-cal-form" id="ap-rule-form" style="display:none">
            <h4 id="ap-rule-form-title">Nueva regla de precio</h4>
            <input type="hidden" id="ap-rule-id" value="">
            <label>Nombre de la temporada</label>
            <input type="text" id="ap-rule-name" placeholder="Ej: Temporada alta verano">
            <label>Precio base por noche (${CUR})</label>
            <input type="number" id="ap-rule-price" step="0.01" min="0" placeholder="150.00">
            <div class="ap-form-row-2">
              <div>
                <label>Noches mín.</label>
                <input type="number" id="ap-rule-min" min="1" value="1" style="margin-bottom:10px">
              </div>
              <div>
                <label>Multiplicador fin de semana</label>
                <input type="number" id="ap-rule-wknd" step="0.01" min="1" value="1.00" style="margin-bottom:10px">
              </div>
            </div>
            <label>Precio extra por huésped adicional (${CUR})</label>
            <input type="number" id="ap-rule-extra-guest" step="0.01" min="0" value="0" placeholder="0.00">
            <label>Prioridad (mayor = prevalece sobre otras reglas)</label>
            <input type="number" id="ap-rule-priority" min="0" value="0">
            <div class="ap-form-actions">
              <button class="ap-btn-cancel" id="ap-rule-cancel">Cancelar</button>
              <button class="ap-btn-save"   id="ap-rule-save">Guardar</button>
            </div>
          </div>

          <!-- Formulario bloqueo -->
          <div class="ap-cal-form" id="ap-block-form" style="display:none">
            <h4>Bloquear fechas</h4>
            <label>Motivo</label>
            <select id="ap-block-type">
              <option value="owner_block">Bloqueo propietario</option>
              <option value="maintenance">Mantenimiento</option>
              <option value="closed">Cerrado</option>
            </select>
            <label>Nota (opcional)</label>
            <input type="text" id="ap-block-note" placeholder="Ej: Obras de pintura">
            <div class="ap-form-actions">
              <button class="ap-btn-cancel" id="ap-block-cancel">Cancelar</button>
              <button class="ap-btn-danger" id="ap-block-save">Bloquear</button>
            </div>
          </div>

          <!-- Lista reglas -->
          <h3>Reglas de temporada</h3>
          <div class="ap-rules-list" id="ap-rules-list"></div>

          <!-- Lista bloqueos -->
          <h3 style="margin-top:16px">Bloqueos activos</h3>
          <div class="ap-blocks-list" id="ap-blocks-list"></div>

        </div>
      </div>
    </div>
    <div class="ap-cal-toast" id="ap-cal-toast"></div>`;
  }

  function cacheRefs(c) {
    $grid      = c.querySelector('#ap-cal-grid');
    $monthLabel = c.querySelector('#ap-cal-month-label');
    $selInfo   = c.querySelector('#ap-cal-sel-info');
    $ruleForm  = c.querySelector('#ap-rule-form');
    $blockForm = c.querySelector('#ap-block-form');
    $rulesList = c.querySelector('#ap-rules-list');
    $blocksList = c.querySelector('#ap-blocks-list');
    $toast     = c.querySelector('#ap-cal-toast');
  }

  /* ── Events ─────────────────────────────────────────────────────────────── */
  function bindEvents(c) {
    c.querySelector('#ap-cal-prev').addEventListener('click', () => { prevMonth(); });
    c.querySelector('#ap-cal-next').addEventListener('click', () => { nextMonth(); });
    c.querySelector('#ap-cal-today').addEventListener('click', () => { goToday(); });
    c.querySelector('#ap-mode-rule').addEventListener('click', () => setMode('rule', c));
    c.querySelector('#ap-mode-block').addEventListener('click', () => setMode('block', c));
    c.querySelector('#ap-rule-save').addEventListener('click', saveRule);
    c.querySelector('#ap-rule-cancel').addEventListener('click', clearSelection);
    c.querySelector('#ap-block-save').addEventListener('click', saveBlock);
    c.querySelector('#ap-block-cancel').addEventListener('click', clearSelection);
  }

  function setMode(mode, c) {
    selMode = mode;
    c.querySelector('#ap-mode-rule').classList.toggle('active', mode === 'rule');
    c.querySelector('#ap-mode-block').classList.toggle('active', mode === 'block');
    clearSelection();
  }

  /* ── Navigation ─────────────────────────────────────────────────────────── */
  function prevMonth() {
    if (viewMonth === 0) { viewMonth = 11; viewYear--; } else { viewMonth--; }
    loadCalendarData();
  }
  function nextMonth() {
    if (viewMonth === 11) { viewMonth = 0; viewYear++; } else { viewMonth++; }
    loadCalendarData();
  }
  function goToday() {
    const t = new Date();
    viewYear = t.getFullYear();
    viewMonth = t.getMonth();
    loadCalendarData();
  }

  /* ── Data loading ────────────────────────────────────────────────────────── */
  async function loadAll() {
    await Promise.all([loadCalendarData(), loadRules(), loadBlocks()]);
  }

  async function loadCalendarData() {
    $monthLabel.textContent = `${MONTHS[viewMonth]} ${viewYear}`;

    const from = isoDate(viewYear, viewMonth, 1);
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
    const to = isoDate(viewYear, viewMonth, daysInMonth);

    showGridLoading();

    try {
      const data = await apiFetch(`${REST}/calendar?product_id=${PID}&from=${from}&to=${to}`);
      calendarData = data || {};
    } catch (e) {
      calendarData = {};
    }
    renderGrid();
  }

  async function loadRules() {
    try {
      rules = await apiFetch(`${REST}/rules?product_id=${PID}`) || [];
    } catch (e) {
      rules = [];
    }
    renderRulesList();
  }

  async function loadBlocks() {
    try {
      blocks = await apiFetch(`${REST}/blocks?product_id=${PID}`) || [];
    } catch (e) {
      blocks = [];
    }
    renderBlocksList();
  }

  /* ── Grid render ────────────────────────────────────────────────────────── */
  function showGridLoading() {
    $grid.innerHTML = DAYS.map(d => `<div class="ap-cal-dow">${d}</div>`).join('')
      + `<div class="ap-cal-loading" style="grid-column:1/-1">Cargando…</div>`;
  }

  function renderGrid() {
    const today = isoDate(new Date().getFullYear(), new Date().getMonth(), new Date().getDate());
    const firstDow = (new Date(viewYear, viewMonth, 1).getDay() + 6) % 7; // 0=Mon
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

    // Active rules for this month range
    const monthFrom = isoDate(viewYear, viewMonth, 1);
    const monthTo   = isoDate(viewYear, viewMonth, daysInMonth);
    const activeRuleDates = buildRuleDateSet(monthFrom, monthTo);
    const activeBlockDates = buildBlockDateSet(monthFrom, monthTo);

    let html = DAYS.map(d => `<div class="ap-cal-dow">${d}</div>`).join('');

    // Empty cells before the 1st
    for (let i = 0; i < firstDow; i++) {
      html += `<div class="ap-cal-cell is-empty"></div>`;
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const key = isoDate(viewYear, viewMonth, d);
      const info = calendarData[key] || { status: 'free', price: 0 };
      const isPast = key < today;
      const isToday = key === today;
      const inSel = selStart && selEnd && key >= selStart && key <= selEnd;
      const isSelStart = key === selStart;
      const isSelEnd   = key === selEnd;

      let classes = 'ap-cal-cell';
      if (info.status === 'booked')   classes += ' is-booked';
      else if (activeBlockDates.has(key) || info.status === 'blocked') classes += ' is-blocked';
      else if (activeRuleDates.has(key) && info.status === 'free')     classes += ' has-rule';
      if (isPast)       classes += ' is-past';
      if (isToday)      classes += ' is-today';
      if (inSel)        classes += ' in-selection';
      if (isSelStart)   classes += ' sel-start';
      if (isSelEnd)     classes += ' sel-end';

      const priceHtml = info.price > 0
        ? `<span class="ap-cal-day-price">${CUR}${fmtN(info.price)}</span>`
        : '';

      let statusIcon = '';
      if (info.status === 'booked')  statusIcon = '●';
      if (info.status === 'blocked') statusIcon = '✕';

      html += `<div class="${classes}" data-date="${key}" tabindex="0" role="button" aria-label="${key}">
        <span class="ap-cal-day-num">${d}</span>
        ${priceHtml}
        <span class="ap-cal-day-status">${statusIcon}</span>
      </div>`;
    }

    $grid.innerHTML = html;

    $grid.querySelectorAll('.ap-cal-cell:not(.is-empty):not(.is-past)').forEach(cell => {
      cell.addEventListener('click', () => handleDayClick(cell.dataset.date));
    });
  }

  function buildRuleDateSet(from, to) {
    const set = new Set();
    rules.forEach(r => {
      if (r.date_from > to || r.date_to < from) return;
      const start = maxDate(r.date_from, from);
      const end   = minDate(r.date_to,   to);
      iterDates(start, end, d => set.add(d));
    });
    return set;
  }

  function buildBlockDateSet(from, to) {
    const set = new Set();
    blocks.forEach(b => {
      if (b.date_from > to || b.date_to < from) return;
      const start = maxDate(b.date_from, from);
      const end   = minDate(b.date_to,   to);
      iterDates(start, end, d => set.add(d));
    });
    return set;
  }

  /* ── Selection ──────────────────────────────────────────────────────────── */
  function handleDayClick(date) {
    if (!selStart) {
      selStart = date;
      selEnd   = null;
      updateSelInfo();
      renderGrid();
      return;
    }
    if (!selEnd || date <= selStart) {
      // Second click
      if (date <= selStart) {
        selEnd = selStart;
        selStart = date;
      } else {
        selEnd = date;
      }
      updateSelInfo();
      showForm();
      renderGrid();
    } else {
      // Reset and start new
      selStart = date;
      selEnd   = null;
      hideAllForms();
      updateSelInfo();
      renderGrid();
    }
  }

  function clearSelection() {
    selStart = null;
    selEnd   = null;
    editingRule = null;
    hideAllForms();
    updateSelInfo();
    renderGrid();
  }

  function updateSelInfo() {
    if (!selStart) {
      $selInfo.textContent = 'Haz clic en un día de inicio y luego en el de fin para seleccionar un rango.';
    } else if (!selEnd) {
      $selInfo.textContent = `Inicio: ${fmtDate(selStart)} — Selecciona el día de fin.`;
    } else {
      const nights = daysBetween(selStart, selEnd);
      $selInfo.textContent = `${fmtDate(selStart)} → ${fmtDate(selEnd)}  (${nights} noche${nights !== 1 ? 's' : ''})`;
    }
  }

  function showForm() {
    if (selMode === 'rule') {
      $ruleForm.style.display = '';
      $blockForm.style.display = 'none';
      // Pre-fill dates in form title
      document.getElementById('ap-rule-form-title').textContent = editingRule
        ? 'Editar regla de precio'
        : 'Nueva regla de precio';
      if (!editingRule) {
        document.getElementById('ap-rule-id').value = '';
        document.getElementById('ap-rule-name').value = '';
        document.getElementById('ap-rule-price').value = '';
        document.getElementById('ap-rule-min').value = '1';
        document.getElementById('ap-rule-wknd').value = '1.00';
        document.getElementById('ap-rule-extra-guest').value = '0';
        document.getElementById('ap-rule-priority').value = '0';
      }
    } else {
      $blockForm.style.display = '';
      $ruleForm.style.display = 'none';
    }
  }

  function hideAllForms() {
    $ruleForm.style.display  = 'none';
    $blockForm.style.display = 'none';
  }

  /* ── Save rule ──────────────────────────────────────────────────────────── */
  async function saveRule() {
    if (!selStart || !selEnd) {
      toast('Selecciona un rango en el calendario primero.', true);
      return;
    }
    const id    = document.getElementById('ap-rule-id').value;
    const name  = document.getElementById('ap-rule-name').value.trim();
    const price = parseFloat(document.getElementById('ap-rule-price').value);

    if (!name || isNaN(price) || price < 0) {
      toast('Introduce un nombre y precio válidos.', true);
      return;
    }

    const body = {
      product_id:        PID,
      name,
      date_from:         selStart,
      date_to:           selEnd,
      base_price:        price,
      min_nights:        parseInt(document.getElementById('ap-rule-min').value, 10) || 1,
      weekend_multiplier: parseFloat(document.getElementById('ap-rule-wknd').value) || 1,
      extra_guest_price:  parseFloat(document.getElementById('ap-rule-extra-guest').value) || 0,
      priority:           parseInt(document.getElementById('ap-rule-priority').value, 10) || 0,
    };

    try {
      const url    = id ? `${REST}/rules/${id}` : `${REST}/rules`;
      const method = id ? 'PUT' : 'POST';
      await apiFetch(url, method, body);
      toast(id ? 'Regla actualizada.' : 'Regla creada.');
      clearSelection();
      await Promise.all([loadRules(), loadCalendarData()]);
    } catch (e) {
      toast('Error al guardar la regla.', true);
    }
  }

  /* ── Save block ─────────────────────────────────────────────────────────── */
  async function saveBlock() {
    if (!selStart || !selEnd) {
      toast('Selecciona un rango en el calendario primero.', true);
      return;
    }
    const body = {
      product_id: PID,
      date_from:  selStart,
      date_to:    selEnd,
      type:       document.getElementById('ap-block-type').value,
      note:       document.getElementById('ap-block-note').value.trim(),
    };

    try {
      await apiFetch(`${REST}/blocks`, 'POST', body);
      toast('Fechas bloqueadas.');
      clearSelection();
      await Promise.all([loadBlocks(), loadCalendarData()]);
    } catch (e) {
      toast('Error al crear el bloqueo.', true);
    }
  }

  /* ── Rules list render ──────────────────────────────────────────────────── */
  function renderRulesList() {
    if (!rules.length) {
      $rulesList.innerHTML = `<p class="ap-rules-empty">Sin reglas de temporada. Selecciona un rango en el calendario para añadir una.</p>`;
      return;
    }
    $rulesList.innerHTML = rules.map(r => `
      <div class="ap-rule-item" data-id="${r.id}">
        <div class="ap-rule-info">
          <div class="ap-rule-name">${esc(r.name || '(sin nombre)')}</div>
          <div class="ap-rule-dates">${fmtDate(r.date_from)} – ${fmtDate(r.date_to)}</div>
        </div>
        <span class="ap-rule-price">${CUR}${fmtN(r.base_price)}</span>
        <div class="ap-rule-actions">
          <button class="edit-btn" data-id="${r.id}" title="Editar">✎</button>
          <button class="del-btn"  data-id="${r.id}" title="Eliminar">✕</button>
        </div>
      </div>`
    ).join('');

    $rulesList.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', () => editRule(parseInt(btn.dataset.id, 10)));
    });
    $rulesList.querySelectorAll('.del-btn').forEach(btn => {
      btn.addEventListener('click', () => deleteRule(parseInt(btn.dataset.id, 10)));
    });
  }

  function editRule(id) {
    const r = rules.find(x => parseInt(x.id, 10) === id);
    if (!r) return;
    editingRule = r;
    selStart = r.date_from;
    selEnd   = r.date_to;
    selMode  = 'rule';
    document.getElementById('ap-rule-id').value      = r.id;
    document.getElementById('ap-rule-name').value    = r.name || '';
    document.getElementById('ap-rule-price').value   = r.base_price;
    document.getElementById('ap-rule-min').value     = r.min_nights;
    document.getElementById('ap-rule-wknd').value    = r.weekend_multiplier;
    document.getElementById('ap-rule-extra-guest').value = r.extra_guest_price;
    document.getElementById('ap-rule-priority').value = r.priority;
    showForm();
    updateSelInfo();
    renderGrid();
  }

  async function deleteRule(id) {
    if (!confirm('¿Eliminar esta regla de precio?')) return;
    try {
      await apiFetch(`${REST}/rules/${id}`, 'DELETE', { product_id: PID });
      toast('Regla eliminada.');
      await Promise.all([loadRules(), loadCalendarData()]);
    } catch (e) {
      toast('Error al eliminar.', true);
    }
  }

  /* ── Blocks list render ─────────────────────────────────────────────────── */
  function renderBlocksList() {
    const future = blocks.filter(b => b.date_to >= isoDate(new Date().getFullYear(), new Date().getMonth(), new Date().getDate()));
    if (!future.length) {
      $blocksList.innerHTML = `<p class="ap-rules-empty">Sin bloqueos activos.</p>`;
      return;
    }
    const typeLabel = { owner_block: 'Propietario', maintenance: 'Mantenimiento', closed: 'Cerrado' };
    $blocksList.innerHTML = future.map(b => `
      <div class="ap-block-item" data-id="${b.id}">
        <div class="ap-block-info">
          <div class="ap-block-dates">${fmtDate(b.date_from)} – ${fmtDate(b.date_to)} <em>(${typeLabel[b.type] || b.type})</em></div>
          ${b.note ? `<div class="ap-block-note">${esc(b.note)}</div>` : ''}
        </div>
        <button class="del-block-btn button button-small" data-id="${b.id}">✕</button>
      </div>`
    ).join('');

    $blocksList.querySelectorAll('.del-block-btn').forEach(btn => {
      btn.addEventListener('click', () => deleteBlock(parseInt(btn.dataset.id, 10)));
    });
  }

  async function deleteBlock(id) {
    if (!confirm('¿Eliminar este bloqueo?')) return;
    try {
      await apiFetch(`${REST}/blocks/${id}`, 'DELETE', { product_id: PID });
      toast('Bloqueo eliminado.');
      await Promise.all([loadBlocks(), loadCalendarData()]);
    } catch (e) {
      toast('Error al eliminar.', true);
    }
  }

  /* ── Utilities ───────────────────────────────────────────────────────────── */
  function isoDate(y, m, d) {
    return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
  }

  function fmtDate(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
  }

  function fmtN(n) {
    return parseFloat(n).toFixed(0);
  }

  function daysBetween(a, b) {
    return Math.round((new Date(b) - new Date(a)) / 86400000);
  }

  function maxDate(a, b) { return a > b ? a : b; }
  function minDate(a, b) { return a < b ? a : b; }

  function iterDates(from, to, cb) {
    let cur = new Date(from + 'T00:00:00Z');
    const end = new Date(to   + 'T00:00:00Z');
    while (cur <= end) {
      cb(cur.toISOString().slice(0, 10));
      cur.setUTCDate(cur.getUTCDate() + 1);
    }
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  async function apiFetch(url, method = 'GET', body = null) {
    const opts = {
      method,
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
    };
    if (body && method !== 'GET') opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    if (!res.ok) {
      const err = await res.json().catch(() => ({ message: res.statusText }));
      throw new Error(err.message || 'API error');
    }
    return res.json();
  }

  let toastTimer;
  function toast(msg, isError = false) {
    $toast.textContent = msg;
    $toast.classList.toggle('is-error', isError);
    $toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => $toast.classList.remove('show'), 3000);
  }

  /* ── Init ────────────────────────────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // Re-mount when the Calendario tab becomes active (the tab system toggles hidden)
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-tab="calendario"]');
    if (btn && !document.getElementById('ap-booking-admin-calendar-root')?.querySelector('.ap-booking-admin-cal')) {
      boot();
    }
  });

})();
