/**
 * Page Reportes Semanales
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  if (window.ListarFiltros) {
    window.ListarFiltros.setOnChange(function (event) {
      const target = event && event.target ? event.target : null;
      if (target && target.id === 'filtro_anio') {
        const filtroSemana = document.getElementById('filtro_semana');
        const semanaPrev = filtroSemana ? filtroSemana.value : '';
        cargarSemanas(target.value, semanaPrev).then(recargarTabla);
        return;
      }
      recargarTabla();
    });
  }

  const dt_table = document.querySelector('.datatables-reportes-semanales');
  let dt_reportes;

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

  function renderMoneda(data) {
    return '<span class="text-nowrap fw-medium">' + (data || '0,00 €') + '</span>';
  }

  function renderGramos(data) {
    return '<span class="text-nowrap">' + (data || '0,00 gr') + '</span>';
  }

  function esTotalCero(valor) {
    if (valor === null || valor === undefined || valor === '') {
      return true;
    }

    const texto = String(valor)
      .replace(/\s/g, '')
      .replace(/%/g, '')
      .replace(/€/g, '')
      .replace(/\./g, '')
      .replace(',', '.');

    const numero = parseFloat(texto);
    return isNaN(numero) || numero === 0;
  }

  function clasesTotalSpan(claseBase, valor) {
    return esTotalCero(valor) ? claseBase + ' nototals' : claseBase;
  }

  function renderPorcentajeBadge(data, type) {
    if (type && type !== 'display') {
      return data;
    }
    const texto = data || '0 %';
    return '<span class="' + clasesTotalSpan('totales-porcentajes', texto) + '">' + texto + '</span>';
  }

  function renderEurosBadge(data, type) {
    if (type && type !== 'display') {
      return data;
    }
    const texto = data || '0 €';
    return '<span class="' + clasesTotalSpan('totales-euros', texto) + '">' + texto + '</span>';
  }

  function aplicarTitulosBeneficioOroPlata(thead, api) {
    const topHeader = thead.querySelector('th.reportes-semanales-th-beneficio-top');
    const bottomHeader = api ? api.column(14).header() : thead.querySelector('th.reportes-semanales-th-beneficio-bottom');

    if (topHeader) {
      let titleSpan = topHeader.querySelector('.dt-column-title');
      if (!titleSpan) {
        titleSpan = document.createElement('span');
        titleSpan.className = 'dt-column-title';
        topHeader.textContent = '';
        topHeader.appendChild(titleSpan);
      }
      titleSpan.textContent = 'Beneficio';
    }

    if (bottomHeader) {
      bottomHeader.classList.add('reportes-semanales-th-beneficio-oro-plata', 'reportes-semanales-th-beneficio-bottom', 'rs-grupo-bottom', 'rs-grupo-full', 'text-center', 'border-0');
      let titleSpan = bottomHeader.querySelector('.dt-column-title');
      if (!titleSpan) {
        titleSpan = document.createElement('span');
        titleSpan.className = 'dt-column-title';
        bottomHeader.textContent = '';
        bottomHeader.appendChild(titleSpan);
      }
      titleSpan.textContent = 'Oro/Plata';
    }
  }

  function formatearSemanaAnio(numeroSemana, yearInforme) {
    return numeroSemana + ' / ' + yearInforme;
  }

  let filaInformeActiva = null;
  const modalEditarInformeEl = document.getElementById('modalEditarInformeSemanal');
  const modalEditarInforme = modalEditarInformeEl && typeof bootstrap !== 'undefined'
    ? new bootstrap.Modal(modalEditarInformeEl)
    : null;

  function hacerModalArrastrable(modalEl) {
    if (!modalEl) {
      return;
    }

    const dialog = modalEl.querySelector('.modal-dialog');
    const header = modalEl.querySelector('.modal-draggable-handle');

    if (!dialog || !header) {
      return;
    }

    let arrastrando = false;
    let inicioX = 0;
    let inicioY = 0;
    let posX = 0;
    let posY = 0;

    function reiniciarPosicion() {
      dialog.classList.remove('modal-dialog-draggable');
      dialog.style.removeProperty('position');
      dialog.style.removeProperty('left');
      dialog.style.removeProperty('top');
      dialog.style.removeProperty('margin');
      dialog.style.removeProperty('transform');
      dialog.style.removeProperty('width');
      header.classList.remove('is-dragging');
      arrastrando = false;
    }

    function fijarPosicionInicial() {
      const rect = dialog.getBoundingClientRect();
      dialog.classList.add('modal-dialog-draggable');
      dialog.style.position = 'fixed';
      dialog.style.margin = '0';
      dialog.style.left = rect.left + 'px';
      dialog.style.top = rect.top + 'px';
      dialog.style.transform = 'none';
      dialog.style.width = rect.width + 'px';
      posX = rect.left;
      posY = rect.top;
    }

    modalEl.addEventListener('shown.bs.modal', fijarPosicionInicial);
    modalEl.addEventListener('hidden.bs.modal', reiniciarPosicion);

    header.addEventListener('mousedown', function (evento) {
      if (evento.button !== 0 || evento.target.closest('.btn-close')) {
        return;
      }

      arrastrando = true;
      inicioX = evento.clientX;
      inicioY = evento.clientY;
      header.classList.add('is-dragging');
      evento.preventDefault();
    });

    document.addEventListener('mousemove', function (evento) {
      if (!arrastrando || !modalEl.classList.contains('show')) {
        return;
      }

      posX += evento.clientX - inicioX;
      posY += evento.clientY - inicioY;
      inicioX = evento.clientX;
      inicioY = evento.clientY;
      dialog.style.left = posX + 'px';
      dialog.style.top = posY + 'px';
    });

    document.addEventListener('mouseup', function () {
      if (!arrastrando) {
        return;
      }

      arrastrando = false;
      header.classList.remove('is-dragging');
    });
  }

  hacerModalArrastrable(modalEditarInformeEl);

  function abrirModalEditarInforme(row) {
    const data = row.data();
    if (!data || !data[24]) {
      return;
    }

    const meta = data[25] || {};
    filaInformeActiva = row;

    document.getElementById('editar_informe_id').value = data[24];
    document.getElementById('editar_informe_semana_anio').textContent = formatearSemanaAnio(data[2], meta.year_informe || '');
    document.getElementById('editar_informe_sucursal').textContent = meta.nombre_sucursal || data[1] || '-';
    document.getElementById('editar_nombre_empresa').textContent = meta.nombre_empresa || data[26] || '-';
    document.getElementById('editar_semanas_desde_hasta').textContent = (data[3] || '-') + '-' + (data[4] || '-');
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
    rowData[20] = totalGastosFormatted;

    if (!rowData[25]) {
      rowData[25] = {};
    }

    rowData[25].total_gastos = totalGastosRaw;
    rowData[25].yulinfo = yulinfoRaw;

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
          d.filtro_empresa = document.getElementById('filtro_empresa')?.value || '';
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
        { data: 0, title: 'Nº' },
        { data: 1, title: 'Nombre' },
        { data: 2, title: 'Número' },
        { data: 3, title: 'Desde' },
        { data: 4, title: 'Hasta' },
        { data: 5, title: 'Pagado' },
        { data: 6, title: 'Abonados' },
        { data: 7, title: 'Peso' },
        { data: 8, title: 'Fundición' },
        { data: 9, title: 'Beneficio' },
        { data: 10, title: 'Pagado' },
        { data: 11, title: 'Peso' },
        { data: 12, title: 'Fundición' },
        { data: 13, title: 'Beneficio' },
        { data: 14, title: 'Oro/Plata' },
        { data: 15, title: 'Renovaciones' },
        { data: 16, title: 'Ventas' },
        { data: 17, title: 'Gramos' },
        { data: 18, title: 'Imp.stock fund' },
        { data: 19, title: 'Arreglos' },
        { data: 20, title: 'Sucursal' },
        { data: 21, title: 'Empresa' },
        { data: 22, title: 'Porcentaje' },
        { data: 23, title: 'Total' },
        { data: 24, visible: false, searchable: false, title: 'ID' },
        { data: 25, visible: false, searchable: false, title: 'Meta' },
        { data: 26, visible: false, searchable: true, title: 'Empresa' }
      ],
      rowGroup: {
        dataSrc: 26,
        startClassName: 'bg-label-primary',
        startRender: function (rows, group) {
          return $('<tr class="bg-label-primary"/>')
            .append(
              '<td colspan="24">' +
                '<span class="reportes-semanales-empresa-group-label">' +
                  '<i class="icon-base ri ri-building-line me-2"></i>' +
                  (group || 'Sin empresa') +
                '</span>' +
              '</td>'
            );
        }
      },
      createdRow: function (row) {
        row.classList.add('cursor-pointer');
        row.setAttribute('title', 'Click para editar gastos y yulinfo');
      },
      columnDefs: [
        {
          targets: 0,
          className: 'text-center',
          render: function (data) {
            return '<span class="text-nowrap">' + (data || '-') + '</span>';
          }
        },
        {
          targets: 1,
          render: function (data) {
            return '<span class="fw-medium text-heading">' + (data || 'Sin sucursal') + '</span>';
          }
        },
        {
          targets: 2,
          className: 'text-center',
          render: function (data, type, full) {
            /*const year = full[24] && full[24].year_informe ? full[24].year_informe : '';*/
            const label = data || '-';
            if (type === 'display') {
              return '<span class="fw-medium text-heading">' + label + '</span>';
            }
            return label;
          }
        },
        {
          targets: [3, 4],
          render: function (data) {
            return '<span class="text-nowrap">' + (data || '-') + '</span>';
          }
        },
        {
          targets: [5, 6, 8, 9, 10, 12, 13, 15, 16, 18, 19, 20, 21],
          className: 'text-end',
          render: renderMoneda
        },
        {
          targets: 22,
          render: renderPorcentajeBadge
        },
        {
          targets: [14, 23],
          render: renderEurosBadge
        },
        {
          targets: [7, 11, 17],
          className: 'text-end',
          render: renderGramos
        },
        {
          targets: [24, 25, 26],
          visible: false,
          searchable: false,
          className: 'reportes-semanales-col-oculta'
        }
      ],
      headerCallback: function (thead) {
        const api = this.api();
        aplicarTitulosBeneficioOroPlata(thead, api);

        const utilidadGroupHeader = thead.querySelector('tr:first-child th.rs-grupo-utilidad');

        if (utilidadGroupHeader) {
          utilidadGroupHeader.classList.add('text-bg-success', 'reportes-semanales-th-utilidad', 'rs-grupo-utilidad', 'rs-grupo-top');
          const titleSpan = utilidadGroupHeader.querySelector('.dt-column-title');
          if (titleSpan) {
            titleSpan.classList.add('fs-4');
          }
        }

        [22, 23].forEach(function (columnIndex) {
          const header = api.column(columnIndex).header();
          if (header) {
            header.classList.add('text-bg-success', 'border-0', 'rs-grupo-utilidad', 'rs-grupo-bottom');
          }
        });

        thead.querySelectorAll('tr:last-child th').forEach(function (th) {
          th.classList.add('border-0');
        });
      },
      initComplete: function () {
        aplicarTitulosBeneficioOroPlata(this.api().table().header(), this.api());
      },
      order: [[26, 'asc'], [0, 'asc']],
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      autoWidth: false,
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
          rowId: 'footer_pagin_reportes',
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
              const year = data[25] && data[25].year_informe ? data[25].year_informe : '';
              return 'Reporte ' + formatearSemanaAnio(data[2], year) + ' - ' + data[1];
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
    bindModalEditarInforme();

    $(dt_table).on('click', 'tbody tr:not(.dtrg-group)', function () {
      const row = dt_reportes.row(this);
      if (!row || !row.data() || !row.data()[24]) {
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
