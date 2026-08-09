/**
 * Page Empresa Main - Sucursales DataTable
 */

'use strict';

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_sucursales_empresa_table = document.querySelector('.datatables-sucursales-empresa'),
    sucursalView = 'app-sucursal-view-account.html',
    statusObj = {
      1: { title: 'Pending', class: 'bg-label-warning' },
      2: { title: 'Active', class: 'bg-label-success' },
      3: { title: 'Inactive', class: 'bg-label-secondary' }
    };
  

  // Variable global para DataTable
  let dt_sucursales_empresa;

  // Sucursales de empresa datatable
  if (dt_sucursales_empresa_table) {
    dt_sucursales_empresa = new DataTable(dt_sucursales_empresa_table, {
      processing: true, // Mostrar indicador de procesamiento
      serverSide: true, // Procesar en el servidor para grandes volúmenes
      deferRender: true, // Mejorar rendimiento con grandes volúmenes
      searchDelay: 500, // Delay de 500ms para búsquedas
      timeout: 60000, // Timeout de 60 segundos para peticiones grandes
      
      language: DATATABLES_SPANISH,
      columns: [
        // columns according to JSON
        { data: 0 }, // ID de la sucursal
        { data: 1 }, // Nombre de la sucursal
        { data: 2 }, // Nombre corto
        { data: 3 }, // Población
        { data: 4 }, // Provincia
        { data: 5 }, // Teléfono
        { data: 6 }, // Estado
        { data: 7 }  // Acciones
      ],
      
      columnDefs: [
        {
          // Nombre Sucursal column
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
          // Nombre Corto
          targets: 2,
          render: function (data, type, full, meta) {
            const nombreCorto = data;
            if (nombreCorto && nombreCorto !== 'N/A') {
              return '<span class="fw-semibold">' + nombreCorto + '</span>';
            } else {
              return '<span class="text-muted">Sin nombre corto</span>';
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
            if (telefono && telefono !== 'Sin teléfono' && telefono !== '0' && telefono !== 0) {
              return '<span class="fw-semibold"><i class="icon-base ri ri-phone-line me-1"></i>' + telefono + '</span>';
            } else {
              return '<span class="text-muted"><i class="icon-base ri ri-phone-line me-1"></i>Sin teléfono</span>';
            }
          }
        },
        {
          // Estado
          targets: 6,
          render: function (data, type, full, meta) {
            const estado = data;
            if (estado) {
              if (estado.toLowerCase() === 'habilitada') {
                return '<span class="badge bg-label-success rounded-pill"><i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Habilitada</span>';
              } else if (estado.toLowerCase() === 'deshabilitada') {
                return '<span class="badge bg-label-danger rounded-pill"><i class="icon-base ri ri-prohibited-line me-1"></i>Deshabilitada</span>';
              } else {
                return '<span class="badge bg-label-warning rounded-pill"><i class="icon-base ri ri-alert-line me-1"></i>' + estado + '</span>';
              }
            } else {
              return '<span class="badge bg-label-secondary rounded-pill"><i class="icon-base ri ri-question-line me-1"></i>Sin estado</span>';
            }
          }
        },
        {
          // Acciones
          targets: 7,
          orderable: false,
          searchable: false,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            const idSucursal = full[0]; // ID de la sucursal
            
            return `
              <a href="sucursal.php?id=${idSucursal}" 
                 class="btn btn-icon btn-text-secondary rounded-pill" 
                 target="_blank"
                 data-bs-toggle="tooltip" 
                 data-bs-placement="top" 
                 title="Ver Sucursal">
                <i class="icon-base ri ri-eye-line icon-md"></i>
              </a>
            `;
          }
        },

      ],
      
      // Configuración de AJAX
      ajax: {
        url: 'parts/fiskaly_manager/main/get_sucursales_empresa.php',
        type: 'POST',
        data: function (d) {
          // Agregar el ID de la empresa a la petición
          d.id_empresa = window.idEmpresa || 0;
          return d;
        },
        error: function (xhr, error, thrown) {
          console.error('Error en DataTable:', error);
          // Mostrar mensaje de error al usuario
          if (dt_sucursales_empresa_table) {
            dt_sucursales_empresa_table.innerHTML = `
              <div class="alert alert-danger">
                <i class="icon-base ri ri-error-warning-line me-2"></i>
                Error al cargar las sucursales. Intente nuevamente.
              </div>
            `;
          }
        }
      },
      
      // Configuración de paginación
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      
      // Configuración de ordenamiento
      order: [[1, 'asc']], // Ordenar por nombre de sucursal ascendente
      
      // Configuración de búsqueda
      search: {
        smart: true,
        regex: false,
        caseInsensitive: true
      },
      
      // Configuración de responsive
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Detalles de ' + data[1];
            }
          }),
          renderer: $.fn.dataTable.Responsive.renderer.tableAll({
            tableClass: 'table'
          })
        }
      },
      
      // Configuración de dom
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>' +
           '<"row"<"col-sm-12"tr>>' +
           '<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      
      // Configuración de idioma personalizado
      language: DATATABLES_SPANISH
    });
  }
});



// Función para recargar el DataTable
function recargarSucursalesEmpresa() {
  if (window.dt_sucursales_empresa) {
    window.dt_sucursales_empresa.ajax.reload(null, false);
  }
}

/**
 * Page Empresa Main - Invoices Fiskaly DataTable
 */

// Variable global para DataTable
let dt_invoices_fiskaly;
let dt_invoices_fiskaly_table;

// Función para obtener el primer client_id disponible
function obtenerPrimerClientId() {
  const tbody = document.getElementById('clients');
  if (tbody && tbody.children.length > 0) {
    const firstRow = tbody.children[0];
    const firstCell = firstRow.querySelector('td');
    if (firstCell) {
      return firstCell.textContent.trim();
    }
  }
  return null;
}

// Función para inicializar el DataTable de Invoices Fiskaly
function inicializarInvoicesFiskalyDataTable() {
  dt_invoices_fiskaly_table = document.querySelector('.datatables-invoices-fiskaly');
  
  // Si ya está inicializado, no hacer nada
  if (window.dt_invoices_fiskaly) {
    return;
  }
  
  // Invoices Fiskaly datatable
  if (dt_invoices_fiskaly_table) {
  dt_invoices_fiskaly = new DataTable(dt_invoices_fiskaly_table, {
    processing: true,
    serverSide: false,
    deferRender: true,
    searchDelay: 500,
    timeout: 60000,
    
    language: DATATABLES_SPANISH,
    
    columns: [
      { data: 'id_invoice' },
      { data: 'client' },
      { data: 'tbai' },
      { data: 'url' },
      { data: 'issued_at' },
      { data: 'signer' },
      { data: 'state' },
      { data: 'cancellation' },
      { data: 'registration' },
      { data: 'registration_csv' },
      { data: 'code' },
      { data: 'description' },
      { data: null, orderable: false, searchable: false }
    ],
    
    columnDefs: [
      {
        // ID Invoice
        targets: 0,
        render: function (data, type, full, meta) {
          return data || '-';
        }
      },
      {
        // Client
        targets: 1,
        render: function (data, type, full, meta) {
          return data || '-';
        }
      },
      {
        // TBAI
        targets: 2,
        render: function (data, type, full, meta) {
          return data || '-';
        }
      },
      {
        // URL
        targets: 3,
        render: function (data, type, full, meta) {
          if (data) {
            return '<a href="' + data + '" target="_blank" class="text-primary"><i class="icon-base ri ri-external-link-line me-1"></i>Ver URL</a>';
          }
          return '-';
        }
      },
      {
        // Issued At
        targets: 4,
        render: function (data, type, full, meta) {
          return data || '-';
        }
      },
      {
        // Signer
        targets: 5,
        render: function (data, type, full, meta) {
          return data || '-';
        }
      },
      {
        // Estado
        targets: 6,
        render: function (data, type, full, meta) {
          const estado = data || 'N/A';
          if (estado === 'ISSUED' || estado === 'ENABLED' || estado === 'COMPLETED') {
            return '<span class="badge bg-label-success rounded-pill">' + estado + '</span>';
          } else if (estado === 'DISABLED' || estado === 'FAILED') {
            return '<span class="badge bg-label-danger rounded-pill">' + estado + '</span>';
          } else {
            return '<span class="badge bg-label-warning rounded-pill">' + estado + '</span>';
          }
        }
      },
      {
        // Cancellation
        targets: 7,
        render: function (data, type, full, meta) {
          const cancellation = data || 'N/A';
          if (cancellation === 'NOT_CANCELLED') {
            return '<span class="badge bg-label-success rounded-pill">' + cancellation + '</span>';
          } else {
            return '<span class="badge bg-label-warning rounded-pill">' + cancellation + '</span>';
          }
        }
      },
      {
        // Registration
        targets: 8,
        render: function (data, type, full, meta) {
          return data || '-';
        }
      },
      {
        // Registration CSV
        targets: 9,
        render: function (data, type, full, meta) {
          return data || '-';
        }
      },
      {
        // Code
        targets: 10,
        render: function (data, type, full, meta) {
          return data || '-';
        }
      },
      {
        // Description
        targets: 11,
        render: function (data, type, full, meta) {
          if (data) {
            return '<span class="text-wrap" style="max-width: 300px;">' + data + '</span>';
          }
          return '-';
        }
      },
      {
        // Acciones
        targets: 12,
        orderable: false,
        searchable: false,
        responsivePriority: 1,
        render: function (data, type, full, meta) {
          const invoiceId = full.id_invoice || '';
          return `
            <button type="button" onclick="verDetalleInvoice('${invoiceId}')" 
                    class="btn btn-icon btn-text-secondary rounded-pill" 
                    data-bs-toggle="tooltip" 
                    data-bs-placement="top" 
                    title="Ver Detalle">
              <i class="icon-base ri ri-eye-line icon-md"></i>
            </button>
          `;
        }
      }
    ],
    
    // Configuración de AJAX
    ajax: function(data, callback, settings) {
      const clientId = obtenerPrimerClientId();
      if (!clientId) {
        callback({ data: [] });
        return;
      }
      
      const urlApiFiskaly = window.urlApiFiskaly || '';
      const url = urlApiFiskaly + 'clients/' + clientId + '/invoices';
      const accessToken = localStorage.getItem('fiskaly_access_token');
      
      if (!accessToken) {
        console.error('No se encontró el token de autenticación');
        callback({ data: [] });
        return;
      }
      
      fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer ' + accessToken
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
      })
      .then(json => {
        let data = [];
        if (json && json.results) {
          data = json.results.map(item => {
            const content = item.content || {};
            const client = content.client || {};
            const compliance = content.compliance || {};
            const signer = content.signer || {};
            const transmission = content.transmission || {};
            const validations = content.validations || [];
            const firstValidation = validations.length > 0 ? validations[0] : {};
            
            return {
              id_invoice: content.id || '-',
              client: client.id || '-',
              tbai: compliance.tbai || '-',
              url: compliance.url || '-',
              issued_at: content.issued_at || '-',
              signer: signer.id || '-',
              state: content.state || '-',
              cancellation: transmission.cancellation || '-',
              registration: transmission.registration || '-',
              registration_csv: transmission.registration_csv || '-',
              code: firstValidation.code || '-',
              description: firstValidation.description || '-'
            };
          });
        }
        callback({ data: data });
      })
      .catch(error => {
        console.error('Error en DataTable Invoices:', error);
        callback({ data: [] });
        if (dt_invoices_fiskaly_table) {
          dt_invoices_fiskaly_table.innerHTML = `
            <div class="alert alert-danger">
              <i class="icon-base ri ri-error-warning-line me-2"></i>
              Error al cargar las facturas. Intente nuevamente.
            </div>
          `;
        }
      });
    },
    
    // Configuración de paginación
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    
    // Configuración de ordenamiento
    order: [[4, 'desc']], // Ordenar por issued_at descendente
    
    // Configuración de búsqueda
    search: {
      smart: true,
      regex: false,
      caseInsensitive: true
    },
    
    // Configuración de responsive
    responsive: {
      details: {
        display: $.fn.dataTable.Responsive.display.modal({
          header: function (row) {
            var data = row.data();
            return 'Detalles de Invoice ' + data.id_invoice;
          }
        }),
        renderer: $.fn.dataTable.Responsive.renderer.tableAll({
          tableClass: 'table'
        })
      }
    },
    
    // Configuración de dom
    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>' +
         '<"row"<"col-sm-12"tr>>' +
         '<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    
    // Configuración de idioma personalizado
    language: DATATABLES_SPANISH
  });
  
  // Guardar referencia global
  window.dt_invoices_fiskaly = dt_invoices_fiskaly;
  
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
}

// Función para recargar el DataTable de Invoices
function recargarInvoicesFiskaly() {
  if (window.dt_invoices_fiskaly) {
    window.dt_invoices_fiskaly.ajax.reload(null, false);
  }
}

// Función para ver detalle de invoice (placeholder)
function verDetalleInvoice(invoiceId) {
  console.log('Ver detalle de invoice:', invoiceId);
  // Implementar según necesidad
}

// Inicializar cuando el tab esté activo
document.addEventListener('DOMContentLoaded', function() {
  // Escuchar cambios de tab
  const tabButtons = document.querySelectorAll('[data-bs-target="#navs-pills-top-invoices-fiskaly"]');
  tabButtons.forEach(button => {
    button.addEventListener('shown.bs.tab', function() {
      if (!window.dt_invoices_fiskaly) {
        inicializarInvoicesFiskalyDataTable();
      }
    });
  });
  
  // Si el tab ya está activo al cargar la página
  const invoicesTab = document.getElementById('navs-pills-top-invoices-fiskaly');
  if (invoicesTab && invoicesTab.classList.contains('active')) {
    inicializarInvoicesFiskalyDataTable();
  }
});


