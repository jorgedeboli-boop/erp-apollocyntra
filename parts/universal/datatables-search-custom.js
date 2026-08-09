/**
 * UI global DataTables:
 * - Buscador con input-group e icono
 * - Fade-in de .card-datatable al primer render (draw.dt)
 */
(function () {
  'use strict';

  function enhanceDataTableSearch(dtSearch) {
    if (!dtSearch || dtSearch.getAttribute('data-dt-search-enhanced') === '1') {
      return;
    }

    var input = dtSearch.querySelector('input[type="search"]');
    if (!input) {
      return;
    }

    var label = dtSearch.querySelector('label');
    if (label) {
      label.remove();
    }

    var placeholder = input.getAttribute('placeholder') || 'Buscar...';
    if (!input.getAttribute('placeholder')) {
      input.setAttribute('placeholder', placeholder);
    }
    if (!input.getAttribute('aria-label')) {
      input.setAttribute('aria-label', placeholder);
    }

    input.classList.remove('form-control-sm');
    if (!input.classList.contains('form-control')) {
      input.classList.add('form-control');
    }

    var group = document.createElement('div');
    group.className = 'flex-grow-1 input-group input-group-sm input-group-merge rounded-pill';

    var span = document.createElement('span');
    span.className = 'input-group-text';
    span.innerHTML = '<i class="icon-base ri ri-search-line icon-20px"></i>';

    group.appendChild(span);
    group.appendChild(input);

    while (dtSearch.firstChild) {
      dtSearch.removeChild(dtSearch.firstChild);
    }
    dtSearch.appendChild(group);
    dtSearch.setAttribute('data-dt-search-enhanced', '1');
  }

  function enhanceAllInContainer(container) {
    if (!container || !container.querySelectorAll) {
      return;
    }
    container.querySelectorAll('.dt-search').forEach(enhanceDataTableSearch);
  }

  function removeColgroupFromReportTables(settings) {
    if (typeof DataTable === 'undefined') {
      return;
    }

    var tableNode;
    try {
      tableNode = new DataTable.Api(settings).table().node();
    } catch (err) {
      return;
    }

    if (!tableNode || !tableNode.classList) {
      return;
    }

    var reportClasses = [
      'datatables-reportes-ventas',
      'datatables-reportes-semanales',
      'datatables-reportes-mensuales',
      'datatables-reportes-diarios'
    ];

    var isReportTable = reportClasses.some(function (className) {
      return tableNode.classList.contains(className);
    });

    if (!isReportTable) {
      return;
    }

    tableNode.querySelectorAll('colgroup').forEach(function (colgroup) {
      colgroup.remove();
    });
  }

  function onDataTableInit(e, settings) {
    try {
      if (typeof DataTable === 'undefined') {
        return;
      }
      var api = new DataTable.Api(settings);
      var container = api.table().container();
      if (container) {
        enhanceAllInContainer(container);
      }
      removeColgroupFromReportTables(settings);
    } catch (err) {
      // Tabla sin API estándar: intentar desde el nodo del evento
      if (e && e.target) {
        enhanceAllInContainer(e.target.closest ? e.target.closest('.dt-container') : null);
      }
    }
  }

  function enhanceExistingTables() {
    document.querySelectorAll('.dt-container .dt-search').forEach(enhanceDataTableSearch);
  }

  function createDatatableLoaderElement() {
    var loader = document.createElement('div');
    loader.className = 'loader_datatable';
    var dots = '';
    var i;
    for (i = 0; i < 12; i += 1) {
      dots += '<div class="sk-circle-dot"></div>';
    }
    loader.innerHTML =
      '<div class="sk-circle" style="margin: 0 auto !important;">' +
        dots +
      '</div>' +
      '<span class="titleloaderdattable">Cargando datos...</span>';
    return loader;
  }

  function ensureDatatableLoader(cardDatatable) {
    if (!cardDatatable || cardDatatable.getAttribute('data-dt-card-revealed') === '1') {
      return null;
    }

    var prev = cardDatatable.previousElementSibling;
    if (prev && prev.classList.contains('loader_datatable')) {
      prev.classList.remove('is-hidden');
      return prev;
    }

    var loader = createDatatableLoaderElement();
    cardDatatable.parentNode.insertBefore(loader, cardDatatable);
    return loader;
  }

  function hideDatatableLoader(cardDatatable) {
    if (!cardDatatable) {
      return;
    }

    var prev = cardDatatable.previousElementSibling;
    if (prev && prev.classList.contains('loader_datatable')) {
      prev.classList.add('is-hidden');
    }
  }

  function ensureAllDatatableLoaders() {
    document.querySelectorAll('.card-datatable:not(.dt-card-visible)').forEach(function (cardDatatable) {
      ensureDatatableLoader(cardDatatable);
    });
  }

  function revealCardDatatable(settings) {
    if (typeof DataTable === 'undefined') {
      return;
    }

    var tableNode;
    try {
      tableNode = new DataTable.Api(settings).table().node();
    } catch (err) {
      return;
    }

    if (!tableNode || !tableNode.closest) {
      return;
    }

    var card = tableNode.closest('.card-datatable');
    if (!card || card.getAttribute('data-dt-card-revealed') === '1') {
      return;
    }

    card.setAttribute('data-dt-card-revealed', '1');
    hideDatatableLoader(card);
    requestAnimationFrame(function () {
      card.classList.add('dt-card-visible');
    });
  }

  function onDataTableInitWithLoader(e, settings) {
    onDataTableInit(e, settings);

    try {
      if (typeof DataTable === 'undefined') {
        return;
      }
      var tableNode = new DataTable.Api(settings).table().node();
      if (tableNode && tableNode.closest) {
        var cardDatatable = tableNode.closest('.card-datatable');
        if (cardDatatable) {
          ensureDatatableLoader(cardDatatable);
        }
      }
    } catch (err) {
      // sin loader para tablas sin API estándar
    }
  }

  function onDataTableDraw(e, settings) {
    revealCardDatatable(settings);
    removeColgroupFromReportTables(settings);
  }

  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('init.dt', onDataTableInitWithLoader);
    jQuery(document).on('draw.dt', onDataTableDraw);
  } else {
    document.addEventListener('init.dt', onDataTableInitWithLoader);
    document.addEventListener('draw.dt', onDataTableDraw);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      ensureAllDatatableLoaders();
      enhanceExistingTables();
    });
  } else {
    ensureAllDatatableLoaders();
    enhanceExistingTables();
  }

  window.enhanceDataTableSearch = enhanceDataTableSearch;
  window.enhanceAllDataTableSearches = enhanceExistingTables;
  window.revealCardDatatable = revealCardDatatable;
})();
