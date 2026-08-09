/**
 * Page Reportes Mensuales
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dt_table = document.querySelector('.datatables-reportes-mensuales');
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
        allowClear: $this.attr('id') === 'filtro_sucursal',
        width: '100%'
      });
    });
  }

  function recargarTabla() {
    if (window.dt_reportes_mensuales) {
      window.dt_reportes_mensuales.ajax.reload();
    }
  }

  function bindFiltros() {
    ['filtro_sucursal', 'filtro_anio', 'filtro_mes'].forEach(function (id) {
      const el = document.getElementById(id);
      if (el) {
        $(el).on('change select2:select select2:clear', recargarTabla);
      }
    });
  }

  function renderMoneda(data) {
    return '<span class="text-nowrap fw-medium">' + (data || '0,00 €') + '</span>';
  }

  function renderGramos(data) {
    return '<span class="text-nowrap">' + (data || '0,00 gr') + '</span>';
  }

  function formatearMesAnio(numeroMes, yearInforme) {
    return numeroMes + ' / ' + yearInforme;
  }

  let filaInformeActiva = null;
  const modalEditarInformeEl = document.getElementById('modalEditarInformeMensual');
  const modalEditarInforme = modalEditarInformeEl && typeof bootstrap !== 'undefined'
    ? new bootstrap.Modal(modalEditarInformeEl)
    : null;

  function abrirModalEditarInforme(row) {
    const data = row.data();
    if (!data || !data[16]) {
      return;
    }

    const meta = data[17] || {};
    filaInformeActiva = row;

    document.getElementById('editar_informe_id').value = data[16];
    document.getElementById('editar_informe_mes_anio').textContent = formatearMesAnio(data[0], meta.year_informe || '');
    document.getElementById('editar_informe_sucursal').textContent = meta.nombre_sucursal || data[15] || '-';
    document.getElementById('editar_informe_total_gastos').value = meta.total_gastos != null ? meta.total_gastos : '';
    document.getElementById('editar_informe_yulinfo').value = meta.yulinfo != null ? meta.yulinfo : '';

    if (modalEditarInforme) {
      modalEditarInforme.show();
    }
  }

  function actualizarFilaInforme(totalGastosFormatted, yulinfoFormatted, totalGastosRaw, yulinfoRaw) {
    if (!filaInformeActiva) {
      return;
    }

    const rowData = filaInformeActiva.data().slice();
    rowData[11] = totalGastosFormatted;
    rowData[13] = yulinfoFormatted;

    if (!rowData[17]) {
      rowData[17] = {};
    }

    rowData[17].total_gastos = totalGastosRaw;
    rowData[17].yulinfo = yulinfoRaw;

    filaInformeActiva.data(rowData).draw(false);
    filaInformeActiva = null;
  }

  function bindModalEditarInforme() {
    const btnGuardar = document.getElementById('btnGuardarInformeMensual');
    const form = document.getElementById('formEditarInformeMensual');

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

      fetch('parts/reportes_mensuales/unique/actualizar_informe.php', {
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

          actualizarFilaInforme(
            data.total_gastos_formatted,
            data.yulinfo_formatted,
            data.total_gastos,
            data.yulinfo
          );

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
        url: 'parts/reportes_mensuales/unique/load_list.php',
        type: 'POST',
        data: function (d) {
          d.filtro_sucursal = document.getElementById('filtro_sucursal')?.value || '';
          d.filtro_mes = document.getElementById('filtro_mes')?.value || '';
          d.filtro_anio = document.getElementById('filtro_anio')?.value || '';
          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
        error: function (xhr) {
          console.error('Error en DataTable reportes mensuales:', xhr.responseText);
          if (xhr.status === 401) {
            window.location.href = 'login.php';
          }
        }
      },
      columns: [
        { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
        { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 },
        { data: 10 }, { data: 11 }, { data: 12 }, { data: 13 }, { data: 14 },
        { data: 15 }, { data: 16, visible: false, searchable: false },
        { data: 17, visible: false, searchable: false }
      ],
      createdRow: function (row) {
        row.classList.add('cursor-pointer');
        row.setAttribute('title', 'Click para editar gastos y yulinfo');
      },
      columnDefs: [
        {
          targets: 0,
          className: 'text-center',
          render: function (data, type, full) {
            const year = full[17] && full[17].year_informe ? full[17].year_informe : '';
            const label = formatearMesAnio(data, year);
            if (type === 'display') {
              return '<span class="badge bg-label-primary rounded-pill text-nowrap">' + label + '</span>';
            }
            return label;
          }
        },
        {
          targets: [1, 2],
          render: function (data) {
            return '<span class="text-nowrap">' + (data || '-') + '</span>';
          }
        },
        {
          targets: [3, 5, 7, 9, 10, 11, 12, 13, 14],
          className: 'text-end',
          render: renderMoneda
        },
        {
          targets: [4, 6, 8],
          className: 'text-end',
          render: renderGramos
        },
        {
          targets: 15,
          render: function (data) {
            return '<span class="fw-medium text-heading">' + (data || 'Sin sucursal') + '</span>';
          }
        },
        {
          targets: [16, 17],
          visible: false,
          searchable: false
        }
      ],
      order: [[15, 'asc']],
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
                    doc.content[0].text = 'Reportes Mensuales';
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
              const year = data[17] && data[17].year_informe ? data[17].year_informe : '';
              return 'Reporte ' + formatearMesAnio(data[0], year) + ' - ' + data[15];
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

    window.dt_reportes_mensuales = dt_reportes;
    inicializarSelect2();
    bindFiltros();
    bindModalEditarInforme();

    $(dt_table).on('click', 'tbody tr', function () {
      const row = dt_reportes.row(this);
      if (!row || !row.data() || !row.data()[16]) {
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
