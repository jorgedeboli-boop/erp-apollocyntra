/**
 * Listado servicios
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dt_table = document.querySelector('.datatables-servicios');
  let dt_servicios;

  const createFilterEmpresa = () => {
    const select = document.createElement('select');
    select.id = 'filtro_empresa_servicio';
    select.className = 'form-select select2-filter text-capitalize select2-custom';
    select.innerHTML = '<option value="">Empresa</option>';
    document.querySelector('.servicio_empresa').appendChild(select);

    fetch('parts/servicios/listar/get_empresas.php')
      .then((r) => r.json())
      .then((data) => {
        if (data.success && data.empresas) {
          data.empresas.forEach((em) => {
            const o = document.createElement('option');
            o.value = em.id_empresa;
            o.textContent = em.nombre_empresa;
            select.appendChild(o);
          });
        }
        const $s = $(select);
        $s.select2({ dropdownParent: $s.parent() });
        $s.on('select2:select select2:unselect', () => {
          dt_servicios.ajax.reload();
          actualizarTituloServicios();
        });
        select.addEventListener('change', () => {
          dt_servicios.ajax.reload();
          actualizarTituloServicios();
        });
      })
      .catch(() => {});
    return select;
  };

  const createFilterFijo = (containerClass, selectId, placeholder, opciones) => {
    const select = document.createElement('select');
    select.id = selectId;
    select.className = 'form-select select2-filter text-capitalize select2-custom';
    select.innerHTML = `<option value="">${placeholder}</option>`;
    document.querySelector(containerClass).appendChild(select);
    opciones.forEach((op) => {
      const o = document.createElement('option');
      o.value = op.value;
      o.textContent = op.label;
      select.appendChild(o);
    });
    const $s = $(select);
    $s.select2({ dropdownParent: $s.parent() });
    $s.on('select2:select select2:unselect', () => {
      dt_servicios.ajax.reload();
      actualizarTituloServicios();
    });
    select.addEventListener('change', () => {
      dt_servicios.ajax.reload();
      actualizarTituloServicios();
    });
    return select;
  };

  function actualizarTituloServicios() {
    const el = document.getElementById('texto_servicios_titulo');
    if (!el) return;
    const partes = [];
    const fe = document.getElementById('filtro_empresa_servicio');
    if (fe && fe.value) {
      partes.push(fe.options[fe.selectedIndex].text);
    }
    const fa = document.getElementById('filtro_activo_servicio');
    if (fa && fa.value !== '') {
      partes.push(fa.options[fa.selectedIndex].text);
    }
    const ft = document.getElementById('filtro_tipo_fact_servicio');
    if (ft && ft.value) {
      partes.push(ft.options[ft.selectedIndex].text);
    }
    el.textContent = partes.length ? '— ' + partes.join(' · ') : '';
    window.titulo_filtros_servicios = el.textContent;
  }

  if (dt_table) {
    createFilterEmpresa();
    createFilterFijo('.servicio_activo', 'filtro_activo_servicio', 'Activo', [
      { value: '1', label: 'Activos' },
      { value: '0', label: 'Inactivos' },
    ]);
    createFilterFijo('.servicio_tipo_fact', 'filtro_tipo_fact_servicio', 'Tipo facturación', [
      { value: 'por_hora', label: 'Por hora' },
      { value: 'precio_fijo', label: 'Precio fijo' },
      { value: 'por_sesion', label: 'Por sesión' },
    ]);

    dt_servicios = new DataTable(dt_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 400,
      language: typeof DATATABLES_SPANISH !== 'undefined' ? DATATABLES_SPANISH : {},
      ajax: {
        url: 'parts/servicios/listar/load_list.php',
        type: 'POST',
        data: function (d) {
          const fe = document.getElementById('filtro_empresa_servicio');
          const fa = document.getElementById('filtro_activo_servicio');
          const ft = document.getElementById('filtro_tipo_fact_servicio');
          d.filtro_empresa = fe ? fe.value : '';
          d.filtro_activo = fa ? fa.value : '';
          d.filtro_tipo_fact = ft ? ft.value : '';
          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
      },
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6 },
        { data: 7 },
        { data: 8 },
        { data: 9 },
        { data: 10 },
        { data: 11 },
        { data: 12, visible: false },
      ],
      columnDefs: [
        {
          targets: 0,
          responsivePriority: 1,
          render: function (data) {
            return '<span class="fw-semibold">' + data + '</span>';
          },
        },
      ],
      order: [[0, 'desc']],
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
                  className: 'btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect button-exportar',
                  text: '<span class="d-flex align-items-center justify-content-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px"></i> <span>Exportar</span></span>',
                  buttons: [
                                        {
                      extend: 'excel',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
                      className: 'dropdown-item',
                      action: function (e, dt, button, config) {
                        exportarServiciosTodos('excel', dt, button, config);
                      },
                    },
                    {
                      extend: 'copy',
                      text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar',
                      className: 'dropdown-item',
                      action: function (e, dt, button, config) {
                        exportarServiciosTodos('copy', dt, button, config);
                      },
                    },
                  ],
                },
              ],
            },
          ],
        },
        topEnd: {
          features: [
            {
              search: {
                placeholder: 'Buscar...',
                text: '_INPUT_',
              },
            },
          ],
        },
        bottomStart: {
          rowClass: 'row mx-3 justify-content-between',
          features: ['info'],
        },
        bottomEnd: 'paging',
      },
      responsive: { details: false },
      createdRow: function (row, data) {
        $(row).css('cursor', 'pointer');
        $(row).on('click', function (e) {
          if ($(e.target).closest('button, a, .select2').length > 0) return;
          const id = data[12];
          if (id) {
            window.location.href = 'servicio.php?id=' + id;
          }
        });
      },
    });

    actualizarTituloServicios();
  }

  window.exportarServiciosTodos = function (tipo, dt, button, config) {
    const searchValue = dt.search();
    const fe = document.getElementById('filtro_empresa_servicio');
    const fa = document.getElementById('filtro_activo_servicio');
    const ft = document.getElementById('filtro_tipo_fact_servicio');

    Swal.fire({
      title: 'Generando exportación...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const formData = new FormData();
    formData.append('search', searchValue);
    formData.append('filtro_empresa', fe ? fe.value : '');
    formData.append('filtro_activo', fa ? fa.value : '');
    formData.append('filtro_tipo_fact', ft ? ft.value : '');

    fetch('parts/servicios/listar/export_all.php', { method: 'POST', body: formData })
      .then((r) => r.json())
      .then((responseData) => {
        Swal.close();
        if (!responseData.success) {
          throw new Error(responseData.error || 'Error al exportar');
        }
        if (!responseData.data || responseData.data.length === 0) {
          Swal.fire({ title: 'Sin datos', icon: 'info', confirmButtonText: 'Aceptar' });
          return;
        }
        const tempTableId = 'temp-export-servicios-' + Date.now();
        const tempDiv = document.createElement('div');
        tempDiv.style.display = 'none';
        tempDiv.innerHTML =
          '<table id="' +
          tempTableId +
          '"><thead><tr><th>ID</th><th>Código</th><th>Nombre</th><th>Empresa</th><th>Categoría</th><th>Activo</th><th>Tipo fact.</th><th>Precio/h</th><th>Precio fijo</th><th>IVA %</th><th>Unidad</th><th>Modificación</th></tr></thead></table>';
        document.body.appendChild(tempDiv);
        const tempTable = $('#' + tempTableId).DataTable({
          data: responseData.data,
          columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6 },
            { data: 7 },
            { data: 8 },
            { data: 9 },
            { data: 10 },
            { data: 11 },
          ],
          paging: false,
          searching: false,
          ordering: false,
          dom: 't',
        });
        const buttonType = tipo === 'excel' ? 'excelHtml5' : tipo;
        const tempButton = tempTable.button().add(0, {
          extend: buttonType,
          exportOptions: { columns: ':visible' },
        });
        tempButton.trigger();
        setTimeout(() => {
          tempTable.destroy();
          tempDiv.remove();
        }, 1500);
      })
      .catch((err) => {
        Swal.close();
        Swal.fire({ title: 'Error', text: err.message, icon: 'error', confirmButtonText: 'Aceptar' });
      });
  };

  const btnNuevo = document.getElementById('btn_nuevo_servicio');
  if (btnNuevo) {
    btnNuevo.addEventListener('click', function (e) {
      e.preventDefault();
      Swal.fire({
        icon: 'question',
        title: '¿Crear nuevo servicio?',
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'crear_servicio.php';
        }
      });
    });
  }

  setTimeout(() => {
    document.querySelectorAll('.dt-buttons .btn').forEach((el) => el.classList.remove('btn-secondary'));
  }, 100);
});
