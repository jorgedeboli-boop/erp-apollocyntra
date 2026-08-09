'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const tableEl = document.querySelector('.datatables-config-movmientos-bancos');
  const tiposConfig = [
    { id: 'transferencia_saliente', text: 'Transferencia saliente' },
    { id: 'transferencia_entrante', text: 'Transferencia entrante' },
    { id: 'cobro_tarjeta', text: 'Cobro tarjeta' },
    { id: 'pago_tarjeta', text: 'Pago tarjeta' },
    { id: 'retiro_tarjeta', text: 'Retiro tarjeta' },
    { id: 'retiro_cuenta', text: 'Retiro cuenta' },
    { id: 'ingreso_cuenta', text: 'Ingreso cuenta' }
  ];

  function initSelect2Filter(select, placeholder) {
    if (typeof $ === 'undefined' || !$.fn.select2) {
      select.addEventListener('change', function () {
        if (window.dt_config_movmientos_bancos) window.dt_config_movmientos_bancos.ajax.reload();
      });
      return;
    }
    const $select = $(select);
    $select.select2({
      dropdownParent: $select.parent(),
      placeholder: placeholder,
      allowClear: true,
      width: '100%',
      minimumResultsForSearch: Infinity
    });
    $select.on('select2:select select2:clear', function () {
      if (window.dt_config_movmientos_bancos) window.dt_config_movmientos_bancos.ajax.reload();
    });
  }

  function crearFiltros() {
    const contTipo = document.querySelector('.filtro_tipo_config');
    const contEstado = document.querySelector('.filtro_estado_config');
    if (!contTipo || !contEstado) return;

    let optsTipo = '<option value="">Tipo config</option>';
    tiposConfig.forEach(function (t) {
      optsTipo += '<option value="' + t.id + '">' + t.text + '</option>';
    });
    contTipo.innerHTML = '<select id="filtro_tipo_config" class="form-select select2-custom select2">' + optsTipo + '</select>';
    contEstado.innerHTML =
      '<select id="filtro_estado_config" class="form-select select2-custom select2">' +
      '<option value="">Estado</option>' +
      '<option value="true">Activo</option>' +
      '<option value="false">Inactivo</option>' +
      '</select>';

    initSelect2Filter(document.getElementById('filtro_tipo_config'), 'Todos los tipos');
    initSelect2Filter(document.getElementById('filtro_estado_config'), 'Todos');
  }

  crearFiltros();
  if (!tableEl) return;

  const dt = new DataTable(tableEl, {
    processing: true,
    serverSide: true,
    deferRender: true,
    searchDelay: 500,
    language: DATATABLES_SPANISH,
    columns: [{ data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }],
    createdRow: function (row, data) {
      $(row).css('cursor', 'pointer');
      $(row).on('click', function (e) {
        if (!$(e.target).closest('a, button').length) {
          window.location.href = 'config_movmiento_banco.php?id=' + data[0];
        }
      });
    },
    columnDefs: [
      {
        targets: 0,
        render: function (data) {
          const id = parseInt(data, 10);
          return id
            ? '<a href="config_movmiento_banco.php?id=' + id + '" class="fw-semibold text-primary">' + id + '</a>'
            : '—';
        }
      },
      {
        targets: 4,
        render: function (data) {
          return data === 'Activo'
            ? '<span class="badge bg-label-success">Activo</span>'
            : '<span class="badge bg-label-secondary">Inactivo</span>';
        }
      }
    ],
    order: [[1, 'asc']],
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    layout: {
      topStart: {
        rowClass: 'row m-2 my-0 mt-0 justify-content-between',
        features: [
          {
            buttons: [
              {
                extend: 'collection',
                className: 'btn buttons-collection btn-primary dropdown-toggle waves-effect',
                text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                buttons: [
                  { extend: 'excel', text: 'Excel', className: 'dropdown-item', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
                  { extend: 'pdf', text: 'PDF', className: 'dropdown-item', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
                  { extend: 'copy', text: 'Copiar', className: 'dropdown-item', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } }
                ]
              }
            ]
          }
        ]
      },
      topEnd: {
        features: [
          { search: { placeholder: 'Buscar...', text: '_INPUT_' } },
          {
            buttons: [
              {
                text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-none d-sm-inline-block">Nueva config</span>',
                className: 'add-new btn btn-primary',
                action: function () {
                  window.location.href = 'crear_config_movmiento_banco.php';
                }
              }
            ]
          }
        ]
      },
      bottomStart: { rowClass: 'row mx-3 justify-content-between', features: ['info'] },
      bottomEnd: 'paging'
    },
    ajax: {
      url: 'parts/config_movmientos_bancos/listar/load_list.php',
      type: 'POST',
      data: function (d) {
        const ft = document.getElementById('filtro_tipo_config');
        const fe = document.getElementById('filtro_estado_config');
        d.filtro_tipo = ft ? ft.value : '';
        d.filtro_estado = fe ? fe.value : '';
        return d;
      },
      dataSrc: function (json) {
        return json.data || [];
      }
    }
  });

  window.dt_config_movmientos_bancos = dt;

  setTimeout(function () {
    const elementsToModify = [
      { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
      { selector: '.dt-length .form-select', classToAdd: 'ms-0' },
      { selector: '.dt-length', classToAdd: 'mb-md-4 mb-0' },
      {
        selector: '.dt-layout-end',
        classToRemove: 'justify-content-between',
        classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center gap-md-2 flex-wrap mt-0'
      },
      { selector: '.dt-layout-start', classToAdd: 'mt-md-0 mt-5' },
      {
        selector: '.dt-layout-start .dt-buttons',
        classToAdd: 'd-md-flex d-block gap-4 justify-content-center'
      },
      {
        selector: '.dt-layout-end .dt-buttons',
        classToAdd: 'd-md-flex d-block gap-4 mb-md-0 mb-5 justify-content-center'
      },
      { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
      { selector: '.dt-layout-full', classToRemove: 'col-md col-12' },
      { selector: '.dt-layout-full .table', classToAdd: 'table-responsive' }
    ];

    elementsToModify.forEach(function (item) {
      document.querySelectorAll(item.selector).forEach(function (element) {
        if (item.classToRemove) {
          item.classToRemove.split(' ').forEach(function (className) {
            element.classList.remove(className);
          });
        }
        if (item.classToAdd) {
          item.classToAdd.split(' ').forEach(function (className) {
            element.classList.add(className);
          });
        }
      });
    });
  }, 100);
});
