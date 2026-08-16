/**
 * Page Cliente List   - Fixed Translations
 */

'use strict';

// Datatable  (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_cliente_table = document.querySelector('.datatables-users'),
    clienteView = 'app-cliente-view-account.html',
    statusObj = {
      1: { title: 'Pending', class: 'bg-label-warning' },
      2: { title: 'Active', class: 'bg-label-success' },
      3: { title: 'Inactive', class: 'bg-label-secondary' }
    };
  

  // Variable global para DataTable
  let dt_cliente;

  function reloadClientesTable() {
    if (dt_cliente) {
      dt_cliente.ajax.reload();
    }
  }

  if (window.ClientesFiltros) {
    window.ClientesFiltros.setOnChange(reloadClientesTable);
  }

  /**
   * Clase de badge según tipo de identificación (texto en mayúsculas)
   */
  function getBadgeClassTipoIdentificacion(tipo) {
    const t = (tipo || '').toString().toUpperCase().trim();
    if (t === 'NIF' || t === 'DNI') {
      return 'info';
    }
    if (t === 'NIE') {
      return 'primary';
    }
    if (t === 'CIF') {
      return 'warning';
    }
    if (t.indexOf('PASAPORTE') !== -1) {
      return 'success';
    }
    if (t.indexOf('OTRO') !== -1) {
      return 'danger';
    }
    return 'secondary';
  }

  // Clientes datatable
  if (dt_cliente_table) {
    dt_cliente = new DataTable(dt_cliente_table, {
      processing: true, // Mostrar indicador de procesamiento
      serverSide: true, // Procesar en el servidor para grandes volúmenes
      deferRender: true, // Mejorar rendimiento con grandes volúmenes
      searchDelay: 500, // Delay de 500ms para búsquedas
      timeout: 60000, // Timeout de 60 segundos para peticiones grandes
      
      language: DATATABLES_SPANISH,
      columns: [
        // columns according to JSON
        { data: 0 }, // ID del cliente
        { data: 1 }, // Nombre completo + ID
        { data: 2 }, // Tipo Identificación
        { data: 3 }, // Número Identificación
        { data: 4 }, // Nacionalidad
        { data: 5 }, // Teléfono
        { data: 6 }, // Provincia
        { data: 7 }, // Estado
        { data: 8 }  // Fecha
      ],
      
      columnDefs: [
        {
          // Cliente column - Nombre completo + ID
          targets: 1,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            if (typeof data === 'object' && data.full_name && data.id_cliente) {
              var fullName = data.full_name;
              var idCliente = data.id_cliente;
              
              var row_output = fullName;
              return row_output;
            } else {
              return '<span class="text-muted">Error en datos</span>';
            }
          }
        },
        {
          // Tipo Identificación
          targets: 2,
          render: function (data, type, full, meta) {
            const tipoIdentificacion = data;
            if (tipoIdentificacion && tipoIdentificacion !== 'Sin tipo' && tipoIdentificacion !== 'SIN TIPO') {
              const badgeClass = getBadgeClassTipoIdentificacion(tipoIdentificacion);
              return '<span class="badge bg-label-' + badgeClass + '">' + tipoIdentificacion + '</span>';
            } else {
              return '<span class="text-muted">Sin tipo</span>';
            }
          }
        },
        {
          // Número Identificación
          targets: 3,
          render: function (data, type, full, meta) {
            const numeroIdentificacion = data;
            if (numeroIdentificacion && numeroIdentificacion !== 'Sin número') {
              return '<span class="fw-semibold">' + numeroIdentificacion + '</span>';
            } else {
              return '<span class="text-muted">Sin número</span>';
            }
          }
        },
        {
          // Nacionalidad
          targets: 4,
          render: function (data, type, full, meta) {
            const nacionalidad = data;
            if (nacionalidad && nacionalidad !== 'Sin nacionalidad') {
              return '<span class="fw-semibold">' + nacionalidad + '</span>';
            } else {
              return '<span class="text-muted">Sin nacionalidad</span>';
            }
          }
        },
        {
          // Teléfono
          targets: 5,
          render: function (data, type, full, meta) {
            const telefono = data;
            if (telefono && telefono !== 'Sin teléfono') {
              return '<span class="fw-semibold">' + telefono + '</span>';
            } else {
              return '<span class="text-muted"><i class="icon-base ri ri-phone-line me-1"></i>Sin teléfono</span>';
            }
          }
        },
        {
          // Provincia
          targets: 6,
          render: function (data, type, full, meta) {
            const provincia = data;
            if (provincia && provincia !== 'Sin provincia') {
              return '<span class="fw-semibold">' + provincia + '</span>';
            } else {
              return '<span class="text-muted">Sin provincia</span>';
            }
          }
        },
        {
          // Estado
          targets: 7,
          render: function (data, type, full, meta) {
            const status = data;
            
            var statusColors = {
              'Habilitado': 'success',
              'Deshabilitado': 'danger'
            };
            
            var color = statusColors[status] || 'secondary';
            
            return (
              '<span class="badge bg-label-' + color + '">' +
              status +
              '</span>'
            );
          }
        },
      ],
      
      // Hacer las filas clickeables
      createdRow: function(row, data, dataIndex) {
        const clienteId = data[0]; // ID del cliente
        $(row).css('cursor', 'pointer');
        $(row).on('click', function(e) {
          // Evitar redirección si se hace click en un enlace o botón dentro de la fila
          if (!$(e.target).closest('a, button').length) {
            window.location.href = 'cliente.php?id=' + clienteId;
          }
        });
      },

      order: [[0, 'desc']],
      pageLength: 25, // Mostrar 25 registros por defecto
      lengthMenu: [10, 25, 50, 100], // Opciones: 10, 25, 50, 100 (sin -1 para serverSide)
      layout: {
        topStart: {
          rowClass: 'row m-2 my-0 mt-0 justify-content-between',
          features: [
            {
              buttons: [
                {
                  extend: 'collection',
                  className: 'btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                  buttons: [
                    {
                      extend: 'excel',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                      className: 'dropdown-item',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatos('excel', dt, button, config);
                      }
                    },
                    {
                      extend: 'pdf',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                      className: 'dropdown-item',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatos('pdf', dt, button, config);
                      }
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      action: function (e, dt, button, config) {
                        exportarTodosLosDatos('copy', dt, button, config);
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

      // Callbacks para debug
      ajax: {
        url: 'parts/clientes/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          // Agregar filtros de columna personalizados
          const tipoIdentificacionFilter = document.getElementById('UserTipoIdentificacion');
          const provinciaFilter = document.getElementById('UserProvincia');
          const estadoFilter = document.getElementById('UserEstado');
          
          d.filtro_tipo_identificacion = tipoIdentificacionFilter ? tipoIdentificacionFilter.value : '';
          d.filtro_provincia = provinciaFilter ? provinciaFilter.value : '';
          d.filtro_estado = estadoFilter ? estadoFilter.value : '';
          
          console.log('DataTables enviando filtros:', {
            tipo_identificacion: d.filtro_tipo_identificacion,
            provincia: d.filtro_provincia,
            estado: d.filtro_estado
          });
          return d;
        },
        dataSrc: function(json) {
          console.log('DataTables recibió:', json);
          return json.data || [];
        },
        error: function(xhr, error, thrown) {
          console.error('Error AJAX:', error, thrown);
          console.log('Respuesta del servidor:', xhr.responseText);
        }
      },
      
      // For responsive popup
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Detalles de ' + data['full_name'];
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

    // REMOVIDO EL SETTIMEOUT QUE CAUSABA CONFLICTOS
    // Ya no necesitamos cambiar manualmente los textos porque están configurados en 'language'

    //? The 'delete-record' class is necessary for the functionality of the following code.
    function deleteRecord(event) {
      let row = document.querySelector('.dtr-expanded');
      if (event) {
        row = event.target.parentElement.closest('tr');
      }
      if (row) {
        dt_cliente.row(row).remove().draw();
      }
    }

    function bindDeleteEvent() {
      const clienteListTable = document.querySelector('.datatables-users');
      const modal = document.querySelector('.dtr-bs-modal');

      if (clienteListTable && clienteListTable.classList.contains('collapsed')) {
        if (modal) {
          modal.addEventListener('click', function (event) {
            if (event.target.parentElement.classList.contains('delete-record')) {
              deleteRecord();
              const closeButton = modal.querySelector('.btn-close');
              if (closeButton) closeButton.click();
            }
          });
        }
      } else {
        const tableBody = clienteListTable?.querySelector('tbody');
        if (tableBody) {
          tableBody.addEventListener('click', function (event) {
            if (event.target.parentElement.classList.contains('delete-record')) {
              deleteRecord(event);
            }
          });
        }
      }
    }

    // Initial event binding
    bindDeleteEvent();

    // Re-bind events when modal is shown or hidden
    document.addEventListener('show.bs.modal', function (event) {
      if (event.target.classList.contains('dtr-bs-modal')) {
        bindDeleteEvent();
      }
    });

    document.addEventListener('hide.bs.modal', function (event) {
      if (event.target.classList.contains('dtr-bs-modal')) {
        bindDeleteEvent();
      }
    });
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

  // Función para cambiar estado de clientes
  window.toggleEstadoCliente = function(clienteId) {
    // Obtener el texto actual del botón para determinar la acción
    const boton = event.target.closest('.dropdown-item');
    const textoActual = boton.querySelector('.align-middle').textContent;
    const esHabilitar = textoActual === 'Habilitar';
    
    const titulo = esHabilitar ? '¿Habilitar cliente?' : '¿Deshabilitar cliente?';
    const texto = esHabilitar ? 
      '¿Estás seguro de que quieres habilitar este cliente?' : 
      '¿Estás seguro de que quieres deshabilitar este cliente?';
    const icono = esHabilitar ? 'question' : 'warning';
    
    Swal.fire({
      title: titulo,
      text: texto,
      icon: icono,
      showCancelButton: true,
      confirmButtonText: esHabilitar ? 'Sí, habilitar' : 'Sí, deshabilitar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: esHabilitar ? '#198754' : '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: 'Procesando...',
          text: 'Actualizando estado del cliente',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        // Enviar petición AJAX
        fetch('parts/clientes/listar/toggle_estado.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'id_cliente=' + clienteId
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Actualizar el botón en la interfaz
            const botonSpan = boton.querySelector('.align-middle');
            const botonIcono = boton.querySelector('i');
            
            botonSpan.textContent = data.boton_texto;
            botonIcono.className = 'icon-base ri ' + data.boton_icono + ' me-2';
            
            // Actualizar el estado en la tabla
            const row = boton.closest('tr');
            const estadoCell = row.querySelector('td:nth-child(7)'); // Columna de estado
            
            if (estadoCell) {
              const nuevoEstado = data.estado_texto;
              const color = nuevoEstado === 'Habilitado' ? 'success' : 'danger';
              const icono = nuevoEstado === 'Habilitado' ? 'checkbox-circle-fill' : 'close-circle-line';
              
              estadoCell.innerHTML = `
                <span class='badge bg-label-${color}'>
                  <i class="icon-base ri ri-${icono} me-1"></i>
                  ${nuevoEstado}
                </span>
              `;
            }
            
            // Mostrar mensaje de éxito
            Swal.fire({
              title: '¡Éxito!',
              text: data.message,
              icon: 'success',
              confirmButtonText: 'Aceptar',
              confirmButtonColor: '#696cff',
              timer: 2000,
              timerProgressBar: true
            });
            
            // Recargar estadísticas
            if (typeof cargarEstadisticas === 'function') {
              cargarEstadisticas();
            }
            
          } else {
            throw new Error(data.error || 'Error desconocido');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            title: 'Error',
            text: 'Ha ocurrido un error al actualizar el estado del cliente: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
          });
        });
      }
    });
  };

  /**
   * Función para exportar TODOS los datos (con filtros aplicados)
   */
  window.exportarTodosLosDatos = function(tipo, dt, button, config) {
    // Capturar filtros actuales
    const searchValue = dt.search();
    const filtroTipoIdentificacion = $('#UserTipoIdentificacion').val() || '';
    const filtroProvincia = $('#UserProvincia').val() || '';
    const filtroEstado = $('#UserEstado').val() || '';
    
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
    formData.append('filtro_tipo_identificacion', filtroTipoIdentificacion);
    formData.append('filtro_provincia', filtroProvincia);
    formData.append('filtro_estado', filtroEstado);
    
    // Hacer fetch con los filtros
    fetch('parts/clientes/listar/export_all.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(responseData => {
      Swal.close();
      
      if (!responseData.success) {
        throw new Error(responseData.error || 'Error al obtener datos');
      }
      
      // Crear tabla temporal oculta en el DOM
      const tempTableId = 'temp-export-table-' + Date.now();
      const tempDiv = document.createElement('div');
      tempDiv.style.display = 'none';
      tempDiv.innerHTML = '<table id="' + tempTableId + '"><thead><tr>' +
        '<th>ID</th><th>Cliente</th><th>Tipo Identificación</th><th>Número Identificación</th><th>Nacionalidad</th><th>Teléfono</th><th>Provincia</th><th>Estado</th><th>Fecha Alta</th>' +
        '</tr></thead></table>';
      document.body.appendChild(tempDiv);
      
      // Crear DataTable temporal con TODOS los datos
      const tempTable = $('#' + tempTableId).DataTable({
        data: responseData.data,
        columns: [
          { data: 0 }, // ID
          { data: 1 }, // Nombre
          { data: 2 }, // Tipo Identificación
          { data: 3 }, // Número Identificación
          { data: 4 }, // Nacionalidad
          { data: 5 }, // Teléfono
          { data: 6 }, // Provincia
          { data: 7 }, // Estado
          { data: 8 }  // Fecha
        ],
        paging: false,
        searching: false,
        ordering: false,
        dom: 't'
      });
      
      // Configuración específica según el tipo de exportación
      let exportConfig = {
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
        }
      };
      
      // Configuración adicional para PDF
      if (tipo === 'pdf') {
        exportConfig.customize = function(doc) {
          doc.pageOrientation = 'landscape';
          doc.defaultStyle.fontSize = 8;
          doc.styles.tableHeader.fontSize = 9;
          doc.styles.tableHeader.fillColor = '#2d4154';
          doc.styles.tableHeader.bold = true;
          doc.styles.tableHeader.color = 'white';
          
          // Cambiar el título del PDF
          doc.content[0].text = 'Listado de Clientes';
          doc.content[0].alignment = 'center';
          doc.content[0].fontSize = 16;
          doc.content[0].margin = [0, 0, 0, 10];
          
          // Configurar tabla para ocupar 100% del ancho
          doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
          
          // Ajustar márgenes para aprovechar todo el ancho
          doc.pageMargins = [20, 20, 20, 20];
          
          // Configurar el ancho de la tabla al 100% sin bordes
          doc.content[1].layout = {
            hLineWidth: function() { return 0; },
            vLineWidth: function() { return 0; },
            paddingLeft: function() { return 4; },
            paddingRight: function() { return 4; },
            paddingTop: function() { return 2; },
            paddingBottom: function() { return 2; }
          };
          
          // Alinear columnas al centro (excepto la primera)
          doc.content[1].table.body.forEach(function(row, index) {
            // Primera columna (Nombre) alineada a la izquierda
            if (row[0]) {
              row[0].alignment = 'left';
            }
            
            // Resto de columnas alineadas al centro
            for (let i = 1; i < row.length; i++) {
              if (row[i]) {
                row[i].alignment = 'center';
              }
            }
          });
          
          // Forzar ancho de tabla al 100%
          doc.content[1].table.widths = doc.content[1].table.widths.map(() => '*');
        };
      }
      
      // Configuración adicional para Print
      if (tipo === 'print') {
        exportConfig.customize = function(win) {
          $(win.document.body).find('h1').text('Listado de Clientes');
          $(win.document.body).find('table').addClass('compact').css('font-size', '12px');
        };
      }
      
      // Determinar el tipo de botón según la exportación
      const buttonType = tipo === 'excel' ? 'excelHtml5' : 
                        tipo === 'csv' ? 'csvHtml5' : 
                        tipo === 'pdf' ? 'pdfHtml5' :
                        tipo === 'print' ? 'print' :
                        tipo === 'copy' ? 'copyHtml5' : tipo;
      
      // Inicializar el sistema de botones en la tabla temporal
      new $.fn.dataTable.Buttons(tempTable, {
        buttons: [{
          extend: buttonType,
          exportOptions: exportConfig.exportOptions,
          customize: exportConfig.customize
        }]
      });
      
      // Agregar el contenedor de botones al wrapper
      tempTable.buttons().container().appendTo($('#' + tempTableId + '_wrapper'));
      
      // Simular el click en el botón de exportación
      tempTable.buttons(0).trigger();
      
      // Destruir tabla y elementos temporales después de un delay
      setTimeout(() => {
        tempTable.destroy();
        document.body.removeChild(tempDiv);
      }, 1000);
    })
    .catch(error => {
      Swal.fire({
        title: 'Error',
        text: 'No se pudo exportar: ' + error.message,
        icon: 'error',
        confirmButtonText: 'Aceptar'
      });
    });
  };

});
