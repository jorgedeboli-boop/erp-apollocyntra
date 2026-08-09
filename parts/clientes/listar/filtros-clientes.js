/**
 * Filtros del listado de clientes — Select2 inmediato + sucursales por AJAX.
 */
(function () {
  'use strict';

  const FILTER_IDS = [
    'UserTipoIdentificacion',
    'UserProvincia',
    'UserSucursal',
    'UserEstado'
  ];

  const COLLAPSE_ID = 'collapse_filtros_clientes';
  const READY_CLASS = 'clientes-filtros-ready';
  const SUCURSALES_URL = 'parts/clientes/listar/get_sucursales.php';

  let onChangeCallback = null;
  let onReadyCallback = null;
  let initialized = false;
  let sucursalesLoaded = false;

  function getFilterSelects() {
    return FILTER_IDS
      .map(id => document.getElementById(id))
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

    $select.select2({
      dropdownParent: $select.parent(),
      width: '100%'
    });

    $select.on('select2:select select2:unselect', triggerFilterChange);
    select.addEventListener('change', triggerFilterChange);
    return true;
  }

  function populateSucursales() {
    const select = document.getElementById('UserSucursal');
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
          select.appendChild(new Option(sucursal.nombre_sucursal, sucursal.nombre_sucursal));
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
    if (initialized || !document.getElementById('UserTipoIdentificacion')) {
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
      if (initAttempts <= MAX_INIT_ATTEMPTS && document.getElementById('UserTipoIdentificacion')) {
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

  window.ClientesFiltros = {
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
