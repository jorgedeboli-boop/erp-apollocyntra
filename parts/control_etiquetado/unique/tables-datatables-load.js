/**
 * Control de etiquetado — listado
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dtTable = document.querySelector('.datatables-control-etiquetado');
  let dtControlEtiquetado;

  if (window.ListarFiltros) {
    window.ListarFiltros.setOnChange(function () {
      if (dtControlEtiquetado) {
        dtControlEtiquetado.ajax.reload();
      }
      actualizarTituloControlEtiquetado();
    });
  }

  function actualizarTituloControlEtiquetado() {
    const textoFiltros = document.getElementById('texto_control_etiquetado_filtros_titulo');
    if (!textoFiltros) return;

    const partes = [];
    const filtroSucursal = document.getElementById('filtro_sucursal_control_etiquetado');
    const periodo = window.filtro_periodo_activo_control_etiquetado || '';
    const fechaDesde = document.getElementById('filtro_fecha_desde')?.value || '';
    const fechaHasta = document.getElementById('filtro_fecha_hasta')?.value || '';

    if (filtroSucursal && filtroSucursal.value) {
      partes.push('de ' + filtroSucursal.options[filtroSucursal.selectedIndex].text);
    }

    const filtroTipo = document.getElementById('filtro_tipo_control_etiquetado');
    if (filtroTipo && filtroTipo.value) {
      partes.push('tipo ' + filtroTipo.options[filtroTipo.selectedIndex].text);
    }

    if (periodo === 'dia') {
      partes.push('de hoy');
    } else if (periodo === 'mes') {
      partes.push('de este mes');
    } else if (periodo === 'fecha') {
      if (fechaDesde && fechaHasta) {
        const fd = new Date(fechaDesde + 'T00:00:00');
        const fh = new Date(fechaHasta + 'T00:00:00');
        if (fechaDesde === fechaHasta) {
          partes.push('del ' + fd.toLocaleDateString('es-ES'));
        } else {
          partes.push('entre el ' + fd.toLocaleDateString('es-ES') + ' y el ' + fh.toLocaleDateString('es-ES'));
        }
      }
    }

    textoFiltros.textContent = partes.length ? ' — ' + partes.join(', ') : '';
  }



  if (dtTable) {
    dtControlEtiquetado = new DataTable(dtTable, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/control_etiquetado/unique/load_list.php',
        type: 'POST',
        data: function (d) {
          const sucursalFilter = document.getElementById('filtro_sucursal_control_etiquetado');
          const tipoFilter = document.getElementById('filtro_tipo_control_etiquetado');
          const fechaDesde = document.getElementById('filtro_fecha_desde');
          const fechaHasta = document.getElementById('filtro_fecha_hasta');

          d.filtro_sucursal = sucursalFilter ? sucursalFilter.value : '';
          d.filtro_tipo = tipoFilter ? tipoFilter.value : '';
          d.filtro_fecha_desde = fechaDesde ? fechaDesde.value : '';
          d.filtro_fecha_hasta = fechaHasta ? fechaHasta.value : '';
          d.filtro_periodo = window.filtro_periodo_activo_control_etiquetado || '';

          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
        error: function (xhr, error, thrown) {
          console.error('Error AJAX control etiquetado:', error, thrown);
          console.log('Respuesta del servidor:', xhr.responseText);
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
        { data: 8, visible: false }
      ],
      columnDefs: [
        {
          targets: 7,
          orderable: false
        },
        {
          targets: 0,
          responsivePriority: 1
        }
      ],
      order: [[8, 'desc']],
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
                  className: 'btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect button-exportar',
                  text: '<span class="d-flex align-items-center justify-content-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px"></i> <span>Exportar</span></span>',
                  buttons: [
                    {
                      extend: 'excel',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
                      className: 'dropdown-item',
                      title: 'Control de etiquetado',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
                        format: {
                          body: function (data) {
                            if (typeof data === 'string') {
                              const temp = document.createElement('div');
                              temp.innerHTML = data;
                              return temp.textContent || temp.innerText || data;
                            }
                            return data;
                          }
                        }
                      }
                    },
                    {
                      extend: 'pdf',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                      className: 'dropdown-item',
                      orientation: 'landscape',
                      title: 'Control de etiquetado',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
                        format: {
                          body: function (data) {
                            if (typeof data === 'string') {
                              const temp = document.createElement('div');
                              temp.innerHTML = data;
                              return temp.textContent || temp.innerText || data;
                            }
                            return data;
                          }
                        }
                      }
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
          rowClass: 'row mx-3 justify-content-between',
          features: ['info']
        },
        bottomEnd: 'paging'
      },
      responsive: { details: false }
    });

    window.dt_control_etiquetado = dtControlEtiquetado;

    dtControlEtiquetado.on('click', 'tbody tr', function (e) {
      if (e.target.closest('a, button, .btn, .dropdown-menu, .dt-buttons')) {
        return;
      }
      const rowData = dtControlEtiquetado.row(this).data();
      if (!rowData || rowData[8] == null || rowData[8] === '') {
        return;
      }
      window.location.href = 'etiquetas_list_control.php?id=' + encodeURIComponent(String(rowData[8]));
    });


    configurarFiltrosFechaControlEtiquetado();
    actualizarTituloControlEtiquetado();
  }

  function configurarFiltrosFechaControlEtiquetado() {
    window.filtro_periodo_activo_control_etiquetado = '';
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    const recargar = function () {
      if (dtControlEtiquetado) dtControlEtiquetado.ajax.reload();
      actualizarTituloControlEtiquetado();
    };

    const btnFecha = document.getElementById('filtro_por_fecha_control_etiquetado');
    if (btnFecha) {
      btnFecha.addEventListener('click', function () {
        if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
          Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe seleccionar al menos una fecha',
            confirmButtonText: 'Aceptar'
          });
          return;
        }
        window.filtro_periodo_activo_control_etiquetado = 'fecha';
        if (rangeFechas) rangeFechas.value = '';
        recargar();
      });
    }

    const btnDia = document.getElementById('filtro_dia_control_etiquetado');
    if (btnDia) {
      btnDia.addEventListener('click', function () {
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo_control_etiquetado = 'dia';
        recargar();
      });
    }

    const btnMes = document.getElementById('filtro_mes_control_etiquetado');
    if (btnMes) {
      btnMes.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo_control_etiquetado = 'mes';
        recargar();
      });
    }

    const btnTodos = document.getElementById('filtro_todos_control_etiquetado');
    if (btnTodos) {
      btnTodos.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo_control_etiquetado = '';
        recargar();
      });
    }
  }
});
