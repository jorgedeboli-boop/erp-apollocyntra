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
        url: 'parts/empresas/main/get_sucursales_empresa.php',
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
      
      pageLength: 10,
      lengthChange: false,
      
      // Configuración de ordenamiento
      order: [[1, 'asc']], // Ordenar por nombre de sucursal ascendente
      
      // Configuración de búsqueda
      search: {
        smart: true,
        regex: false,
        caseInsensitive: true
      },
      
      responsive: false,
      
      dom: '<"row g-2 mb-3"<"col-12 col-md-6 ms-md-auto"f>><"row"<"col-12"tr>><"row align-items-center g-2 mt-3 pt-2 border-top"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6 d-flex justify-content-md-end"p>>',
      
      // Configuración de idioma personalizado
      language: DATATABLES_SPANISH
    });
    window.dt_sucursales_empresa = dt_sucursales_empresa;
  }
});



// Función para recargar el DataTable
function recargarSucursalesEmpresa() {
  if (window.dt_sucursales_empresa) {
    window.dt_sucursales_empresa.ajax.reload(null, false);
  }
}


