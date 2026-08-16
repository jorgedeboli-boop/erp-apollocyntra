/**
 * Page User List - Fixed Translations
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_user_table = document.querySelector('.datatables-users'),
    userView = 'app-user-view-account.html',
    statusObj = {
      1: { title: 'Pending', class: 'bg-label-warning' },
      2: { title: 'Active', class: 'bg-label-success' },
      3: { title: 'Inactive', class: 'bg-label-secondary' }
    };
  var select2 = $('.select2');

  if (select2.length) {
    var $this = select2;
    select2Focus($this);
    $this.select2({
      dropdownParent: $this.parent()
    });
  }

  // Users datatable
  if (dt_user_table) {
    const dt_user = new DataTable(dt_user_table, {
      ajax: 'parts/usuarios/listar/load_list.php', // Cargar usuarios desde PHP
      
      // CONFIGURACIÓN COMPLETA DE IDIOMA EN ESPAÑOL
      
      language: DATATABLES_SPANISH,
      
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 }
      ],

      createdRow: function (row, data) {
        const userId = data[0];
        $(row).css('cursor', 'pointer');
        $(row).on('click', function (e) {
          if (!$(e.target).closest('a, button').length) {
            window.location.href = 'usuario.php?id=' + userId;
          }
        });
      },
      
      columnDefs: [
        {
          targets: 0,
          render: function (data) {
            const id = parseInt(data, 10);
            if (!id) {
              return '—';
            }
            return (
              '<a href="usuario.php?id=' +
              id +
              '" class="fw-semibold text-primary text-decoration-none">' +
              id +
              '</a>'
            );
          }
        },
        {
          // User column - Nombre completo + username
          targets: 1,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            /*
            console.log('Renderizando columna User, data:', data);
            console.log('Tipo de data:', typeof data);
            */
            if (typeof data === 'object' && data.full_name && data.username) {
              var fullName = data.full_name;
              var username = data.username;
              
              var row_output =
                '<div class="d-flex justify-content-start align-items-center user-name">' +
                '<div class="d-flex flex-column">' +
                '<span class="fw-medium text-heading">' +
                fullName +
                '</span>' +
                '<small class="text-muted">' +
                username +
                '</small>' +
                '</div>' +
                '</div>';
              return row_output;
            } else {
              /*console.log('Data no es un objeto válido, usando fallback');*/
              return '<span class="text-muted">Error en datos</span>';
            }
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            // data es un objeto con {nombre: 'Administrador', color: 'danger'}
            var roleName = (data && typeof data === 'object' && data.nombre) ? data.nombre : 'Sin privilegio';
            var roleColor = (data && typeof data === 'object' && data.color) ? data.color : 'secondary';

            if (type === 'filter' || type === 'sort' || type === 'type') {
              return roleName;
            }
            
            return (
              "<span class='badge bg-label-" + roleColor + " rounded-pill'>" +
              '<i class="icon-base ri ri-shield-user-line me-1"></i>' +
              roleName +
              '</span>'
            );
          }
        },
        {
          // Última conexión
          targets: 2,
          render: function (data, type, full, meta) {
            const ultimaConexion = data;
            
            if (ultimaConexion === '---------') {
              return '<span class="text-muted"><i class="icon-base ri ri-time-line me-1"></i>---------</span>';
            } else {
              return '<span class="text-success"><i class="icon-base ri ri-check-line me-1"></i>' + ultimaConexion + '</span>';
            }
          }
        },
        {
          // User Status
          targets: 4,
          render: function (data, type, full, meta) {
            const status = data;
            
            var statusColors = {
              'Habilitado': 'success',
              'Sin acceso': 'danger'
            };
            
            var color = statusColors[status] || 'secondary';
            
            return (
              '<span class="badge bg-label-' + color + ' rounded-pill">' +
              '<i class="icon-base ri ri-' + (status === 'Habilitado' ? 'checkbox-circle-fill' : 'close-circle-line') + ' me-1"></i>' +
              status +
              '</span>'
            );
          }
        },
      ],

      order: [[1, 'desc']],
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
                                                            {
                      extend: 'excel',
                      text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [1, 2, 3, 4],
                        format: {
                          body: function (inner, coldex, rowdex) {
                            if (inner.length <= 0) return inner;
                            const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                            let result = '';
                            el.forEach(item => {
                              if (item.classList && item.classList.contains('user-name')) {
                                result += item.lastChild.firstChild.textContent;
                              } else {
                                result += item.textContent || item.innerText || '';
                              }
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
                        columns: [1, 2, 3, 4],
                        format: {
                          body: function (inner, coldex, rowdex) {
                            if (inner.length <= 0) return inner;
                            const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                            let result = '';
                            el.forEach(item => {
                              if (item.classList && item.classList.contains('user-name')) {
                                result += item.lastChild.firstChild.textContent;
                              } else {
                                result += item.textContent || item.innerText || '';
                              }
                            });
                            return result;
                          }
                        }
                      }
                    },
                    {
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [1, 2, 3, 4],
                        format: {
                          body: function (inner, coldex, rowdex) {
                            if (inner.length <= 0) return inner;
                            const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                            let result = '';
                            el.forEach(item => {
                              if (item.classList && item.classList.contains('user-name')) {
                                result += item.lastChild.firstChild.textContent;
                              } else {
                                result += item.textContent || item.innerText || '';
                              }
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
                  text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-none d-sm-inline-block">Nuevo usuario</span>',
                  className: 'add-new btn btn-primary',
                  action: function () {
                    window.location.href = 'crear_usuario.php';
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
      },
      
      initComplete: function () {
        const api = this.api();

        // Verificar que Select2 esté disponible
        if (typeof $ === 'undefined' || !$.fn.select2) {
          console.warn('Select2 no está disponible, usando selects nativos');
          // Fallback a selects nativos si Select2 no está disponible
          this.initCompleteFallback(api);
          return;
        }

        // Helper function to create a select dropdown with Select2 and append options
        const getJerarquiaNombre = (data) => {
          if (data && typeof data === 'object' && data.nombre) {
            return data.nombre;
          }
          return typeof data === 'string' ? data : 'Sin privilegio';
        };

        const createFilter = (columnIndex, containerClass, selectId, defaultOptionText) => {
          const column = api.column(columnIndex);
          const select = document.createElement('select');
          select.id = selectId;
          select.className = 'form-select select2-filter text-capitalize select2-custom';
          select.innerHTML = `<option value="">${defaultOptionText}</option>`;
          document.querySelector(containerClass).appendChild(select);

          // Populate options based on unique column data
          const uniqueData = Array.from(new Set(column.data().toArray())).sort();
          uniqueData.forEach(d => {
            const option = document.createElement('option');
            option.value = d;
            option.textContent = d;
            select.appendChild(option);
          });

          // Initialize Select2 using template code
          var select2 = $(select);
          if (select2.length) {
            select2.each(function () {
              var $this = $(this);
              select2Focus($this);
              $this.select2({
                dropdownParent: $this.parent()
              });
            });
          }

          // Add event listener for filtering
          $(select).on('change', function() {
            const val = $(this).val() ? `^${$(this).val()}$` : '';
            column.search(val, true, false).draw();
          });
        };

        createFilter(4, '.user_estado', 'UserEstado', 'Seleccionar Estado');

        // Jerarquía filter with Select2
        const jerarquiaFilter = document.createElement('select');
        jerarquiaFilter.id = 'FilterJerarquia';
        jerarquiaFilter.className = 'form-select select2-filter text-capitalize select2-custom';
        jerarquiaFilter.innerHTML = '<option value="">Seleccionar Jerarquía</option>';
        document.querySelector('.user_jerarquia').appendChild(jerarquiaFilter);

        const jerarquiaColumn = api.column(3);
        const uniqueJerarquiaData = Array.from(
          new Set(jerarquiaColumn.data().toArray().map(getJerarquiaNombre))
        ).sort();
        uniqueJerarquiaData.forEach(nombre => {
          const option = document.createElement('option');
          option.value = nombre;
          option.textContent = nombre;
          option.className = 'text-capitalize';
          jerarquiaFilter.appendChild(option);
        });

        // Initialize Select2 for Jerarquía using template code
        var select2 = $(jerarquiaFilter);
        if (select2.length) {
          select2.each(function () {
            var $this = $(this);
            select2Focus($this);
            $this.select2({
              dropdownParent: $this.parent()
            });
          });
        }

        // Add event listener for filtering
        $(jerarquiaFilter).on('change', function() {
          const val = $(this).val() ? `^${$(this).val()}$` : '';
          api.column(3).search(val, true, false).draw();
        });
      },

      // Fallback function para selects nativos si Select2 no está disponible
      initCompleteFallback: function(api) {
        const getJerarquiaNombre = (data) => {
          if (data && typeof data === 'object' && data.nombre) {
            return data.nombre;
          }
          return typeof data === 'string' ? data : 'Sin privilegio';
        };

        // Helper function to create a select dropdown and append options
        const createFilter = (columnIndex, containerClass, selectId, defaultOptionText) => {
          const column = api.column(columnIndex);
          const select = document.createElement('select');
          select.id = selectId;
          select.className = 'form-select text-capitalize';
          select.innerHTML = `<option value="">${defaultOptionText}</option>`;
          document.querySelector(containerClass).appendChild(select);

          // Add event listener for filtering
          select.addEventListener('change', () => {
            const val = select.value ? `^${select.value}$` : '';
            column.search(val, true, false).draw();
          });

          // Populate options based on unique column data
          const uniqueData = Array.from(new Set(column.data().toArray())).sort();
          uniqueData.forEach(d => {
            const option = document.createElement('option');
            option.value = d;
            option.textContent = d;
            select.appendChild(option);
          });
        };

        createFilter(4, '.user_estado', 'UserEstado', 'Seleccionar Estado');

        // Jerarquía filter
        const jerarquiaFilter = document.createElement('select');
        jerarquiaFilter.id = 'FilterJerarquia';
        jerarquiaFilter.className = 'form-select text-capitalize';
        jerarquiaFilter.innerHTML = '<option value="">Seleccionar Jerarquía</option>';
        document.querySelector('.user_jerarquia').appendChild(jerarquiaFilter);
        jerarquiaFilter.addEventListener('change', () => {
          const val = jerarquiaFilter.value ? `^${jerarquiaFilter.value}$` : '';
          api.column(3).search(val, true, false).draw();
        });

        const jerarquiaColumn = api.column(3);
        const uniqueJerarquiaData = Array.from(
          new Set(jerarquiaColumn.data().toArray().map(getJerarquiaNombre))
        ).sort();
        uniqueJerarquiaData.forEach(nombre => {
          const option = document.createElement('option');
          option.value = nombre;
          option.textContent = nombre;
          option.className = 'text-capitalize';
          jerarquiaFilter.appendChild(option);
        });
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
        dt_user.row(row).remove().draw();
      }
    }

    function bindDeleteEvent() {
      const userListTable = document.querySelector('.datatables-users');
      const modal = document.querySelector('.dtr-bs-modal');

      if (userListTable && userListTable.classList.contains('collapsed')) {
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
        const tableBody = userListTable?.querySelector('tbody');
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



  // Función para bloquear/desbloquear usuarios
  window.bloquearUsuario = function(userId) {
    // Obtener el texto actual del botón para determinar la acción
    const boton = event.target.closest('.dropdown-item');
    const textoActual = boton.querySelector('.align-middle').textContent;
    const esBloquear = textoActual === 'Bloquear';
    
    const titulo = esBloquear ? '¿Bloquear usuario?' : '¿Desbloquear usuario?';
    const texto = esBloquear ? 
      '¿Estás seguro de que quieres bloquear este usuario? No podrá acceder al sistema.' : 
      '¿Estás seguro de que quieres desbloquear este usuario? Podrá acceder al sistema nuevamente.';
    const icono = esBloquear ? 'warning' : 'question';
    
    Swal.fire({
      title: titulo,
      text: texto,
      icon: icono,
      showCancelButton: true,
      confirmButtonText: esBloquear ? 'Sí, bloquear' : 'Sí, desbloquear',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: esBloquear ? '#dc3545' : '#198754',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: 'Procesando...',
          text: 'Actualizando estado del usuario',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        // Enviar petición AJAX
        fetch('parts/usuarios/listar/toggle_estado.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'id_usuario=' + userId
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
            const estadoCell = row.querySelector('td:nth-child(6)'); // Columna de estado
            
            if (estadoCell) {
              const nuevoEstado = data.estado_texto;
              const color = nuevoEstado === 'Habilitado' ? 'success' : 'danger';
              const icono = nuevoEstado === 'Habilitado' ? 'checkbox-circle-fill' : 'close-circle-line';
              
              estadoCell.innerHTML = `
                <span class='badge bg-label-${color} rounded-pill'>
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
            text: 'Ha ocurrido un error al actualizar el estado del usuario: ' + error.message,
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#dc3545'
          });
        });
      }
    });
  };


});