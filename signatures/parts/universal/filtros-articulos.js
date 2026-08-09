/**
 * Filtros de listados de artículos — Select2 inmediato + sucursales por AJAX.
 * Requiere window.ArticulosFiltrosConfig definido antes de cargar este script.
 */
(function () {
  'use strict';

  const config = window.ArticulosFiltrosConfig || {};
  const FILTER_IDS = config.filterIds || [];
  const CONTAINER_ID = config.containerId || 'articulos_filtros_container';
  const READY_CLASS = config.readyClass || 'articulos-filtros-ready';
  const SUCURSALES_URL = config.sucursalesUrl || 'parts/clientes/listar/get_sucursales.php';
  const SUCURSAL_SELECT_ID = config.sucursalSelectId || null;
  const SUCURSAL_VALUE_FIELD = config.sucursalValueField || 'nombre_sucursal';
  const INIT_MARKER_ID = config.initMarkerId || (FILTER_IDS.length ? FILTER_IDS[0] : null);

  let onChangeCallback = null;
  let onReadyCallback = null;
  let initialized = false;
  let sucursalesLoaded = !SUCURSAL_SELECT_ID;

  function getFilterSelects() {
    return FILTER_IDS
      .map(function (id) {
        return document.getElementById(id);
      })
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
    const container = document.getElementById(CONTAINER_ID);
    if (container) {
      container.classList.add(READY_CLASS);
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

    $select.select2({
      dropdownParent: $select.parent(),
      width: '100%'
    });

    $select.on('select2:select select2:unselect', triggerFilterChange);
    select.addEventListener('change', triggerFilterChange);
    return true;
  }

  function populateSucursales() {
    if (!SUCURSAL_SELECT_ID) {
      return Promise.resolve();
    }

    const select = document.getElementById(SUCURSAL_SELECT_ID);
    if (!select || sucursalesLoaded) {
      return Promise.resolve();
    }

    return fetch(SUCURSALES_URL)
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'Error al cargar sucursales');
        }

        data.sucursales.forEach(function (sucursal) {
          const value = sucursal[SUCURSAL_VALUE_FIELD];
          const label = sucursal.nombre_sucursal;
          select.appendChild(new Option(label, value));
        });

        sucursalesLoaded = true;

        const $select = jQuery(select);
        if ($select.data('select2')) {
          $select.trigger('change.select2');
        }
      })
      .catch(function (error) {
        console.error('Error al cargar sucursales:', error);
      });
  }

  function init() {
    if (initialized || !INIT_MARKER_ID || !document.getElementById(INIT_MARKER_ID)) {
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
      if (initAttempts <= MAX_INIT_ATTEMPTS && INIT_MARKER_ID && document.getElementById(INIT_MARKER_ID)) {
        requestAnimationFrame(bootstrapFiltros);
      } else {
        markReady();
      }
      return;
    }

    populateSucursales().finally(function () {
      triggerReady();
    });
  }

  window.ArticulosFiltros = {
    init: init,
    setOnChange: function (callback) {
      onChangeCallback = callback;
    },
    setOnReady: function (callback) {
      onReadyCallback = callback;
      if (initialized && sucursalesLoaded) {
        callback();
      }
    }
  };

  bootstrapFiltros();
})();
