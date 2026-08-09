/**
 * Page Movimientos de Caja List
 */

'use strict';

const movimientosExportConfig = {
  exportUrl: 'parts/movimientos_de_caja/listar/export_all.php',
  headers: ['ID', 'Fecha', 'Sucursal', 'Grupo', 'Concepto', 'Salida', 'Entrada', 'Usuario']
};

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_movimiento_table = document.querySelector('.datatables-movimientos-caja');

  const FD = window.FiltrosDinamicosListar;

  function onFiltroMovimientoChange() {
    if (window.dt_movimiento) {
      window.dt_movimiento.ajax.reload();
    }
    window.recargarEstadisticasMovimientos();
    if (typeof window.actualizarTituloFiltros === 'function') {
      window.actualizarTituloFiltros();
    }
  }

  const createFilterSucursal = function (containerClass, selectId, defaultOptionText) {
    return FD.createFilterSucursal(containerClass, selectId, defaultOptionText, onFiltroMovimientoChange);
  };

  const createFilterGrupo = function (containerClass, selectId, defaultOptionText) {
    return FD.createFilterAjax(
      containerClass,
      selectId,
      defaultOptionText,
      'parts/movimientos_de_caja/listar/get_grupos.php',
      function (data) {
        return (data.grupos || []).map(function (grupo) {
          return { value: grupo, label: grupo };
        });
      },
      onFiltroMovimientoChange
    );
  };

  function escapeHtml(texto) {
    if (texto == null) {
      return '';
    }
    var div = document.createElement('div');
    div.textContent = String(texto);
    return div.innerHTML;
  }

  function formatearEuroMovimiento(valor) {
    var numero = parseFloat(valor) || 0;
    if (numero <= 0) {
      return '-';
    }
    return numero.toLocaleString('es-ES', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' €';
  }

  function renderDropdownAccionesMovimiento(full) {
    var idMovimiento = full[0];
    var sucursalNombre = full[2];
    var idSucursal = full[8] || '';

    return (
      '<div class="btn-group">' +
        '<button type="button" class="btn btn-icon rounded-pill dropdown-toggle hide-arrow" ' +
          'data-bs-toggle="dropdown" aria-expanded="false">' +
          '<i class="icon-base ri ri-more-2-line"></i>' +
        '</button>' +
        '<ul class="dropdown-menu dropdown-menu-end">' +
          '<li><a class="dropdown-item accion-editar-movimiento" href="javascript:void(0);" ' +
            'data-id="' + escapeHtml(idMovimiento) + '" ' +
            'data-sucursal="' + escapeHtml(sucursalNombre) + '">Editar apunte</a></li>' +
          '<li><a class="dropdown-item accion-mover-movimiento" href="javascript:void(0);" data-tipo="tarjeta" ' +
            'data-id="' + escapeHtml(idMovimiento) + '" ' +
            'data-sucursal="' + escapeHtml(sucursalNombre) + '" ' +
            'data-id-sucursal="' + escapeHtml(idSucursal) + '">A movimientos tarjetas</a></li>' +
          '<li><a class="dropdown-item accion-mover-movimiento" href="javascript:void(0);" data-tipo="transferencia" ' +
            'data-id="' + escapeHtml(idMovimiento) + '" ' +
            'data-sucursal="' + escapeHtml(sucursalNombre) + '" ' +
            'data-id-sucursal="' + escapeHtml(idSucursal) + '">A movimientos tranferencias</a></li>' +
          '<li><a class="dropdown-item accion-mover-movimiento" href="javascript:void(0);" data-tipo="bizum" ' +
            'data-id="' + escapeHtml(idMovimiento) + '" ' +
            'data-sucursal="' + escapeHtml(sucursalNombre) + '" ' +
            'data-id-sucursal="' + escapeHtml(idSucursal) + '">A movimientos bizum</a></li>' +
          '<li><a class="dropdown-item accion-eliminar-movimiento text-danger" href="javascript:void(0);" ' +
            'data-id="' + escapeHtml(idMovimiento) + '" ' +
            'data-id-sucursal="' + escapeHtml(idSucursal) + '">Eliminar apunte</a></li>' +
        '</ul>' +
      '</div>'
    );
  }

  // Función para abrir el modal de nuevo apunte
  function abrirModalNuevoApunte() {
    // Limpiar formulario
    document.getElementById('formNuevoApunte').reset();
    
    // Establecer fecha de hoy por defecto
    const hoy = typeof window.obtenerFechaLocalISO === 'function'
      ? window.obtenerFechaLocalISO()
      : new Date().toISOString().split('T')[0];
    document.getElementById('nuevo-fecha').value = hoy;
    
    // Cargar sucursales y grupos
    cargarSucursalesNuevoApunte();
    cargarGruposNuevoApunte();
    
    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalNuevoApunte'));
    modal.show();
  }

  // Función para cargar sucursales en el modal de nuevo apunte
  function cargarSucursalesNuevoApunte() {
    fetch('parts/clientes/listar/get_sucursales.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const select = document.getElementById('nuevo-sucursal');
          select.innerHTML = '<option value="">Seleccionar sucursal...</option>';
          
          data.sucursales.forEach(sucursal => {
            const option = document.createElement('option');
            option.value = sucursal.id_sucursal;
            option.textContent = sucursal.nombre_sucursal;
            select.appendChild(option);
          });
          
          // Inicializar Select2 después de cargar las opciones
          const select2 = $(select);
          if (select2.length) {
            // Destruir Select2 si ya está inicializado
            if (select2.hasClass("select2-hidden-accessible")) {
              select2.select2('destroy');
            }
            
            // Inicializar Select2
            select2.select2({
              dropdownParent: $('#modalNuevoApunte'),
              placeholder: 'Seleccionar sucursal...',
              allowClear: true
            });
          }
        } else {
          console.error('Error al cargar sucursales:', data.error);
        }
      })
      .catch(error => {
        console.error('Error al cargar sucursales:', error);
      });
  }
  
  // Función para cargar grupos en el modal de nuevo apunte
  function cargarGruposNuevoApunte() {
    fetch('parts/movimientos_de_caja/listar/get_grupos.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const select = document.getElementById('nuevo-grupo');
          select.innerHTML = '<option value="">Seleccionar grupo...</option>';
          
          data.grupos.forEach(grupo => {
            const option = document.createElement('option');
            option.value = grupo;
            option.textContent = grupo;
            select.appendChild(option);
          });
          
          // Inicializar Select2 después de cargar las opciones
          const select2 = $(select);
          if (select2.length) {
            // Destruir Select2 si ya está inicializado
            if (select2.hasClass("select2-hidden-accessible")) {
              select2.select2('destroy');
            }
            
            // Inicializar Select2
            select2.select2({
              dropdownParent: $('#modalNuevoApunte'),
              placeholder: 'Seleccionar grupo...',
              allowClear: true,
              tags: true
            });
          }
        } else {
          console.error('Error al cargar grupos:', data.error);
        }
      })
      .catch(error => {
        console.error('Error al cargar grupos:', error);
      });
  }

  // Movimientos de Caja datatable
  if (dt_movimiento_table) {
    window.filtro_periodo_activo = 'dia';
    const hoyInicial = typeof window.obtenerFechaLocalISO === 'function'
      ? window.obtenerFechaLocalISO()
      : new Date().toISOString().split('T')[0];
    const filtroFechaDesdeInicial = document.getElementById('filtro_fecha_desde');
    const filtroFechaHastaInicial = document.getElementById('filtro_fecha_hasta');
    if (filtroFechaDesdeInicial) filtroFechaDesdeInicial.value = hoyInicial;
    if (filtroFechaHastaInicial) filtroFechaHastaInicial.value = hoyInicial;

    const botonesNuevoApunte = window.puede_acceder_edit ? [{
      text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2 d-sm-none d-inline-block"></i><span class="d-none d-sm-inline-block">Nuevo Apunte</span>',
      className: 'add-new btn btn-primary',
      action: function () {
        abrirModalNuevoApunte();
      }
    }] : [];

    window.dt_movimiento = new DataTable(dt_movimiento_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      
      language: DATATABLES_SPANISH,
      
      ajax: {
        url: 'parts/movimientos_de_caja/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          // Agregar filtros de columna personalizados
          const sucursalFilter = document.getElementById('filtro_sucursal');
          const grupoFilter = document.getElementById('filtro_grupo');
          const fechaDesdeFilter = document.getElementById('filtro_fecha_desde');
          const fechaHastaFilter = document.getElementById('filtro_fecha_hasta');
          
          d.filtro_sucursal = sucursalFilter ? sucursalFilter.value : '';
          d.filtro_grupo = grupoFilter ? grupoFilter.value : '';
          d.filtro_fecha_desde = fechaDesdeFilter ? fechaDesdeFilter.value : '';
          d.filtro_fecha_hasta = fechaHastaFilter ? fechaHastaFilter.value : '';
          d.filtro_periodo = window.filtro_periodo_activo || 'dia';
          
          return d;
        },
        dataSrc: function(json) {
          return json.data || [];
        },
        error: function(xhr, error, thrown) {
          console.error('Error AJAX:', error, thrown);
          console.log('Respuesta del servidor:', xhr.responseText);
        }
      },
      
      columns: (function () {
        var cols = [
          { data: 0 },
          { data: 1 },
          { data: 2 },
          { data: 3 },
          { data: 4 },
          { data: 5 },
          { data: 6 },
          { data: 7 }
        ];
        if (window.puede_acceder_edit) {
          cols.push({ data: 8, visible: false, searchable: false });
          cols.push({ data: null, orderable: false, searchable: false, defaultContent: '' });
        }
        return cols;
      })(),
      
      columnDefs: [
        {
          targets: 0,
          orderable: false,
          responsivePriority: 1,
          render: function (data) {
            return '<span class="fw-semibold">#' + escapeHtml(data) + '</span>';
          }
        },
        {
          // Sucursal
          targets: 2,
          render: function (data, type, full, meta) {
            return '<span class="badge bg-label-primary">' + data + '</span>';
          }
        },
        {
          // Salida (formato moneda)
          targets: 5,
          width: '100px',
          className: 'text-center',
          render: function (data, type, full, meta) {
            if (parseFloat(data) > 0) {
              return '<span class="text-danger fw-semibold" style="font-size: 12px;">' + parseFloat(data).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €</span>';
            }
            return '<span style="font-size: 12px;">-</span>';
          }
        },
        {
          // Entrada (formato moneda)
          targets: 6,
          width: '100px',
          className: 'text-center',
          render: function (data, type, full, meta) {
            if (parseFloat(data) > 0) {
              return '<span class="text-success fw-semibold" style="font-size: 12px;">' + parseFloat(data).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €</span>';
            }
            return '<span style="font-size: 12px;">-</span>';
          }
        }
      ].concat(window.puede_acceder_edit ? [{
          targets: 9,
          className: 'text-center',
          orderable: false,
          searchable: false,
          render: function (data, type, full) {
            return renderDropdownAccionesMovimiento(full);
          }
        }] : []),
      
      order: [[1, 'desc']], // Fecha + hora (más reciente primero)
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      
      createdRow: function(row, data, dataIndex) {
        // data[3] es la columna de Grupo
        const grupo = data[3];
        
        if (grupo === 'CAJA INICIO') {
          $(row).addClass('bg-label-success');
        } else if (grupo === 'CAJA FINAL') {
          $(row).addClass('bg-label-danger');
        }
      },
      
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
                  buttons: window.crearBotonesExportarMovimientos(movimientosExportConfig)
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
              buttons: botonesNuevoApunte
            }
          ]
        },
        bottomStart: {
          rowClass: 'row mx-3 justify-content-between',
          features: ['info']
        },
        bottomEnd: 'paging'
      },
      
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'Detalles del Movimiento #' + data[0];
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

    // Cargar filtros dinámicamente
    cargarFiltros();
    
    // Configurar filtros de fecha
    configurarFiltrosFecha();
  }

  // Función para configurar los filtros de fecha
  function configurarFiltrosFecha() {
    window.filtro_tipo_fecha = 'apunte';

    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');
    const hoy = typeof window.obtenerFechaLocalISO === 'function'
      ? window.obtenerFechaLocalISO()
      : new Date().toISOString().split('T')[0];
console.log(hoy);
    window.filtro_periodo_activo = 'dia';
    if (filtroFechaDesde) filtroFechaDesde.value = hoy;
    if (filtroFechaHasta) filtroFechaHasta.value = hoy;
    if (rangeFechas) rangeFechas.value = '';

    document.querySelectorAll('#filtro_por_fecha_apunte, #filtro_dia, #filtro_mes, #filtro_todos').forEach(btn => {
      btn.classList.remove('active');
    });
    const filtroDiaDefault = document.getElementById('filtro_dia');
    if (filtroDiaDefault) filtroDiaDefault.classList.add('active');

    const recargar = function () {
      window.dt_movimiento.ajax.reload();
      window.recargarEstadisticasMovimientos();
      if (typeof window.actualizarTituloFiltros === 'function') {
        window.actualizarTituloFiltros();
      }
    };

    const filtroPorFechaApunte = document.getElementById('filtro_por_fecha_apunte');
    if (filtroPorFechaApunte) {
      filtroPorFechaApunte.addEventListener('click', function () {
        if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
          Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe seleccionar al menos una fecha',
            confirmButtonText: 'Aceptar'
          });
          return;
        }

        window.filtro_periodo_activo = 'fecha';
        window.filtro_tipo_fecha = 'apunte';

        document.querySelectorAll('#filtro_por_fecha_apunte, #filtro_dia, #filtro_mes, #filtro_todos').forEach(btn => {
          btn.classList.remove('active');
        });
        this.classList.add('active');

        recargar();
      });
    }

    const filtroDia = document.getElementById('filtro_dia');
    if (filtroDia) {
      filtroDia.addEventListener('click', function () {
        const hoy = typeof window.obtenerFechaLocalISO === 'function'
          ? window.obtenerFechaLocalISO()
          : new Date().toISOString().split('T')[0];
        if (filtroFechaDesde) filtroFechaDesde.value = hoy;
        if (filtroFechaHasta) filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo = 'dia';
        window.filtro_tipo_fecha = 'apunte';

        document.querySelectorAll('#filtro_por_fecha_apunte, #filtro_dia, #filtro_mes, #filtro_todos').forEach(btn => {
          btn.classList.remove('active');
        });
        this.classList.add('active');

        recargar();
      });
    }

    const filtroMes = document.getElementById('filtro_mes');
    if (filtroMes) {
      filtroMes.addEventListener('click', function () {
        if (filtroFechaDesde) filtroFechaDesde.value = '';
        if (filtroFechaHasta) filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo = 'mes';
        window.filtro_tipo_fecha = 'apunte';

        document.querySelectorAll('#filtro_por_fecha_apunte, #filtro_dia, #filtro_mes, #filtro_todos').forEach(btn => {
          btn.classList.remove('active');
        });
        this.classList.add('active');

        recargar();
      });
    }

    const filtroTodos = document.getElementById('filtro_todos');
    if (filtroTodos) {
      filtroTodos.addEventListener('click', function () {
        if (filtroFechaDesde) filtroFechaDesde.value = '';
        if (filtroFechaHasta) filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo = 'todos';
        window.filtro_tipo_fecha = 'apunte';

        document.querySelectorAll('#filtro_por_fecha_apunte, #filtro_dia, #filtro_mes, #filtro_todos').forEach(btn => {
          btn.classList.remove('active');
        });
        this.classList.add('active');

        recargar();
      });
    }

    if (typeof window.actualizarTituloFiltros === 'function') {
      window.actualizarTituloFiltros();
    }
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

  // Función para cargar los filtros
  function cargarFiltros() {
    // Filtro de Sucursal usando la función createFilterSucursal
    createFilterSucursal('.movimiento_sucursal', 'filtro_sucursal', 'Seleccionar Sucursal');
    
    // Filtro de Grupo
    createFilterGrupo('.movimiento_grupo', 'filtro_grupo', 'Todos los grupos');

    if (FD) {
      FD.finalize();
    }
  }
  
  // Función global para recargar estadísticas con filtros
  window.recargarEstadisticasMovimientos = function() {
    if (typeof cargarEstadisticas === 'function') {
      cargarEstadisticas();
    }
  };
  
  // Función para cargar grupos en el select
  function cargarGruposMovimientos() {
    const select = document.getElementById('edit-grupo');
    if (!select) {
      return;
    }

    fetch('parts/movimientos_de_caja/listar/get_grupos.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const select = document.getElementById('edit-grupo');
          if (!select) {
            return;
          }
          // Limpiar opciones excepto la primera
          select.innerHTML = '<option value="">Seleccionar grupo...</option>';
          
          // Agregar grupos
          data.grupos.forEach(grupo => {
            const option = document.createElement('option');
            option.value = grupo;
            option.textContent = grupo;
            select.appendChild(option);
          });
        } else {
          console.error('Error al cargar grupos:', data.error);
        }
      })
      .catch(error => {
        console.error('Error al cargar grupos:', error);
      });
  }
  
  // Cargar grupos al iniciar
  cargarGruposMovimientos();
  
  // Guardar nuevo apunte
  const btnCrearApunte = document.getElementById('btnCrearApunte');
  if (btnCrearApunte) {
  btnCrearApunte.addEventListener('click', function() {
    const form = document.getElementById('formNuevoApunte');
    
    const salida = parseFloat(document.getElementById('nuevo-salida').value) || 0;
    const entrada = parseFloat(document.getElementById('nuevo-entrada').value) || 0;
    
    if (salida === 0 && entrada === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Advertencia',
        text: 'Debe ingresar un valor en Salida o Entrada'
      });
      return;
    }
    
    // Validar formulario
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    
    // Deshabilitar botón mientras se procesa
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creando...';
    
    // Enviar datos
    const formData = new FormData(form);
    
    fetch('parts/movimientos_de_caja/listar/create_movimiento.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Éxito',
          text: 'Apunte creado correctamente',
          showConfirmButton: false,
          timer: 1500
        });
        
        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('modalNuevoApunte')).hide();
        
        // Recargar tabla y estadísticas
        window.dt_movimiento.ajax.reload();
        window.recargarEstadisticasMovimientos();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.error || 'No se pudo crear el apunte'
        });
      }
    })
    .catch(error => {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al crear el apunte'
      });
    })
    .finally(() => {
      // Rehabilitar botón
      btn.disabled = false;
      btn.innerHTML = '<i class="ri-save-line me-1"></i> Crear Apunte';
    });
  });
  }
  
  // Event delegation para acciones del movimiento
  document.addEventListener('click', function(e) {
    var btnEditar = e.target.closest('.accion-editar-movimiento');
    if (btnEditar) {
      abrirModalEditarMovimiento(
        btnEditar.getAttribute('data-id'),
        btnEditar.getAttribute('data-sucursal')
      );
      return;
    }

    var btnMover = e.target.closest('.accion-mover-movimiento');
    if (btnMover) {
      abrirModalMoverMovimiento(
        btnMover.getAttribute('data-tipo'),
        btnMover.getAttribute('data-id'),
        btnMover.getAttribute('data-sucursal'),
        btnMover.getAttribute('data-id-sucursal')
      );
      return;
    }

    var btnEliminar = e.target.closest('.accion-eliminar-movimiento');
    if (btnEliminar) {
      confirmarEliminarMovimiento(
        btnEliminar.getAttribute('data-id'),
        btnEliminar.getAttribute('data-id-sucursal')
      );
    }
  });

  var modalesTrasladoMap = {
    tarjeta: 'modalMoverMovimientoTarjeta',
    transferencia: 'modalMoverMovimientoTransferencia',
    bizum: 'modalMoverMovimientoBizum'
  };

  function abrirModalMoverMovimiento(tipo, idMovimiento, sucursalNombre, idSucursal) {
    var modalId = modalesTrasladoMap[tipo];
    if (!modalId) {
      return;
    }

    var modalEl = document.getElementById(modalId);
    if (!modalEl) {
      return;
    }

    fetch('parts/movimientos_de_caja/listar/get_movimiento.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'id_movimiento=' + encodeURIComponent(idMovimiento) +
        '&sucursal_nombre=' + encodeURIComponent(sucursalNombre)
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
      if (!data.success) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.error || 'No se pudo cargar el movimiento'
        });
        return;
      }

      var mov = data.movimiento;
      modalEl.querySelectorAll('.mover-movimiento-id-label').forEach(function(el) {
        el.textContent = mov.id_movimientos;
      });
      modalEl.querySelector('.mover-id-movimiento').value = mov.id_movimientos;
      modalEl.querySelector('.mover-id-sucursal').value = mov.id_sucursal || idSucursal || '';
      modalEl.querySelector('.mover-sucursal-nombre').value = sucursalNombre;
      modalEl.querySelector('.mover-fecha-apunte').textContent = formatearFechaApunte(mov.fecha_apunte, mov.hora_de_apunte);
      modalEl.querySelector('.mover-grupo').textContent = mov.grupos || '';
      modalEl.querySelector('.mover-concepto').textContent = mov.concepto || '';
      modalEl.querySelector('.mover-salida').textContent = formatearEuroMovimiento(mov.salida);
      modalEl.querySelector('.mover-entrada').textContent = formatearEuroMovimiento(mov.entrada);

      var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
    })
    .catch(function(error) {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al cargar los datos del movimiento'
      });
    });
  }

  function confirmarEliminarMovimiento(idMovimiento, idSucursal) {
    Swal.fire({
      icon: 'warning',
      title: '¿Eliminar apunte?',
      text: '¿Está seguro que desea eliminar este apunte? Esta acción no se puede deshacer.',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true
    }).then(function(result) {
      if (!result.isConfirmed) {
        return;
      }

      Swal.fire({
        title: 'Eliminando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: function() {
          Swal.showLoading();
        }
      });

      var formData = new FormData();
      formData.append('id_movimiento', idMovimiento);
      formData.append('id_sucursal', idSucursal);

      fetch('parts/movimientos_de_caja/listar/delete_movimiento.php', {
        method: 'POST',
        body: formData
      })
      .then(function(response) { return response.json(); })
      .then(function(data) {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Eliminado',
            text: data.message || 'Apunte eliminado correctamente',
            timer: 1500,
            showConfirmButton: false
          });
          window.dt_movimiento.ajax.reload();
          window.recargarEstadisticasMovimientos();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.error || 'No se pudo eliminar el apunte'
          });
        }
      })
      .catch(function(error) {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error al eliminar el apunte'
        });
      });
    });
  }

  document.querySelectorAll('.btn-confirmar-traslado-movimiento').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tipo = btn.getAttribute('data-tipo');
      var modalId = modalesTrasladoMap[tipo];
      var modalEl = modalId ? document.getElementById(modalId) : null;
      if (!modalEl) {
        return;
      }

      var idMovimiento = modalEl.querySelector('.mover-id-movimiento').value;
      var idSucursal = modalEl.querySelector('.mover-id-sucursal').value;

      if (!idMovimiento || !idSucursal) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudieron obtener los datos del movimiento'
        });
        return;
      }

      Swal.fire({
        icon: 'question',
        title: '¿Confirmar traslado?',
        text: 'El apunte se moverá a movimientos ' + tipo + ' y quedará anulado en caja.',
        showCancelButton: true,
        confirmButtonText: 'Sí, trasladar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#696cff',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
      }).then(function(result) {
        if (!result.isConfirmed) {
          return;
        }

        Swal.fire({
          title: 'Trasladando...',
          text: 'Por favor espere',
          allowOutsideClick: false,
          didOpen: function() {
            Swal.showLoading();
          }
        });

        var formData = new FormData();
        formData.append('id_movimiento', idMovimiento);
        formData.append('id_sucursal', idSucursal);
        formData.append('tipo', tipo);

        fetch('parts/movimientos_de_caja/listar/trasladar_movimiento_caja.php', {
          method: 'POST',
          body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (data.success) {
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
              modalInstance.hide();
            }
            Swal.fire({
              icon: 'success',
              title: 'Trasladado',
              text: data.message || 'Movimiento trasladado correctamente',
              timer: 1800,
              showConfirmButton: false
            });
            window.dt_movimiento.ajax.reload();
            window.recargarEstadisticasMovimientos();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.error || 'No se pudo trasladar el movimiento'
            });
          }
        })
        .catch(function(error) {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al trasladar el movimiento'
          });
        });
      });
    });
  });
  
  // Función para abrir el modal y cargar datos del movimiento
  function formatearFechaApunte(fecha, hora) {
    if (!fecha) {
      return '—';
    }

    const partes = String(fecha).split('-');
    let texto = fecha;

    if (partes.length === 3) {
      texto = partes[2] + '/' + partes[1] + '/' + partes[0];
    }

    if (hora) {
      texto += ' ' + hora;
    }

    return texto;
  }

  function abrirModalEditarMovimiento(idMovimiento, sucursalNombre) {
    // Mostrar el ID en el modal
    document.getElementById('modal-movimiento-id').textContent = idMovimiento;
    
    // Obtener los datos del movimiento
    fetch('parts/movimientos_de_caja/listar/get_movimiento.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'id_movimiento=' + idMovimiento + '&sucursal_nombre=' + encodeURIComponent(sucursalNombre)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const mov = data.movimiento;
        
        // Llenar el formulario
        document.getElementById('edit-id-movimiento').value = mov.id_movimientos;
        document.getElementById('edit-id-sucursal').value = mov.id_sucursal;
        document.getElementById('edit-fecha-apunte').value = formatearFechaApunte(mov.fecha_apunte, mov.hora_de_apunte);
        document.getElementById('edit-grupo').value = mov.grupos;
        document.getElementById('edit-concepto').value = mov.concepto;
        document.getElementById('edit-salida').value = mov.salida;
        document.getElementById('edit-entrada').value = mov.entrada;
        
        // Abrir el modal
        const modal = new bootstrap.Modal(document.getElementById('modalEditarMovimiento'));
        modal.show();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.error || 'No se pudo cargar el movimiento'
        });
      }
    })
    .catch(error => {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al cargar los datos del movimiento'
      });
    });
  }
  
  // Guardar cambios del movimiento
  const btnGuardarMovimiento = document.getElementById('btnGuardarMovimiento');
  if (btnGuardarMovimiento) {
  btnGuardarMovimiento.addEventListener('click', function() {
    const form = document.getElementById('formEditarMovimiento');
    
    // Validar formulario
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    
    // Deshabilitar botón mientras se procesa
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Actualizando...';
    
    // Enviar datos
    const formData = new FormData(form);
    
    fetch('parts/movimientos_de_caja/listar/update_movimiento.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Éxito',
          text: 'Movimiento actualizado correctamente',
          showConfirmButton: false,
          timer: 1500
        });
        
        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('modalEditarMovimiento')).hide();
        
        // Recargar tabla y estadísticas
        window.dt_movimiento.ajax.reload();
        window.recargarEstadisticasMovimientos();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.error || 'No se pudo actualizar el movimiento'
        });
      }
    })
    .catch(error => {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al actualizar el movimiento'
      });
    })
    .finally(() => {
      // Rehabilitar botón
      btn.disabled = false;
      btn.innerHTML = '<i class="ri-save-line me-1"></i> Actualizar Movimiento';
    });
  });
  }
});

