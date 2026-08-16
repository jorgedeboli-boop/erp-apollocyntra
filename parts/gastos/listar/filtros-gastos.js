/**
 * Filtros del listado de gastos — Select2 inmediato (mismo patrón que lotes).
 */
(function () {
  'use strict';

  const FILTER_IDS = [
    'filtro_empresa',
    'filtro_proveedor',
    'filtro_estado',
    'filtro_tipo_gasto',
    'filtro_forma_pago'
  ];

  const COLLAPSE_ID = 'collapse_filtros_gastos';
  const READY_CLASS = 'gastos-filtros-ready';

  let onChangeCallback = null;
  let onReadyCallback = null;
  let initialized = false;

  function getFilterSelects() {
    return FILTER_IDS
      .map(function (id) { return document.getElementById(id); })
      .filter(Boolean);
  }

  function triggerFilterChange(event) {
    if (typeof onChangeCallback === 'function') {
      onChangeCallback(event);
    }
  }

  function triggerReady() {
    if (typeof onReadyCallback === 'function') {
      onReadyCallback();
    }
  }

  function markReady() {
    const collapse = document.getElementById(COLLAPSE_ID);
    if (collapse) {
      collapse.classList.add(READY_CLASS);
    }
  }

  function initSelect2(select) {
    if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
      return false;
    }

    const $select = jQuery(select);
    if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
      return true;
    }

    if (typeof select2Focus === 'function') {
      select2Focus($select);
    }

    $select.select2({
      dropdownParent: $select.parent(),
      width: '100%'
    });

    $select.on('select2:select select2:unselect', triggerFilterChange);
    select.addEventListener('change', triggerFilterChange);
    return true;
  }

  function init() {
    if (initialized || !document.getElementById('filtro_empresa')) {
      return initialized;
    }

    if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
      return false;
    }

    const selects = getFilterSelects();
    if (!selects.length) {
      return false;
    }

    let allReady = true;
    selects.forEach(function (select) {
      if (!initSelect2(select)) {
        allReady = false;
      }
    });

    if (!allReady) {
      return false;
    }

    initialized = true;
    markReady();
    return true;
  }

  let initAttempts = 0;
  const MAX_INIT_ATTEMPTS = 120;

  function bootstrapFiltros() {
    if (!init()) {
      initAttempts += 1;
      if (initAttempts <= MAX_INIT_ATTEMPTS && document.getElementById('filtro_empresa')) {
        requestAnimationFrame(bootstrapFiltros);
      } else {
        markReady();
      }
      return;
    }

    triggerReady();
  }

  window.GastosFiltros = {
    init: init,
    setOnChange: function (callback) {
      onChangeCallback = callback;
    },
    setOnReady: function (callback) {
      onReadyCallback = callback;
      if (initialized) {
        callback();
      }
    }
  };

  bootstrapFiltros();
})();
