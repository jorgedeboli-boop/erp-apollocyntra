/**
 * Page Gastos List - Fixed Translations
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  const dt_gastos_table = document.querySelector('.datatables-gastos');
  let dt_gastos;

  function obtenerValorFiltro(id) {
    const el = document.getElementById(id);
    if (!el) {
      return '';
    }
    if (typeof jQuery !== 'undefined' && jQuery(el).data('select2')) {
      return jQuery(el).val() || '';
    }
    return el.value || '';
  }

  function recargarTabla() {
    if (window.dt_gastos) {
      window.dt_gastos.ajax.reload();
    }
    if (typeof cargarEstadisticas === 'function') {
      cargarEstadisticas();
    }
  }

  window.recargarTablaGastos = recargarTabla;

  if (window.GastosFiltros) {
    window.GastosFiltros.setOnChange(function () {
      recargarTabla();
    });
    window.GastosFiltros.setOnReady(function () {
      inicializarFiltrosFecha();
    });
  }

  function inicializarDataTable() {
    // Gastos datatable
    if (dt_gastos_table) {
      dt_gastos = new DataTable(dt_gastos_table, {
      processing: true, // Mostrar indicador de procesamiento
      serverSide: true, // Procesar en el servidor para grandes volúmenes
      deferRender: true, // Mejorar rendimiento con grandes volúmenes
      searchDelay: 500, // Delay de 500ms para búsquedas
      timeout: 60000, // Timeout de 60 segundos para peticiones grandes

      language: DATATABLES_SPANISH,

      // Configuración AJAX
      ajax: {
        url: 'parts/gastos/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          d.filtro_empresa = obtenerValorFiltro('filtro_empresa');
          d.filtro_proveedor = obtenerValorFiltro('filtro_proveedor');
          d.filtro_estado = obtenerValorFiltro('filtro_estado');
          d.filtro_tipo_gasto = obtenerValorFiltro('filtro_tipo_gasto');
          d.filtro_forma_pago = obtenerValorFiltro('filtro_forma_pago');
          d.filtro_fecha_desde = obtenerValorFiltro('filtro_fecha_desde');
          d.filtro_fecha_hasta = obtenerValorFiltro('filtro_fecha_hasta');
        },
        error: function(xhr, error, thrown) {
          console.error('Error en DataTable:', error, thrown);
          // Mostrar mensaje de error al usuario
          if (xhr.status === 500) {
            alert('Error del servidor. Por favor, recarga la página.');
          } else if (xhr.status === 401) {
            alert('Sesión expirada. Por favor, inicia sesión nuevamente.');
            window.location.href = 'login.php';
          }
        }
      },
      
      // Configuración de columnas
      columns: [
        { data: 0, title: 'ID', width: '60px' },
        { data: 5, title: 'DESCRIPCIÓN', width: '200px' },
        { data: 1, title: 'FECHA GASTO', width: '100px' },
        { data: 2, title: 'EMPRESA', width: '120px' },
        { data: 3, title: 'PROVEEDOR', width: '120px' },
        { data: 4, title: 'TIPO GASTO', width: '120px' },
        { data: 6, title: 'TOTAL', width: '100px' },
        { data: 7, title: 'ESTADO', width: '100px' }
      ],
      
      // Configuración de renderizado de columnas
      columnDefs: [
        {
          // ID
          targets: 0,
          render: function (data, type, full, meta) {
            return '<a href="gasto.php?id=' + data + '" class="fw-medium text-heading text-decoration-none">' + data + '</a>';
          }
        },
        {
          // Descripción
          targets: 1,
          render: function (data, type, full, meta) {
            if (data && data !== 'Sin descripción') {
              const descripcion = data.length > 50 ? data.substring(0, 50) + '...' : data;
              return '<span class="fw-medium" title="' + data + '">' + descripcion + '</span>';
            } else {
              return '<span class="text-muted">Sin descripción</span>';
            }
          }
        },
        {
          // Fecha
          targets: 2,
          render: function (data, type, full, meta) {
            return '<span class="fw-semibold">' + data + '</span>';
          }
        },
        {
          // Empresa
          targets: 3,
          render: function (data, type, full, meta) {
            if (data && data !== 'N/A') {
              return '<span class="fw-semibold">' + data + '</span>';
            } else {
              return '<span class="text-muted">N/A</span>';
            }
          }
        },
        {
          // Proveedor
          targets: 4,
          render: function (data, type, full, meta) {
            if (data && data !== 'N/A') {
              return '<span class="fw-semibold">' + data + '</span>';
            } else {
              return '<span class="text-muted">N/A</span>';
            }
          }
        },
        {
          // Tipo Gasto
          targets: 5,
          render: function (data, type, full, meta) {
            if (data && data !== 'N/A') {
              return '<span class="fw-semibold">' + data + '</span>';
            } else {
              return '<span class="text-muted">N/A</span>';
            }
          }
        },
        {
          // Total
          targets: 6,
          render: function (data, type, full, meta) {
            return '<span class="fw-bold text-success">' + data + '</span>';
          }
        },
        {
          // Estado
          targets: 7,
          render: function (data, type, full, meta) {
            let badgeClass = 'bg-label-secondary';
            let iconClass = 'ri-question-line';
            
            switch(data) {
              case 'pendiente':
                badgeClass = 'bg-label-warning';
                iconClass = 'ri-time-line';
                break;
              case 'pagado':
                badgeClass = 'bg-label-success';
                iconClass = 'ri-check-line';
                break;
              case 'cancelado':
                badgeClass = 'bg-label-danger';
                iconClass = 'ri-close-line';
                break;
            }
            
            return '<span class="badge ' + badgeClass + '"><i class="icon-base ri ' + iconClass + ' me-1"></i>' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
          }
        }
      ],
      
      // Configuración de ordenamiento
      order: [[0, 'desc']], // Por ID descendente
      
      // Configuración de paginación (igual que parts/lotes/listar)
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      
      // Configuración de responsive desactivada para evitar conflictos con dropdowns
      responsive: false,
      
      // Configuración de layout
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
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
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
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
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
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
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
          rowClass: 'row mx-0 justify-content-between w-100',
          features: ['info']
        },
        bottomEnd: 'paging'
      }
    });

    // Clic en fila → ficha del gasto (mismo criterio que listado de lotes)
    dt_gastos_table.addEventListener('click', function (e) {
      if (e.target.closest('a')) {
        return;
      }
      const row = e.target.closest('tbody tr');
      if (row && dt_gastos) {
        const rowData = dt_gastos.row(row).data();
        if (rowData && rowData[0]) {
          window.location.href = 'gasto.php?id=' + rowData[0];
        }
      }
    });

    // Hacer la tabla globalmente accesible
    window.dt_gastos = dt_gastos;

  }
  }

  function inicializarFiltrosFecha() {
    setTimeout(function () {
      const rangeEl = document.getElementById('rangeFechas');
      if (!rangeEl) {
        return;
      }

      document.getElementById('gasto_filtro_dia')?.addEventListener('click', function (e) {
        e.preventDefault();
        const fp = rangeEl._flatpickr;
        if (!fp) {
          return;
        }
        const d = new Date();
        d.setHours(0, 0, 0, 0);
        fp.setDate([d, d], true);
      });

      document.getElementById('gasto_filtro_mes')?.addEventListener('click', function (e) {
        e.preventDefault();
        const fp = rangeEl._flatpickr;
        if (!fp) {
          return;
        }
        const now = new Date();
        const desde = new Date(now.getFullYear(), now.getMonth(), 1);
        const hasta = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        fp.setDate([desde, hasta], true);
      });

      document.getElementById('gasto_filtro_todos')?.addEventListener('click', function (e) {
        e.preventDefault();
        const fp = rangeEl._flatpickr;
        if (fp) {
          fp.clear();
        }
        const fd = document.getElementById('filtro_fecha_desde');
        const fh = document.getElementById('filtro_fecha_hasta');
        if (fd) {
          fd.value = '';
        }
        if (fh) {
          fh.value = '';
        }
        rangeEl.value = '';
        recargarTabla();
      });
    }, 650);
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
  
  // Inicializar DataTable (filtros Select2 los gestiona filtros-gastos.js)
  inicializarDataTable();
  if (!window.GastosFiltros) {
    inicializarFiltrosFecha();
  }
});