/**
 * Page Devoluciones List
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_devoluciones_table = document.querySelector('.datatables-devoluciones');

  // Variable global para DataTable
  window.dt_devoluciones = null;

  if (window.ListarFiltros) {
    window.ListarFiltros.setOnChange(function () {
      if (window.dt_devoluciones) {
        window.dt_devoluciones.ajax.reload();
      }
    });
  }

  // Devoluciones datatable
  if (dt_devoluciones_table) {
    window.dt_devoluciones = new DataTable(dt_devoluciones_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      
      language: DATATABLES_SPANISH,
      
      ajax: {
        url: 'parts/devoluciones/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          // Agregar filtros de columna personalizados
          const sucursalFilter = document.getElementById('filtro_sucursal');
          
          d.filtro_sucursal = sucursalFilter ? sucursalFilter.value : '';
          
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
        { data: 0, responsivePriority: 1 },  // ID Devolución
        { data: 1, responsivePriority: 2 },  // ID Venta Original
        { data: 2, responsivePriority: 3 },  // Fecha Devolución
        { data: 3, responsivePriority: 4 },  // Cliente
        { data: 4, responsivePriority: 5 },  // Sucursal
        { data: 5, responsivePriority: 6 },  // Motivo
        { data: 6, responsivePriority: 7 },  // SKU
        { data: 7, responsivePriority: 8 },  // Descripción
        { data: 8, responsivePriority: 9 }, // Importe
        { data: 9, responsivePriority: 10 }, // Forma de Pago
        { data: 10, responsivePriority: 11 } // Devolución Web
      ],
      
      columnDefs: [
        {
          // ID Devolución - con link
          targets: 0,
          render: function (data, type, full, meta) {
            return '<a href="devolucion.php?id=' + data + '" class="fw-semibold text-primary">' + data + '</a>';
          }
        },
        {
          // ID Venta Original - con link
          targets: 1,
          render: function (data, type, full, meta) {
            if (data && data !== '-') {
              return '<a href="venta.php?id=' + data + '" class="fw-semibold text-primary" target="_blank">' + data + '</a>';
            }
            return '-';
          }
        },
        {
          // Fecha Devolución
          targets: 2,
          render: function (data, type, full, meta) {
            if (data) {
              const date = new Date(data);
              return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
            }
            return '-';
          }
        },
        {
          // Cliente
          targets: 3,
          render: function (data, type, full, meta) {
            return data || '-';
          }
        },
        {
          // Sucursal
          targets: 4,
          render: function (data, type, full, meta) {
            return data || '-';
          }
        },
        {
          // Motivo
          targets: 5,
          render: function (data, type, full, meta) {
            return data || '-';
          }
        },
        {
          // SKU - con link
          targets: 6,
          render: function (data, type, full, meta) {
            if (data && data !== '-') {
              return '<a href="articulo.php?id=' + data + '" class="fw-semibold text-primary" target="_blank">' + data + '</a>';
            }
            return '-';
          }
        },
        {
          // Descripción
          targets: 7,
          render: function (data, type, full, meta) {
            if (data && data.length > 50) {
              return '<span title="' + data + '">' + data.substring(0, 50) + '...</span>';
            }
            return data || '-';
          }
        },
        {
          // Importe
          targets: 8,
          render: function (data, type, full, meta) {
            return '<span class="fw-semibold text-success">' + data + '</span>';
          }
        },
        {
          // Forma de Pago - con badges
          targets: 9,
          render: function (data, type, full, meta) {
            if (!data || data === '-') {
              return '-';
            }
            const formaPago = data.toLowerCase();
            let badgeClass = 'bg-label-secondary';
            let badgeText = data;
            
            if (formaPago === 'contado' || formaPago === 'efectivo') {
              badgeClass = 'bg-label-success';
            } else if (formaPago === 'tarjeta') {
              badgeClass = 'bg-label-primary';
            } else if (formaPago === 'transferencia') {
              badgeClass = 'bg-label-info';
            } else if (formaPago === 'bizum') {
              badgeClass = 'bg-label-warning';
            } else if (formaPago === 'cheque') {
              badgeClass = 'bg-label-danger';
            }
            
            return '<span class="badge ' + badgeClass + ' rounded-pill">' + badgeText + '</span>';
          }
        },
        {
          // Devolución Web
          targets: 10,
          render: function (data, type, full, meta) {
            if (data === 'si' || data === 'true' || data === '1') {
              return '<span class="badge bg-label-success rounded-pill">Sí</span>';
            } else {
              return '<span class="badge bg-label-secondary rounded-pill">No</span>';
            }
          }
        }
      ],
      
      order: [[2, 'desc']], // Ordenar por fecha de devolución descendente
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
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatos('pdf', dt, button, config);
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
                        
                        const filtroSucursal = document.getElementById('filtro_sucursal');
                        const nombreSucursal = (filtroSucursal && filtroSucursal.value && filtroSucursal.value !== '') 
                          ? filtroSucursal.options[filtroSucursal.selectedIndex].text
                          : 'todas las sucursales';
                        
                        let tituloPDF = 'Listado de Devoluciones de ' + nombreSucursal;
                        
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
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatos('copy', dt, button, config);
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
        bottomStart: null,
        bottomEnd: {
          features: ['paging']
        }
      },
      
      // Configuración de responsive
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Detalles de Devolución ' + data[0];
            }
          }),
          renderer: $.fn.dataTable.Responsive.renderer.tableAll({
            tableClass: 'table'
          })
        }
      },
      
      // Configuración de idioma personalizado
      language: DATATABLES_SPANISH
    });
    
    // Filter form control to default size
    setTimeout(() => {
      const elementsToModify = [
        { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
        {
          selector: '.dt-layout-topEnd',
          classToAdd: 'pe-3'
        },
        {
          selector: '.dt-layout-topStart',
          classToAdd: 'ps-3'
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
  }

  // Función para cargar los filtros
  
  /**
   * Función para exportar TODOS los datos (con filtros aplicados)
   */
  window.exportarTodosLosDatos = function(tipo, dt, button, config) {
    // Capturar filtros actuales
    const searchValue = dt.search();
    const filtroSucursal = document.getElementById('filtro_sucursal');
    
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
    
    // Hacer fetch con los filtros
    fetch('parts/devoluciones/listar/export_all.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(responseData => {
      Swal.close();
      
      if (!responseData.success) {
        Swal.fire({
          title: 'Error',
          text: responseData.message || 'Error al exportar los datos',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
        return;
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
      
      // Crear tabla temporal oculta en el DOM
      const tempTableId = 'temp-export-table-' + Date.now();
      const tempDiv = document.createElement('div');
      tempDiv.style.display = 'none';
      tempDiv.innerHTML = '<table id="' + tempTableId + '"><thead><tr>' +
        '<th>Nº</th><th>VENTA</th><th>FECHA</th><th>CLIENTE</th><th>SUCURSAL</th>' +
        '<th>MOTIVO</th><th>SKU</th><th>DESCRIPCIÓN</th><th>IMPORTE</th><th>FORMA PAGO</th><th>DEV. WEB</th>' +
        '</tr></thead></table>';
      document.body.appendChild(tempDiv);
      
      // Crear DataTable temporal con todos los datos
      const tempTable = $('#' + tempTableId).DataTable({
        data: responseData.data,
        searching: false,
        ordering: false,
        dom: 't',
        buttons: []
      });
      
      // Configuración específica según el tipo de exportación
      let exportConfig = {
        exportOptions: {
          columns: ':visible'
        }
      };
      
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
          
          let tituloPDF = 'Listado de Devoluciones de ' + nombreSucursal;
          
          doc.content[0].text = tituloPDF;
          doc.content[0].alignment = 'center';
          doc.content[0].fontSize = 14;
          doc.content[0].margin = [0, 0, 0, 10];
          doc.pageMargins = [5, 5, 5, 5];
          doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
        };
      }
      
      // Determinar el tipo de botón según la exportación
      const buttonType = tipo === 'excel' ? 'excelHtml5' : tipo;
      
      // Crear botón temporal y ejecutar exportación
      try {
        const tempButton = tempTable.button().add(0, {
          extend: buttonType,
          ...exportConfig
        });
        
        tempTable.button(0).trigger();
        
        // Limpiar después de un tiempo
        setTimeout(() => {
          tempTable.destroy();
          document.body.removeChild(tempDiv);
        }, 1000);
      } catch (error) {
        console.error('Error al exportar:', error);
        Swal.fire({
          title: 'Error',
          text: 'Ha ocurrido un error al exportar: ' + error.message,
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      }
    })
    .catch(error => {
      Swal.close();
      console.error('Error al exportar:', error);
      Swal.fire({
        title: 'Error',
        text: 'Ha ocurrido un error al exportar: ' + error.message,
        icon: 'error',
        confirmButtonText: 'Aceptar'
      });
    });
  };
  
});
