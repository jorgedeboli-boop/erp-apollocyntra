/**
 * Page Semanas Config
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dt_table = document.querySelector('.datatables-semanas-config');
  const modalEl = document.getElementById('modalEditarPreciosSemana');
  const formPreciosSemana = document.getElementById('formEditarPreciosSemana');
  const btnGuardarPreciosSemana = document.getElementById('btnGuardarPreciosSemana');
  let modalPreciosSemana = null;

  if (modalEl) {
    modalPreciosSemana = bootstrap.Modal.getOrCreateInstance(modalEl);
  }

  function inicializarSelect2() {
    if (typeof $ === 'undefined' || !$.fn.select2) {
      return;
    }

    $('.select2-filter').each(function () {
      const $this = $(this);
      if ($this.hasClass('select2-hidden-accessible')) {
        return;
      }

      if (typeof select2Focus === 'function') {
        select2Focus($this);
      }

      $this.select2({
        dropdownParent: $this.parent(),
        allowClear: $this.attr('id') === 'filtro_semana',
        width: '100%'
      });
    });
  }

  function recargarTabla() {
    if (window.dt_semanas_config) {
      window.dt_semanas_config.ajax.reload(null, false);
    }
  }

  function formatearEtiquetaSemana(semana) {
    let label = 'Semana ' + semana.numero_semana;
    if (semana.fecha_semana_desde && semana.fecha_semana_hasta) {
      const desde = new Date(semana.fecha_semana_desde + 'T00:00:00');
      const hasta = new Date(semana.fecha_semana_hasta + 'T00:00:00');
      const fmt = { day: '2-digit', month: '2-digit', year: 'numeric' };
      label += ' (' + desde.toLocaleDateString('es-ES', fmt) + ' - ' + hasta.toLocaleDateString('es-ES', fmt) + ')';
    }
    return label;
  }

  function cargarSemanas(anio, semanaSeleccionada) {
    const selectSemana = document.getElementById('filtro_semana');
    if (!selectSemana) {
      return Promise.resolve();
    }

    return fetch('parts/semanas_config/unique/get_semanas.php?anio=' + encodeURIComponent(anio))
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'No se pudieron cargar las semanas');
        }

        const semanas = data.semanas || [];
        const numerosSemana = semanas.map(function (semana) {
          return String(semana.numero_semana);
        });
        let semanaActual = semanaSeleccionada !== undefined && semanaSeleccionada !== null
          ? String(semanaSeleccionada)
          : '';

        if (semanaActual && numerosSemana.indexOf(semanaActual) === -1) {
          semanaActual = '';
        }

        selectSemana.innerHTML = '<option value="">Todas las semanas</option>';
        semanas.forEach(function (semana) {
          const option = document.createElement('option');
          option.value = semana.numero_semana;
          option.textContent = formatearEtiquetaSemana(semana);
          if (String(semana.numero_semana) === semanaActual) {
            option.selected = true;
          }
          selectSemana.appendChild(option);
        });

        if (typeof $ !== 'undefined' && $.fn.select2) {
          $(selectSemana).trigger('change.select2');
        }
      });
  }

  function bindFiltros() {
    const filtroAnio = document.getElementById('filtro_anio');
    const filtroSemana = document.getElementById('filtro_semana');

    if (filtroSemana) {
      $(filtroSemana).on('change select2:select select2:clear', recargarTabla);
    }

    if (filtroAnio) {
      $(filtroAnio).on('change select2:select', function () {
        const semanaPrev = filtroSemana ? filtroSemana.value : '';
        cargarSemanas(filtroAnio.value, semanaPrev).then(recargarTabla);
      });
    }
  }

  function inicializarFiltrosFecha() {
    setTimeout(function () {
      const rangeEl = document.getElementById('rangeFechas');
      if (!rangeEl) {
        return;
      }

      document.getElementById('semanas_config_filtro_dia')?.addEventListener('click', function (e) {
        e.preventDefault();
        const fp = rangeEl._flatpickr;
        if (!fp) {
          return;
        }
        const d = new Date();
        d.setHours(0, 0, 0, 0);
        fp.setDate([d, d], true);
      });

      document.getElementById('semanas_config_filtro_mes')?.addEventListener('click', function (e) {
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

      document.getElementById('semanas_config_filtro_todos')?.addEventListener('click', function (e) {
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

  function formatearPorcentaje(valor) {
    const n = parseFloat(valor);
    if (Number.isNaN(n)) {
      return '0,00 %';
    }
    return n.toLocaleString('es-ES', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' %';
  }

  function renderPorcentajeMedia(data, type) {
    if (type === 'sort' || type === 'type' || type === 'filter') {
      return data;
    }

    const n = parseFloat(data);
    const valor = Number.isNaN(n) ? 0 : n;
    let claseBadge = 'bg-label-primary';

    if (valor > 0) {
      claseBadge = 'bg-label-success';
    } else if (valor < 0) {
      claseBadge = 'bg-label-danger';
    }

    return '<span class="badge ' + claseBadge + ' rounded-pill text-nowrap">' +
      formatearPorcentaje(valor) + '</span>';
  }

  function formatearEuro(valor) {
    const n = parseFloat(valor);
    if (Number.isNaN(n)) {
      return '0,00 €';
    }
    return n.toLocaleString('es-ES', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' €';
  }

  const COL_PRECIO_MERCADO = 4;
  const COL_PRECIO_ORO = 6;
  const COL_CALCULO_PRECIO = 7;
  const COL_ID = 8;
  const COL_PRECIO_ORO_ANT = 9;
  const COL_PRECIO_MERCADO_ANT = 10;

  function parsePrecioNum(valor) {
    if (valor === null || valor === undefined || valor === '') {
      return NaN;
    }
    if (typeof valor === 'number') {
      return valor;
    }

    const texto = String(valor).trim().replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
    const n = parseFloat(texto);
    return Number.isNaN(n) ? NaN : n;
  }

  function compararPrecioSemana(actual, anterior) {
    const a = parsePrecioNum(actual);
    const b = parsePrecioNum(anterior);

    if (Number.isNaN(a) || Number.isNaN(b) || a <= 0 || b <= 0) {
      return 'neutral';
    }

    const aCent = Math.round(a * 100);
    const bCent = Math.round(b * 100);

    if (aCent > bCent) {
      return 'up';
    }
    if (aCent < bCent) {
      return 'down';
    }

    return 'neutral';
  }

  function renderPrecioComparadoBadge(precio, precioAnterior, extraClass) {
    extraClass = extraClass || '';
    const actual = parsePrecioNum(precio);
    const tendencia = compararPrecioSemana(precio, precioAnterior);
    let claseBadge = 'bg-label-primary';
    let icono = '';

    if (tendencia === 'up') {
      claseBadge = 'bg-label-success';
      icono = '<i class="icon-base ri ri-arrow-up-line ms-1"></i>';
    } else if (tendencia === 'down') {
      claseBadge = 'bg-label-danger';
      icono = '<i class="icon-base ri ri-arrow-down-line ms-1"></i>';
    }

    const valorMostrar = Number.isNaN(actual) ? 0 : actual;

    return '<span class="badge ' + claseBadge + ' rounded-pill text-nowrap d-inline-flex align-items-center justify-content-center ' + extraClass + '">' +
      '<span>' + formatearEuro(valorMostrar) + '</span>' +
      icono +
      '</span>';
  }

  function renderPrecioMercadoColumn(data, type, row) {
    if (type === 'sort' || type === 'type' || type === 'filter') {
      return data;
    }

    return renderPrecioComparadoBadge(data, row[COL_PRECIO_MERCADO_ANT], '');
  }

  function renderPrecioGramoOroColumn(data, type, row) {
    if (type === 'sort' || type === 'type' || type === 'filter') {
      return data;
    }

    return renderPrecioComparadoBadge(data, row[COL_PRECIO_ORO_ANT], '');
  }

  function renderCalculoPrecioColumn(data, type) {
    if (type === 'sort' || type === 'type' || type === 'filter') {
      return data;
    }

    const valor = String(data || 'false').toLowerCase();
    let claseBadge = 'bg-label-secondary';
    let texto = '—';

    if (valor === 'automatico') {
      claseBadge = 'bg-label-warning';
      texto = 'Automatico';
    } else if (valor === 'manual') {
      claseBadge = 'bg-label-success';
      texto = 'Manual';
    } else if (valor === 'proformas') {
      claseBadge = 'bg-label-primary';
      texto = 'Proformas';
    }

    return '<span class="badge ' + claseBadge + ' rounded-pill text-nowrap">' + texto + '</span>';
  }

  function abrirModalEditarPreciosSemana(rowData) {
    if (!modalPreciosSemana || !formPreciosSemana) {
      return;
    }

    const idInput = document.getElementById('semana_precio_id_numero_semana');
    const resumen = document.getElementById('semana_precio_resumen');
    const inputMercado = document.getElementById('semana_precio_24_mercado');
    const inputOro = document.getElementById('semana_precio_gramo_oro');

    if (!idInput || !resumen || !inputMercado || !inputOro) {
      return;
    }

    const numeroSemana = rowData[0];
    const desde = rowData[1] || '-';
    const hasta = rowData[2] || '-';
    const anio = rowData[3];

    idInput.value = String(rowData[COL_ID]);
    resumen.textContent = 'Semana ' + numeroSemana + ' · ' + anio + ' (' + desde + ' - ' + hasta + ')';
    inputMercado.value = parsePrecioNum(rowData[COL_PRECIO_MERCADO]) || 0;
    inputOro.value = parsePrecioNum(rowData[COL_PRECIO_ORO]) || 0;

    modalPreciosSemana.show();
  }

  if (formPreciosSemana) {
    formPreciosSemana.addEventListener('submit', function (e) {
      e.preventDefault();

      if (btnGuardarPreciosSemana) {
        btnGuardarPreciosSemana.disabled = true;
      }

      const formData = new FormData(formPreciosSemana);

      fetch('parts/semanas_config/unique/actualizar_precios_semana.php', {
        method: 'POST',
        body: formData
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (!data.success) {
            throw new Error(data.error || 'No se pudo guardar');
          }

          if (modalPreciosSemana) {
            modalPreciosSemana.hide();
          }

          recargarTabla();

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Guardado',
              text: data.message || 'Precios actualizados',
              timer: 1800,
              showConfirmButton: false
            });
          }
        })
        .catch(function (error) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error.message || 'Error al guardar los precios'
            });
          }
        })
        .finally(function () {
          if (btnGuardarPreciosSemana) {
            btnGuardarPreciosSemana.disabled = false;
          }
        });
    });
  }

  if (!dt_table) {
    return;
  }

  const dt_semanas_config = new DataTable(dt_table, {
    processing: true,
    serverSide: true,
    deferRender: true,
    searchDelay: 500,
    timeout: 60000,
    language: DATATABLES_SPANISH,
    ajax: {
      url: 'parts/semanas_config/unique/load_list.php',
      type: 'POST',
      data: function (d) {
        d.filtro_anio = document.getElementById('filtro_anio')?.value || '';
        d.filtro_semana = document.getElementById('filtro_semana')?.value || '';
        d.filtro_fecha_desde = document.getElementById('filtro_fecha_desde')?.value || '';
        d.filtro_fecha_hasta = document.getElementById('filtro_fecha_hasta')?.value || '';
        return d;
      },
      dataSrc: function (json) {
        return json.data || [];
      },
      error: function (xhr) {
        console.error('Error en DataTable semanas config:', xhr.responseText);
        if (xhr.status === 401) {
          window.location.href = 'login.php';
        }
      }
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
      { data: 8, visible: false, searchable: false, orderable: false },
      { data: 9, visible: false, searchable: false, orderable: false },
      { data: 10, visible: false, searchable: false, orderable: false }
    ],
    columnDefs: [
      {
        targets: 0,
        width: '72px',
        className: 'text-center',
        render: function (data) {
          return '<span class="badge bg-label-primary rounded-pill numero_semana_datatable">' + data + '</span>';
        }
      },
      {
        targets: [1, 2],
        width: '108px',
        className: 'text-center',
        render: function (data) {
          return '<span class="text-nowrap">' + (data || '-') + '</span>';
        }
      },
      {
        targets: 3,
        width: '64px',
        className: 'text-center'
      },
      {
        targets: 4,
        width: '148px',
        className: 'text-center celda-precio-mercado',
        render: renderPrecioMercadoColumn
      },
      {
        targets: 5,
        width: '140px',
        className: 'text-center celda-porcentaje-media',
        render: renderPorcentajeMedia
      },
      {
        targets: 6,
        width: '148px',
        className: 'text-center celda-precio-oro',
        render: renderPrecioGramoOroColumn
      },
      {
        targets: 7,
        width: '132px',
        className: 'text-center celda-calculo-precio',
        render: renderCalculoPrecioColumn
      },
      {
        targets: 8,
        visible: false,
        searchable: false,
        orderable: false
      },
      {
        targets: 9,
        visible: false,
        searchable: false,
        orderable: false
      },
      {
        targets: 10,
        visible: false,
        searchable: false,
        orderable: false
      }
    ],
    order: [[0, 'asc']],
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    autoWidth: true,
    rowCallback: function (row) {
      row.classList.add('cursor-pointer');
      row.setAttribute('title', 'Editar precios de la semana');
    },
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
                    text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
                    className: 'dropdown-item',
                    exportOptions: {
                      columns: ':visible'
                    }
                  },
                  {
                    extend: 'pdf',
                    text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                    className: 'dropdown-item',
                    orientation: 'landscape',
                    exportOptions: {
                      columns: ':visible'
                    },
                    customize: function (doc) {
                      doc.pageOrientation = 'landscape';
                      doc.pageSize = 'LEGAL';
                      doc.defaultStyle.fontSize = 8;
                      doc.styles.tableHeader.fontSize = 8;
                      doc.content[0].text = 'Configuración de semanas';
                      doc.content[0].alignment = 'center';
                      doc.content[0].fontSize = 12;
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
    }
  });

  window.dt_semanas_config = dt_semanas_config;

  dt_table.addEventListener('click', function (e) {
    if (e.target.closest('.dt-buttons, .dt-search, .page-link, .pagination')) {
      return;
    }

    const tr = e.target.closest('tbody tr');
    if (!tr) {
      return;
    }

    const row = dt_semanas_config.row(tr);
    if (!row || !row.data()) {
      return;
    }

    abrirModalEditarPreciosSemana(row.data());
  });

  inicializarSelect2();
  bindFiltros();
  inicializarFiltrosFecha();

  setTimeout(function () {
    const elementsToModify = [
      { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
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
      { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
      { selector: '.dt-layout-full', classToRemove: 'col-md col-12' }
    ];

    elementsToModify.forEach(function (item) {
      document.querySelectorAll(item.selector).forEach(function (element) {
        if (item.classToRemove) {
          item.classToRemove.split(' ').forEach(function (className) {
            element.classList.remove(className);
          });
        }
        if (item.classToAdd) {
          item.classToAdd.split(' ').forEach(function (className) {
            element.classList.add(className);
          });
        }
      });
    });
  }, 300);
});
