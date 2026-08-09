/**
 * Page Empresas List - Fixed Translations
 */

'use strict';

document.addEventListener('DOMContentLoaded', function (e) {
  const dt_empresas_table = document.querySelector('.datatables-empresas');
  let dt_empresas;
  const FD = window.FiltrosDinamicosListar;

  let filtroFiskalyReloadTimer = null;

  function onFiltroFiskalyChange() {
    if (filtroFiskalyReloadTimer) {
      clearTimeout(filtroFiskalyReloadTimer);
    }
    filtroFiskalyReloadTimer = setTimeout(function () {
      if (window.dt_empresas) {
        window.dt_empresas.ajax.reload(null, false);
      }
    }, 150);
  }

  function attachFiltroReload(select) {
    if (!select || !FD) {
      return;
    }
    FD.registerSelect(select);
    FD.initSelect2(select, onFiltroFiskalyChange);
  }

  function createFilterSelect(containerClass, selectId, placeholder, options) {
    const container = document.querySelector(containerClass);
    if (!container) {
      return null;
    }
    const select = document.createElement('select');
    select.id = selectId;
    select.className = 'form-select select2-filter text-capitalize form-select-sm select2-custom';
    let html = '<option value="">' + placeholder + '</option>';
    (options || []).forEach(function (opt) {
      html += '<option value="' + opt.value + '">' + opt.label + '</option>';
    });
    select.innerHTML = html;
    container.appendChild(select);
    attachFiltroReload(select);
    return select;
  }

  createFilterSelect('.fiskaly_factura_digital', 'filtro_factura_digital', 'Factura digital', [
    { value: 'true', label: 'Sí' },
    { value: 'false', label: 'No' }
  ]);

  createFilterSelect('.fiskaly_region_regimen', 'filtro_region_regimen', 'Región régimen', [
    { value: 'false', label: 'Ninguna' },
    { value: 'General', label: 'General' },
    { value: 'Verifactu', label: 'Verifactu' },
    { value: 'TicketBAIBizkaia', label: 'TicketBAI Bizkaia' },
    { value: 'TicketBAIAlava', label: 'TicketBAI Álava' },
    { value: 'TicketBAIGipuzkua', label: 'TicketBAI Gipuzkoa' }
  ]);

  createFilterSelect('.fiskaly_tipo_api', 'filtro_tipo_api', 'Tipo API', [
    { value: 'test', label: 'Test' },
    { value: 'produccion', label: 'Producción' }
  ]);

  if (FD && typeof FD.finalize === 'function') {
    FD.finalize();
  }

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
        // columns according to JSON
        { data: 0 }, // ID de la empresa
        { data: 1 }, // Nombre de la empresa
        { data: 2 }, // Dirección
        { data: 3 }, // Población
        { data: 4 }, // Provincia
        { data: 5 }, // Teléfono
        { data: 6 }, // CIF
        { data: 7 }, // Factura digital
        { data: 8 }, // Región régimen
        { data: 9 }  // Tipo API
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
              return '<span class="text-muted">Sin dirección</span>';
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
              return '<span class="text-muted">Sin población</span>';
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
              return '<span class="text-muted">Sin provincia</span>';
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
              return '<span class="text-muted">Sin teléfono</span>';
            }
          }
        },
        {
          // CIF
          targets: 6,
          render: function (data, type, full, meta) {
            const cif = data;
            if (cif && cif !== 'Sin CIF') {
              return '<span class="fw-semibold">' + cif + '</span>';
            } else {
              return '<span class="text-muted">Sin CIF</span>';
            }
          }
        },
        {
          // Factura digital
          targets: 7,
          render: function (data) {
            const val = String(data || '').toLowerCase();
            const activa = val === 'true' || val === '1';
            const badgeClass = activa ? 'bg-label-success' : 'bg-label-secondary';
            const texto = activa ? 'Sí' : 'No';
            return '<span class="badge ' + badgeClass + ' rounded-pill">' + texto + '</span>';
          }
        },
        {
          // Región régimen
          targets: 8,
          render: function (data) {
            const val = String(data || '').trim();
            if (!val || val === '—' || val.toLowerCase() === 'false') {
              return '<span class="text-muted">Ninguna</span>';
            }
            return '<span class="fw-semibold">' + val + '</span>';
          }
        },
        {
          // Tipo API
          targets: 9,
          render: function (data) {
            const val = String(data || '').trim().toLowerCase();
            if (!val || val === '—') {
              return '<span class="text-muted">N/A</span>';
            }
            const badgeClass = val === 'produccion' ? 'bg-label-primary' : 'bg-label-warning';
            const texto = val === 'produccion' ? 'Producción' : (val === 'test' ? 'Test' : data);
            return '<span class="badge ' + badgeClass + ' rounded-pill">' + texto + '</span>';
          }
        }
      ],

      order: [[1, 'asc']], // Ordenar por nombre de empresa (columna 1)
      
      // Hacer las filas clickeables
      createdRow: function(row, data, dataIndex) {
        const empresaId = data[0]; // ID de la empresa
        $(row).css('cursor', 'pointer');
        $(row).on('click', function(e) {
          // Evitar redirección si se hace click en un enlace o botón dentro de la fila
          if (!$(e.target).closest('a, button').length) {
            window.location.href = 'fiskaly_file_manager.php?id=' + empresaId;
          }
        });
      },
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
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
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
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
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
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
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
        url: 'parts/fiskaly_manager/listar/load_list.php',
        type: 'POST',
        data: function (d) {
          const fd = document.getElementById('filtro_factura_digital');
          const rr = document.getElementById('filtro_region_regimen');
          const ta = document.getElementById('filtro_tipo_api');
          d.filtro_factura_digital = fd ? fd.value : '';
          d.filtro_region_regimen = rr ? rr.value : '';
          d.filtro_tipo_api = ta ? ta.value : '';
          return d;
        },
        dataSrc: function (json) {
          if (json && json.error) {
            console.error('Error load_list:', json.error);
            return [];
          }
          return json.data || [];
        },
        error: function (xhr, error, thrown) {
          console.error('Error AJAX:', error, thrown, xhr.responseText);
        }
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

