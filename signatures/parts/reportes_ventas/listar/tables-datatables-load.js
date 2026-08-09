/**
 * Page Reportes Ventas
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  if (window.ListarFiltros) {
    window.ListarFiltros.setOnChange(function () {
      recargarTabla();
    });
  }

  const dt_table = document.querySelector('.datatables-reportes-ventas');
  let dt_reportes_ventas;

  function recargarTabla() {
    if (window.dt_reportes_ventas) {
      window.dt_reportes_ventas.ajax.reload();
    }
    actualizarTituloReportesVentas();
  }

  function formatearFechaDisplay(fecha) {
    if (!fecha || fecha === '0000-00-00') {
      return '-';
    }
    const d = new Date(String(fecha).substring(0, 10) + 'T00:00:00');
    if (isNaN(d.getTime())) {
      return fecha;
    }
    return d.toLocaleDateString('es-ES');
  }

  function obtenerAnioFecha(fecha) {
    if (!fecha || fecha === '0000-00-00') {
      return '';
    }
    const d = new Date(String(fecha).substring(0, 10) + 'T00:00:00');
    return isNaN(d.getTime()) ? '' : String(d.getFullYear());
  }

  function renderSku(data, type, full) {
    if (type !== 'display' || !data) {
      return data;
    }
    const idSucursal = full[15] || '';
    return '<a class="text-heading fw-semibold" target="_blank" rel="noopener" href="articulo.php?id=' +
      encodeURIComponent(data) + (idSucursal ? '&sucursal_articulo=' + encodeURIComponent(idSucursal) : '') +
      '">' + data + '</a>';
  }

  function renderDescripcion(data) {
    return '<span style="font-size: 9px; line-height: 9px !important;">' + (data || '-') + '</span>';
  }

  function renderVenta(data, type, full) {
    const idVenta = full[16] || '';
    const idSucursal = full[15] || '';
    const label = 'Nº ' + (data || '-');

    if (type !== 'display' || !data) {
      return label;
    }

    if (!idVenta) {
      return label;
    }

    return '<a class="text-heading" target="_blank" rel="noopener" href="venta.php?id=' +
      encodeURIComponent(idVenta) + (idSucursal ? '&sucursal_venta=' + encodeURIComponent(idSucursal) : '') +
      '">' + label + '</a>';
  }

  function renderFactura(data, type, full) {
    if (type !== 'display' || data === '' || data === null || data === undefined) {
      return '-';
    }

    const prefijo = full[22] || '';
    const anio = obtenerAnioFecha(full[3]);
    let texto = (prefijo ? prefijo + ' ' : '') + data + (anio ? '/' + anio : '');
    const idFactura = full[21] || '';

    if (!idFactura) {
      return texto;
    }

    return '<a class="text-heading" target="_blank" rel="noopener" href="Impresiones/Facturas/factura.php?id_factura=' +
      encodeURIComponent(idFactura) + '">' + texto + '</a>';
  }

  function renderWeb(data) {
    const esWeb = String(data).toLowerCase() === 'true' || data === 'Si';
    if (esWeb) {
      return '<span class="badge bg-label-success text-center" style="min-width: 20px;">Si</span>';
    }
    return '<span class="text-center d-inline-block" style="min-width: 20px;">----</span>';
  }

  function renderTipo(data) {
    const tipo = String(data || '').toLowerCase();
    if (tipo.indexOf('oro') !== -1) {
      return '<span class="badge bg-label-warning text-center" style="min-width: 35px;">Oro</span>';
    }
    return '<span class="badge bg-label-secondary text-center" style="min-width: 35px;">Plata</span>';
  }

  function renderPlazos(data) {
    if (String(data).toLowerCase() === 'si') {
      return '<span class="badge bg-label-success text-center" style="min-width: 20px;">Si</span>';
    }
    return '<span class="text-center d-inline-block" style="min-width: 20px;">----</span>';
  }

  function renderPlazosPdtes(data, type, full) {
    if (type !== 'display') {
      return data;
    }

    if (String(full[11]).toLowerCase() === 'no') {
      return '<span class="text-center d-inline-block" style="min-width: 20px;">----</span>';
    }

    const numeroPlazos = parseInt(full[12], 10) || 0;
    const pagados = parseInt(full[20], 10) || 0;
    const pendientes = numeroPlazos - pagados;

    if (pendientes > 0) {
      return '<span class="badge bg-label-warning text-center text-nowrap" style="min-width: 90px;">' +
        pendientes + ' de ' + numeroPlazos + '</span>';
    }

    return '<span class="badge bg-label-success text-center text-nowrap" style="min-width: 90px;">Pagado</span>';
  }

  function renderTipoPago(data) {
    const tipo = String(data || '').toLowerCase();

    if (tipo === 'tarjeta') {
      return '<span class="badge bg-label-warning text-center text-nowrap" style="min-width: 90px;">Tarjeta</span>';
    }
    if (tipo === 'contado') {
      return '<span class="badge bg-label-success text-center text-nowrap" style="min-width: 90px;">Efectivo</span>';
    }
    if (tipo === 'bizum') {
      return '<span class="badge bg-label-info text-center text-nowrap" style="min-width: 90px;">Bizum</span>';
    }
    if (tipo === 'combinado') {
      return '<span class="badge bg-label-info text-center text-nowrap" style="min-width: 90px;">Combinado</span>';
    }
    if (tipo === 'transferencia') {
      return '<span class="badge bg-label-info text-center text-nowrap" style="min-width: 90px;">Transferencia</span>';
    }

    return '<span class="badge bg-label-success text-center text-nowrap" style="min-width: 90px;">Efectivo</span>';
  }

  function renderPagos(data, type, full) {
    if (type !== 'display') {
      return data;
    }

    const tipoPago = String(full[13] || '').toLowerCase();
    const precioArticulo = full[7];

    if (tipoPago === 'combinado') {
      const partes = [];
      const contado = parseFloat(full[14]) || 0;
      const tarjeta = parseFloat(full[17]) || 0;
      const transferencia = parseFloat(full[18]) || 0;
      const bizum = parseFloat(full[19]) || 0;

      if (contado > 0) {
        partes.push(' Contado: ' + contado);
      }
      if (tarjeta > 0) {
        partes.push(' Tarjeta: ' + tarjeta);
      }
      if (transferencia > 0) {
        partes.push(' Transferencia: ' + transferencia);
      }
      if (bizum > 0) {
        partes.push(' Bizum: ' + bizum);
      }

      return '<span class="text-nowrap">' + (partes.join('') || precioArticulo) + ' €</span>';
    }

    return '<span class="text-nowrap">' + precioArticulo + ' €</span>';
  }

  function configurarFiltrosFecha() {
    window.filtro_periodo_activo_reportes_ventas = 'todos';

    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    const btnFecha = document.getElementById('filtro_por_fecha_venta_reportes');
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
        window.filtro_periodo_activo_reportes_ventas = 'fecha';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        recargarTabla();
      });
    }

    const btnDia = document.getElementById('filtro_dia_reportes');
    if (btnDia) {
      btnDia.addEventListener('click', function () {
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo_reportes_ventas = 'dia';
        recargarTabla();
      });
    }

    const btnMes = document.getElementById('filtro_mes_reportes');
    if (btnMes) {
      btnMes.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        window.filtro_periodo_activo_reportes_ventas = 'mes';
        recargarTabla();
      });
    }

    const btnTodos = document.getElementById('filtro_todos_reportes');
    if (btnTodos) {
      btnTodos.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        window.filtro_periodo_activo_reportes_ventas = 'todos';
        recargarTabla();
      });
    }
  }

  function actualizarTituloReportesVentas() {
    const textoTitulo = document.getElementById('texto_reportes_ventas_titulo');
    if (!textoTitulo) {
      return;
    }

    const partes = [];
    const filtroSucursal = document.getElementById('filtro_sucursal');
    if (filtroSucursal && filtroSucursal.value) {
      partes.push('de ' + filtroSucursal.options[filtroSucursal.selectedIndex].text);
    }

    const filtroActivo = window.filtro_periodo_activo_reportes_ventas || 'todos';
    const fechaDesde = document.getElementById('filtro_fecha_desde')?.value || '';
    const fechaHasta = document.getElementById('filtro_fecha_hasta')?.value || '';

    if (filtroActivo === 'dia') {
      partes.push('de hoy');
    } else if (filtroActivo === 'mes') {
      partes.push('de este mes');
    } else if (filtroActivo === 'fecha') {
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
    window.titulo_filtros_reportes_ventas = textoTitulo.textContent;
  }

  if (dt_table) {
    dt_reportes_ventas = new DataTable(dt_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/reportes_ventas/listar/load_list.php',
        type: 'POST',
        data: function (d) {
          d.filtro_sucursal = document.getElementById('filtro_sucursal')?.value || '';
          d.filtro_tipo = document.getElementById('filtro_tipo')?.value || '';
          d.filtro_plazos = document.getElementById('filtro_plazos')?.value || '';
          d.filtro_plazos_pendientes = document.getElementById('filtro_plazos_pendientes')?.value || '';
          d.filtro_tipo_pago = document.getElementById('filtro_tipo_pago')?.value || '';
          d.filtro_fecha_desde = document.getElementById('filtro_fecha_desde')?.value || '';
          d.filtro_fecha_hasta = document.getElementById('filtro_fecha_hasta')?.value || '';

          let periodo = window.filtro_periodo_activo_reportes_ventas || 'todos';
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
          console.error('Error en DataTable reportes ventas:', xhr.responseText);
          let mensaje = 'No se pudieron cargar los datos del reporte.';
          try {
            const json = JSON.parse(xhr.responseText);
            if (json && json.error) {
              mensaje = json.error;
            }
          } catch (e) {}
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: mensaje });
          }
          if (xhr.status === 401) {
            window.location.href = 'login.php';
          }
        }
      },
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6 },
        { data: 7 },
        { data: 8 },
        { data: 9 },
        { data: 10 },
        { data: 11 },
        { data: 12 },
        { data: 13 },
        { data: 14 },
        { data: 15, visible: false, searchable: false },
        { data: 16, visible: false, searchable: false },
        { data: 17, visible: false, searchable: false },
        { data: 18, visible: false, searchable: false },
        { data: 19, visible: false, searchable: false },
        { data: 20, visible: false, searchable: false },
        { data: 21, visible: false, searchable: false },
        { data: 22, visible: false, searchable: false }
      ],
      columnDefs: [
        { targets: 0, className: 'text-center', render: renderSku },
        { targets: 1, render: renderDescripcion },
        {
          targets: 2,
          render: function (data) {
            return '<span class="fw-medium text-heading">' + (data || 'Sin sucursal') + '</span>';
          }
        },
        {
          targets: 3,
          render: function (data, type) {
            if (type === 'display') {
              return '<span class="text-nowrap">' + formatearFechaDisplay(data) + '</span>';
            }
            return data;
          }
        },
        { targets: 4, render: renderVenta },
        { targets: 5, render: renderFactura },
        {
          targets: [6, 7],
          className: 'text-end',
          render: function (data) {
            return '<span class="text-nowrap">' + (data ?? '0') + ' €</span>';
          }
        },
        {
          targets: 8,
          className: 'text-end',
          render: function (data) {
            return '<span class="text-nowrap">' + (data ?? '0') + ' grs</span>';
          }
        },
        { targets: 9, className: 'text-center', render: renderWeb },
        { targets: 10, className: 'text-center', render: renderTipo },
        { targets: 11, className: 'text-center', render: renderPlazos },
        { targets: 12, className: 'text-center', render: renderPlazosPdtes },
        { targets: 13, className: 'text-center', render: renderTipoPago },
        { targets: 14, className: 'text-center', render: renderPagos },
        {
          targets: [15, 16, 17, 18, 19, 20, 21, 22],
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
          features: [
            {
              buttons: [
                {
                  extend: 'collection',
                  className: 'btn btn-outline-secondary dropdown-toggle waves-effect',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                  buttons: [
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
                        doc.defaultStyle.fontSize = 6;
                        doc.styles.tableHeader.fontSize = 6;
                        doc.content[0].text = 'Reportes Ventas';
                        doc.content[0].alignment = 'center';
                        doc.content[0].fontSize = 12;
                      }
                    },
                    { extend: 'copy', text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar', className: 'dropdown-item', exportOptions: { columns: ':visible' } }
                  ]
                }
              ]
            }
          ]
        },
        topEnd: {
          features: [{ search: { placeholder: 'Buscar...', text: '_INPUT_' } }]
        },
        bottomStart: {
          rowClass: 'row mx-3 justify-content-between',
          features: ['info']
        },
        bottomEnd: 'paging'
      },
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              return 'Artículo SKU ' + (row.data()[0] || '-');
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            const html = columns
              .map(function (col) {
                return col.hidden
                  ? '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                      '<td>' + col.title + ':</td><td>' + col.data + '</td></tr>'
                  : '';
              })
              .join('');
            return html ? $('<table class="table"/><tbody />').append(html) : false;
          }
        }
      }
    });

    window.dt_reportes_ventas = dt_reportes_ventas;
    window.actualizarTituloFiltros = actualizarTituloReportesVentas;
    configurarFiltrosFecha();
    actualizarTituloReportesVentas();
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
