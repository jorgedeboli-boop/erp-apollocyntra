/**
 * Listado facturas simplificadas
 */

'use strict';

document.addEventListener('DOMContentLoaded', function (e) {
  const dt_table = document.querySelector('.datatables-facturas-renovaciones');
  window.dt_facturas_renovaciones = null;

  const FD = window.FiltrosDinamicosListar;

  function onFiltroFacturaSimplificadaChange() {
    if (window.dt_facturas_renovaciones) {
      window.dt_facturas_renovaciones.ajax.reload();
    }
  }

  const attachFiltroReload = function (select) {
    if (!select) {
      return;
    }
    FD.registerSelect(select);
    FD.initSelect2(select, onFiltroFacturaSimplificadaChange);
  };

  const createFilterSucursal = function (containerClass, selectId) {
    const select = document.createElement('select');
    select.id = selectId;
    select.className = 'form-select select2-filter text-capitalize form-select-sm select2-custom';
    select.innerHTML = '<option value="">Sucursales</option>';
    const container = document.querySelector(containerClass);
    if (!container) {
      return select;
    }
    container.appendChild(select);
    attachFiltroReload(select);
    fetch('parts/clientes/listar/get_sucursales.php')
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.success && data.sucursales) {
          data.sucursales.forEach(function (s) {
            const opt = document.createElement('option');
            opt.value = s.nombre_sucursal;
            opt.textContent = s.nombre_sucursal;
            select.appendChild(opt);
          });
          const $sel = $(select);
          if ($sel.data('select2')) {
            $sel.trigger('change.select2');
          }
        }
      })
      .catch(function (err) { console.error('Error cargar sucursales:', err); });
    return select;
  };

  const createFilterEstado = function (containerClass, selectId) {
    const select = document.createElement('select');
    select.id = selectId;
    select.className = 'form-select select2-filter text-capitalize form-select-sm select2-custom';
    select.innerHTML =
      '<option value="">Estado</option>' +
      '<option value="nopagada">No pagada</option>' +
      '<option value="pagada">Pagada</option>' +
      '<option value="anulada">Anulada</option>';
    const container = document.querySelector(containerClass);
    if (container) container.appendChild(select);
    attachFiltroReload(select);
    return select;
  };

  const createFiltrosEmpresaTipoPago = function () {
    const contEmp = document.querySelector('.factura_simplificada_empresa');
    const contTp = document.querySelector('.factura_simplificada_tipo_pago');
    if (!contEmp || !contTp) return;

    const selEmp = document.createElement('select');
    selEmp.id = 'filtro_empresa_ren';
    selEmp.className = 'form-select select2-filter text-capitalize form-select-sm select2-custom';
    selEmp.innerHTML = '<option value="">Empresas</option>';
    contEmp.appendChild(selEmp);

    const selTp = document.createElement('select');
    selTp.id = 'filtro_tipo_pago_ren';
    selTp.className = 'form-select select2-filter text-capitalize form-select-sm select2-custom';
    selTp.innerHTML = '<option value="">Tipo pago</option>';
    contTp.appendChild(selTp);

    attachFiltroReload(selEmp);
    attachFiltroReload(selTp);

    fetch('parts/facturas_renovaciones/listar/get_filtros_facturas_simplificadas.php')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) return;
        if (data.empresas) {
          data.empresas.forEach(function (e) {
            const opt = document.createElement('option');
            opt.value = String(e.id_empresa);
            opt.textContent = e.nombre_empresa || ('Empresa ' + e.id_empresa);
            selEmp.appendChild(opt);
          });
        }
        if (data.tipos_pago) {
          data.tipos_pago.forEach(function (t) {
            if (!t) return;
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            selTp.appendChild(opt);
          });
        }
        attachFiltroReload(selEmp);
        attachFiltroReload(selTp);
        const $emp = $(selEmp);
        const $tp = $(selTp);
        if ($emp.data('select2')) {
          $emp.trigger('change.select2');
        }
        if ($tp.data('select2')) {
          $tp.trigger('change.select2');
        }
      })
      .catch(function (err) { console.error('Error cargar filtros facturas simplificadas:', err); });
  };

  if (dt_table) {
    window.dt_facturas_renovaciones = new DataTable(dt_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/facturas_renovaciones/listar/load_list.php',
        type: 'POST',
        data: function (d) {
          const s = document.getElementById('filtro_sucursal_ren');
          const emp = document.getElementById('filtro_empresa_ren');
          const tp = document.getElementById('filtro_tipo_pago_ren');
          const est = document.getElementById('filtro_estado_factura_ren');
          const fd = document.getElementById('filtro_fecha_desde_ren');
          const fh = document.getElementById('filtro_fecha_hasta_ren');
          d.filtro_sucursal = s ? s.value : '';
          d.filtro_empresa = emp ? emp.value : '';
          d.filtro_tipo_pago = tp ? tp.value : '';
          d.filtro_estado_factura = est ? est.value : '';
          d.filtro_fecha_desde = fd ? fd.value : '';
          d.filtro_fecha_hasta = fh ? fh.value : '';
          d.filtro_periodo = window.filtro_periodo_activo_ren || 'todos';
          d.filtro_tipo_fecha = window.filtro_tipo_fecha_ren || 'compra';
          return d;
        },
        dataSrc: function(json) { return json.data || []; }
      },
      columns: [
        { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 },
        { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 }, { data: 10 }, { data: 11, visible: false }
      ],
      columnDefs: [
        { targets: 0, render: function(data, type, row) { const url = row && row[11] ? row[11] : ('Impresiones/Facturas/factura_simplificada.php?id_factura=' + data); return '<a href="' + url + '" target="_blank" class="fw-semibold text-primary">' + data + '</a>'; } },
        { targets: 1, render: function(data) { return data || '-'; } },
        { targets: 2, render: function(data) { return data || '-'; } },
        { targets: 3, render: function(data) { return data || '-'; } },
        { targets: 4, render: function(data) { return data || '-'; } },
        { targets: 5, render: function(data) { return data || '-'; } },
        { targets: 6, render: function(data) { return '<span class="fw-semibold text-success">' + (data || '-') + '</span>'; } },
        {
          targets: 7,
          render: function(data) {
            let c = 'secondary';
            if (data === 'pagada') c = 'success'; else if (data === 'anulada') c = 'danger'; else if (data === 'nopagada') c = 'warning';
            return '<span class="badge bg-label-' + c + ' rounded-pill">' + (data || '-') + '</span>';
          }
        },
        {
          targets: 8,
          render: function(data) {
            if (!data || data === '-') return '-';
            const v = (data || '').toLowerCase();
            let c = 'secondary';
            if (v === 'contado' || v === 'efectivo') c = 'success'; else if (v === 'tarjeta') c = 'primary'; else if (v === 'transferencia') c = 'info'; else if (v === 'bizum') c = 'warning';
            return '<span class="badge bg-label-' + c + ' rounded-pill">' + data + '</span>';
          }
        },
        {
          targets: 9,
          render: function(data) {
            if (!data || data === '-') return '-';
            const v = (data || '').toLowerCase();
            let c = 'secondary';
            if (v === 'articulos') c = 'primary';
            else if (v === 'renovaciones') c = 'info';
            else if (v === 'oro_inversion') c = 'warning';
            return '<span class="badge bg-label-' + c + ' rounded-pill">' + data + '</span>';
          }
        },
        {
          targets: 10,
          orderable: false,
          responsivePriority: 1,
          render: function(data, type, row) {
            const id = typeof data !== 'undefined' ? data : (row && row[0]);
            const url = row && row[11] ? row[11] : ('Impresiones/Facturas/factura_simplificada.php?id_factura=' + id);
            return '<div class="dropdown">' +
              '<button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">' +
              '<i class="icon-base ri ri-more-2-fill icon-28px"></i></button>' +
              '<div class="dropdown-menu dropdown-menu-end">' +
              '<a class="dropdown-item" href="' + url + '" target="_blank"><i class="icon-base ri ri-file-text-line me-2"></i>Ver PDF</a>' +
              '<a class="dropdown-item btn-enviar-factura-simplificada" href="javascript:void(0)" data-id="' + id + '"><i class="icon-base ri ri-mail-send-line me-2"></i>Enviar por email</a>' +
              '</div></div>';
          }
        }
      ],
      order: [[2, 'desc']],
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      layout: {
        topStart: {
          rowClass: 'row m-2 my-0 mt-0 justify-content-between',
          features: [{
            buttons: [{
              extend: 'collection',
              className: 'btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect button-exportar',
              text: '<span class="d-flex align-items-center justify-content-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px"></i><span>Exportar</span></span>',
              buttons: [
                { extend: 'print', text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-printer-line me-1"></i>Imprimir</span>', className: 'dropdown-item', action: function(e, dt) { exportarTodosLosDatos('print', dt); } },
                { extend: 'csv', text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-text-line me-1"></i>CSV</span>', className: 'dropdown-item', action: function(e, dt) { exportarTodosLosDatos('csv', dt); } },
                { extend: 'excel', text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>', className: 'dropdown-item', action: function(e, dt) { exportarTodosLosDatos('excel', dt); } },
                { extend: 'pdf', text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>', className: 'dropdown-item', action: function(e, dt) { exportarTodosLosDatos('pdf', dt); } }
              ]
            }]
          }]
        },
        topEnd: { features: [{ search: { placeholder: 'Buscar...', text: '_INPUT_' } }] },
        bottomStart: null,
        bottomEnd: { features: ['paging'] }
      },
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({ header: function(row) { return 'Factura simplificada ' + (row.data()[0]); } }),
          renderer: $.fn.dataTable.Responsive.renderer.tableAll({ tableClass: 'table' })
        }
      }
    });

    setTimeout(function() {
      document.querySelectorAll('.dt-layout-topStart').forEach(el => el.classList.add('ps-3'));
      document.querySelectorAll('.dt-layout-topEnd').forEach(el => el.classList.add('pe-3'));
    }, 100);

    configurarFiltrosFacturasSimplificadas();
  }

  function configurarFiltrosFacturasSimplificadas() {
    window.filtro_periodo_activo_ren = 'todos';
    window.filtro_tipo_fecha_ren = 'compra';

    const filtroFechaDesde = document.getElementById('filtro_fecha_desde_ren');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta_ren');
    const rangeFechas = document.getElementById('rangeFechasRen');

    if (filtroFechaDesde) filtroFechaDesde.value = '';
    if (filtroFechaHasta) filtroFechaHasta.value = '';
    if (rangeFechas) rangeFechas.value = '';

    const reloadFacturas = function () {
      if (window.dt_facturas_renovaciones) window.dt_facturas_renovaciones.ajax.reload();
    };

    const selBtnsFs = '#filtro_por_fecha_compra_ren, #filtro_por_fecha_vencimiento_ren, #filtro_dia_ren, #filtro_mes_ren, #filtro_todos_ren';

    const filtroPorFechaCompra = document.getElementById('filtro_por_fecha_compra_ren');
    if (filtroPorFechaCompra) {
      filtroPorFechaCompra.addEventListener('click', function () {
        if (!filtroFechaDesde || !filtroFechaHasta) return;
        if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
          Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe seleccionar al menos una fecha', confirmButtonText: 'Aceptar' });
          return;
        }
        window.filtro_periodo_activo_ren = 'fecha';
        window.filtro_tipo_fecha_ren = 'compra';
        document.querySelectorAll(selBtnsFs).forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
    }

    const filtroPorFechaVencimiento = document.getElementById('filtro_por_fecha_vencimiento_ren');
    if (filtroPorFechaVencimiento) {
      filtroPorFechaVencimiento.addEventListener('click', function () {
        if (!filtroFechaDesde || !filtroFechaHasta) return;
        if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
          Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe seleccionar al menos una fecha', confirmButtonText: 'Aceptar' });
          return;
        }
        window.filtro_periodo_activo_ren = 'fecha';
        window.filtro_tipo_fecha_ren = 'vencimiento';
        document.querySelectorAll(selBtnsFs).forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
    }

    const filtroDia = document.getElementById('filtro_dia_ren');
    if (filtroDia) {
      filtroDia.addEventListener('click', function () {
        if (!filtroFechaDesde || !filtroFechaHasta) return;
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo_ren = 'dia';
        window.filtro_tipo_fecha_ren = 'compra';
        document.querySelectorAll(selBtnsFs).forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
    }

    const filtroMes = document.getElementById('filtro_mes_ren');
    if (filtroMes) {
      filtroMes.addEventListener('click', function () {
        if (!filtroFechaDesde || !filtroFechaHasta) return;
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo_ren = 'mes';
        window.filtro_tipo_fecha_ren = 'compra';
        document.querySelectorAll(selBtnsFs).forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
    }

    const filtroTodos = document.getElementById('filtro_todos_ren');
    if (filtroTodos) {
      filtroTodos.addEventListener('click', function () {
        if (filtroFechaDesde) filtroFechaDesde.value = '';
        if (filtroFechaHasta) filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo_ren = 'todos';
        window.filtro_tipo_fecha_ren = 'compra';
        document.querySelectorAll(selBtnsFs).forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
      filtroTodos.classList.add('active');
    }
  }

  function exportarTodosLosDatos(tipo, dt) {
    const filtro = document.getElementById('filtro_sucursal_ren');
    const filtroEmp = document.getElementById('filtro_empresa_ren');
    const filtroTp = document.getElementById('filtro_tipo_pago_ren');
    const filtroEst = document.getElementById('filtro_estado_factura_ren');
    const filtroFd = document.getElementById('filtro_fecha_desde_ren');
    const filtroFh = document.getElementById('filtro_fecha_hasta_ren');
    const formData = new FormData();
    formData.append('search', dt.search());
    formData.append('filtro_sucursal', filtro ? filtro.value : '');
    formData.append('filtro_empresa', filtroEmp ? filtroEmp.value : '');
    formData.append('filtro_tipo_pago', filtroTp ? filtroTp.value : '');
    formData.append('filtro_estado_factura', filtroEst ? filtroEst.value : '');
    formData.append('filtro_fecha_desde', filtroFd ? filtroFd.value : '');
    formData.append('filtro_fecha_hasta', filtroFh ? filtroFh.value : '');
    formData.append('filtro_periodo', window.filtro_periodo_activo_ren || 'todos');
    formData.append('filtro_tipo_fecha', window.filtro_tipo_fecha_ren || 'compra');
    Swal.fire({ title: 'Generando exportación...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    fetch('parts/facturas_renovaciones/listar/export_all.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(data => {
        Swal.close();
        if (!data.success || !data.data || data.data.length === 0) {
          Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay datos para exportar.' });
          return;
        }
        const tid = 'temp-export-' + Date.now();
        const div = document.createElement('div');
        div.style.display = 'none';
        div.innerHTML = '<table id="' + tid + '"><thead><tr><th>Nº</th><th>NÚMERO</th><th>FECHA</th><th>HORA</th><th>SUCURSAL</th><th>EMPRESA</th><th>TOTAL</th><th>ESTADO</th><th>TIPO PAGO</th><th>TIPO</th></tr></thead></table>';
        document.body.appendChild(div);
        const $t = $('#' + tid).DataTable({ data: data.data, searching: false, ordering: false, dom: 't' });
        $t.button().add(0, { extend: tipo === 'excel' ? 'excelHtml5' : tipo }).trigger();
        setTimeout(function() { $t.destroy(); div.remove(); }, 1000);
      })
      .catch(err => { Swal.close(); Swal.fire({ icon: 'error', title: 'Error', text: err.message }); });
  }

  document.addEventListener('click', function(e) {
    const btnEnv = e.target.closest('.btn-enviar-factura-simplificada');
    if (btnEnv) {
      e.preventDefault();
      const id = btnEnv.getAttribute('data-id');
      if (!id) return;
      Swal.fire({
        title: 'Enviar factura simplificada',
        input: 'email',
        inputLabel: 'Correo electrónico',
        inputPlaceholder: 'email@ejemplo.com',
        showCancelButton: true,
        inputValidator: function(value) {
          if (!value) return 'Introduzca un correo';
          const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!re.test(value)) return 'Correo no válido';
        }
      }).then(function(result) {
        if (result.isConfirmed && result.value) {
          const fd = new FormData();
          fd.append('id_factura', id);
          fd.append('email', result.value);
          fetch('parts/facturas_renovaciones/listar/enviar_factura_simplificada.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
              if (res.success) Swal.fire({ icon: 'success', title: 'Enviado', text: res.message || 'Factura enviada.' });
              else Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Error al enviar' });
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' }));
        }
      });
    }
  });

  createFilterSucursal('.factura_simplificada_sucursal', 'filtro_sucursal_ren');
  createFilterEstado('.factura_simplificada_estado', 'filtro_estado_factura_ren');
  createFiltrosEmpresaTipoPago();
  if (FD) {
    FD.finalize();
  }
});
