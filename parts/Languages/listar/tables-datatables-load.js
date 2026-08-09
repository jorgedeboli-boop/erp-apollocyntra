/**
 * Page Idiomas List - Fixed Translations
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_idiomas_table = document.querySelector('.datatables-idiomas');

  // Variable global para DataTable
  let dt_idiomas;

  // Idiomas datatable
  if (dt_idiomas_table) {
    dt_idiomas = new DataTable(dt_idiomas_table, {
      processing: true, // Mostrar indicador de procesamiento
      serverSide: true, // Procesar en el servidor para grandes volúmenes
      deferRender: true, // Mejorar rendimiento con grandes volúmenes
      searchDelay: 500, // Delay de 500ms para búsquedas
      timeout: 60000, // Timeout de 60 segundos para peticiones grandes
      
      language: DATATABLES_SPANISH,
      columns: [
        // columns according to JSON
        { data: 0 }, // ID del idioma
        { data: 1 }, // Código
        { data: 2 }, // Descripción
        { data: 3 }, // País
        { data: 4 }, // Estado
        { data: 5 }  // Acciones
      ],
      
      columnDefs: [
        {
          // Código
          targets: 1,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            if (typeof data === 'string' && data) {
              return '<span class="fw-medium text-heading">' + data + '</span>';
            } else {
              return '<span class="text-muted">Sin código</span>';
            }
          }
        },
        {
          // Descripción
          targets: 2,
          render: function (data, type, full, meta) {
            const descripcion = data;
            if (descripcion && descripcion !== 'Sin descripción') {
              return '<span class="fw-semibold">' + descripcion + '</span>';
            } else {
              return '<span class="text-muted"><i class="icon-base ri ri-global-line me-1"></i>Sin descripción</span>';
            }
          }
        },
        {
          // País
          targets: 3,
          render: function (data, type, full, meta) {
            const pais = data;
            if (pais && pais !== 'Sin país') {
              return '<span class="fw-semibold">' + pais + '</span>';
            } else {
              return '<span class="text-muted"><i class="icon-base ri ri-map-pin-line me-1"></i>Sin país</span>';
            }
          }
        },
        {
          // Estado
          targets: 4,
          render: function (data, type, full, meta) {
            let badgeClass = 'bg-label-secondary';
            let iconClass = 'ri-question-line';
            
            if (data === 'true') {
              badgeClass = 'bg-label-success';
              iconClass = 'ri-check-line';
            } else if (data === 'false') {
              badgeClass = 'bg-label-danger';
              iconClass = 'ri-close-line';
            }
            
            return '<span class="badge ' + badgeClass + '"><i class="icon-base ri ' + iconClass + ' me-1"></i>' + (data === 'true' ? 'Activo' : 'Inactivo') + '</span>';
          }
        },
        {
          targets: 5,
          title: 'Acciones',
          searchable: false,
          orderable: false,
          render: (data, type, full, meta) => {
            const idiomaId = full[0]; // ID del idioma
            
            return `
              <div class="d-flex align-items-center">
                <a href="Language.php?id=${idiomaId}" class="btn btn-icon btn-text-secondary rounded-pill me-1" title="Ver Language">
                  <i class="icon-base ri ri-eye-line icon-md"></i>
                </a>
                <div class="dropdown d-inline-block">
                  <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="icon-base ri ri-more-2-line icon-md"></i>
                  </a>
                  <div class="dropdown-menu dropdown-menu-end m-0">
                    <a href="editar_Language.php?id=${idiomaId}" class="dropdown-item">
                      <i class="icon-base ri ri-pencil-line me-2"></i>
                      <span class="align-middle">Editar</span>
                    </a>
                    <a href="javascript:;" class="dropdown-item" onclick="eliminarLanguage(${idiomaId})">
                      <i class="icon-base ri ri-delete-bin-line me-2"></i>
                      <span class="align-middle">Eliminar</span>
                    </a>
                  </div>
                </div>
              </div>
            `;
          }
        }
      ],

      order: [[1, 'asc']], // Ordenar por código de idioma
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
                        columns: [0, 1, 2, 3, 4],
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
                        columns: [0, 1, 2, 3, 4],
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
                      extend: 'copy',
                      text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: [0, 1, 2, 3, 4],
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
                  text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-none d-sm-inline-block">Nuevo Language</span>',
                  className: 'add-new btn btn-primary',
                  action: function () {
                    window.location.href = 'crear_Language.php';
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
        url: 'parts/Languages/listar/load_list.php',
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
              return 'Detalles de ' + data[1]; // Código del idioma
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

    // Guardar referencia global para poder recargar desde otras funciones
    window.dt_idiomas = dt_idiomas;

    // REMOVIDO EL SETTIMEOUT QUE CAUSABA CONFLICTOS
    // Ya no necesitamos cambiar manualmente los textos porque están configurados en 'language'

    //? The 'delete-record' class is necessary for the functionality of the following code.
    function deleteRecord(event) {
      let row = document.querySelector('.dtr-expanded');
      if (event) {
        row = event.target.parentElement.closest('tr');
      }
      if (row) {
        dt_idiomas.row(row).remove().draw();
      }
    }

    function bindDeleteEvent() {
      const idiomasListTable = document.querySelector('.datatables-idiomas');
      const modal = document.querySelector('.dtr-bs-modal');

      if (idiomasListTable && idiomasListTable.classList.contains('collapsed')) {
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
        const tableBody = idiomasListTable?.querySelector('tbody');
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

});

// Función para eliminar Language
function eliminarLanguage(idiomaId) {
  Swal.fire({
    title: '¿Eliminar idioma?',
    text: '¿Estás seguro de que quieres eliminar este idioma? Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then((result) => {
    if (result.isConfirmed) {
      // Mostrar loading
      Swal.fire({
        title: 'Eliminando...',
        text: 'Por favor espera mientras se elimina el idioma',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      // Hacer la petición AJAX para eliminar
      fetch('parts/Languages/listar/eliminar_idioma.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + idiomaId
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            title: '¡Idioma eliminado!',
            text: 'El idioma se ha eliminado correctamente',
            icon: 'success',
            confirmButtonColor: '#007bff'
          }).then(() => {
            // Recargar la tabla
            if (window.dt_idiomas) {
              window.dt_idiomas.ajax.reload();
            }
            // Recargar estadísticas
            if (typeof cargarEstadisticas === 'function') {
              cargarEstadisticas();
            }
          });
        } else {
          Swal.fire({
            title: 'Error',
            text: data.message || 'No se pudo eliminar el idioma',
            icon: 'error',
            confirmButtonColor: '#dc3545'
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          title: 'Error',
          text: 'Ocurrió un error al eliminar el idioma',
          icon: 'error',
          confirmButtonColor: '#dc3545'
        });
      });
    }
  });
}
