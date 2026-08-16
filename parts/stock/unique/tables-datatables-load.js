/**
 * Page Stock Unique
 */

'use strict';

const COLUMNAS_EXPORTABLES_STOCK = [
  { index: 0, label: 'SKU' },
  { index: 1, label: 'Descripción' },
  { index: 2, label: 'Peso' },
  { index: 3, label: 'Precio' },
  { index: 4, label: 'Precio Coste' },
  { index: 5, label: '€/g' },
  { index: 6, label: 'Tipo' },
  { index: 7, label: 'F. Enviado' },
  { index: 8, label: 'F. En Venta' },
  { index: 9, label: 'Creado Por' },
  { index: 10, label: 'Origen' }
];

const SELECTORES_BOTONES_FECHA_STOCK = '#filtro_por_fecha_en_venta, #filtro_dia, #filtro_mes, #filtro_todos';

function obtenerTextoTipoFechaStock() {
  return 'en venta';
}

function activarBotonFiltroFechaStock(activeId) {
  document.querySelectorAll(SELECTORES_BOTONES_FECHA_STOCK).forEach(function (btn) {
    btn.classList.remove('active');
  });
  const activeBtn = document.getElementById(activeId);
  if (activeBtn) {
    activeBtn.classList.add('active');
  }
}

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_stock_table = document.querySelector('.datatables-stock');

  // Variable global para DataTable
  let dt_stock;
  window.dt_stock = null;

  function reloadPorFiltrosStock() {
    if (dt_stock) {
      dt_stock.ajax.reload();
    }
    if (typeof window.recargarEstadisticasStock === 'function') {
      window.recargarEstadisticasStock();
    }
    if (typeof actualizarTituloStock === 'function') {
      actualizarTituloStock();
    }
  }

  if (window.ArticulosFiltros) {
    window.ArticulosFiltros.setOnChange(reloadPorFiltrosStock);
  }



  // Articulos datatable
  if (dt_stock_table) {
    dt_stock = window.dt_stock = new DataTable(dt_stock_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      
      language: DATATABLES_SPANISH,
      
      ajax: {
        url: 'parts/stock/unique/load_list.php',
        type: 'POST',
        data: function(d) {
          const tipoFilter = document.getElementById('filtro_tipo');
          const origenFilter = document.getElementById('filtro_origen');
          const fechaDesdeFilter = document.getElementById('filtro_fecha_desde');
          const fechaHastaFilter = document.getElementById('filtro_fecha_hasta');
          
          d.filtro_tipo = tipoFilter ? tipoFilter.value : '';
          d.filtro_origen = origenFilter ? origenFilter.value : '';
          d.filtro_fecha_desde = fechaDesdeFilter ? fechaDesdeFilter.value : '';
          d.filtro_fecha_hasta = fechaHastaFilter ? fechaHastaFilter.value : '';
          d.filtro_periodo = window.filtro_periodo_activo || 'todos';
          d.filtro_tipo_fecha = 'en_venta';
          
          return d;
        },
        dataSrc: function(json) {
          return json.data || [];
        },
        error: function(xhr, error, thrown) {
          console.error('Error AJAX:', error, thrown);
          console.log('Respuesta del servidor:', xhr.responseText);
        }
      },
      
      columns: [
        { data: 0 },  // SKU
        { data: 1 },  // Descripción
        { data: 2 },  // Peso
        { data: 3 },  // Precio
        { data: 4 },  // Precio Coste
        { data: 5 },  // € gramo
        { data: 6 },  // Tipo
        { data: 7 },  // F. Enviado
        { data: 8 },  // F. En Venta
        { data: 9 },  // Creado Por
        { data: 10 }, // Origen
        { data: 11, visible: false } // ID (hidden para click)
      ],
      
      columnDefs: [
        {
          // SKU
          targets: 0,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            return '<span class="fw-semibold">' + data + '</span>';
          }
        }
      ],
      
      order: [[0, 'desc']], // Ordenar por ID descendente
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
                      action: function (e, dt) {
                        exportarTodosLosDatos('excel', dt);
                      },
                      exportOptions: {
                        columns: ':visible',
                        format: {
                          body: function (data, row, column, node) {
                            if (typeof data === 'string') {
                              const tempDiv = document.createElement('div');
                              tempDiv.innerHTML = data;
                              return tempDiv.textContent || tempDiv.innerText || data;
                            }
                            return data;
                          }
                        }
                      }
                    },
                    {
                      extend: 'pdf',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                      className: 'dropdown-item',
                      orientation: 'landscape',
                      action: function (e, dt) {
                        exportarTodosLosDatos('pdf', dt);
                      },
                      exportOptions: {
                        columns: ':visible',
                        format: {
                          body: function (data, row, column, node) {
                            if (typeof data === 'string') {
                              const tempDiv = document.createElement('div');
                              tempDiv.innerHTML = data;
                              return tempDiv.textContent || tempDiv.innerText || data;
                            }
                            return data;
                          }
                        }
                      },
                      customize: function(doc) {
                        doc.pageOrientation = 'landscape';
                        doc.pageSize = 'LEGAL';
                        doc.defaultStyle.fontSize = 7;
                        doc.styles.tableHeader.fontSize = 8;
                        doc.styles.tableHeader.fillColor = '#2d4154';
                        doc.styles.tableHeader.bold = true;
                        doc.styles.tableHeader.color = 'white';
                        
                        let tituloPDF = 'Listado de Stock';
                        
                        if (window.titulo_filtros_stock) {
                          tituloPDF += ' - ' + window.titulo_filtros_stock;
                        }
                        
                        doc.content[0].text = tituloPDF;
                        doc.content[0].alignment = 'center';
                        doc.content[0].fontSize = 14;
                        doc.content[0].margin = [0, 0, 0, 10];
                        
                        doc.pageMargins = [5, 5, 5, 5];
                        
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
                      }
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      action: function (e, dt) {
                        exportarTodosLosDatos('copy', dt);
                      },
                      exportOptions: {
                        columns: ':visible',
                        format: {
                          body: function (data, row, column, node) {
                            if (typeof data === 'string') {
                              const tempDiv = document.createElement('div');
                              tempDiv.innerHTML = data;
                              return tempDiv.textContent || tempDiv.innerText || data;
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
      
      responsive: {
        details: false
      },
      
      // Event listener para hacer clickable toda la fila
      createdRow: function(row, data, dataIndex) {
        // Hacer la fila clickable (excepto si se hace click en un botón)
        $(row).css('cursor', 'pointer');
        $(row).on('click', function(e) {
          // No redirigir si se hace click en un botón o enlace
          if ($(e.target).closest('button, a, .select2').length > 0) {
            return;
          }
          // Obtener el ID del artículo (columna oculta)
          const idArticulo = data[11];
          if (idArticulo) {
            window.location.href = 'articulo.php?id=' + idArticulo;
          }
        });
      }
    });

    // Configurar filtros de fecha
    configurarFiltrosFecha();
  }


  // Función para configurar los filtros de fecha
  function configurarFiltrosFecha() {
    window.filtro_periodo_activo = 'todos';
    window.filtro_tipo_fecha = 'en_venta';

    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    function aplicarFiltroPorRango(tipoFecha, botonId) {
      if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
        Swal.fire({
          icon: 'warning',
          title: 'Atención',
          text: 'Debe seleccionar al menos una fecha',
          confirmButtonText: 'Aceptar'
        });
        return;
      }

      window.filtro_periodo_activo = 'fecha';
      window.filtro_tipo_fecha = tipoFecha;
      if (rangeFechas) {
        rangeFechas.value = '';
      }

      activarBotonFiltroFechaStock(botonId);
      dt_stock.ajax.reload();
      window.recargarEstadisticasStock();
      actualizarTituloStock();
    }

    const filtroPorFechaEnVenta = document.getElementById('filtro_por_fecha_en_venta');
    if (filtroPorFechaEnVenta) {
      filtroPorFechaEnVenta.addEventListener('click', function () {
        aplicarFiltroPorRango('en_venta', 'filtro_por_fecha_en_venta');
      });
    }

    const filtroDia = document.getElementById('filtro_dia');
    if (filtroDia) {
      filtroDia.addEventListener('click', function () {
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo = 'dia';

        activarBotonFiltroFechaStock('filtro_dia');
        dt_stock.ajax.reload();
        window.recargarEstadisticasStock();
        actualizarTituloStock();
      });
    }

    const filtroMes = document.getElementById('filtro_mes');
    if (filtroMes) {
      filtroMes.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        window.filtro_periodo_activo = 'mes';

        activarBotonFiltroFechaStock('filtro_mes');
        dt_stock.ajax.reload();
        window.recargarEstadisticasStock();
        actualizarTituloStock();
      });
    }

    const filtroTodos = document.getElementById('filtro_todos');
    if (filtroTodos) {
      filtroTodos.addEventListener('click', function () {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) {
          rangeFechas.value = '';
        }
        window.filtro_periodo_activo = 'todos';

        activarBotonFiltroFechaStock('filtro_todos');
        dt_stock.ajax.reload();
        window.recargarEstadisticasStock();
        actualizarTituloStock();
      });
    }

    actualizarTituloStock();
  }
  
  // Función para actualizar el título combinando todos los filtros
  function actualizarTituloStock() {
    const textoTitulo = document.getElementById('texto_articulos_titulo');
    if (!textoTitulo) return;
    
    let partes = [];
    
    // 1. Agregar filtro de fechas si existe
    const filtroActivo = window.filtro_periodo_activo || 'todos';
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    
    if (filtroActivo === 'dia') {
      partes.push('de hoy (fecha ' + obtenerTextoTipoFechaStock() + ')');
    } else if (filtroActivo === 'mes') {
      partes.push('de este mes (fecha ' + obtenerTextoTipoFechaStock() + ')');
    } else if (filtroActivo === 'fecha') {
      const fechaDesde = filtroFechaDesde ? filtroFechaDesde.value : '';
      const fechaHasta = filtroFechaHasta ? filtroFechaHasta.value : '';
      const prefijoFecha = 'fecha ' + obtenerTextoTipoFechaStock() + ' ';

      if (fechaDesde && fechaHasta) {
        if (fechaDesde === fechaHasta) {
          const fecha = new Date(fechaDesde + 'T00:00:00');
          partes.push(prefijoFecha + 'del ' + fecha.toLocaleDateString('es-ES'));
        } else {
          const fechaD = new Date(fechaDesde + 'T00:00:00');
          const fechaH = new Date(fechaHasta + 'T00:00:00');
          partes.push(prefijoFecha + 'entre el ' + fechaD.toLocaleDateString('es-ES') + ' y el ' + fechaH.toLocaleDateString('es-ES'));
        }
      } else if (fechaDesde) {
        const fechaD = new Date(fechaDesde + 'T00:00:00');
        partes.push(prefijoFecha + 'desde el ' + fechaD.toLocaleDateString('es-ES'));
      } else if (fechaHasta) {
        const fechaH = new Date(fechaHasta + 'T00:00:00');
        partes.push(prefijoFecha + 'hasta el ' + fechaH.toLocaleDateString('es-ES'));
      }
    }
    
    // 2. Agregar tipo si está seleccionado
    const filtroTipo = document.getElementById('filtro_tipo');
    if (filtroTipo && filtroTipo.value && filtroTipo.value !== '') {
      let textoTipo = filtroTipo.options[filtroTipo.selectedIndex].text;
      partes.push(textoTipo);
    }
    
    // 3. Agregar origen si está seleccionado
    const filtroOrigen = document.getElementById('filtro_origen');
    if (filtroOrigen && filtroOrigen.value && filtroOrigen.value !== '') {
      let textoOrigen = filtroOrigen.options[filtroOrigen.selectedIndex].text;
      partes.push('origen ' + textoOrigen);
    }
    
    let textoFinal = '';
    if (partes.length > 0) {
      textoFinal = partes.join(' - ');
    }
    
    textoTitulo.textContent = textoFinal;
    
    // Guardar en variable global para usar en el PDF
    window.titulo_filtros_stock = textoFinal;
  }

  // Filter form control to default size
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
      { selector: '.dt-layout-full .table', classToAdd: 'table-responsive' }
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
  
  // Función global para recargar estadísticas con filtros
  window.recargarEstadisticasStock = function() {
    if (typeof cargarEstadisticas === 'function') {
      cargarEstadisticas();
    }
  };

  /**
   * Función para exportar TODOS los datos (con filtros aplicados)
   */
  window.exportarTodosLosDatos = function (tipo, dt) {
    pedirColumnasExportacion({
      columnas: COLUMNAS_EXPORTABLES_STOCK,
      idPrefix: 'stock'
    })
      .then(function (columnasSeleccionadas) {
        ejecutarExportacionStock(tipo, dt, columnasSeleccionadas);
      })
      .catch(function () {
        // Usuario canceló la selección de columnas
      });
  };

  function ejecutarExportacionStock(tipo, dt, columnasSeleccionadas) {
    const searchValue = dt.search();
    const filtroTipo = document.getElementById('filtro_tipo');
    const filtroOrigen = document.getElementById('filtro_origen');
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');

    Swal.fire({
      title: 'Generando exportación...',
      text: 'Obteniendo todos los registros',
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      }
    });

    const formData = new FormData();
    formData.append('search', searchValue);
    formData.append('filtro_tipo', filtroTipo ? filtroTipo.value : '');
    formData.append('filtro_origen', filtroOrigen ? filtroOrigen.value : '');
    formData.append('filtro_fecha_desde', filtroFechaDesde ? filtroFechaDesde.value : '');
    formData.append('filtro_fecha_hasta', filtroFechaHasta ? filtroFechaHasta.value : '');
    formData.append('filtro_periodo', window.filtro_periodo_activo || 'todos');
    formData.append('filtro_tipo_fecha', 'en_venta');

    fetch('parts/stock/unique/export_all.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (responseData) {
        Swal.close();

        if (!responseData.success) {
          throw new Error(responseData.error || 'Error al obtener datos');
        }

        if (!responseData.data || responseData.data.length === 0) {
          Swal.fire({
            title: 'Sin datos',
            text: 'No hay datos para exportar con los filtros aplicados',
            icon: 'info',
            confirmButtonText: 'Aceptar'
          });
          return;
        }

        const headersHtml = COLUMNAS_EXPORTABLES_STOCK
          .map(function (col) {
            return '<th>' + col.label + '</th>';
          })
          .join('');

        const columnsConfig = COLUMNAS_EXPORTABLES_STOCK.map(function (col) {
          return { data: col.index };
        });

        const tempTableId = 'temp-export-table-' + Date.now();
        const tempDiv = document.createElement('div');
        tempDiv.style.display = 'none';
        tempDiv.innerHTML = '<table id="' + tempTableId + '"><thead><tr>' + headersHtml + '</tr></thead></table>';
        document.body.appendChild(tempDiv);

        const tempTable = $('#' + tempTableId).DataTable({
          data: responseData.data,
          columns: columnsConfig,
          paging: false,
          searching: false,
          ordering: false,
          dom: 't',
          buttons: []
        });

        const exportConfig = {
          exportOptions: {
            columns: columnasSeleccionadas
          }
        };

        if (tipo === 'pdf') {
          exportConfig.customize = function (doc) {
            doc.pageOrientation = 'landscape';
            doc.pageSize = 'LEGAL';
            doc.defaultStyle.fontSize = 7;
            doc.styles.tableHeader.fontSize = 8;
            doc.styles.tableHeader.fillColor = '#2d4154';
            doc.styles.tableHeader.bold = true;
            doc.styles.tableHeader.color = 'white';

            let tituloPDF = 'Listado de Stock';

            if (window.titulo_filtros_stock) {
              tituloPDF += ' - ' + window.titulo_filtros_stock;
            }

            doc.content[0].text = tituloPDF;
            doc.content[0].alignment = 'center';
            doc.content[0].fontSize = 14;
            doc.content[0].margin = [0, 0, 0, 10];

            doc.pageMargins = [5, 5, 5, 5];
            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
          };
        }

        const buttonType = tipo === 'excel' ? 'excelHtml5' : tipo;

        try {
          const tempButton = tempTable.button().add(0, Object.assign({
            extend: buttonType
          }, exportConfig));

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
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
          title: 'Error',
          text: 'Ha ocurrido un error al exportar: ' + error.message,
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      });
  }
  
});



