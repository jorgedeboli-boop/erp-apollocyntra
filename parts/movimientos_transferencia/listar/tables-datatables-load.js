/**
 * Page Movimientos Transferencia List
 */

'use strict';

const movimientosExportConfig = {
  exportUrl: 'parts/movimientos_transferencia/listar/export_all.php',
  headers: ['ID', 'Fecha', 'Grupo', 'Descripción', 'Salida', 'Entrada', 'Usuario']
};

// Datatable (js)
document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  borderColor = config.colors.borderColor;
  bodyBg = config.colors.bodyBg;
  headingColor = config.colors.headingColor;

  // Variable declaration for table
  const dt_movimiento_table = document.querySelector('.datatables-movimientos-transferencia');

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

  const createFilterGrupo = function (containerClass, selectId, defaultOptionText) {
    return FD.createFilterAjax(
      containerClass,
      selectId,
      defaultOptionText,
      'parts/movimientos_transferencia/listar/get_grupos.php',
      function (data) {
        return (data.grupos || []).map(function (grupo) {
          return { value: grupo, label: grupo };
        });
      },
      onFiltroMovimientoChange
    );
  };

  // Movimientos Transferencia datatable
  if (dt_movimiento_table) {
    window.filtro_periodo_activo = 'dia';
    const hoyInicial = new Date().toISOString().split('T')[0];
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
        url: 'parts/movimientos_transferencia/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          // Agregar filtros de columna personalizados
          const grupoFilter = document.getElementById('filtro_grupo');
          const fechaDesdeFilter = document.getElementById('filtro_fecha_desde');
          const fechaHastaFilter = document.getElementById('filtro_fecha_hasta');
          
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
      
      columns: [
        { data: 0 },  // ID
        { data: 1 },  // Fecha
        { data: 2 },  // Grupo
        { data: 3 },  // Descripción
        { data: 4 },  // Salida
        { data: 5 },  // Entrada
        { data: 6 }   // Usuario
      ],
      
      columnDefs: [
        {
          // ID - Clickeable para editar
          targets: 0,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            if (window.puede_acceder_edit) {
              return '<a href="javascript:void(0);" class="fw-semibold text-primary editar-movimiento" data-id="' + data + '">#' + data + '</a>';
            }
            return '<span class="fw-semibold">#' + data + '</span>';
          }
        },
        {
          // Salida (formato moneda)
          targets: 4,
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
          targets: 5,
          width: '100px',
          className: 'text-center',
          render: function (data, type, full, meta) {
            if (parseFloat(data) > 0) {
              return '<span class="text-success fw-semibold" style="font-size: 12px;">' + parseFloat(data).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €</span>';
            }
            return '<span style="font-size: 12px;">-</span>';
          }
        }
      ],
      
      order: [[0, 'desc']], // Ordenar por ID descendente
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
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
    const hoy = new Date().toISOString().split('T')[0];

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
        const hoy = new Date().toISOString().split('T')[0];
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
    createFilterGrupo('.movimiento_grupo', 'filtro_grupo', 'Todos los grupos');

    if (FD) { FD.finalize(); }
  }
  
  // Función global para recargar estadísticas con filtros
  window.recargarEstadisticasMovimientos = function() {
    if (typeof cargarEstadisticas === 'function') {
      cargarEstadisticas();
    }
    if (typeof window.actualizarTituloFiltros === 'function') {
      window.actualizarTituloFiltros();
    }
  };
  
  function cargarGruposMovimientos() {
    const selectEditGrupo = document.getElementById('edit-grupo');
    if (!selectEditGrupo) {
      return;
    }

    fetch('parts/movimientos_transferencia/listar/get_grupos.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const select = document.getElementById('edit-grupo');
          if (!select) {
            return;
          }
          select.innerHTML = '<option value="">Seleccionar grupo...</option>';
          data.grupos.forEach(grupo => {
            const option = document.createElement('option');
            option.value = grupo;
            option.textContent = grupo;
            select.appendChild(option);
          });
        }
      });
  }
  
  cargarGruposMovimientos();
  
  function abrirModalNuevoApunte() {
    document.getElementById('formNuevoApunte').reset();
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('nuevo-fecha').value = hoy;
    cargarGruposNuevoApunte();
    const modal = new bootstrap.Modal(document.getElementById('modalNuevoApunte'));
    modal.show();
  }

  function cargarGruposNuevoApunte() {
    fetch('parts/movimientos_transferencia/listar/get_grupos.php')
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
          const select2 = $(select);
          if (select2.length) {
            if (select2.hasClass("select2-hidden-accessible")) {
              select2.select2('destroy');
            }
            select2.select2({
              dropdownParent: $('#modalNuevoApunte'),
              placeholder: 'Seleccionar grupo...',
              allowClear: true,
              tags: true
            });
          }
        }
      });
  }

  const btnCrearApunteTransferencia = document.getElementById('btnCrearApunte');
  if (btnCrearApunteTransferencia) {
  btnCrearApunteTransferencia.addEventListener('click', function() {
    const form = document.getElementById('formNuevoApunte');
    const salida = parseFloat(document.getElementById('nuevo-salida').value) || 0;
    const entrada = parseFloat(document.getElementById('nuevo-entrada').value) || 0;
    
    if (salida > 0 && entrada > 0) {
      Swal.fire({icon: 'warning', title: 'Advertencia', text: 'Solo uno de los campos (Salida o Entrada) debe tener valor'});
      return;
    }
    if (salida === 0 && entrada === 0) {
      Swal.fire({icon: 'warning', title: 'Advertencia', text: 'Debe ingresar un valor en Salida o Entrada'});
      return;
    }
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creando...';
    const formData = new FormData(form);
    fetch('parts/movimientos_transferencia/listar/create_movimiento.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        Swal.fire({icon: 'success', title: 'Éxito', text: 'Movimiento creado correctamente', showConfirmButton: false, timer: 1500});
        bootstrap.Modal.getInstance(document.getElementById('modalNuevoApunte')).hide();
        window.dt_movimiento.ajax.reload();
        window.recargarEstadisticasMovimientos();
      } else {
        Swal.fire({icon: 'error', title: 'Error', text: data.error || 'No se pudo crear el movimiento'});
      }
    })
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="ri-save-line me-1"></i> Crear Apunte';
    });
  });
  }

  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('editar-movimiento') || e.target.closest('.editar-movimiento')) {
      const link = e.target.classList.contains('editar-movimiento') ? e.target : e.target.closest('.editar-movimiento');
      const idMovimiento = link.getAttribute('data-id');
      abrirModalEditarMovimiento(idMovimiento);
    }
  });

  function abrirModalEditarMovimiento(idMovimiento) {
    document.getElementById('modal-movimiento-id').textContent = idMovimiento;
    fetch('parts/movimientos_transferencia/listar/get_movimiento.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'id_movimiento=' + idMovimiento
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const mov = data.movimiento;
        document.getElementById('edit-id-movimiento').value = mov.id;
        document.getElementById('edit-grupo').value = mov.grupos;
        document.getElementById('edit-descripcion').value = mov.descripcion;
        document.getElementById('edit-salida').value = mov.salida;
        document.getElementById('edit-entrada').value = mov.entrada;
        const modal = new bootstrap.Modal(document.getElementById('modalEditarMovimiento'));
        modal.show();
      } else {
        Swal.fire({icon: 'error', title: 'Error', text: data.error || 'No se pudo cargar el movimiento'});
      }
    });
  }

  const btnEliminarMovimientoTransferencia = document.getElementById('btnEliminarMovimiento');
  if (btnEliminarMovimientoTransferencia) {
  btnEliminarMovimientoTransferencia.addEventListener('click', function() {
    const idMovimiento = document.getElementById('edit-id-movimiento').value;
    Swal.fire({
      title: '¿Estás seguro?',
      text: "Esta acción no se puede deshacer.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Eliminando...';
        const formData = new FormData();
        formData.append('id_movimiento', idMovimiento);
        fetch('parts/movimientos_transferencia/listar/delete_movimiento.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({icon: 'success', title: 'Eliminado', text: 'El movimiento ha sido eliminado', showConfirmButton: false, timer: 1500});
            bootstrap.Modal.getInstance(document.getElementById('modalEditarMovimiento')).hide();
            window.dt_movimiento.ajax.reload();
            window.recargarEstadisticasMovimientos();
          } else {
            Swal.fire({icon: 'error', title: 'Error', text: data.error || 'No se pudo eliminar'});
          }
        })
        .finally(() => {
          btn.disabled = false;
          btn.innerHTML = '<i class="ri-delete-bin-line me-1"></i> Eliminar Apunte';
        });
      }
    });
  });
  }

  const btnGuardarMovimientoTransferencia = document.getElementById('btnGuardarMovimiento');
  if (btnGuardarMovimientoTransferencia) {
  btnGuardarMovimientoTransferencia.addEventListener('click', function() {
    const form = document.getElementById('formEditarMovimiento');
    const salida = parseFloat(document.getElementById('edit-salida').value) || 0;
    const entrada = parseFloat(document.getElementById('edit-entrada').value) || 0;
    
    if (salida > 0 && entrada > 0) {
      Swal.fire({icon: 'warning', title: 'Advertencia', text: 'Solo uno de los campos (Salida o Entrada) debe tener valor'});
      return;
    }
    if (salida === 0 && entrada === 0) {
      Swal.fire({icon: 'warning', title: 'Advertencia', text: 'Debe ingresar un valor en Salida o Entrada'});
      return;
    }
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Actualizando...';
    const formData = new FormData(form);
    fetch('parts/movimientos_transferencia/listar/update_movimiento.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        Swal.fire({icon: 'success', title: 'Éxito', text: 'Movimiento actualizado correctamente', showConfirmButton: false, timer: 1500});
        bootstrap.Modal.getInstance(document.getElementById('modalEditarMovimiento')).hide();
        window.dt_movimiento.ajax.reload();
        window.recargarEstadisticasMovimientos();
      } else {
        Swal.fire({icon: 'error', title: 'Error', text: data.error || 'No se pudo actualizar'});
      }
    })
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="ri-save-line me-1"></i> Actualizar Movimiento';
    });
  });
  }
});

