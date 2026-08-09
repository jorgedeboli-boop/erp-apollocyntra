/**
 * Page Empresas List - Fixed Translations
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_empresas_table = document.querySelector('.datatables-empresas');

  // Variable global para DataTable
  let dt_empresas;

  // Empresas datatable
  if (dt_empresas_table) {
    dt_empresas = new DataTable(dt_empresas_table, {
      processing: true, // Mostrar indicador de procesamiento
      serverSide: true, // Procesar en el servidor para grandes volúmenes
      deferRender: true, // Mejorar rendimiento con grandes volúmenes
      searchDelay: 500, // Delay de 500ms para búsquedas
      timeout: 60000, // Timeout de 60 segundos para peticiones grandes
      
      language: DATATABLES_SPANISH,
      columns: [
        { data: 0 }, // ID de la empresa
        { data: 1 }, // Nombre de la empresa
        { data: 2 }, // Dirección
        { data: 3 }, // Población
        { data: 4 }, // Provincia
        { data: 5 }, // Teléfono
        { data: 6 }  // CIF
      ],
      
      columnDefs: [
        {
          // Nombre Empresa column
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
          // Dirección
          targets: 2,
          render: function (data, type, full, meta) {
            const direccion = data;
            if (direccion && direccion !== 'Sin dirección') {
              return '<span class="fw-semibold">' + direccion + '</span>';
            } else {
              return '<span class="text-muted"><i class="icon-base ri ri-map-pin-line me-1"></i>Sin dirección</span>';
            }
          }
        },
        {
          // Población
          targets: 3,
          render: function (data, type, full, meta) {
            const poblacion = data;
            if (poblacion && poblacion !== 'Sin población') {
              return '<span class="fw-semibold">' + poblacion + '</span>';
            } else {
              return '<span class="text-muted"><i class="icon-base ri ri-map-pin-line me-1"></i>Sin población</span>';
            }
          }
        },
        {
          // Provincia
          targets: 4,
          render: function (data, type, full, meta) {
            const provincia = data;
            if (provincia && provincia !== 'Sin provincia') {
              return '<span class="fw-semibold">' + provincia + '</span>';
            } else {
              return '<span class="text-muted"><i class="icon-base ri ri-map-pin-line me-1"></i>Sin provincia</span>';
            }
          }
        },
        {
          // Teléfono
          targets: 5,
          render: function (data, type, full, meta) {
            const telefono = data;
            if (telefono && telefono !== 'Sin teléfono') {
              return '<span class="fw-semibold"><i class="icon-base ri ri-phone-line me-1"></i>' + telefono + '</span>';
            } else {
              return '<span class="text-muted"><i class="icon-base ri ri-phone-line me-1"></i>Sin teléfono</span>';
            }
          }
        },
        {
          // CIF
          targets: 6,
          render: function (data, type, full, meta) {
            const cif = data;
            if (cif && cif !== 'Sin CIF') {
              return '<span class="fw-semibold"><i class="icon-base ri ri-id-card-line me-1"></i>' + cif + '</span>';
            } else {
              return '<span class="text-muted"><i class="icon-base ri ri-id-card-line me-1"></i>Sin CIF</span>';
            }
          }
        }
      ],

      order: [[1, 'asc']], // Ordenar por nombre de empresa
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
                        columns: [0, 1, 2, 3, 4, 5, 6],
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
                        columns: [0, 1, 2, 3, 4, 5, 6],
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
                        columns: [0, 1, 2, 3, 4, 5, 6],
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
                  text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-none d-sm-inline-block">Nueva Empresa</span>',
                  className: 'add-new btn btn-primary',
                  action: function () {
                    window.location.href = 'crear_empresa.php';
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
        url: 'parts/empresas/listar/load_list.php',
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
      
      createdRow: function (row, data) {
        const empresaId = data[0];
        $(row).css('cursor', 'pointer');
        $(row).on('click', function (e) {
          if (!$(e.target).closest('a, button').length) {
            window.location.href = 'empresa.php?id=' + empresaId;
          }
        });
      },

      // For responsive popup
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Detalles de ' + data[1]; // Nombre de la empresa
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
    window.dt_empresas = dt_empresas;

    // REMOVIDO EL SETTIMEOUT QUE CAUSABA CONFLICTOS
    // Ya no necesitamos cambiar manualmente los textos porque están configurados en 'language'

    //? The 'delete-record' class is necessary for the functionality of the following code.
    function deleteRecord(event) {
      let row = document.querySelector('.dtr-expanded');
      if (event) {
        row = event.target.parentElement.closest('tr');
      }
      if (row) {
        dt_empresas.row(row).remove().draw();
      }
    }

    function bindDeleteEvent() {
      const empresasListTable = document.querySelector('.datatables-empresas');
      const modal = document.querySelector('.dtr-bs-modal');

      if (empresasListTable && empresasListTable.classList.contains('collapsed')) {
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
        const tableBody = empresasListTable?.querySelector('tbody');
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
