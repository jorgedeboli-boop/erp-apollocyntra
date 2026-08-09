/**
 * Page Reportes Diarios
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dt_table = document.querySelector('.datatables-reportes-diarios');
  let dt_reportes;

  function inicializarSelect2() {
    if (typeof $ === 'undefined' || !$.fn.select2) {
      return;
    }

    $('.select2-filter').each(function () {
      const $this = $(this);
      if ($this.hasClass('select2-hidden-accessible')) {
        return;
      }

      if (typeof select2Focus === 'function') {
        select2Focus($this);
      }

      $this.select2({
        dropdownParent: $this.parent(),
        allowClear: true,
        width: '100%'
      });
    });
  }

  function recargarTabla() {
    if (window.dt_reportes_diarios) {
      window.dt_reportes_diarios.ajax.reload();
    }
  }

  function bindFiltros() {
    const filtroSucursal = document.getElementById('filtro_sucursal');
    if (filtroSucursal) {
      $(filtroSucursal).on('change select2:select select2:clear', recargarTabla);
    }
  }

  function renderMoneda(data) {
    return '<span class="text-nowrap fw-medium">' + (data || '0,00 €') + '</span>';
  }

  function renderGramos(data) {
    return '<span class="text-nowrap">' + (data || '0,00 gr') + '</span>';
  }

  let filaInformeActiva = null;
  const modalEditarInformeEl = document.getElementById('modalEditarInformeDiario');
  const modalEditarInforme = modalEditarInformeEl && typeof bootstrap !== 'undefined'
    ? new bootstrap.Modal(modalEditarInformeEl)
    : null;

  function abrirModalEditarInforme(row) {
    const data = row.data();
    if (!data || !data[12]) {
      return;
    }

    const meta = data[13] || {};
    filaInformeActiva = row;

    document.getElementById('editar_informe_id').value = data[12];
    document.getElementById('editar_informe_fecha').textContent = data[0] || '-';
    document.getElementById('editar_informe_sucursal').textContent = meta.nombre_sucursal || data[11] || '-';
    document.getElementById('editar_informe_total_gastos').value = meta.total_gastos != null ? meta.total_gastos : '';

    if (modalEditarInforme) {
      modalEditarInforme.show();
    }
  }

  function actualizarFilaInforme(totalGastosFormatted, totalGastosRaw) {
    if (!filaInformeActiva) {
      return;
    }

    const rowData = filaInformeActiva.data().slice();
    rowData[9] = totalGastosFormatted;

    if (!rowData[13]) {
      rowData[13] = {};
    }

    rowData[13].total_gastos = totalGastosRaw;

    filaInformeActiva.data(rowData).draw(false);
    filaInformeActiva = null;
  }

  function bindModalEditarInforme() {
    const btnGuardar = document.getElementById('btnGuardarInformeDiario');
    const form = document.getElementById('formEditarInformeDiario');

    if (!btnGuardar || !form) {
      return;
    }

    btnGuardar.addEventListener('click', function () {
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const textoOriginal = btnGuardar.innerHTML;
      btnGuardar.disabled = true;
      btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

      fetch('parts/reportes_diarios/unique/actualizar_informe.php', {
        method: 'POST',
        body: new FormData(form)
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (!data.success) {
            throw new Error(data.error || 'No se pudo actualizar el informe');
          }

          actualizarFilaInforme(data.total_gastos_formatted, data.total_gastos);

          if (modalEditarInforme) {
            modalEditarInforme.hide();
          }

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Guardado',
              text: data.message || 'Informe actualizado correctamente',
              timer: 1500,
              showConfirmButton: false
            });
          }
        })
        .catch(function (error) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error.message || 'Error al actualizar el informe'
            });
          }
        })
        .finally(function () {
          btnGuardar.disabled = false;
          btnGuardar.innerHTML = textoOriginal;
        });
    });

    if (modalEditarInformeEl) {
      modalEditarInformeEl.addEventListener('hidden.bs.modal', function () {
        filaInformeActiva = null;
      });
    }
  }

  if (dt_table) {
    dt_reportes = new DataTable(dt_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/reportes_diarios/unique/load_list.php',
        type: 'POST',
        data: function (d) {
          d.filtro_sucursal = document.getElementById('filtro_sucursal')?.value || '';
          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
        error: function (xhr) {
          console.error('Error en DataTable reportes diarios:', xhr.responseText);
          if (xhr.status === 401) {
            window.location.href = 'login.php';
          }
        }
      },
      columns: [
        { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
        { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 },
        { data: 10 }, { data: 11 }, { data: 12, visible: false, searchable: false },
        { data: 13, visible: false, searchable: false }
      ],
      createdRow: function (row) {
        row.classList.add('cursor-pointer');
        row.setAttribute('title', 'Click para editar gastos');
      },
      columnDefs: [
        {
          targets: 0,
          className: 'text-center',
          render: function (data) {
            return '<span class="badge bg-label-primary rounded-pill text-nowrap">' + (data || '-') + '</span>';
          }
        },
        {
          targets: [1, 3, 5, 7, 8, 9, 10],
          className: 'text-end',
          render: renderMoneda
        },
        {
          targets: [2, 4, 6],
          className: 'text-end',
          render: renderGramos
        },
        {
          targets: 11,
          render: function (data) {
            return '<span class="fw-medium text-heading">' + (data || 'Sin sucursal') + '</span>';
          }
        },
        {
          targets: [12, 13],
          visible: false,
          searchable: false
        }
      ],
      order: [[0, 'desc']],
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      scrollX: true,
      layout: {
        topStart: {
          rowClass: 'row m-2 my-0 mt-0 justify-content-between',
          features: [{
            buttons: [{
              extend: 'collection',
              className: 'btn btn-outline-secondary dropdown-toggle waves-effect',
              text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
              buttons: [
                { extend: 'print', text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-printer-line me-1"></i>Imprimir</span>', className: 'dropdown-item', exportOptions: { columns: ':visible' } },
                { extend: 'csv', text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-text-line me-1"></i>CSV</span>', className: 'dropdown-item', exportOptions: { columns: ':visible' } },
                { extend: 'excel', text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>', className: 'dropdown-item', exportOptions: { columns: ':visible' } },
                {
                  extend: 'pdf',
                  text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                  className: 'dropdown-item',
                  orientation: 'landscape',
                  exportOptions: { columns: ':visible' },
                  customize: function (doc) {
                    doc.pageOrientation = 'landscape';
                    doc.pageSize = 'LEGAL';
                    doc.defaultStyle.fontSize = 7;
                    doc.styles.tableHeader.fontSize = 7;
                    doc.content[0].text = 'Reportes Diarios';
                    doc.content[0].alignment = 'center';
                    doc.content[0].fontSize = 12;
                  }
                },
                { extend: 'copy', text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar', className: 'dropdown-item', exportOptions: { columns: ':visible' } }
              ]
            }]
          }]
        },
        topEnd: { features: [{ search: { placeholder: 'Buscar...', text: '_INPUT_' } }] },
        bottomStart: { rowClass: 'row mx-3 justify-content-between', features: ['info'] },
        bottomEnd: 'paging'
      },
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Reporte ' + data[0] + ' - ' + data[11];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            const html = columns.map(function (col) {
              return col.hidden
                ? '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '"><td>' + col.title + ':</td><td>' + col.data + '</td></tr>'
                : '';
            }).join('');
            return html ? $('<table class="table"/><tbody />').append(html) : false;
          }
        }
      }
    });

    window.dt_reportes_diarios = dt_reportes;
    inicializarSelect2();
    bindFiltros();
    bindModalEditarInforme();

    $(dt_table).on('click', 'tbody tr', function () {
      const row = dt_reportes.row(this);
      if (!row || !row.data() || !row.data()[12]) {
        return;
      }
      abrirModalEditarInforme(row);
    });
  }

  setTimeout(function () {
    [
      { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
      { selector: '.dt-length .form-select', classToAdd: 'ms-0' },
      { selector: '.dt-length', classToAdd: 'mb-md-4 mb-0' },
      { selector: '.dt-layout-end', classToRemove: 'justify-content-between', classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center gap-md-2 flex-wrap mt-0' },
      { selector: '.dt-layout-start', classToAdd: 'mt-md-0 mt-5' },
      { selector: '.dt-layout-start .dt-buttons', classToAdd: 'd-md-flex d-block gap-4 justify-content-center' },
      { selector: '.dt-layout-end .dt-buttons', classToAdd: 'd-md-flex d-block gap-4 mb-md-0 mb-5 justify-content-center' },
      { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
      { selector: '.dt-layout-full', classToRemove: 'col-md col-12' },
      { selector: '.dt-layout-full .table', classToAdd: 'table-responsive' }
    ].forEach(function (item) {
      document.querySelectorAll(item.selector).forEach(function (element) {
        if (item.classToRemove) {
          item.classToRemove.split(' ').forEach(function (c) { element.classList.remove(c); });
        }
        if (item.classToAdd) {
          item.classToAdd.split(' ').forEach(function (c) { element.classList.add(c); });
        }
      });
    });
  }, 100);
});
