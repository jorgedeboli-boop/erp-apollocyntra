/**
 * Filtros dinámicos de listados — Select2 inmediato al crear el <select>.
 * Requiere window.FiltrosDinamicosConfig definido antes de cargar este script.
 */
(function () {
  'use strict';

  const config = window.FiltrosDinamicosConfig || {};
  const CONTAINER_ID = config.containerId || 'collapse_filtros';
  const READY_CLASS = config.readyClass || 'listar-filtros-ready';
  const SUCURSALES_URL = config.sucursalesUrl || 'parts/clientes/listar/get_sucursales.php';
  const SUCURSAL_VALUE_FIELD = config.sucursalValueField || 'nombre_sucursal';
  const SUCURSAL_LABEL_FIELD = config.sucursalLabelField || 'nombre_sucursal';

  let registeredIds = [];
  let finalized = false;
  let markedReady = false;

  function markReady() {
    if (markedReady) {
      return;
    }
    const container = document.getElementById(CONTAINER_ID);
    if (container) {
      container.classList.add(READY_CLASS);
      markedReady = true;
    }
  }

  function initSelect2(select, onChange) {
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

    const extra = (config.select2OptionsById && config.select2OptionsById[select.id]) || {};

    $select.select2(Object.assign({
      dropdownParent: $select.parent(),
      width: '100%'
    }, extra));

    if (typeof onChange === 'function') {
      $select.on('select2:select select2:unselect', onChange);
      select.addEventListener('change', onChange);
    }

    return true;
  }

  function refreshSelect2(select) {
    const $select = jQuery(select);
    if ($select.data('select2')) {
      $select.trigger('change.select2');
    }
  }

  function registerSelect(select) {
    if (select && select.id && registeredIds.indexOf(select.id) === -1) {
      registeredIds.push(select.id);
    }
  }

  function createFilterSucursal(containerClass, selectId, defaultOptionText, onChange, className) {
    const select = document.createElement('select');
    select.id = selectId;
    select.className = className || 'form-select select2-filter text-capitalize form-select-sm select2-custom';
    select.innerHTML = '<option value="">' + (defaultOptionText || 'Sucursales') + '</option>';

    const container = document.querySelector(containerClass);
    if (!container) {
      return null;
    }

    container.appendChild(select);
    registerSelect(select);
    initSelect2(select, onChange);

    fetch(SUCURSALES_URL)
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'Error al cargar sucursales');
        }

        data.sucursales.forEach(function (sucursal) {
          const value = sucursal[SUCURSAL_VALUE_FIELD];
          const label = sucursal[SUCURSAL_LABEL_FIELD];
          select.appendChild(new Option(label, value));
        });

        refreshSelect2(select);
      })
      .catch(function (error) {
        console.error('Error al cargar sucursales:', error);
      });

    return select;
  }

  function createFilterFijo(containerClass, selectId, defaultOptionText, opciones, onChange, className) {
    const select = document.createElement('select');
    select.id = selectId;
    select.className = className || 'form-select select2-filter text-capitalize select2-custom';
    select.innerHTML = '<option value="">' + defaultOptionText + '</option>';

    const container = document.querySelector(containerClass);
    if (!container) {
      return null;
    }

    container.appendChild(select);

    (opciones || []).forEach(function (opcion) {
      select.appendChild(new Option(opcion.label, opcion.value));
    });

    registerSelect(select);
    initSelect2(select, onChange);
    return select;
  }

  function allSelectsReady() {
    if (!registeredIds.length) {
      return false;
    }

    return registeredIds.every(function (id) {
      const select = document.getElementById(id);
      return select && select.classList.contains('select2-hidden-accessible');
    });
  }

  let finalizeAttempts = 0;
  const MAX_FINALIZE_ATTEMPTS = 120;

  function finalize() {
    if (finalized) {
      return;
    }

    if (!allSelectsReady()) {
      finalizeAttempts += 1;
      if (finalizeAttempts <= MAX_FINALIZE_ATTEMPTS) {
        requestAnimationFrame(finalize);
      } else {
        finalized = true;
        markReady();
      }
      return;
    }

    finalized = true;
    markReady();
  }

  function createFilterAjax(containerClass, selectId, defaultOptionText, url, mapOptions, onChange, className) {
    const select = document.createElement('select');
    select.id = selectId;
    select.className = className || 'form-select select2-filter text-capitalize select2-custom';
    select.innerHTML = '<option value="">' + (defaultOptionText || '') + '</option>';

    const container = document.querySelector(containerClass);
    if (!container) {
      return null;
    }

    container.appendChild(select);
    registerSelect(select);
    initSelect2(select, onChange);

    fetch(url)
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'Error al cargar opciones');
        }

        const items = typeof mapOptions === 'function' ? mapOptions(data) : [];
        items.forEach(function (item) {
          select.appendChild(new Option(item.label, item.value));
        });

        refreshSelect2(select);
      })
      .catch(function (error) {
        console.error('Error al cargar filtro ' + selectId + ':', error);
      });

    return select;
  }

  window.FiltrosDinamicosListar = {
    createFilterSucursal: createFilterSucursal,
    createFilterFijo: createFilterFijo,
    createFilterAjax: createFilterAjax,
    initSelect2: initSelect2,
    markReady: markReady,
    finalize: finalize,
    registerSelect: registerSelect
  };
})();
