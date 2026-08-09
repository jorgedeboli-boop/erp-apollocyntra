/**
 * Page Reportes Actuales
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  if (window.ListarFiltros) {
    window.ListarFiltros.setOnChange(function () {
      recargarTabla();
    });
  }

  const dt_table = document.querySelector('.datatables-reportes-actuales');
  let dt_reportes;

  function recargarTabla() {
    if (window.dt_reportes_actuales) {
      window.dt_reportes_actuales.ajax.reload();
    }
    actualizarTituloReportesActuales();
  }

  function configurarFiltrosFecha() {
    window.filtro_periodo_activo_reportes_actuales = 'todos';

    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    const btnFecha = document.getElementById('filtro_por_fecha_actuales');
    if (btnFecha) {
      btnFecha.addEventListener('click', function () {
        if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: 'Atención',
              text: 'Debe seleccionar al menos una fecha',
              confirmButtonText: 'Aceptar'
            });
          }
          return;
        }
        window.filtro_periodo_activo_reportes_actuales = 'fecha';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        recargarTabla();
      });
    }

    const btnDia = document.getElementById('filtro_dia_actuales');
    if (btnDia) {
      btnDia.addEventListener('click', function () {
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo_reportes_actuales = 'dia';
        recargarTabla();
      });
    }

    const btnMes = document.getElementById('filtro_mes_actuales');
    if (btnMes) {
      btnMes.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        window.filtro_periodo_activo_reportes_actuales = 'mes';
        recargarTabla();
      });
    }

    const btnTodos = document.getElementById('filtro_todos_actuales');
    if (btnTodos) {
      btnTodos.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        window.filtro_periodo_activo_reportes_actuales = 'todos';
        recargarTabla();
      });
    }
  }

  function actualizarTituloReportesActuales() {
    const textoTitulo = document.getElementById('texto_reportes_actuales_titulo');
    if (!textoTitulo) {
      return;
    }

    const partes = [];
    const filtroSucursal = document.getElementById('filtro_sucursal');
    if (filtroSucursal && filtroSucursal.value) {
      partes.push('de ' + filtroSucursal.options[filtroSucursal.selectedIndex].text);
    }

    const filtroActivo = window.filtro_periodo_activo_reportes_actuales || 'todos';
    const fechaDesde = document.getElementById('filtro_fecha_desde')?.value || '';
    const fechaHasta = document.getElementById('filtro_fecha_hasta')?.value || '';

    if (filtroActivo === 'dia') {
      partes.push('de hoy');
    } else if (filtroActivo === 'mes') {
      partes.push('de este mes');
    } else if (
      filtroActivo === 'fecha' ||
      (window.filtro_periodo_activo === 'personalizado' && (fechaDesde || fechaHasta))
    ) {
      if (fechaDesde && fechaHasta) {
        if (fechaDesde === fechaHasta) {
          partes.push('del ' + new Date(fechaDesde + 'T00:00:00').toLocaleDateString('es-ES'));
        } else {
          partes.push(
            'entre el ' + new Date(fechaDesde + 'T00:00:00').toLocaleDateString('es-ES') +
            ' y el ' + new Date(fechaHasta + 'T00:00:00').toLocaleDateString('es-ES')
          );
        }
      }
    }

    textoTitulo.textContent = partes.length ? partes.join(' ') : '';
  }

  function renderMoneda(data) {
    return '<span class="text-nowrap fw-medium">' + (data || '0,00 €') + '</span>';
  }

  function renderGramos(data) {
    return '<span class="text-nowrap">' + (data || '0,00 gr') + '</span>';
  }

  let filaInformeActiva = null;
  const modalEditarInformeEl = document.getElementById('modalEditarInformeActual');
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
    const btnGuardar = document.getElementById('btnGuardarInformeActual');
    const form = document.getElementById('formEditarInformeActual');

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

      fetch('parts/reportes_actuales/unique/actualizar_informe.php', {
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

  function obtenerFiltroPeriodoReportes() {
    let periodo = window.filtro_periodo_activo_reportes_actuales || 'todos';
    const fechaDesde = document.getElementById('filtro_fecha_desde')?.value || '';
    const fechaHasta = document.getElementById('filtro_fecha_hasta')?.value || '';
    if (
      periodo === 'todos' &&
      (fechaDesde || fechaHasta) &&
      (window.filtro_periodo_activo === 'personalizado' || window.filtro_periodo_activo === 'fecha')
    ) {
      periodo = 'fecha';
    }
    return periodo;
  }

  function exportarTodosLosDatos(tipo, dt) {
    const formData = new FormData();
    formData.append('search', dt.search());
    formData.append('filtro_sucursal', document.getElementById('filtro_sucursal')?.value || '');
    formData.append('filtro_fecha_desde', document.getElementById('filtro_fecha_desde')?.value || '');
    formData.append('filtro_fecha_hasta', document.getElementById('filtro_fecha_hasta')?.value || '');
    formData.append('filtro_periodo', obtenerFiltroPeriodoReportes());

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Generando exportación...',
        text: 'Obteniendo todos los registros',
        allowOutsideClick: false,
        didOpen: function () {
          Swal.showLoading();
        }
      });
    }

    fetch('parts/reportes_actuales/unique/export_all.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (responseData) {
        if (typeof Swal !== 'undefined') {
          Swal.close();
        }

        if (!responseData.success) {
          throw new Error(responseData.error || 'Error al obtener datos');
        }

        if (!responseData.data || responseData.data.length === 0) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: 'Sin datos',
              text: 'No hay datos para exportar con los filtros aplicados',
              icon: 'info',
              confirmButtonText: 'Aceptar'
            });
          }
          return;
        }

        const tempTableId = 'temp-export-reportes-actuales-' + Date.now();
        const tempDiv = document.createElement('div');
        tempDiv.style.display = 'none';
        tempDiv.innerHTML =
          '<table id="' + tempTableId + '"><thead><tr>' +
          '<th>Fecha</th><th>Compras € oro</th><th>Compras grs oro</th><th>Compras € plata</th>' +
          '<th>Compras grs plata</th><th>Empeños €</th><th>Empeños grs</th><th>Renovaciones €</th>' +
          '<th>Ventas</th><th>Gastos</th><th>Stock</th><th>Sucursal</th>' +
          '</tr></thead></table>';
        document.body.appendChild(tempDiv);

        const tempTable = $('#' + tempTableId).DataTable({
          data: responseData.data,
          columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 },
            { data: 4 }, { data: 5 }, { data: 6 }, { data: 7 },
            { data: 8 }, { data: 9 }, { data: 10 }, { data: 11 }
          ],
          paging: false,
          searching: false,
          ordering: false,
          dom: 't',
          buttons: []
        });

        let exportConfig = { exportOptions: { columns: ':visible' } };

        if (tipo === 'pdf') {
          exportConfig.orientation = 'landscape';
          exportConfig.customize = function (doc) {
            doc.pageOrientation = 'landscape';
            doc.pageSize = 'LEGAL';
            doc.defaultStyle.fontSize = 7;
            doc.styles.tableHeader.fontSize = 7;
            doc.content[0].text = 'Reportes Actuales';
            doc.content[0].alignment = 'center';
            doc.content[0].fontSize = 12;
            doc.content[0].margin = [0, 0, 0, 10];
            doc.pageMargins = [5, 5, 5, 5];
            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
          };
        }

        const buttonType = tipo === 'excel' ? 'excelHtml5' : tipo;

        try {
          const tempButton = tempTable.button().add(0, Object.assign({ extend: buttonType }, exportConfig));
          tempButton.trigger();

          setTimeout(function () {
            tempTable.destroy();
            tempDiv.remove();
          }, 2000);
        } catch (error) {
          tempTable.destroy();
          tempDiv.remove();
          throw error;
        }
      })
      .catch(function (error) {
        if (typeof Swal !== 'undefined') {
          Swal.close();
          Swal.fire({
            title: 'Error',
            text: 'Ha ocurrido un error al exportar: ' + (error.message || 'Error desconocido'),
            icon: 'error',
            confirmButtonText: 'Aceptar'
          });
        } else {
          console.error('Error al exportar:', error);
        }
      });
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
        url: 'parts/reportes_actuales/unique/load_list.php',
        type: 'POST',
        data: function (d) {
          d.filtro_sucursal = document.getElementById('filtro_sucursal')?.value || '';
          d.filtro_fecha_desde = document.getElementById('filtro_fecha_desde')?.value || '';
          d.filtro_fecha_hasta = document.getElementById('filtro_fecha_hasta')?.value || '';

          let periodo = window.filtro_periodo_activo_reportes_actuales || 'todos';
          const fechaDesde = d.filtro_fecha_desde;
          const fechaHasta = d.filtro_fecha_hasta;
          if (
            periodo === 'todos' &&
            (fechaDesde || fechaHasta) &&
            (window.filtro_periodo_activo === 'personalizado' || window.filtro_periodo_activo === 'fecha')
          ) {
            periodo = 'fecha';
          }
          d.filtro_periodo = periodo;
          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
        error: function (xhr) {
          console.error('Error en DataTable reportes actuales:', xhr.responseText);
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
      autoWidth: false,
      layout: {
        topStart: {
          rowClass: 'row m-2 my-0 mt-0 justify-content-between',
          features: [{
            buttons: [{
              extend: 'collection',
              className: 'btn btn-outline-secondary dropdown-toggle waves-effect',
              text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
              buttons: [
                                                {
                  extend: 'excel',
                  text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
                  className: 'dropdown-item',
                  action: function (e, dt) { exportarTodosLosDatos('excel', dt); }
                },
                {
                  extend: 'pdf',
                  text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                  className: 'dropdown-item',
                  orientation: 'landscape',
                  action: function (e, dt) { exportarTodosLosDatos('pdf', dt); }
                },
                {
                  extend: 'copy',
                  text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar',
                  className: 'dropdown-item',
                  action: function (e, dt) { exportarTodosLosDatos('copy', dt); }
                }
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

    window.dt_reportes_actuales = dt_reportes;
    window.actualizarTituloFiltros = actualizarTituloReportesActuales;
    configurarFiltrosFecha();
    bindModalEditarInforme();
    actualizarTituloReportesActuales();

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
