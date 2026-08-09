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

  // Variable declaration for table
  const dt_gastos_table = document.querySelector('.datatables-gastos');

  // Variable global para DataTable
  let dt_gastos;

  // Función para crear filtros dinámicamente con Select2
  function crearFiltros() {
    fetch('parts/gastos/listar/get_filtros.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const filtros = data.filtros;
          
          // Crear filtro de empresas
          const userEmpresa = document.querySelector('.user_empresa');
          if (userEmpresa) {
            userEmpresa.innerHTML = `
              <label class="form-label">Empresa</label>
              <select id="filtro_empresa" class="form-select select2">
                <option value="">Todas las empresas</option>
                ${filtros.empresas.map(empresa => `<option value="${empresa.id}">${empresa.nombre}</option>`).join('')}
              </select>
            `;
          }
          
          // Crear filtro de sucursales
          const userSucursal = document.querySelector('.user_sucursal');
          if (userSucursal) {
            userSucursal.innerHTML = `
              <label class="form-label">Sucursal</label>
              <select id="filtro_sucursal" class="form-select select2">
                <option value="">Todas las sucursales</option>
                ${filtros.sucursales.map(sucursal => `<option value="${sucursal.id}">${sucursal.nombre}</option>`).join('')}
              </select>
            `;
          }
          
          // Crear filtro de proveedores
          const userProveedor = document.querySelector('.user_proveedor');
          if (userProveedor) {
            userProveedor.innerHTML = `
              <label class="form-label">Proveedor</label>
              <select id="filtro_proveedor" class="form-select select2">
                <option value="">Todos los proveedores</option>
                ${filtros.proveedores.map(proveedor => `<option value="${proveedor.id}">${proveedor.nombre}</option>`).join('')}
              </select>
            `;
          }
          
          // Crear filtro de estados
          const userEstado = document.querySelector('.user_estado');
          if (userEstado) {
            userEstado.innerHTML = `
              <label class="form-label">Estado</label>
              <select id="filtro_estado" class="form-select select2">
                <option value="">Todos los estados</option>
                ${filtros.estados.map(estado => `<option value="${estado.id}">${estado.nombre}</option>`).join('')}
              </select>
            `;
          }
          
          // Crear filtro de tipos de gasto
          const userTipoGasto = document.querySelector('.user_tipo_gasto');
          if (userTipoGasto) {
            userTipoGasto.innerHTML = `
              <label class="form-label">Tipo de Gasto</label>
              <select id="filtro_tipo_gasto" class="form-select select2">
                <option value="">Todos los tipos</option>
                ${filtros.tipos_gasto.map(tipo => `<option value="${tipo.id}">${tipo.nombre}</option>`).join('')}
              </select>
            `;
          }
          
          // Crear filtro de formas de pago
          const userFormaPago = document.querySelector('.user_forma_pago');
          if (userFormaPago) {
            userFormaPago.innerHTML = `
              <label class="form-label">Forma de Pago</label>
              <select id="filtro_forma_pago" class="form-select select2">
                <option value="">Todas las formas</option>
                ${filtros.formas_pago.map(forma => `<option value="${forma.id}">${forma.nombre}</option>`).join('')}
              </select>
            `;
          }
          
          // Inicializar Select2 para todos los filtros
          inicializarSelect2();
          
          // Inicializar DataTable después de crear los filtros
          inicializarDataTable();
          
          // Inicializar filtros de fecha después de que todo esté listo
          setTimeout(() => {
            inicializarFiltrosFecha();
          }, 100);
        } else {
          console.error('Error al cargar filtros:', data.error);
          // Inicializar DataTable sin filtros
          inicializarDataTable();
        }
      })
      .catch(error => {
        console.error('Error al cargar filtros:', error);
        // Inicializar DataTable sin filtros
        inicializarDataTable();
      });
  }
  
  // Función para inicializar Select2
  function inicializarSelect2() {
    // Verificar que Select2 esté disponible
    if (typeof $ === 'undefined' || !$.fn.select2) {
      console.warn('Select2 no está disponible, usando selects nativos');
      return;
    }
    
    // Inicializar Select2 para todos los filtros
    const select2Elements = $('.select2');
    if (select2Elements.length) {
      select2Elements.each(function () {
        var $this = $(this);
        $this.select2({
          dropdownParent: $this.parent(),
          placeholder: $this.find('option:first').text(),
          allowClear: true,
          width: '100%'
        });
      });
    }
  }
  
  // Función para inicializar DataTable
  function inicializarDataTable() {
    // Gastos datatable
    if (dt_gastos_table) {
      dt_gastos = new DataTable(dt_gastos_table, {
      processing: true, // Mostrar indicador de procesamiento
      serverSide: true, // Procesar en el servidor para grandes volúmenes
      deferRender: true, // Mejorar rendimiento con grandes volúmenes
      searchDelay: 500, // Delay de 500ms para búsquedas
      timeout: 60000, // Timeout de 60 segundos para peticiones grandes
      
     // Configuración de idioma español
      language: {
        "decimal": "",
        "emptyTable": "No hay datos disponibles en la tabla",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
        "infoFiltered": "(filtrado de _MAX_ entradas totales)",
        "infoPostFix": "",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ entradas",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Buscar:",
        "zeroRecords": "No se encontraron registros coincidentes",
        "paginate": {
          "first": "Primero",
          "last": "Último",
          "next": "Siguiente",
          "previous": "Anterior"
        },
        "aria": {
          "sortAscending": ": activar para ordenar la columna de manera ascendente",
          "sortDescending": ": activar para ordenar la columna de manera descendente"
        }
      },
      
      // Configuración AJAX
      ajax: {
        url: 'parts/gastos/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          // Agregar filtros personalizados
          d.filtro_empresa = document.getElementById('filtro_empresa')?.value || '';
          d.filtro_sucursal = document.getElementById('filtro_sucursal')?.value || '';
          d.filtro_proveedor = document.getElementById('filtro_proveedor')?.value || '';
          d.filtro_estado = document.getElementById('filtro_estado')?.value || '';
          d.filtro_tipo_gasto = document.getElementById('filtro_tipo_gasto')?.value || '';
          d.filtro_forma_pago = document.getElementById('filtro_forma_pago')?.value || '';
          d.filtro_fecha_desde = document.getElementById('filtro_fecha_desde')?.value || '';
          d.filtro_fecha_hasta = document.getElementById('filtro_fecha_hasta')?.value || '';
          
          // Debug: mostrar filtros en consola
          console.log('Filtros enviados:', {
            empresa: d.filtro_empresa,
            sucursal: d.filtro_sucursal,
            proveedor: d.filtro_proveedor,
            estado: d.filtro_estado,
            tipo_gasto: d.filtro_tipo_gasto,
            forma_pago: d.filtro_forma_pago,
            fecha_desde: d.filtro_fecha_desde,
            fecha_hasta: d.filtro_fecha_hasta
          });
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
        { data: null, title: '#', width: '80px', orderable: false, searchable: false },
        { data: 0, title: 'ID', width: '60px' },
        { data: 6, title: 'DESCRIPCIÓN', width: '200px' },
        { data: 1, title: 'FECHA GASTO', width: '100px' },
        { data: 2, title: 'EMPRESA', width: '120px' },
        { data: 3, title: 'SUCURSAL', width: '120px' },
        { data: 4, title: 'PROVEEDOR', width: '120px' },
        { data: 5, title: 'TIPO GASTO', width: '120px' },
        { data: 7, title: 'TOTAL', width: '100px' },
        { data: 8, title: 'ESTADO', width: '100px' }
      ],
      
      // Configuración de renderizado de columnas
      columnDefs: [
        {
          // Acciones
          targets: 0,
          render: function (data, type, full, meta) {
            const gastoId = full[0]; // ID del gasto (siempre en posición 0 del array de datos del servidor)
            return `
              <div class="dropdown d-inline-block">
                <a href="javascript:;" class="btn btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="icon-base ri ri-more-2-line icon-md"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-start m-0">
                  <a href="gasto.php?id=${gastoId}" class="dropdown-item">
                    <i class="icon-base ri ri-eye-line me-2"></i>
                    <span class="align-middle">Ver Gasto</span>
                  </a>
                  <a href="editar_gasto.php?id=${gastoId}" class="dropdown-item">
                    <i class="icon-base ri ri-pencil-line me-2"></i>
                    <span class="align-middle">Editar Gasto</span>
                  </a>
                  <a href="javascript:;" class="dropdown-item" onclick="duplicarGasto(${gastoId})">
                    <i class="icon-base ri ri-file-copy-line me-2"></i>
                    <span class="align-middle">Duplicar Gasto</span>
                  </a>
                </div>
              </div>
            `;
          }
        },
        {
          // ID
          targets: 1,
          render: function (data, type, full, meta) {
            return '<a href="gasto.php?id=' + data + '" class="fw-medium text-heading text-decoration-none">#' + data + '</a>';
          }
        },
        {
          // Descripción
          targets: 2,
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
          targets: 3,
          render: function (data, type, full, meta) {
            return '<span class="fw-semibold">' + data + '</span>';
          }
        },
        {
          // Empresa
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
          // Sucursal
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
          // Proveedor
          targets: 6,
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
          targets: 7,
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
          targets: 8,
          render: function (data, type, full, meta) {
            return '<span class="fw-bold text-success">' + data + '</span>';
          }
        },
        {
          // Estado
          targets: 9,
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
      order: [[1, 'desc']], // Ordenar por fecha descendente por defecto
      
      // Configuración de paginación
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      
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
                        columns: [1, 2, 3, 4, 5, 6, 7, 8, 9],
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
                        columns: [1, 2, 3, 4, 5, 6, 7, 8, 9],
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
                        columns: [1, 2, 3, 4, 5, 6, 7, 8, 9],
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
        bottomEnd: {
          rowClass: 'row mx-3 justify-content-between',
          features: ['paging']
        }
      }
    });

    // Hacer la tabla globalmente accesible
    window.dt_gastos = dt_gastos;

    // Event listeners para los filtros
    const filtros = ['filtro_empresa', 'filtro_sucursal', 'filtro_proveedor', 'filtro_estado', 'filtro_tipo_gasto', 'filtro_forma_pago'];
    
    filtros.forEach(filtroId => {
      const elemento = document.getElementById(filtroId);
      if (elemento) {
        // Usar jQuery para el evento change si Select2 está disponible
        if (typeof $ !== 'undefined' && $.fn.select2) {
          $(elemento).on('change', function() {
            recargarTabla();
          });
        } else {
          elemento.addEventListener('change', recargarTabla);
        }
      }
    });
  }
  }
  
  // Función global para recargar la tabla cuando cambien los filtros
  function recargarTabla() {
    console.log('recargarTabla() llamada');
    console.log('dt_gastos disponible:', window.dt_gastos);
    if (window.dt_gastos) {
      console.log('Recargando DataTable...');
      window.dt_gastos.ajax.reload();
      
      // También recargar las estadísticas cuando cambien los filtros
      if (typeof cargarEstadisticas === 'function') {
        console.log('Recargando estadísticas...');
        cargarEstadisticas();
      }
    } else {
      console.error('dt_gastos no está disponible');
    }
  }
  
  // Event listeners para filtros de fecha
  function inicializarFiltrosFecha() {
    console.log('Inicializando filtros de fecha...');
    
    // Los filtros de fecha ahora se crean dinámicamente en los botones de DataTables
    // Necesitamos esperar a que DataTables termine de renderizar
    setTimeout(() => {
      const fechaDesde = document.getElementById('filtro_fecha_desde');
      const fechaHasta = document.getElementById('filtro_fecha_hasta');
      
      console.log('Campo fecha desde encontrado:', fechaDesde);
      console.log('Campo fecha hasta encontrado:', fechaHasta);
      
      if (fechaDesde) {
        fechaDesde.addEventListener('change', function() {
          console.log('Fecha desde cambiada:', this.value);
          // Validar que fecha desde no sea mayor que fecha hasta
          if (fechaHasta.value && this.value > fechaHasta.value) {
            alert('La fecha "desde" no puede ser mayor que la fecha "hasta"');
            this.value = '';
            return;
          }
          console.log('Recargando tabla por cambio de fecha desde');
          recargarTabla();
        });
      }
      
      if (fechaHasta) {
        fechaHasta.addEventListener('change', function() {
          console.log('Fecha hasta cambiada:', this.value);
          // Validar que fecha hasta no sea menor que fecha desde
          if (fechaDesde.value && this.value < fechaDesde.value) {
            alert('La fecha "hasta" no puede ser menor que la fecha "desde"');
            this.value = '';
            return;
          }
          console.log('Recargando tabla por cambio de fecha hasta');
          recargarTabla();
        });
      }
    }, 500); // Esperar 500ms para que DataTables termine de renderizar
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
    
    // Debug: verificar que se aplicaron los estilos
    console.log('Estilos aplicados a DataTables');
    const dtLayoutEnd = document.querySelector('.dt-layout-end');
    if (dtLayoutEnd) {
      console.log('dt-layout-end classes:', dtLayoutEnd.className);
    }
    const dtButtons = document.querySelector('.dt-layout-end .dt-buttons');
    if (dtButtons) {
      console.log('dt-buttons classes:', dtButtons.className);
    }
  }, 500);
  
  // Inicializar filtros y DataTable
  crearFiltros();
});

// Función para duplicar gasto
function duplicarGasto(gastoId) {
  Swal.fire({
    title: '¿Duplicar gasto?',
    text: '¿Estás seguro de que quieres duplicar este gasto? Se creará una copia con los mismos datos.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#007bff',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, duplicar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then((result) => {
    if (result.isConfirmed) {
      // Mostrar loading
      Swal.fire({
        title: 'Duplicando...',
        text: 'Por favor espera mientras se duplica el gasto',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      // Hacer la petición AJAX para duplicar
      fetch('parts/gastos/listar/duplicar_gasto.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          gasto_id: gastoId
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            title: '¡Gasto duplicado!',
            text: 'El gasto se ha duplicado correctamente',
            icon: 'success',
            confirmButtonColor: '#007bff'
          }).then(() => {
            // Recargar la tabla
            if (window.dt_gastos) {
              window.dt_gastos.ajax.reload();
            }
          });
        } else {
          Swal.fire({
            title: 'Error',
            text: data.message || 'No se pudo duplicar el gasto',
            icon: 'error',
            confirmButtonColor: '#dc3545'
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          title: 'Error',
          text: 'Ocurrió un error al duplicar el gasto',
          icon: 'error',
          confirmButtonColor: '#dc3545'
        });
      });
    }
  });
}