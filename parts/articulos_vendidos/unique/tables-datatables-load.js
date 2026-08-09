/**
 * Page Articulos Vendidos List
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dtTable = document.querySelector('.datatables-articulos-vendidos');
  let dtVendidos;

  const createFilterSucursal = (containerClass, selectId) => {
    const select = document.createElement('select');
    select.id = selectId;
    select.className = 'form-select select2-filter text-capitalize select2-custom';
    select.innerHTML = `<option value="">Sucursales</option>`;
    document.querySelector(containerClass).appendChild(select);

    fetch('parts/clientes/listar/get_sucursales.php')
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;
        data.sucursales.forEach(sucursal => {
          const option = document.createElement('option');
          option.value = sucursal.id_sucursal;
          option.textContent = sucursal.nombre_sucursal;
          select.appendChild(option);
        });

        const select2 = $(select);
        if (select2.length) {
          select2.each(function () {
            const $this = $(this);
            $this.select2({ dropdownParent: $this.parent() });
            $this.on('select2:select select2:unselect', function () {
              dtVendidos.ajax.reload();
              actualizarTituloVendidos();
            });
          });
        }

        select.addEventListener('change', function () {
          dtVendidos.ajax.reload();
          actualizarTituloVendidos();
        });
      })
      .catch(err => console.error('Error al cargar sucursales:', err));

    return select;
  };

  // Helper para filtros fijos (tipo oro/plata)
  const createFilterFijo = (containerClass, selectId, defaultOptionText, opciones) => {
    const select = document.createElement('select');
    select.id = selectId;
    select.className = 'form-select select2-filter text-capitalize select2-custom';
    select.innerHTML = `<option value="">${defaultOptionText}</option>`;
    document.querySelector(containerClass).appendChild(select);

    opciones.forEach(opcion => {
      const option = document.createElement('option');
      option.value = opcion.value;
      option.textContent = opcion.label;
      select.appendChild(option);
    });

    const select2 = $(select);
    if (select2.length) {
      select2.each(function () {
        const $this = $(this);
        $this.select2({ dropdownParent: $this.parent() });
        $this.on('select2:select select2:unselect', function () {
          dtVendidos.ajax.reload();
          actualizarTituloVendidos();
        });
      });
    }

    select.addEventListener('change', function () {
      dtVendidos.ajax.reload();
      actualizarTituloVendidos();
    });

    return select;
  };

  if (dtTable) {
    dtVendidos = new DataTable(dtTable, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/articulos_vendidos/unique/load_list.php',
        type: 'POST',
        data: function (d) {
          const sucursalFilter = document.getElementById('filtro_sucursal_articulo_vendido');
          const tipoFilter = document.getElementById('filtro_tipo_vendidos');
          const fechaDesde = document.getElementById('filtro_fecha_desde');
          const fechaHasta = document.getElementById('filtro_fecha_hasta');

          d.filtro_sucursal = sucursalFilter ? sucursalFilter.value : '';
          d.filtro_tipo = tipoFilter ? tipoFilter.value : '';
          d.filtro_fecha_desde = fechaDesde ? fechaDesde.value : '';
          d.filtro_fecha_hasta = fechaHasta ? fechaHasta.value : '';
          d.filtro_periodo = window.filtro_periodo_activo_vendidos || 'todos';

          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
        error: function (xhr, error, thrown) {
          console.error('Error AJAX:', error, thrown);
          console.log('Respuesta del servidor:', xhr.responseText);
        }
      },
      columns: [
        { data: 0 }, // SKU
        { data: 1 }, // Descripción
        { data: 2 }, // Sucursal
        { data: 3 }, // Fecha de venta
        { data: 4 }, // Venta Nº
        { data: 5 }, // Precio
        { data: 6 }, // Coste
        { data: 7 }, // Peso
        { data: 8 }, // Tipo
        { data: 9 }  // Web
      ],
      columnDefs: [
        {
          targets: 0,
          responsivePriority: 1,
          render: function (data) {
            return '<span class="fw-semibold">' + data + '</span>';
          }
        }
      ],
      order: [[0, 'desc']],
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
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                      className: 'dropdown-item',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatosVendidos('excel', dt, button, config);
                      }
                    },
                    {
                      extend: 'pdf',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                      className: 'dropdown-item',
                      orientation: 'landscape',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatosVendidos('pdf', dt, button, config);
                      }
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatosVendidos('copy', dt, button, config);
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

    createFilterSucursal('.articulo_vendido_sucursal', 'filtro_sucursal_articulo_vendido');
    createFilterFijo('.articulo_vendido_tipo', 'filtro_tipo_vendidos', 'Tipo', [
      { value: 'oro', label: 'Oro' },
      { value: 'plata', label: 'Plata' }
    ]);
    configurarFiltrosFechaVendidos();
    // Exponer para el script universal flatpickr (recargarDataTable)
    window.dt_articulos_vendidos = dtVendidos;
  }

  function configurarFiltrosFechaVendidos() {
    window.filtro_periodo_activo_vendidos = 'todos';
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    const btnFecha = document.getElementById('filtro_por_fecha_venta_vendidos');
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
        window.filtro_periodo_activo_vendidos = 'fecha';
        if (rangeFechas) rangeFechas.value = '';
        dtVendidos.ajax.reload();
        actualizarTituloVendidos();
      });
    }

    const btnDia = document.getElementById('filtro_dia_vendidos');
    if (btnDia) {
      btnDia.addEventListener('click', function () {
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo_vendidos = 'dia';
        dtVendidos.ajax.reload();
        actualizarTituloVendidos();
      });
    }

    const btnMes = document.getElementById('filtro_mes_vendidos');
    if (btnMes) {
      btnMes.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo_vendidos = 'mes';
        dtVendidos.ajax.reload();
        actualizarTituloVendidos();
      });
    }

    const btnTodos = document.getElementById('filtro_todos_vendidos');
    if (btnTodos) {
      btnTodos.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo_vendidos = 'todos';
        dtVendidos.ajax.reload();
        actualizarTituloVendidos();
      });
    }

    actualizarTituloVendidos();
  }

  function actualizarTituloVendidos() {
    const textoTitulo = document.getElementById('texto_articulos_vendidos_titulo');
    if (!textoTitulo) return;

    let partes = [];
    const filtroSucursal = document.getElementById('filtro_sucursal_articulo_vendido');
    let nombreSucursal = '';
    if (filtroSucursal && filtroSucursal.value) {
      nombreSucursal = 'de ' + filtroSucursal.options[filtroSucursal.selectedIndex].text;
    }

    const filtroActivo = window.filtro_periodo_activo_vendidos || 'todos';
    const fechaDesde = document.getElementById('filtro_fecha_desde')?.value || '';
    const fechaHasta = document.getElementById('filtro_fecha_hasta')?.value || '';

    if (filtroActivo === 'dia') {
      partes.push('de hoy');
    } else if (filtroActivo === 'mes') {
      partes.push('de este mes');
    } else if (filtroActivo === 'fecha') {
      if (fechaDesde && fechaHasta) {
        if (fechaDesde === fechaHasta) {
          const f = new Date(fechaDesde + 'T00:00:00');
          partes.push('del ' + f.toLocaleDateString('es-ES'));
        } else {
          const fd = new Date(fechaDesde + 'T00:00:00');
          const fh = new Date(fechaHasta + 'T00:00:00');
          partes.push('entre el ' + fd.toLocaleDateString('es-ES') + ' y el ' + fh.toLocaleDateString('es-ES'));
        }
      } else if (fechaDesde) {
        const fd = new Date(fechaDesde + 'T00:00:00');
        partes.push('desde el ' + fd.toLocaleDateString('es-ES'));
      } else if (fechaHasta) {
        const fh = new Date(fechaHasta + 'T00:00:00');
        partes.push('hasta el ' + fh.toLocaleDateString('es-ES'));
      }
    }

    let textoFinal = nombreSucursal;
    if (partes.length) {
      textoFinal = (textoFinal ? textoFinal + ' ' : '') + partes.join(' - ');
    }
    textoTitulo.textContent = textoFinal;
    window.titulo_filtros_articulos_vendidos = textoFinal;
  }

  window.exportarTodosLosDatosVendidos = function (tipo, dt, button, config) {
    const searchValue = dt.search();
    const filtroSucursal = document.getElementById('filtro_sucursal_articulo_vendido');
    const filtroTipo = document.getElementById('filtro_tipo_vendidos');
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');

    Swal.fire({
      title: 'Generando exportación...',
      text: 'Obteniendo todos los registros',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    const formData = new FormData();
    formData.append('search', searchValue);
    formData.append('filtro_sucursal', filtroSucursal ? filtroSucursal.value : '');
    formData.append('filtro_tipo', filtroTipo ? filtroTipo.value : '');
    formData.append('filtro_fecha_desde', filtroFechaDesde ? filtroFechaDesde.value : '');
    formData.append('filtro_fecha_hasta', filtroFechaHasta ? filtroFechaHasta.value : '');
    formData.append('filtro_periodo', window.filtro_periodo_activo_vendidos || 'todos');

    fetch('parts/articulos_vendidos/unique/export_all.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(responseData => {
        Swal.close();
        if (!responseData.success) throw new Error(responseData.error || 'Error al obtener datos');
        if (!responseData.data || responseData.data.length === 0) {
          Swal.fire({ title: 'Sin datos', text: 'No hay datos para exportar con los filtros aplicados', icon: 'info', confirmButtonText: 'Aceptar' });
          return;
        }

        const tempTableId = 'temp-export-table-vendidos-' + Date.now();
        const tempDiv = document.createElement('div');
        tempDiv.style.display = 'none';
        tempDiv.innerHTML =
          '<table id="' + tempTableId + '"><thead><tr>' +
          '<th>SKU</th><th>Descripción</th><th>Sucursal</th><th>Fecha de venta</th><th>Venta Nº</th><th>Precio</th><th>Coste</th><th>Peso</th><th>Tipo</th><th>Web</th>' +
          '</tr></thead></table>';
        document.body.appendChild(tempDiv);

        const tempTable = $('#' + tempTableId).DataTable({
          data: responseData.data,
          columns: [{ data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 }],
          paging: false,
          searching: false,
          ordering: false,
          dom: 't',
          buttons: []
        });

        let exportConfig = {
          exportOptions: {
            columns: ':visible',
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
        };

        if (tipo === 'pdf') {
          exportConfig.customize = function (doc) {
            doc.pageOrientation = 'landscape';
            doc.pageSize = 'A4';
            doc.defaultStyle.fontSize = 8;
            doc.styles.tableHeader.fontSize = 9;
            doc.styles.tableHeader.fillColor = '#2d4154';
            doc.styles.tableHeader.bold = true;
            doc.styles.tableHeader.color = 'white';

            const filtroSucursal = document.getElementById('filtro_sucursal_articulo_vendido');
            const nombreSucursal = (filtroSucursal && filtroSucursal.value) ? filtroSucursal.options[filtroSucursal.selectedIndex].text : 'todas las sucursales';
            let tituloPDF = 'Artículos vendidos de ' + nombreSucursal;
            if (window.titulo_filtros_articulos_vendidos) tituloPDF += ' - ' + window.titulo_filtros_articulos_vendidos;
            doc.content[0].text = tituloPDF;
            doc.content[0].alignment = 'center';
            doc.content[0].fontSize = 13;
            doc.content[0].margin = [0, 0, 0, 10];
            doc.pageMargins = [10, 10, 10, 10];
            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
          };
        }

        const buttonType = tipo === 'excel' ? 'excelHtml5' : tipo;
        const tempButton = tempTable.button().add(0, { extend: buttonType, ...exportConfig });
        tempButton.trigger();

        setTimeout(() => {
          tempTable.destroy();
          tempDiv.remove();
        }, 2000);
      })
      .catch(error => {
        Swal.close();
        Swal.fire({ title: 'Error', text: 'Ha ocurrido un error al exportar: ' + error.message, icon: 'error', confirmButtonText: 'Aceptar' });
      });
  };
});

