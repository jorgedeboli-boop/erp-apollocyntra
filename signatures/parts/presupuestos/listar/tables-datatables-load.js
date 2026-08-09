/**
 * Listado presupuestos (DataTables)
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const tableEl = document.querySelector('.datatables-presupuestos');
  if (!tableEl) return;

  function exportarTodosLosDatosPresupuestos(tipo, dt, button, config) {
    const searchValue = dt.search();
    const est = document.getElementById('filtro_presupuesto_estado');
    const emp = document.getElementById('filtro_presupuesto_empresa');
    const fd = document.getElementById('filtro_fecha_desde');
    const fh = document.getElementById('filtro_fecha_hasta');

    Swal.fire({
      title: 'Generando exportación...',
      text: 'Obteniendo todos los registros',
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      }
    });

    const formData = new FormData();
    formData.append('search', searchValue);
    formData.append('filtro_estado', est ? est.value : '');
    formData.append('filtro_empresa', emp ? emp.value : '');
    formData.append('filtro_fecha_desde', fd ? fd.value : '');
    formData.append('filtro_fecha_hasta', fh ? fh.value : '');

    fetch('parts/presupuestos/listar/export_all.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (responseData) {
        Swal.close();

        if (!responseData.success) {
          throw new Error(responseData.error || 'Error al obtener datos');
        }

        if (!responseData.data || responseData.data.length === 0) {
          Swal.fire({
            title: 'Sin datos',
            text: 'No hay datos para exportar con los filtros aplicados',
            icon: 'info',
            confirmButtonText: 'Aceptar'
          });
          return;
        }

        const tempTableId = 'temp-export-presupuestos-' + Date.now();
        const tempDiv = document.createElement('div');
        tempDiv.style.display = 'none';
        tempDiv.innerHTML =
          '<table id="' +
          tempTableId +
          '"><thead><tr>' +
          '<th>Número</th><th>Título</th><th>Cliente</th><th>Total</th><th>Estado</th>' +
          '<th>Fecha creación</th><th>Validez</th>' +
          '</tr></thead></table>';
        document.body.appendChild(tempDiv);

        const $ = window.jQuery;
        const tempTable = $('#' + tempTableId).DataTable({
          data: responseData.data,
          columns: [{ data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }],
          paging: false,
          searching: false,
          ordering: false,
          dom: 't',
          buttons: []
        });

        const exportConfig = {
          exportOptions: {
            columns: ':visible'
          }
        };

        if (tipo === 'pdf') {
          exportConfig.customize = function (doc) {
            doc.pageOrientation = 'landscape';
            doc.pageSize = 'LEGAL';
            doc.defaultStyle.fontSize = 8;
            doc.styles.tableHeader.fontSize = 9;
            doc.styles.tableHeader.fillColor = '#2d4154';
            doc.styles.tableHeader.bold = true;
            doc.styles.tableHeader.color = 'white';
            doc.content[0].text = 'Listado de presupuestos';
            doc.content[0].alignment = 'center';
            doc.content[0].fontSize = 14;
            doc.content[0].margin = [0, 0, 0, 10];
            doc.pageMargins = [5, 5, 5, 5];
            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
          };
        }

        const buttonType = tipo === 'excel' ? 'excelHtml5' : tipo;

        try {
          const tempButton = tempTable.button().add(0, {
            extend: buttonType,
            ...exportConfig
          });
          tempButton.trigger();
          setTimeout(function () {
            tempTable.destroy();
            tempDiv.remove();
          }, 2000);
        } catch (error) {
          console.error('Error al exportar:', error);
          throw error;
        }
      })
      .catch(function (error) {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
          title: 'Error',
          text: 'Ha ocurrido un error al exportar: ' + (error.message || String(error)),
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      });
  }

  window.exportarTodosLosDatosPresupuestos = exportarTodosLosDatosPresupuestos;

  window.dt_presupuestos = new DataTable(tableEl, {
    processing: true,
    serverSide: true,
    deferRender: true,
    searchDelay: 400,
    language: typeof DATATABLES_SPANISH !== 'undefined' ? DATATABLES_SPANISH : {},
    ajax: {
      url: 'parts/presupuestos/listar/load_list.php',
      type: 'POST',
      data: function (d) {
        const est = document.getElementById('filtro_presupuesto_estado');
        const emp = document.getElementById('filtro_presupuesto_empresa');
        const fd = document.getElementById('filtro_fecha_desde');
        const fh = document.getElementById('filtro_fecha_hasta');
        d.filtro_estado = est ? est.value : '';
        d.filtro_empresa = emp ? emp.value : '';
        d.filtro_fecha_desde = fd ? fd.value : '';
        d.filtro_fecha_hasta = fh ? fh.value : '';
        return d;
      },
      dataSrc: function (json) {
        return json.data || [];
      },
      error: function (xhr) {
        console.error('Error listado presupuestos:', xhr.responseText);
      }
    },
    columns: [
      { data: 0 },
      { data: 1 },
      { data: 2 },
      { data: 3 },
      { data: 4, orderable: false },
      { data: 5 },
      { data: 6 },
      { data: 7, visible: false }
    ],
    order: [[5, 'desc']],
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
                    text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                    className: 'dropdown-item',
                    action: function (e, dt, button, config) {
                      exportarTodosLosDatosPresupuestos('excel', dt, button, config);
                    },
                    exportOptions: {
                      columns: ':visible',
                      format: {
                        body: function (data, row, column, node) {
                          if (typeof data === 'string') {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = data;
                            return tempDiv.textContent || tempDiv.innerText || data;
                          }
                          return data;
                        }
                      }
                    }
                  },
                  {
                    extend: 'pdf',
                    text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>`,
                    className: 'dropdown-item',
                    orientation: 'landscape',
                    action: function (e, dt, button, config) {
                      exportarTodosLosDatosPresupuestos('pdf', dt, button, config);
                    },
                    exportOptions: {
                      columns: ':visible',
                      format: {
                        body: function (data, row, column, node) {
                          if (typeof data === 'string') {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = data;
                            return tempDiv.textContent || tempDiv.innerText || data;
                          }
                          return data;
                        }
                      }
                    },
                    customize: function (doc) {
                      doc.pageOrientation = 'landscape';
                      doc.pageSize = 'LEGAL';
                      doc.defaultStyle.fontSize = 8;
                      doc.styles.tableHeader.fontSize = 9;
                      doc.styles.tableHeader.fillColor = '#2d4154';
                      doc.styles.tableHeader.bold = true;
                      doc.styles.tableHeader.color = 'white';
                      doc.content[0].text = 'Listado de presupuestos';
                      doc.content[0].alignment = 'center';
                      doc.content[0].fontSize = 14;
                      doc.content[0].margin = [0, 0, 0, 10];
                      doc.pageMargins = [5, 5, 5, 5];
                      if (doc.content[1] && doc.content[1].table) {
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
                      }
                    }
                  },
                  {
                    extend: 'copy',
                    text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                    className: 'dropdown-item',
                    action: function (e, dt, button, config) {
                      exportarTodosLosDatosPresupuestos('copy', dt, button, config);
                    },
                    exportOptions: {
                      columns: ':visible',
                      format: {
                        body: function (data, row, column, node) {
                          if (typeof data === 'string') {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = data;
                            return tempDiv.textContent || tempDiv.innerText || data;
                          }
                          return data;
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
        rowClass: 'row mx-0 justify-content-between w-100',
        features: ['info']
      },
      bottomEnd: 'paging'
    },
    responsive: true,
    createdRow: function (row, data) {
      row.style.cursor = 'pointer';
      row.addEventListener('click', function (e) {
        if (e.target.closest('a, button, .dt-control')) return;
        var id = data[7];
        if (id) window.location.href = 'editar_presupuesto.php?id=' + id;
      });
    }
  });

  function initSelect2Filter(sel) {
    if (!sel || !window.jQuery) return;
    const $s = window.jQuery(sel);
    if (!$s.length) return;
    $s.select2({ dropdownParent: $s.parent(), width: '100%' });
    $s.on('change', function () {
      window.dt_presupuestos.ajax.reload();
    });
  }

  initSelect2Filter('#filtro_presupuesto_estado');
  initSelect2Filter('#filtro_presupuesto_empresa');

  const btnNuevo = document.getElementById('btn_nuevo_presupuesto');
  const wrapSuc = document.getElementById('select_sucursal_nuevo_presupuesto_container');
  const selSuc = window.jQuery ? window.jQuery('#select_sucursal_nuevo_presupuesto') : null;

  if (btnNuevo && wrapSuc && selSuc && selSuc.length) {
    btnNuevo.addEventListener('click', function () {
      btnNuevo.style.display = 'none';
      wrapSuc.style.display = 'block';
      if (!selSuc.hasClass('select2-hidden-accessible')) {
        selSuc.select2({
          placeholder: 'Sucursal para el presupuesto',
          width: '100%',
          dropdownParent: wrapSuc
        });
      }
      setTimeout(function () {
        selSuc.select2('open');
      }, 100);
    });

    selSuc.on('change', function () {
      const id = window.jQuery(this).val();
      if (id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'crear_presupuesto.php';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id_sucursal';
        input.value = id;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      }
    });
  }
});
