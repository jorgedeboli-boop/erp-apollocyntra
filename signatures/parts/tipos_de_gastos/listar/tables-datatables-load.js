/**
 * Page Tipos de Gasto List - DataTables Implementation
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_tipos_gasto_table = document.querySelector('.datatables-tipos-gasto');

  // Variable global para DataTable
  let dt_tipos_gasto;

  // Tipos de gasto datatable
  if (dt_tipos_gasto_table) {
    dt_tipos_gasto = new DataTable(dt_tipos_gasto_table, {
      processing: true, // Mostrar indicador de procesamiento
      serverSide: true, // Procesar en el servidor para grandes volúmenes
      deferRender: true, // Mejorar rendimiento con grandes volúmenes
      searchDelay: 500, // Delay de 500ms para búsquedas
      timeout: 60000, // Timeout de 60 segundos para peticiones grandes
      
      language: DATATABLES_SPANISH,
      columns: [
        // columns according to JSON
        { data: 0 }, // ID del tipo de gasto
        { data: 1 }, // Nombre del tipo de gasto
        { data: 2 }  // Acciones
      ],
      
      columnDefs: [
        {
          // Nombre Tipo Gasto column
          targets: 1,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            if (typeof data === 'string' && data) {
              return '<span class="fw-medium text-heading">' + data + '</span>';
            } else {
              return '<span class="text-muted">Sin nombre</span>';
            }
          }
        },
        {
          targets: 2,
          title: 'Acciones',
          searchable: false,
          orderable: false,
          render: (data, type, full, meta) => {
            const tipoGastoId = data.id;
            
            return `
              <div class="d-flex align-items-center">
                <button type="button" class="btn btn-icon btn-text-secondary rounded-pill me-1" title="Editar tipo de gasto" onclick="editarTipoGasto(${tipoGastoId}, '${full[1].replace(/'/g, "\\'")}')">
                  <i class="icon-base ri ri-pencil-line icon-md"></i>
                </button>
                <button type="button" class="btn btn-icon btn-text-danger rounded-pill" title="Eliminar tipo de gasto" onclick="eliminarTipoGasto(${tipoGastoId})">
                  <i class="icon-base ri ri-delete-bin-line icon-md"></i>
                </button>
              </div>
            `;
          }
        }
      ],

      order: [[1, 'asc']], // Ordenar por nombre del tipo de gasto
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
                  className: 'btn buttons-collection btn-primary dropdown-toggle waves-effect',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                  buttons: [
                                                            {
                      extend: 'excel',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1],
                        format: {
                          body: function (inner, coldex, rowdex) {
                            if (inner.length <= 0) return inner;
                            const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                            let result = '';
                            el.forEach(item => {
                              result += item.textContent || item.innerText || '';
                            });
                            return result;
                          }
                        }
                      }
                    },
                    {
                      extend: 'pdf',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1],
                        format: {
                          body: function (inner, coldex, rowdex) {
                            if (inner.length <= 0) return inner;
                            const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                            let result = '';
                            el.forEach(item => {
                              result += item.textContent || item.innerText || '';
                            });
                            return result;
                          }
                        }
                      },
                      customize: function(doc) {
                        doc.pageOrientation = 'portrait';
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 11;
                        doc.styles.tableHeader.fillColor = '#2d4154';
                        doc.styles.tableHeader.bold = true;
                        doc.styles.tableHeader.color = 'white';
                        
                        // Cambiar el título del PDF
                        doc.content[0].text = 'Listado de Tipos de Gasto';
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
                          // Primera columna (ID) alineada a la izquierda
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
                      }
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1],
                        format: {
                          body: function (inner, coldex, rowdex) {
                            if (inner.length <= 0) return inner;
                            const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                            let result = '';
                            el.forEach(item => {
                              result += item.textContent || item.innerText || '';
                            });
                            return result;
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
            },
            {
              buttons: [
                {
                  text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-none d-sm-inline-block">Crear Tipo de Gasto</span>',
                  className: 'add-new btn btn-primary',
                  action: function () {
                    // Limpiar formulario y abrir modal
                    document.getElementById('formCrearTipoGasto').reset();
                    const modal = new bootstrap.Modal(document.getElementById('modalCrearTipoGasto'));
                    modal.show();
                  }
                }
              ]
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
        url: 'parts/tipos_de_gastos/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          return d;
        },
        dataSrc: function(json) {
          return json.data || [];
        },
        error: function(xhr, error, thrown) {
          console.error('Error AJAX:', error, thrown);
        }
      },
      
      // For responsive popup
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Detalles de ' + data[1]; // Nombre del tipo de gasto
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
  }

  // Función para editar tipo de gasto
  window.editarTipoGasto = function(id, nombre) {
    // Llenar el formulario de edición
    document.getElementById('editIdTipoGasto').value = id;
    document.getElementById('editNombreTipoGasto').value = nombre;
    
    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditarTipoGasto'));
    modal.show();
  };

  // Función para eliminar tipo de gasto
  window.eliminarTipoGasto = function(id) {
    Swal.fire({
      title: '¿Estás seguro?',
      text: 'Esta acción no se puede deshacer',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: 'Eliminando...',
          text: 'Por favor espere',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        // Enviar petición AJAX
        fetch('parts/tipos_de_gastos/listar/eliminar_tipo_gasto.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'id_tipo_gasto=' + id
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              title: '¡Eliminado!',
              text: data.message,
              icon: 'success',
              confirmButtonText: 'Aceptar'
            });
            // Recargar tabla
            dt_tipos_gasto.ajax.reload();
            
            // Recargar estadísticas
            if (typeof cargarEstadisticas === 'function') {
              cargarEstadisticas();
            }
          } else {
            throw new Error(data.message);
          }
        })
        .catch(error => {
          Swal.fire({
            title: 'Error',
            text: error.message || 'Error al eliminar el tipo de gasto',
            icon: 'error',
            confirmButtonText: 'Aceptar'
          });
        });
      }
    });
  };

  // Event listeners para los modales
  // Los event listeners se configuran después de que el DOM esté listo
  const btnCrear = document.getElementById('btnCrearTipoGasto');
    if (btnCrear) {
      btnCrear.addEventListener('click', function() {
        const nombre = document.getElementById('nombreTipoGasto').value.trim();
      
      if (!nombre) {
        Swal.fire({
          title: 'Error',
          text: 'Por favor ingrese un nombre para el tipo de gasto',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
        return;
      }

      // Mostrar loading
      this.disabled = true;
      this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creando...';

      // Enviar petición AJAX
      fetch('parts/tipos_de_gastos/listar/crear_tipo_gasto.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'nombre_tipo_gasto=' + encodeURIComponent(nombre)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            title: '¡Creado!',
            text: data.message,
            icon: 'success',
            confirmButtonText: 'Aceptar'
          });
          
          // Cerrar modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearTipoGasto'));
          modal.hide();
          
          // Limpiar formulario
          document.getElementById('formCrearTipoGasto').reset();
          
          // Recargar tabla
          dt_tipos_gasto.ajax.reload();
          
          // Recargar estadísticas
          if (typeof cargarEstadisticas === 'function') {
            cargarEstadisticas();
          }
        } else {
          throw new Error(data.message);
        }
      })
      .catch(error => {
        Swal.fire({
          title: 'Error',
          text: error.message || 'Error al crear el tipo de gasto',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      })
      .finally(() => {
        // Restaurar botón
        this.disabled = false;
        this.innerHTML = 'Crear';
      });
    });

    // Botón actualizar tipo de gasto
    document.getElementById('btnActualizarTipoGasto').addEventListener('click', function() {
      const id = document.getElementById('editIdTipoGasto').value;
      const nombre = document.getElementById('editNombreTipoGasto').value.trim();
      
      if (!nombre) {
        Swal.fire({
          title: 'Error',
          text: 'Por favor ingrese un nombre para el tipo de gasto',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
        return;
      }

      // Mostrar loading
      this.disabled = true;
      this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Actualizando...';

      // Enviar petición AJAX
      fetch('parts/tipos_de_gastos/listar/actualizar_tipo_gasto.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_tipo_gasto=' + id + '&nombre_tipo_gasto=' + encodeURIComponent(nombre)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            title: '¡Actualizado!',
            text: data.message,
            icon: 'success',
            confirmButtonText: 'Aceptar'
          });
          
          // Cerrar modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarTipoGasto'));
          modal.hide();
          
          // Recargar tabla
          dt_tipos_gasto.ajax.reload();
          
          // Recargar estadísticas
          if (typeof cargarEstadisticas === 'function') {
            cargarEstadisticas();
          }
        } else {
          throw new Error(data.message);
        }
      })
      .catch(error => {
        Swal.fire({
          title: 'Error',
          text: error.message || 'Error al actualizar el tipo de gasto',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      })
      .finally(() => {
        // Restaurar botón
        this.disabled = false;
        this.innerHTML = 'Actualizar';
      });
    });
  }
});