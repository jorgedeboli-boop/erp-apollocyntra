/**
 * Page Reportes Semanales
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const dt_table = document.querySelector('.datatables-reportes-semanales');
  let dt_reportes;

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
        allowClear: $this.attr('id') === 'filtro_sucursal',
        width: '100%'
      });
    });
  }

  function recargarTabla() {
    if (window.dt_reportes_semanales) {
      window.dt_reportes_semanales.ajax.reload();
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

    return fetch('parts/reportes_semanales/unique/get_semanas.php?anio=' + encodeURIComponent(anio))
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
        let semanaActual = semanaSeleccionada ? String(semanaSeleccionada) : '';

        if (semanaActual && numerosSemana.indexOf(semanaActual) === -1) {
          semanaActual = '';
        }

        if (!semanaActual) {
          const semanaCalendario = data.semana_actual ? String(data.semana_actual) : '';
          if (semanaCalendario && numerosSemana.indexOf(semanaCalendario) !== -1) {
            semanaActual = semanaCalendario;
          } else if (semanas.length) {
            semanaActual = String(semanas[semanas.length - 1].numero_semana);
          }
        }

        selectSemana.innerHTML = '';
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
    const filtroSucursal = document.getElementById('filtro_sucursal');
    const filtroAnio = document.getElementById('filtro_anio');
    const filtroSemana = document.getElementById('filtro_semana');

    if (filtroSucursal) {
      $(filtroSucursal).on('change select2:select select2:clear', recargarTabla);
    }

    if (filtroSemana) {
      $(filtroSemana).on('change select2:select', recargarTabla);
    }

    if (filtroAnio) {
      $(filtroAnio).on('change select2:select', function () {
        const semanaPrev = filtroSemana ? filtroSemana.value : '';
        cargarSemanas(filtroAnio.value, semanaPrev).then(recargarTabla);
      });
    }
  }

  function renderMoneda(data) {
    return '<span class="text-nowrap fw-medium">' + (data || '0,00 €') + '</span>';
  }

  function renderGramos(data) {
    return '<span class="text-nowrap">' + (data || '0,00 gr') + '</span>';
  }

  function formatearSemanaAnio(numeroSemana, yearInforme) {
    return numeroSemana + ' / ' + yearInforme;
  }

  let filaInformeActiva = null;
  const modalEditarInformeEl = document.getElementById('modalEditarInformeSemanal');
  const modalEditarInforme = modalEditarInformeEl && typeof bootstrap !== 'undefined'
    ? new bootstrap.Modal(modalEditarInformeEl)
    : null;

  function abrirModalEditarInforme(row) {
    const data = row.data();
    if (!data || !data[16]) {
      return;
    }

    const meta = data[17] || {};
    filaInformeActiva = row;

    document.getElementById('editar_informe_id').value = data[16];
    document.getElementById('editar_informe_semana_anio').textContent = formatearSemanaAnio(data[0], meta.year_informe || '');
    document.getElementById('editar_informe_sucursal').textContent = meta.nombre_sucursal || data[15] || '-';
    document.getElementById('editar_informe_total_gastos').value = meta.total_gastos != null ? meta.total_gastos : '';
    document.getElementById('editar_informe_yulinfo').value = meta.yulinfo != null ? meta.yulinfo : '';

    if (modalEditarInforme) {
      modalEditarInforme.show();
    }
  }

  function actualizarFilaInforme(totalGastosFormatted, yulinfoFormatted, totalGastosRaw, yulinfoRaw) {
    if (!filaInformeActiva) {
      return;
    }

    const rowData = filaInformeActiva.data().slice();
    rowData[11] = totalGastosFormatted;
    rowData[13] = yulinfoFormatted;

    if (!rowData[17]) {
      rowData[17] = {};
    }

    rowData[17].total_gastos = totalGastosRaw;
    rowData[17].yulinfo = yulinfoRaw;

    filaInformeActiva.data(rowData).draw(false);
    filaInformeActiva = null;
  }

  function bindModalEditarInforme() {
    const btnGuardar = document.getElementById('btnGuardarInformeSemanal');
    const form = document.getElementById('formEditarInformeSemanal');

    if (!btnGuardar || !form) {
      return;
    }

    btnGuardar.addEventListener('click', function () {
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const textoOriginal = btnGuardar.innerHTML;
      btnGuardar.disabled = true;
      btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

      const formData = new FormData(form);

      fetch('parts/reportes_semanales/unique/actualizar_informe.php', {
        method: 'POST',
        body: formData
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (!data.success) {
            throw new Error(data.error || 'No se pudo actualizar el informe');
          }

          actualizarFilaInforme(
            data.total_gastos_formatted,
            data.yulinfo_formatted,
            data.total_gastos,
            data.yulinfo
          );

          if (modalEditarInforme) {
            modalEditarInforme.hide();
          }

          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Guardado',
              text: data.message || 'Informe actualizado correctamente',
              timer: 1500,
              showConfirmButton: false
            });
          }
        })
        .catch(function (error) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error.message || 'Error al actualizar el informe'
            });
          }
        })
        .finally(function () {
          btnGuardar.disabled = false;
          btnGuardar.innerHTML = textoOriginal;
        });
    });

    if (modalEditarInformeEl) {
      modalEditarInformeEl.addEventListener('hidden.bs.modal', function () {
        filaInformeActiva = null;
      });
    }
  }

  if (dt_table) {
    dt_reportes = new DataTable(dt_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/reportes_semanales/unique/load_list.php',
        type: 'POST',
        data: function (d) {
          d.filtro_sucursal = document.getElementById('filtro_sucursal')?.value || '';
          d.filtro_semana = document.getElementById('filtro_semana')?.value || '';
          d.filtro_anio = document.getElementById('filtro_anio')?.value || '';
          return d;
        },
        dataSrc: function (json) {
          return json.data || [];
        },
        error: function (xhr) {
          console.error('Error en DataTable reportes semanales:', xhr.responseText);
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
        { data: 8 },
        { data: 9 },
        { data: 10 },
        { data: 11 },
        { data: 12 },
        { data: 13 },
        { data: 14 },
        { data: 15 },
        { data: 16, visible: false, searchable: false },
        { data: 17, visible: false, searchable: false }
      ],
      createdRow: function (row) {
        row.classList.add('cursor-pointer');
        row.setAttribute('title', 'Click para editar gastos y yulinfo');
      },
      columnDefs: [
        {
          targets: 0,
          className: 'text-center',
          render: function (data, type, full) {
            const year = full[17] && full[17].year_informe ? full[17].year_informe : '';
            const label = formatearSemanaAnio(data, year);
            if (type === 'display') {
              return '<span class="badge bg-label-primary rounded-pill text-nowrap">' + label + '</span>';
            }
            return label;
          }
        },
        {
          targets: [1, 2],
          render: function (data) {
            return '<span class="text-nowrap">' + (data || '-') + '</span>';
          }
        },
        {
          targets: [3, 5, 7, 9, 10, 11, 12, 13, 14],
          className: 'text-end',
          render: renderMoneda
        },
        {
          targets: [4, 6, 8],
          className: 'text-end',
          render: renderGramos
        },
        {
          targets: 15,
          render: function (data) {
            return '<span class="fw-medium text-heading">' + (data || 'Sin sucursal') + '</span>';
          }
        },
        {
          targets: [16, 17],
          visible: false,
          searchable: false
        }
      ],
      order: [[15, 'asc']],
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      scrollX: true,
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
                      extend: 'print',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-printer-line me-1"></i>Imprimir</span>',
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: ':visible'
                      }
                    },
                    {
                      extend: 'csv',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-text-line me-1"></i>CSV</span>',
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: ':visible'
                      }
                    },
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
                        doc.defaultStyle.fontSize = 7;
                        doc.styles.tableHeader.fontSize = 7;
                        doc.content[0].text = 'Reportes Semanales';
                        doc.content[0].alignment = 'center';
                        doc.content[0].fontSize = 12;
                      }
                    },
                    {
                      extend: 'copy',
                      text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar',
                      className: 'dropdown-item',
                      exportOptions: {
                        columns: ':visible'
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
      },
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              const year = data[17] && data[17].year_informe ? data[17].year_informe : '';
              return 'Reporte ' + formatearSemanaAnio(data[0], year) + ' - ' + data[15];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            const data = columns
              .map(function (col) {
                return col.hidden
                  ? '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                      '<td>' + col.title + ':</td>' +
                      '<td>' + col.data + '</td>' +
                    '</tr>'
                  : '';
              })
              .join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });

    window.dt_reportes_semanales = dt_reportes;
    inicializarSelect2();
    bindFiltros();
    bindModalEditarInforme();

    $(dt_table).on('click', 'tbody tr', function () {
      const row = dt_reportes.row(this);
      if (!row || !row.data() || !row.data()[16]) {
        return;
      }

      abrirModalEditarInforme(row);
    });
  }

  setTimeout(function () {
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
  }, 100);
});
