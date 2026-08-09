/**
 * Page Ventas List
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_ventas_table = document.querySelector('.datatables-ventas');

  // Variable global para DataTable
  window.dt_ventas = null;

  const FD = window.FiltrosDinamicosListar;

  function onFiltroVentaChange() {
    if (window.dt_ventas) {
      window.dt_ventas.ajax.reload();
    }
    window.recargarEstadisticasVentas();
    actualizarTituloVentas();
  }

  const createFilterSucursal = function (containerClass, selectId) {
    return FD.createFilterSucursal(containerClass, selectId, 'Sucursales', onFiltroVentaChange);
  };

  const createFilterFijo = function (containerClass, selectId, defaultOptionText, opciones) {
    return FD.createFilterFijo(containerClass, selectId, defaultOptionText, opciones, onFiltroVentaChange);
  };

  // Ventas datatable
  if (dt_ventas_table) {
    window.dt_ventas = new DataTable(dt_ventas_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      
      language: DATATABLES_SPANISH,
      
      ajax: {
        url: 'parts/ventas/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          const sucursalFilter = document.getElementById('filtro_sucursal');
          const tipoVentaFilter = document.getElementById('filtro_tipo_venta');
          const ventaWebFilter = document.getElementById('filtro_venta_web');
          const formaPagoFilter = document.getElementById('filtro_forma_pago');
          const fechaDesdeFilter = document.getElementById('filtro_fecha_desde');
          const fechaHastaFilter = document.getElementById('filtro_fecha_hasta');
          
          d.filtro_sucursal = sucursalFilter ? sucursalFilter.value : '';
          d.filtro_tipo_venta = tipoVentaFilter ? tipoVentaFilter.value : '';
          d.filtro_venta_web = ventaWebFilter ? ventaWebFilter.value : '';
          d.filtro_forma_pago = formaPagoFilter ? formaPagoFilter.value : '';
          d.filtro_fecha_desde = fechaDesdeFilter ? fechaDesdeFilter.value : '';
          d.filtro_fecha_hasta = fechaHastaFilter ? fechaHastaFilter.value : '';
          d.filtro_periodo = window.filtro_periodo_activo || 'todos';
          
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
        { data: 0, responsivePriority: 1 },  // Nº venta
        { data: 1, responsivePriority: 2 },  // Total venta
        { data: 2, responsivePriority: 3 },  // Fecha venta
        { data: 3, responsivePriority: 4 },  // Sucursal venta
        { data: 4, responsivePriority: 10 }, // Vendido por
        { data: 5, responsivePriority: 10 }, // Venta plazos
        { data: 6, responsivePriority: 10 }, // Venta web
        { data: 7, responsivePriority: 5 },  // Forma de pago
        { data: 8, visible: false }          // ID (hidden)
      ],
      
      columnDefs: [
        {
          // Nº venta (id_venta_sucursal) con enlace a ficha por identificador_venta (av.id) en full[8]
          targets: 0,
          render: function (data, type, full, meta) {
            const idVenta = full[8];
            const numeroTicket = data;
            return (
              '<a href="venta.php?id=' +
              encodeURIComponent(idVenta) +
              '" class="fw-semibold text-primary">' +
              numeroTicket +
              '</a>'
            );
          }
        }
      ],
      
      order: [[2, 'desc']], // Ordenar por fecha descendente
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
                        exportarTodosLosDatos('excel', dt, button, config);
                      },
                      exportOptions: { columns: ':visible' }
                    },
                    {
                      extend: 'pdf',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                      className: 'dropdown-item',
                      orientation: 'landscape',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatos('pdf', dt, button, config);
                      },
                      exportOptions: { columns: ':visible' }
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatos('copy', dt, button, config);
                      },
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
      
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Detalles de Venta #' + data[0];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            const data = columns
              .map(function (col) {
                return col.title !== ''
                  ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td>${col.title}:</td>
                      <td>${col.data}</td>
                    </tr>`
                  : '';
              })
              .join('');

            if (data) {
              const div = document.createElement('div');
              div.classList.add('table-responsive');
              const table = document.createElement('table');
              div.appendChild(table);
              table.classList.add('table');
              const tbody = document.createElement('tbody');
              tbody.innerHTML = data;
              table.appendChild(tbody);
              return div;
            }
            return false;
          }
        }
      }
    });

    // Cargar filtros
    cargarFiltros();
    
    // Configurar filtros de fecha
    configurarFiltrosFecha();
  }

  // Función para cargar los filtros
  function cargarFiltros() {
    // Filtro de Sucursal
    createFilterSucursal('.venta_sucursal', 'filtro_sucursal');

    // Filtro de Tipo de Venta
    const opcionesTipoVenta = [
      { value: 'normal', label: 'Venta' },
      { value: 'plazos', label: 'Venta a plazos' }
    ];
    createFilterFijo('.venta_tipo', 'filtro_tipo_venta', 'Tipo venta', opcionesTipoVenta);

    // Filtro de Venta Web
    const opcionesVentaWeb = [
      { value: 'true', label: 'Sí' },
      { value: 'false', label: 'No' }
    ];
    createFilterFijo('.venta_web', 'filtro_venta_web', 'Web', opcionesVentaWeb);

    // Filtro de Forma de Pago
    const opcionesFormaPago = [
      { value: 'contado', label: 'Contado' },
      { value: 'tarjeta', label: 'Tarjeta' },
      { value: 'transferencia', label: 'Transferencia' },
      { value: 'bizum', label: 'Bizum' },
      { value: 'combinado', label: 'Combinado' }
    ];
    createFilterFijo('.venta_forma_pago', 'filtro_forma_pago', 'Forma de pago', opcionesFormaPago);

    if (FD) {
      FD.finalize();
    }
  }
  
  /**
   * Función para exportar TODOS los datos (con filtros aplicados)
   */
  function exportarTodosLosDatos(tipo, dt, button, config) {
    // Capturar filtros actuales
    const searchValue = dt.search();
    const filtroSucursal = document.getElementById('filtro_sucursal');
    const filtroTipoVenta = document.getElementById('filtro_tipo_venta');
    const filtroVentaWeb = document.getElementById('filtro_venta_web');
    const filtroFormaPago = document.getElementById('filtro_forma_pago');
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    
    // Mostrar loading
    Swal.fire({
      title: 'Generando exportación...',
      text: 'Obteniendo todos los registros',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    // Preparar datos para enviar
    const formData = new FormData();
    formData.append('search', searchValue);
    formData.append('filtro_sucursal', filtroSucursal ? filtroSucursal.value : '');
    formData.append('filtro_tipo_venta', filtroTipoVenta ? filtroTipoVenta.value : '');
    formData.append('filtro_venta_web', filtroVentaWeb ? filtroVentaWeb.value : '');
    formData.append('filtro_forma_pago', filtroFormaPago ? filtroFormaPago.value : '');
    formData.append('filtro_fecha_desde', filtroFechaDesde ? filtroFechaDesde.value : '');
    formData.append('filtro_fecha_hasta', filtroFechaHasta ? filtroFechaHasta.value : '');
    formData.append('filtro_periodo', window.filtro_periodo_activo || 'todos');
    
    // Hacer fetch con los filtros
    fetch('parts/ventas/listar/export_all.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(responseData => {
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
      
      // Crear tabla temporal oculta
      const tempTableId = 'temp-export-table-' + Date.now();
      const tempDiv = document.createElement('div');
      tempDiv.style.display = 'none';
      tempDiv.innerHTML = '<table id="' + tempTableId + '"><thead><tr>' +
        '<th>Nº venta</th><th>Total venta</th><th>Fecha venta</th><th>Sucursal venta</th>' +
        '<th>Vendido por</th><th>Venta plazos</th><th>Venta web</th><th>Forma de pago</th>' +
        '</tr></thead></table>';
      document.body.appendChild(tempDiv);
      
      // Crear DataTable temporal
      const tempTable = $('#' + tempTableId).DataTable({
        data: responseData.data,
        columns: [
          { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 },
          { data: 4 }, { data: 5 }, { data: 6 }, { data: 7 }
        ],
        paging: false,
        searching: false,
        ordering: false,
        dom: 't',
        buttons: []
      });
      
      // Configuración de exportación
      let exportConfig = { exportOptions: { columns: ':visible' } };
      
      // Configuración adicional para PDF
      if (tipo === 'pdf') {
        exportConfig.customize = function(doc) {
          doc.pageOrientation = 'landscape';
          doc.pageSize = 'LEGAL';
          doc.defaultStyle.fontSize = 7;
          doc.styles.tableHeader.fontSize = 8;
          doc.styles.tableHeader.fillColor = '#2d4154';
          doc.styles.tableHeader.bold = true;
          doc.styles.tableHeader.color = 'white';
          
          const filtroSucursal = document.getElementById('filtro_sucursal');
          const nombreSucursal = (filtroSucursal && filtroSucursal.value && filtroSucursal.value !== '') 
            ? filtroSucursal.options[filtroSucursal.selectedIndex].text
            : 'todas las sucursales';
          
          let tituloPDF = 'Listado de Ventas de ' + nombreSucursal;
          
          if (window.titulo_filtros_ventas) {
            tituloPDF += ' - ' + window.titulo_filtros_ventas;
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
        const tempButton = tempTable.button().add(0, {
          extend: buttonType,
          ...exportConfig
        });
        
        tempButton.trigger();
        
        setTimeout(() => {
          tempTable.destroy();
          tempDiv.remove();
        }, 2000);
      } catch (error) {
        console.error('Error al exportar:', error);
        throw error;
      }
    })
    .catch(error => {
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

  // Función para configurar los filtros de fecha
  function configurarFiltrosFecha() {
    window.filtro_periodo_activo = 'todos';
    
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');
    
    // Event listener para botón "Por Fecha de Venta"
    const filtroPorFechaVenta = document.getElementById('filtro_por_fecha_venta');
    if (filtroPorFechaVenta) {
      filtroPorFechaVenta.addEventListener('click', function() {
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
        if (rangeFechas) rangeFechas.value = '';
        
        document.querySelectorAll('#filtro_por_fecha_venta, #filtro_dia, #filtro_mes, #filtro_todos').forEach(btn => {
          btn.classList.remove('active');
        });
        this.classList.add('active');
        
        window.dt_ventas.ajax.reload();
        window.recargarEstadisticasVentas();
        actualizarTituloVentas();
      });
    }
    
    // Event listener para botón "Día"
    const filtroDia = document.getElementById('filtro_dia');
    if (filtroDia) {
      filtroDia.addEventListener('click', function() {
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo = 'dia';
        
        document.querySelectorAll('#filtro_por_fecha_venta, #filtro_dia, #filtro_mes, #filtro_todos').forEach(btn => {
          btn.classList.remove('active');
        });
        this.classList.add('active');
        
        window.dt_ventas.ajax.reload();
        window.recargarEstadisticasVentas();
        actualizarTituloVentas();
      });
    }
    
    // Event listener para botón "Mes"
    const filtroMes = document.getElementById('filtro_mes');
    if (filtroMes) {
      filtroMes.addEventListener('click', function() {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo = 'mes';
        
        document.querySelectorAll('#filtro_por_fecha_venta, #filtro_dia, #filtro_mes, #filtro_todos').forEach(btn => {
          btn.classList.remove('active');
        });
        this.classList.add('active');
        
        window.dt_ventas.ajax.reload();
        window.recargarEstadisticasVentas();
        actualizarTituloVentas();
      });
    }
    
    // Event listener para botón "Todos"
    const filtroTodos = document.getElementById('filtro_todos');
    if (filtroTodos) {
      filtroTodos.addEventListener('click', function() {
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo = 'todos';
        
        document.querySelectorAll('#filtro_por_fecha_venta, #filtro_dia, #filtro_mes, #filtro_todos').forEach(btn => {
          btn.classList.remove('active');
        });
        this.classList.add('active');
        
        window.dt_ventas.ajax.reload();
        window.recargarEstadisticasVentas();
        actualizarTituloVentas();
      });
    }
    
    actualizarTituloVentas();
  }
  
  function formatearFechaTituloVentas(fechaIso) {
    if (!fechaIso) {
      return '';
    }
    const fecha = new Date(fechaIso + 'T00:00:00');
    if (isNaN(fecha.getTime())) {
      return fechaIso;
    }
    return fecha.toLocaleDateString('es-ES');
  }

  function obtenerMesActualTituloVentas() {
    const meses = [
      'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
    ];
    const fecha = new Date();
    return meses[fecha.getMonth()] + ' de ' + fecha.getFullYear();
  }

  function agregarTextoFechasTituloVentas(partes) {
    let filtroActivo = window.filtro_periodo_activo || 'todos';
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const fechaDesde = filtroFechaDesde ? filtroFechaDesde.value : '';
    const fechaHasta = filtroFechaHasta ? filtroFechaHasta.value : '';

    if (filtroActivo === 'todos' && (fechaDesde || fechaHasta)) {
      filtroActivo = 'personalizado';
    }

    if (filtroActivo === 'dia') {
      if (fechaDesde) {
        partes.push('del ' + formatearFechaTituloVentas(fechaDesde));
      } else {
        partes.push('de hoy');
      }
      return;
    }

    if (filtroActivo === 'mes') {
      partes.push('de ' + obtenerMesActualTituloVentas());
      return;
    }

    if (filtroActivo === 'fecha' || filtroActivo === 'personalizado') {
      if (fechaDesde && fechaHasta) {
        if (fechaDesde === fechaHasta) {
          partes.push('del ' + formatearFechaTituloVentas(fechaDesde));
        } else {
          partes.push(
            'entre el ' + formatearFechaTituloVentas(fechaDesde) +
            ' y el ' + formatearFechaTituloVentas(fechaHasta)
          );
        }
      } else if (fechaDesde) {
        partes.push('desde el ' + formatearFechaTituloVentas(fechaDesde));
      } else if (fechaHasta) {
        partes.push('hasta el ' + formatearFechaTituloVentas(fechaHasta));
      }
    }
  }

  // Función para actualizar el título
  function actualizarTituloVentas() {
    const textoTitulo = document.getElementById('texto_ventas_titulo');
    if (!textoTitulo) {
      return;
    }

    const partes = [];

    const filtroSucursal = document.getElementById('filtro_sucursal');
    if (filtroSucursal && filtroSucursal.value) {
      const selectedOption = filtroSucursal.options[filtroSucursal.selectedIndex];
      if (selectedOption && selectedOption.value) {
        partes.push('de ' + selectedOption.textContent.trim());
      }
    }

    agregarTextoFechasTituloVentas(partes);

    const filtroTipoVenta = document.getElementById('filtro_tipo_venta');
    if (filtroTipoVenta && filtroTipoVenta.value) {
      const selectedOption = filtroTipoVenta.options[filtroTipoVenta.selectedIndex];
      if (selectedOption && selectedOption.value) {
        partes.push(selectedOption.textContent.trim());
      }
    }

    const filtroVentaWeb = document.getElementById('filtro_venta_web');
    if (filtroVentaWeb && filtroVentaWeb.value) {
      const selectedOption = filtroVentaWeb.options[filtroVentaWeb.selectedIndex];
      if (selectedOption && selectedOption.value) {
        partes.push('Web: ' + selectedOption.textContent.trim());
      }
    }

    const filtroFormaPago = document.getElementById('filtro_forma_pago');
    if (filtroFormaPago && filtroFormaPago.value) {
      const selectedOption = filtroFormaPago.options[filtroFormaPago.selectedIndex];
      if (selectedOption && selectedOption.value) {
        partes.push(selectedOption.textContent.trim());
      }
    }

    const textoFinal = partes.length > 0 ? '(' + partes.join(' · ') + ')' : '';
    textoTitulo.textContent = textoFinal;
    window.titulo_filtros_ventas = textoFinal.replace(/^\(|\)$/g, '');
  }

  window.actualizarTituloVentas = actualizarTituloVentas;

  // Filter form control to default size
  setTimeout(() => {
    const elementsToModify = [
      { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
      { selector: '.dt-length .form-select', classToAdd: 'ms-0' },
      { selector: '.dt-length', classToAdd: 'mb-md-4 mb-0' },
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
  
  // Agregar evento click en las filas para ir al detalle (solo si existe la tabla)
  if (dt_ventas_table) {
    dt_ventas_table.addEventListener('click', function(e) {
      const row = e.target.closest('tr');
      if (row && row.querySelector('td')) {
        const rowData = window.dt_ventas.row(row).data();
        const idVenta = rowData[8]; // identificador_venta (PK ventas.id) para venta.php?id=
        if (idVenta) {
          window.location.href = 'venta.php?id=' + encodeURIComponent(idVenta);
        }
      }
    });
  }
  
  /**
   * Función para exportar TODOS los datos (con filtros aplicados)
   */
  window.exportarTodosLosDatos = function(tipo, dt, button, config) {
    // Capturar filtros actuales
    const searchValue = dt.search();
    const filtroSucursal = document.getElementById('filtro_sucursal');
    const filtroTipoVenta = document.getElementById('filtro_tipo_venta');
    const filtroVentaWeb = document.getElementById('filtro_venta_web');
    const filtroFormaPago = document.getElementById('filtro_forma_pago');
    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    
    // Mostrar loading
    Swal.fire({
      title: 'Generando exportación...',
      text: 'Obteniendo todos los registros',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    // Preparar datos para enviar
    const formData = new FormData();
    formData.append('search', searchValue);
    formData.append('filtro_sucursal', filtroSucursal ? filtroSucursal.value : '');
    formData.append('filtro_tipo_venta', filtroTipoVenta ? filtroTipoVenta.value : '');
    formData.append('filtro_venta_web', filtroVentaWeb ? filtroVentaWeb.value : '');
    formData.append('filtro_forma_pago', filtroFormaPago ? filtroFormaPago.value : '');
    formData.append('filtro_fecha_desde', filtroFechaDesde ? filtroFechaDesde.value : '');
    formData.append('filtro_fecha_hasta', filtroFechaHasta ? filtroFechaHasta.value : '');
    formData.append('filtro_periodo', window.filtro_periodo_activo || 'todos');
    
    // Hacer fetch con los filtros
    fetch('parts/ventas/listar/export_all.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(responseData => {
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
      
      // Crear tabla temporal oculta
      const tempTableId = 'temp-export-table-' + Date.now();
      const tempDiv = document.createElement('div');
      tempDiv.style.display = 'none';
      tempDiv.innerHTML = '<table id="' + tempTableId + '"><thead><tr>' +
        '<th>Nº venta</th><th>Total venta</th><th>Fecha venta</th><th>Sucursal venta</th>' +
        '<th>Vendido por</th><th>Venta plazos</th><th>Venta web</th><th>Forma de pago</th>' +
        '</tr></thead></table>';
      document.body.appendChild(tempDiv);
      
      // Crear DataTable temporal
      const tempTable = $('#' + tempTableId).DataTable({
        data: responseData.data,
        columns: [
          { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 },
          { data: 4 }, { data: 5 }, { data: 6 }, { data: 7 }
        ],
        paging: false,
        searching: false,
        ordering: false,
        dom: 't',
        buttons: []
      });
      
      // Configuración de exportación
      let exportConfig = { exportOptions: { columns: ':visible' } };
      
      // Configuración adicional para PDF
      if (tipo === 'pdf') {
        exportConfig.customize = function(doc) {
          doc.pageOrientation = 'landscape';
          doc.pageSize = 'LEGAL';
          doc.defaultStyle.fontSize = 7;
          doc.styles.tableHeader.fontSize = 8;
          doc.styles.tableHeader.fillColor = '#2d4154';
          doc.styles.tableHeader.bold = true;
          doc.styles.tableHeader.color = 'white';
          
          const filtroSucursal = document.getElementById('filtro_sucursal');
          const nombreSucursal = (filtroSucursal && filtroSucursal.value && filtroSucursal.value !== '') 
            ? filtroSucursal.options[filtroSucursal.selectedIndex].text
            : 'todas las sucursales';
          
          let tituloPDF = 'Listado de Ventas de ' + nombreSucursal;
          
          if (window.titulo_filtros_ventas) {
            tituloPDF += ' - ' + window.titulo_filtros_ventas;
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
        const tempButton = tempTable.button().add(0, {
          extend: buttonType,
          ...exportConfig
        });
        
        tempButton.trigger();
        
        setTimeout(() => {
          tempTable.destroy();
          tempDiv.remove();
        }, 2000);
      } catch (error) {
        console.error('Error al exportar:', error);
        throw error;
      }
    })
    .catch(error => {
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
  
  // Exponer función globalmente
  window.exportarTodosLosDatos = exportarTodosLosDatos;
  
  // Función global para recargar estadísticas
  window.recargarEstadisticasVentas = function() {
    if (typeof cargarEstadisticas === 'function') {
      cargarEstadisticas();
    }
  };
  
  // Manejar botón "Nueva venta" y select de sucursal
  const btnNuevaVenta = document.getElementById('btn_nueva_venta');
  const selectSucursalContainer = document.getElementById('select_sucursal_nueva_venta_container');
  const selectSucursalNuevaVenta = $('#select_sucursal_nueva_venta');
  
  // Evento click en el botón "Nueva venta"
  if (btnNuevaVenta) {
    btnNuevaVenta.addEventListener('click', function() {
      // Ocultar botón
      btnNuevaVenta.style.display = 'none';
      
      // Mostrar select de sucursales
      if (selectSucursalContainer) {
        selectSucursalContainer.style.display = 'block';
        
        // Inicializar Select2 si aún no está inicializado
        if (selectSucursalNuevaVenta.length && !selectSucursalNuevaVenta.hasClass('select2-hidden-accessible')) {
          selectSucursalNuevaVenta.select2({
            placeholder: 'Seleccionar sucursal para venta',
            allowClear: false,
            width: '100%',
            containerCssClass: 'select-custom'
          });
        }
        
        // Abrir el dropdown automáticamente
        setTimeout(() => {
          selectSucursalNuevaVenta.select2('open');
        }, 100);
      }
    });
  }
  
  // Evento change para enviar por POST a crear_venta.php
  if (selectSucursalNuevaVenta.length) {
    selectSucursalNuevaVenta.on('change', function() {
      const idSucursal = $(this).val();
      
      if (idSucursal) {
        // Crear formulario oculto para enviar por POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'crear_venta.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id_sucursal';
        input.value = idSucursal;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      }
    });
  }
});

