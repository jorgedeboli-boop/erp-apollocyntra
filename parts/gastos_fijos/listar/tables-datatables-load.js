/**
 * Page Gastos Fijos List
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dtTable = document.querySelector('.datatables-gastos-fijos');
  if (!dtTable) return;

  const $fProveedor = $('#filtro_proveedor');
  const $fFormaPago = $('#filtro_forma_pago');
  const $fTipoGasto = $('#filtro_tipo_gasto');
  const $fPeriodo = $('#filtro_periodo');
  const $fEstado = $('#filtro_estado');

  // Estos 3 selects vienen renderizados desde functions.php sin `select2-custom`
  // (por convención del proyecto), así que se lo aplicamos aquí.
  [$fProveedor, $fFormaPago, $fTipoGasto].forEach($el => {
    if ($el && $el.length) {
      $el.addClass('select2-custom');
    }
  });

  // Select2
  [$fProveedor, $fFormaPago, $fTipoGasto, $fPeriodo, $fEstado].forEach($el => {
    if ($el && $el.length) {
      $el.select2({ dropdownParent: $el.parent() });
    }
  });

  // Flatpickr rango
  if (window.initFlatpickrRange) {
    window.initFlatpickrRange('#rangeFechas', '#filtro_fecha_desde', '#filtro_fecha_hasta');
  }

  const dt = $(dtTable).DataTable({
    processing: true,
    serverSide: true,
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    // Igual que lotes: buscador arriba derecha y botón Exportar; sin dt-length.
    layout: {
      topStart: {
        rowClass: 'row m-2 my-0 mt-0 justify-content-between',
        features: [
          {
            buttons: [
              {
                extend: 'collection',
                className: 'btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect button-exportar',
                text: '<span class="d-flex align-items-center justify-content-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px"></i> <span>Exportar</span></span>',
                buttons: [
                                                      {
                    extend: 'excel',
                    text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                    className: 'dropdown-item',
                    exportOptions: { columns: ':visible' }
                  },
                  {
                    extend: 'pdf',
                    text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                    className: 'dropdown-item',
                    orientation: 'landscape',
                    exportOptions: { columns: ':visible' }
                  },
                  {
                    extend: 'copy',
                    text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-copy-line me-1"></i>Copiar</span>`,
                    className: 'dropdown-item',
                    exportOptions: { columns: ':visible' }
                  }
                ]
              }
            ]
          }
        ]
      },
      topEnd: {
        features: [
          {
            search: {
              placeholder: 'Buscar...',
              text: '_INPUT_'
            }
          }
        ]
      },
      bottomStart: {
        rowClass: 'row mx-0 justify-content-between w-100',
        features: ['info']
      },
      bottomEnd: 'paging'
    },
    ajax: {
      url: 'parts/gastos_fijos/listar/load_list.php',
      type: 'POST',
      data: function (d) {
        d.filtro_proveedor = $fProveedor.val() || '';
        d.filtro_forma_pago = $fFormaPago.val() || '';
        d.filtro_tipo_gasto = $fTipoGasto.val() || '';
        d.filtro_periodo = $fPeriodo.val() || '';
        d.filtro_estado = $fEstado.val() || '';
        d.filtro_fecha_desde = $('#filtro_fecha_desde').val() || '';
        d.filtro_fecha_hasta = $('#filtro_fecha_hasta').val() || '';
      }
    },
    columns: [
      { data: 'id_gasto_fijo' },
      { data: 'fecha_alta' },
      { data: 'proveedor_nombre' },
      { data: 'proveedor_cif' },
      { data: 'total' },
      { data: 'descripcion' },
      { data: 'tipo_gasto' },
      { data: 'forma_pago' },
      { data: 'fecha_inicio' },
      { data: 'periodo' },
      { data: 'estado' }
    ],
    columnDefs: [
      {
        targets: 0,
        render: function (data, type, full) {
          const id = full?.id_gasto_fijo ?? data;
          return `<a class="fw-semibold" href="gasto_fijo.php?id=${id}">${id}</a>`;
        }
      },
      {
        targets: 1,
        render: function (data) {
          if (!data) return '-';
          const d = new Date(data);
          if (isNaN(d.getTime())) return data;
          return d.toLocaleDateString('es-ES');
        }
      },
      {
        targets: 4,
        className: 'text-end',
        render: function (data) {
          const n = parseFloat(data);
          if (isNaN(n)) return data ?? '-';
          return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(n);
        }
      },
      {
        targets: 8,
        render: function (data) {
          if (!data) return '-';
          const d = new Date(data);
          if (isNaN(d.getTime())) return data;
          return d.toLocaleDateString('es-ES');
        }
      },
      {
        targets: 10,
        render: function (data) {
          if (data === 'true') return '<span class="badge bg-label-success">Activo</span>';
          if (data === 'false') return '<span class="badge bg-label-warning">Desactivado</span>';
          return '-';
        }
      }
    ],
    order: [[0, 'desc']]
  });

  // Click en la fila -> ficha del gasto fijo (excepto clicks en enlaces/botones)
  dtTable.addEventListener('click', function (e) {
    const a = e.target.closest('a, button');
    if (a) return;
    const tr = e.target.closest('tr');
    if (!tr) return;
    const rowData = dt.row(tr).data();
    if (rowData && rowData.id_gasto_fijo) {
      window.location.href = `gasto_fijo.php?id=${rowData.id_gasto_fijo}`;
    }
  });

  // Mismo post-procesado de clases que en lotes
  setTimeout(() => {
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
      { selector: '.dt-layout-full .table', classToAdd: 'table-responsive' },
      // Ajuste adicional pedido para gastos_fijos
      {
        selector: '.dt-layout-full',
        classToAdd: 'd-md-flex justify-content-between align-items-center dt-layout-full'
      }
    ];

    elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
      document.querySelectorAll(selector).forEach(element => {
        if (classToRemove) {
          classToRemove.split(' ').forEach(className => element.classList.remove(className));
        }
        if (classToAdd) {
          classToAdd.split(' ').forEach(className => element.classList.add(className));
        }
      });
    });
  }, 100);

  // Recargar al cambiar filtros
  [$fProveedor, $fFormaPago, $fTipoGasto, $fPeriodo, $fEstado].forEach($el => {
    if ($el && $el.length) {
      $el.on('change', function () {
        dt.ajax.reload();
      });
    }
  });

  $('#rangeFechas').on('change', function () {
    dt.ajax.reload();
  });
});

