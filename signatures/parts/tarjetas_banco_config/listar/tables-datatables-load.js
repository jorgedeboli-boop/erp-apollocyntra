'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const tableEl = document.querySelector('.datatables-tarjetas-banco-config');
  let dt;

  function initSelect2Filter(select, placeholder) {
    if (typeof $ === 'undefined' || !$.fn.select2) {
      select.addEventListener('change', function () {
        if (window.dt_tarjetas_banco_config) window.dt_tarjetas_banco_config.ajax.reload();
      });
      return;
    }
    const $select = $(select);
    $select.select2({
      dropdownParent: $select.parent(),
      placeholder: placeholder,
      allowClear: true,
      width: '100%'
    });
    $select.on('select2:select select2:clear', function () {
      if (window.dt_tarjetas_banco_config) window.dt_tarjetas_banco_config.ajax.reload();
    });
  }

  function crearFiltros() {
    const contBanco = document.querySelector('.filtro_banco_tarjeta');
    const contEmpresa = document.querySelector('.filtro_empresa_tarjeta');
    if (!contBanco || !contEmpresa) return;

    contBanco.innerHTML = '<select id="filtro_banco_tarjeta" class="form-select select2-custom select2"><option value="">Banco</option></select>';
    contEmpresa.innerHTML = '<select id="filtro_empresa_tarjeta" class="form-select select2-custom select2"><option value="">Empresa</option></select>';

    fetch('parts/tarjetas_banco_config/listar/get_filtros.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) return;
        const selBanco = document.getElementById('filtro_banco_tarjeta');
        const selEmpresa = document.getElementById('filtro_empresa_tarjeta');
        (data.bancos || []).forEach(function (b) {
          const opt = document.createElement('option');
          opt.value = b.id;
          opt.textContent = b.text;
          selBanco.appendChild(opt);
        });
        (data.empresas || []).forEach(function (e) {
          const opt = document.createElement('option');
          opt.value = e.id;
          opt.textContent = e.text;
          selEmpresa.appendChild(opt);
        });
        initSelect2Filter(selBanco, 'Todos los bancos');
        initSelect2Filter(selEmpresa, 'Todas las empresas');
      });
  }

  crearFiltros();
  if (!tableEl) return;

  dt = new DataTable(tableEl, {
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
          window.location.href = 'tarjeta_banco_config.php?id=' + data[0];
        }
      });
    },
    columnDefs: [
      {
        targets: 0,
        render: function (data) {
          const id = parseInt(data, 10);
          return id
            ? '<a href="tarjeta_banco_config.php?id=' + id + '" class="fw-semibold text-primary">' + id + '</a>'
            : '—';
        }
      },
      {
        targets: 4,
        render: function (data) {
          return data === 'Sí'
            ? '<span class="badge bg-label-success">Sí</span>'
            : '<span class="badge bg-label-secondary">No</span>';
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
                text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-none d-sm-inline-block">Nueva tarjeta</span>',
                className: 'add-new btn btn-primary',
                action: function () {
                  window.location.href = 'crear_tarjeta_banco_config.php';
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
      url: 'parts/tarjetas_banco_config/listar/load_list.php',
      type: 'POST',
      data: function (d) {
        const fb = document.getElementById('filtro_banco_tarjeta');
        const fe = document.getElementById('filtro_empresa_tarjeta');
        d.filtro_banco = fb ? fb.value : '';
        d.filtro_empresa = fe ? fe.value : '';
        return d;
      },
      dataSrc: function (json) {
        return json.data || [];
      }
    }
  });

  window.dt_tarjetas_banco_config = dt;

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
