/**
 * Page Facturas List
 */

'use strict';

document.addEventListener('DOMContentLoaded', function (e) {
  const dt_table = document.querySelector('.datatables-facturas');
  window.dt_facturas = null;

  const FD = window.FiltrosDinamicosListar;

  function onFiltroFacturaChange() {
    if (window.dt_facturas) {
      window.dt_facturas.ajax.reload();
    }
  }

  const attachFiltroReload = function (select) {
    if (!select) {
      return;
    }
    FD.registerSelect(select);
    FD.initSelect2(select, onFiltroFacturaChange);
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
    const contEmp = document.querySelector('.factura_empresa');
    const contTp = document.querySelector('.factura_tipo_pago');
    if (!contEmp || !contTp) return;

    const selEmp = document.createElement('select');
    selEmp.id = 'filtro_empresa';
    selEmp.className = 'form-select select2-filter text-capitalize form-select-sm select2-custom';
    selEmp.innerHTML = '<option value="">Empresas</option>';
    contEmp.appendChild(selEmp);

    const selTp = document.createElement('select');
    selTp.id = 'filtro_tipo_pago';
    selTp.className = 'form-select select2-filter text-capitalize form-select-sm select2-custom';
    selTp.innerHTML = '<option value="">Tipo pago</option>';
    contTp.appendChild(selTp);

    attachFiltroReload(selEmp);
    attachFiltroReload(selTp);

    fetch('parts/facturas/listar/get_filtros_facturas.php')
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
      .catch(function (err) { console.error('Error cargar filtros facturas:', err); });
  };

  if (dt_table) {
    window.dt_facturas = new DataTable(dt_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/facturas/listar/load_list.php',
        type: 'POST',
        data: function (d) {
          const s = document.getElementById('filtro_sucursal');
          const emp = document.getElementById('filtro_empresa');
          const tp = document.getElementById('filtro_tipo_pago');
          const est = document.getElementById('filtro_estado_factura');
          const fd = document.getElementById('filtro_fecha_desde');
          const fh = document.getElementById('filtro_fecha_hasta');
          d.filtro_sucursal = s ? s.value : '';
          d.filtro_empresa = emp ? emp.value : '';
          d.filtro_tipo_pago = tp ? tp.value : '';
          d.filtro_estado_factura = est ? est.value : '';
          d.filtro_fecha_desde = fd ? fd.value : '';
          d.filtro_fecha_hasta = fh ? fh.value : '';
          d.filtro_periodo = window.filtro_periodo_activo || 'todos';
          d.filtro_tipo_fecha = window.filtro_tipo_fecha || 'compra';
          return d;
        },
        dataSrc: function(json) { return json.data || []; }
      },
      columns: [
        { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 },
        { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 }, { data: 10 }, { data: 11 },
        { data: 12 }, { data: 13, visible: false }
      ],
      columnDefs: [
        { targets: 0, render: function(data, type, row) { const url = row && row[13] ? row[13] : ('Impresiones/Facturas/factura.php?id_factura=' + data); return '<a href="' + url + '" target="_blank" class="fw-semibold text-primary">' + data + '</a>'; } },
        { targets: 1, render: function(data) { return data || '-'; } },
        { targets: 2, render: function(data) { return data || '-'; } },
        { targets: 3, render: function(data) { return data || '-'; } },
        { targets: 4, render: function(data) { return data || '-'; } },
        { targets: 5, render: function(data) { return data || '-'; } },
        { targets: 6, render: function(data) { return data || '-'; } },
        { targets: 7, render: function(data) { return '<span class="fw-semibold text-success">' + (data || '-') + '</span>'; } },
        {
          targets: 8,
          render: function(data) {
            let c = 'secondary';
            if (data === 'pagada') c = 'success'; else if (data === 'anulada') c = 'danger'; else if (data === 'nopagada') c = 'warning';
            return '<span class="badge bg-label-' + c + ' rounded-pill">' + (data || '-') + '</span>';
          }
        },
        {
          targets: 9,
          render: function(data) {
            if (!data || data === '-') return '-';
            const v = (data || '').toLowerCase();
            let c = 'secondary';
            if (v === 'contado' || v === 'efectivo') c = 'success'; else if (v === 'tarjeta') c = 'primary'; else if (v === 'transferencia') c = 'info'; else if (v === 'bizum') c = 'warning';
            return '<span class="badge bg-label-' + c + ' rounded-pill">' + data + '</span>';
          }
        },
        {
          targets: 10,
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
          targets: 11,
          render: function(data) {
            if (!data || data === '-') return '-';
            const v = String(data);
            let c = 'secondary';
            if (v === 'Regimen general' || v === 'General') c = 'secondary';
            else if (v === 'Verifactu') c = 'primary';
            else if (v.indexOf('TicketBAI') === 0) c = 'info';
            return '<span class="badge bg-label-' + c + ' rounded-pill">' + data + '</span>';
          }
        },
        {
          targets: 12,
          orderable: false,
          responsivePriority: 1,
          render: function(data, type, row) {
            const id = typeof data !== 'undefined' ? data : (row && row[0]);
            const url = row && row[13] ? row[13] : ('Impresiones/Facturas/factura.php?id_factura=' + id);
            return '<div class="dropdown">' +
              '<button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">' +
              '<i class="icon-base ri ri-more-2-fill icon-28px"></i></button>' +
              '<div class="dropdown-menu dropdown-menu-end">' +
              '<a class="dropdown-item" href="' + url + '" target="_blank"><i class="icon-base ri ri-file-text-line me-2"></i>Ver factura</a>' +
              '<a class="dropdown-item btn-enviar-factura" href="javascript:void(0)" data-id="' + id + '"><i class="icon-base ri ri-mail-send-line me-2"></i>Enviar factura</a>' +
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
          display: $.fn.dataTable.Responsive.display.modal({ header: function(row) { return 'Factura ' + (row.data()[0]); } }),
          renderer: $.fn.dataTable.Responsive.renderer.tableAll({ tableClass: 'table' })
        }
      }
    });

    setTimeout(function() {
      document.querySelectorAll('.dt-layout-topStart').forEach(el => el.classList.add('ps-3'));
      document.querySelectorAll('.dt-layout-topEnd').forEach(el => el.classList.add('pe-3'));
    }, 100);

    configurarFiltrosFacturas();
  }

  function configurarFiltrosFacturas() {
    window.filtro_periodo_activo = 'todos';
    window.filtro_tipo_fecha = 'compra';

    const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
    const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
    const rangeFechas = document.getElementById('rangeFechas');

    if (filtroFechaDesde) filtroFechaDesde.value = '';
    if (filtroFechaHasta) filtroFechaHasta.value = '';
    if (rangeFechas) rangeFechas.value = '';

    const reloadFacturas = function () {
      if (window.dt_facturas) window.dt_facturas.ajax.reload();
    };

    const filtroPorFechaCompra = document.getElementById('filtro_por_fecha_compra');
    if (filtroPorFechaCompra) {
      filtroPorFechaCompra.addEventListener('click', function () {
        if (!filtroFechaDesde || !filtroFechaHasta) return;
        if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
          Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe seleccionar al menos una fecha', confirmButtonText: 'Aceptar' });
          return;
        }
        window.filtro_periodo_activo = 'fecha';
        window.filtro_tipo_fecha = 'compra';
        document.querySelectorAll('#filtro_por_fecha_compra, #filtro_por_fecha_vencimiento, #filtro_dia, #filtro_mes, #filtro_todos').forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
    }

    const filtroPorFechaVencimiento = document.getElementById('filtro_por_fecha_vencimiento');
    if (filtroPorFechaVencimiento) {
      filtroPorFechaVencimiento.addEventListener('click', function () {
        if (!filtroFechaDesde || !filtroFechaHasta) return;
        if (!filtroFechaDesde.value && !filtroFechaHasta.value) {
          Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe seleccionar al menos una fecha', confirmButtonText: 'Aceptar' });
          return;
        }
        window.filtro_periodo_activo = 'fecha';
        window.filtro_tipo_fecha = 'vencimiento';
        document.querySelectorAll('#filtro_por_fecha_compra, #filtro_por_fecha_vencimiento, #filtro_dia, #filtro_mes, #filtro_todos').forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
    }

    const filtroDia = document.getElementById('filtro_dia');
    if (filtroDia) {
      filtroDia.addEventListener('click', function () {
        if (!filtroFechaDesde || !filtroFechaHasta) return;
        const hoy = new Date().toISOString().split('T')[0];
        filtroFechaDesde.value = hoy;
        filtroFechaHasta.value = hoy;
        window.filtro_periodo_activo = 'dia';
        window.filtro_tipo_fecha = 'compra';
        document.querySelectorAll('#filtro_por_fecha_compra, #filtro_por_fecha_vencimiento, #filtro_dia, #filtro_mes, #filtro_todos').forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
    }

    const filtroMes = document.getElementById('filtro_mes');
    if (filtroMes) {
      filtroMes.addEventListener('click', function () {
        if (!filtroFechaDesde || !filtroFechaHasta) return;
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo = 'mes';
        window.filtro_tipo_fecha = 'compra';
        document.querySelectorAll('#filtro_por_fecha_compra, #filtro_por_fecha_vencimiento, #filtro_dia, #filtro_mes, #filtro_todos').forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
    }

    const filtroTodos = document.getElementById('filtro_todos');
    if (filtroTodos) {
      filtroTodos.addEventListener('click', function () {
        if (filtroFechaDesde) filtroFechaDesde.value = '';
        if (filtroFechaHasta) filtroFechaHasta.value = '';
        if (rangeFechas) rangeFechas.value = '';
        window.filtro_periodo_activo = 'todos';
        window.filtro_tipo_fecha = 'compra';
        document.querySelectorAll('#filtro_por_fecha_compra, #filtro_por_fecha_vencimiento, #filtro_dia, #filtro_mes, #filtro_todos').forEach(function (btn) { btn.classList.remove('active'); });
        this.classList.add('active');
        reloadFacturas();
      });
      filtroTodos.classList.add('active');
    }
  }

  function exportarTodosLosDatos(tipo, dt) {
    const filtro = document.getElementById('filtro_sucursal');
    const filtroEmp = document.getElementById('filtro_empresa');
    const filtroTp = document.getElementById('filtro_tipo_pago');
    const filtroEst = document.getElementById('filtro_estado_factura');
    const filtroFd = document.getElementById('filtro_fecha_desde');
    const filtroFh = document.getElementById('filtro_fecha_hasta');
    const formData = new FormData();
    formData.append('search', dt.search());
    formData.append('filtro_sucursal', filtro ? filtro.value : '');
    formData.append('filtro_empresa', filtroEmp ? filtroEmp.value : '');
    formData.append('filtro_tipo_pago', filtroTp ? filtroTp.value : '');
    formData.append('filtro_estado_factura', filtroEst ? filtroEst.value : '');
    formData.append('filtro_fecha_desde', filtroFd ? filtroFd.value : '');
    formData.append('filtro_fecha_hasta', filtroFh ? filtroFh.value : '');
    formData.append('filtro_periodo', window.filtro_periodo_activo || 'todos');
    formData.append('filtro_tipo_fecha', window.filtro_tipo_fecha || 'compra');
    Swal.fire({ title: 'Generando exportación...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    fetch('parts/facturas/listar/export_all.php', { method: 'POST', body: formData })
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
        div.innerHTML = '<table id="' + tid + '"><thead><tr><th>Nº</th><th>NÚMERO</th><th>FECHA</th><th>HORA</th><th>CLIENTE</th><th>SUCURSAL</th><th>EMPRESA</th><th>TOTAL</th><th>ESTADO</th><th>TIPO PAGO</th><th>TIPO</th><th>REGIMEN</th></tr></thead></table>';
        document.body.appendChild(div);
        const $t = $('#' + tid).DataTable({ data: data.data, searching: false, ordering: false, dom: 't' });
        $t.button().add(0, { extend: tipo === 'excel' ? 'excelHtml5' : tipo }).trigger();
        setTimeout(function() { $t.destroy(); div.remove(); }, 1000);
      })
      .catch(err => { Swal.close(); Swal.fire({ icon: 'error', title: 'Error', text: err.message }); });
  }

  document.addEventListener('click', function(e) {
    const btnEnv = e.target.closest('.btn-enviar-factura');
    if (btnEnv) {
      e.preventDefault();
      const id = btnEnv.getAttribute('data-id');
      if (!id) return;
      Swal.fire({
        title: 'Enviar factura',
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
          fetch('parts/facturas/listar/enviar_factura.php', { method: 'POST', body: fd })
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

  createFilterSucursal('.factura_sucursal', 'filtro_sucursal');
  createFilterEstado('.factura_estado', 'filtro_estado_factura');
  createFiltrosEmpresaTipoPago();
  if (FD) {
    FD.finalize();
  }
});
