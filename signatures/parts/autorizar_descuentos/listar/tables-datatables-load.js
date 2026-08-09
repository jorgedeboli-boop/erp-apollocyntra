/**
 * Listado: autorizaciones descuento artículo venta
 */

'use strict';

function fmtEuro(val) {
  if (val === null || val === undefined || val === '') {
    return '<span class="text-muted">-</span>';
  }
  const n = parseFloat(val);
  if (isNaN(n)) {
    return '<span class="text-muted">-</span>';
  }
  return (
    '<span class="fw-medium">' +
    n.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
    ' €</span>'
  );
}

document.addEventListener('DOMContentLoaded', function () {
  const dt_table = document.querySelector('.datatables-autorizaciones-descuento');
  let dt_autorizaciones;

  function reloadPorFiltrosAutorizaciones() {
    if (dt_autorizaciones) {
      dt_autorizaciones.ajax.reload();
    }
  }

  if (window.ListarFiltros) {
    window.ListarFiltros.setOnChange(reloadPorFiltrosAutorizaciones);
  }


  if (!dt_table) {
    return;
  }

  dt_autorizaciones = new DataTable(dt_table, {
    processing: true,
    serverSide: true,
    deferRender: true,
    searchDelay: 500,
    timeout: 60000,
    language: DATATABLES_SPANISH,
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
      { data: 10 }
    ],
    columnDefs: [
      {
        targets: 0,
        render: function (data) {
          return '<span class="fw-medium">' + data + '</span>';
        }
      },
      {
        targets: 1,
        responsivePriority: 4,
        render: function (data) {
          if (typeof data === 'string' && data) {
            return '<span class="fw-medium text-heading">' + data + '</span>';
          }
          return '<span class="text-muted">Sin sucursal</span>';
        }
      },
      {
        targets: 2,
        render: function (data) {
          if (typeof data === 'string' && data) {
            return '<span class="text-heading">' + data + '</span>';
          }
          return '<span class="text-muted">-</span>';
        }
      },
      {
        targets: 3,
        render: function (data) {
          return '<span class="fw-medium">' + (data || '-') + '</span>';
        }
      },
      {
        targets: 4,
        render: function (data, type, full) {
          const id = data;
          if (!id && id !== 0) {
            return '<span class="text-muted">-</span>';
          }
          return (
            '<a href="articulo.php?id=' +
            encodeURIComponent(id) +
            '" target="_blank" class="fw-medium text-primary text-decoration-underline">' +
            id +
            '</a>'
          );
        }
      },
      {
        targets: 5,
        render: function (data) {
          if (!data || data === '—') {
            return '<span class="text-muted">-</span>';
          }
          const t = String(data);
          const corto = t.length > 60 ? t.substring(0, 60) + '…' : t;
          return '<span class="fw-medium" title="' + t.replace(/"/g, '&quot;') + '">' + corto + '</span>';
        }
      },
      {
        targets: 6,
        render: function (data) {
          const map = {
            pendiente: { cls: 'bg-label-warning', icon: 'ri-time-line', txt: 'Pendiente' },
            autorizada: { cls: 'bg-label-success', icon: 'ri-checkbox-circle-fill', txt: 'Autorizada' },
            usada: { cls: 'bg-label-info', icon: 'ri-shopping-cart-line', txt: 'Usada' },
            nousada: { cls: 'bg-label-secondary', icon: 'ri-close-circle-line', txt: 'No usada' }
          };
          const m = map[data] || { cls: 'bg-label-secondary', icon: 'ri-question-line', txt: data || '-' };
          return (
            '<span class="badge ' +
            m.cls +
            ' rounded-pill"><i class="icon-base ' +
            m.icon +
            ' me-1"></i>' +
            m.txt +
            '</span>'
          );
        }
      },
      {
        targets: 7,
        render: function (data) {
          if (!data) {
            return '<span class="text-muted">-</span>';
          }
          const fecha = new Date(data).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
          const hora = new Date(data).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
          return '<span class="fw-medium">' + fecha + ' ' + hora + '</span>';
        }
      },
      {
        targets: 8,
        render: function (data) {
          return fmtEuro(data);
        }
      },
      {
        targets: 9,
        render: function (data) {
          return fmtEuro(data);
        }
      },
      {
        targets: 10,
        render: function (data, type, full) {
          const estado = full[6];
          if (estado === 'pendiente') {
            if (!window.puede_acceder_edit) {
              return '<span class="badge bg-label-warning rounded-pill"><i class="icon-base ri ri-time-line me-1"></i>Pendiente</span>';
            }
            return (
              '<button type="button" class="btn btn-warning btn-sm waves-effect waves-light btn-autorizar-descuento" ' +
              'data-id="' +
              full[0] +
              '" ' +
              'data-sucursal="' +
              (full[1] || '').replace(/"/g, '&quot;') +
              '" ' +
              'data-usuario="' +
              (full[2] || '').replace(/"/g, '&quot;') +
              '" ' +
              'data-codigo="' +
              (full[3] || '').replace(/"/g, '&quot;') +
              '" ' +
              'data-id-articulo="' +
              full[4] +
              '" ' +
              'data-descripcion="' +
              (full[5] || '').replace(/"/g, '&quot;') +
              '" ' +
              'data-precio-original="' +
              (full[8] != null ? full[8] : '') +
              '" ' +
              'title="Autorizar o rechazar">' +
              '<i class="icon-base ri ri-time-line me-1"></i>Gestionar' +
              '</button>'
            );
          }
          if (estado === 'autorizada') {
            return (
              '<span class="badge bg-label-success rounded-pill"><i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Listo</span>'
            );
          }
          if (estado === 'usada') {
            return (
              '<span class="badge bg-label-info rounded-pill"><i class="icon-base ri ri-shopping-cart-line me-1"></i>Usada</span>'
            );
          }
          if (estado === 'nousada') {
            return (
              '<span class="badge bg-label-secondary rounded-pill"><i class="icon-base ri ri-close-circle-line me-1"></i>No usada</span>'
            );
          }
          return '<span class="text-muted">-</span>';
        }
      }
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
                className: 'btn buttons-collection btn-primary dropdown-toggle waves-effect',
                text:
                  '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                buttons: [
                                                      {
                    extend: 'excel',
                    text: `<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>`,
                    className: 'dropdown-item',
                    exportOptions: {
                      columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                      format: {
                        body: function (inner) {
                          if (inner.length <= 0) return inner;
                          const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                          let result = '';
                          el.forEach(function (item) {
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
                      columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                      format: {
                        body: function (inner) {
                          if (inner.length <= 0) return inner;
                          const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                          let result = '';
                          el.forEach(function (item) {
                            result += item.textContent || item.innerText || '';
                          });
                          return result;
                        }
                      }
                    },
                    customize: function (doc) {
                      doc.pageOrientation = 'landscape';
                      doc.defaultStyle.fontSize = 8;
                      doc.styles.tableHeader.fontSize = 9;
                      doc.styles.tableHeader.fillColor = '#2d4154';
                      doc.styles.tableHeader.bold = true;
                      doc.styles.tableHeader.color = 'white';
                      doc.content[0].text = 'Autorizaciones descuento (venta)';
                      doc.content[0].alignment = 'center';
                      doc.content[0].fontSize = 16;
                      doc.content[0].margin = [0, 0, 0, 10];
                      doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
                      doc.pageMargins = [20, 20, 20, 20];
                      doc.content[1].layout = {
                        hLineWidth: function () {
                          return 0;
                        },
                        vLineWidth: function () {
                          return 0;
                        },
                        paddingLeft: function () {
                          return 4;
                        },
                        paddingRight: function () {
                          return 4;
                        },
                        paddingTop: function () {
                          return 2;
                        },
                        paddingBottom: function () {
                          return 2;
                        }
                      };
                      doc.content[1].table.widths = doc.content[1].table.widths.map(function () {
                        return '*';
                      });
                    }
                  },
                  {
                    extend: 'copy',
                    text: `<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar`,
                    className: 'dropdown-item',
                    exportOptions: {
                      columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                      format: {
                        body: function (inner) {
                          if (inner.length <= 0) return inner;
                          const el = new DOMParser().parseFromString(inner, 'text/html').body.childNodes;
                          let result = '';
                          el.forEach(function (item) {
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
      bottomEnd: 'paging'
    },
    ajax: {
      url: 'parts/autorizar_descuentos/listar/load_list.php',
      type: 'POST',
      data: function (d) {
        d.filtro_sucursal = document.getElementById('FiltroSucursalDescuento')
          ? document.getElementById('FiltroSucursalDescuento').value
          : '';
        d.filtro_estado = document.getElementById('FiltroEstadoDescuento')
          ? document.getElementById('FiltroEstadoDescuento').value
          : '';
        return d;
      },
      dataSrc: function (json) {
        return json.data || [];
      },
      error: function () {}
    },
    responsive: {
      details: {
        display: DataTable.Responsive.display.modal({
          header: function (row) {
            const data = row.data();
            return 'Autorización #' + data[0];
          }
        }),
        type: 'column',
        renderer: function (api, rowIdx, columns) {
          const data = columns
            .map(function (col) {
              return col.title !== ''
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '"><td>' +
                    col.title +
                    ':</td><td>' +
                    col.data +
                    '</td></tr>'
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
    elementsToModify.forEach(function (cfg) {
      document.querySelectorAll(cfg.selector).forEach(function (element) {
        if (cfg.classToRemove) {
          cfg.classToRemove.split(' ').forEach(function (className) {
            element.classList.remove(className);
          });
        }
        if (cfg.classToAdd) {
          cfg.classToAdd.split(' ').forEach(function (className) {
            element.classList.add(className);
          });
        }
      });
    });
  }, 100);

  if (window.puede_acceder_edit) {
  let modalIdAutorizacion = null;

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-autorizar-descuento');
    if (!btn) {
      return;
    }
    modalIdAutorizacion = btn.getAttribute('data-id');
    document.getElementById('modal-desc-sucursal').textContent = btn.getAttribute('data-sucursal') || '-';
    document.getElementById('modal-desc-usuario').textContent = btn.getAttribute('data-usuario') || '-';
    document.getElementById('modal-desc-codigo').textContent = btn.getAttribute('data-codigo') || '-';
    document.getElementById('modal-desc-id-articulo').textContent = btn.getAttribute('data-id-articulo') || '-';
    document.getElementById('modal-desc-descripcion').textContent = btn.getAttribute('data-descripcion') || '-';
    const po = btn.getAttribute('data-precio-original');
    const n = po !== '' && po != null ? parseFloat(po) : NaN;
    document.getElementById('modal-desc-precio-original').textContent = !isNaN(n)
      ? n.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'
      : '-';
    const inputPrecio = document.getElementById('input-precio-nuevo-autorizacion');
    inputPrecio.value = !isNaN(n) ? n.toFixed(2) : '';
    const modal = new bootstrap.Modal(document.getElementById('modalAutorizarDescuento'));
    modal.show();
  });

  document.getElementById('btn-confirmar-autorizacion-descuento').addEventListener('click', function () {
    if (!modalIdAutorizacion) {
      return;
    }
    const inputPrecio = document.getElementById('input-precio-nuevo-autorizacion');
    const precioRaw = inputPrecio ? String(inputPrecio.value).replace(',', '.').trim() : '';
    const precioNuevo = parseFloat(precioRaw);
    if (precioRaw === '' || isNaN(precioNuevo) || precioNuevo < 0) {
      Swal.fire({
        title: 'Precio requerido',
        text: 'Indique un precio autorizado válido (≥ 0).',
        icon: 'warning',
        confirmButtonText: 'Aceptar'
      });
      return;
    }
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalAutorizarDescuento'));
    modal.hide();

    Swal.fire({
      title: 'Autorizando...',
      text: 'Por favor espere',
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      }
    });

    const body =
      'id=' +
      encodeURIComponent(modalIdAutorizacion) +
      '&estado=autorizada&precio_nuevo=' +
      encodeURIComponent(precioNuevo);

    fetch('parts/autorizar_descuentos/listar/actualizar_estado.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: body
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data.success) {
          Swal.fire({
            title: 'Autorizado',
            text: data.message || 'Listo',
            icon: 'success',
            confirmButtonText: 'Aceptar'
          });
          dt_autorizaciones.ajax.reload(null, false);
        } else {
          throw new Error(data.error || data.message || 'Error al autorizar');
        }
      })
      .catch(function (error) {
        Swal.fire({
          title: 'Error',
          text: error.message || 'Error al autorizar',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      });
  });

  const btnRechazar = document.getElementById('btn-cancelar-solicitud-descuento');
  if (btnRechazar) {
    btnRechazar.addEventListener('click', function (e) {
      e.preventDefault();
      if (!modalIdAutorizacion) {
        const m = bootstrap.Modal.getInstance(document.getElementById('modalAutorizarDescuento'));
        if (m) {
          m.hide();
        }
        return;
      }
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalAutorizarDescuento'));
      if (modal) {
        modal.hide();
      }

      Swal.fire({
        title: 'Rechazando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: function () {
          Swal.showLoading();
        }
      });

      fetch('parts/autorizar_descuentos/listar/actualizar_estado.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'id=' + encodeURIComponent(modalIdAutorizacion) + '&estado=nousada'
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (data.success) {
            Swal.fire({
              title: 'Actualizado',
              text: data.message || 'Listo',
              icon: 'info',
              confirmButtonText: 'Aceptar'
            });
            modalIdAutorizacion = null;
            dt_autorizaciones.ajax.reload(null, false);
          } else {
            throw new Error(data.error || data.message || 'Error');
          }
        })
        .catch(function (error) {
          Swal.fire({
            title: 'Error',
            text: error.message || 'Error al rechazar',
            icon: 'error',
            confirmButtonText: 'Aceptar'
          });
        });
    });
  }

  const modalAutorizarDescuento = document.getElementById('modalAutorizarDescuento');
  if (modalAutorizarDescuento) {
  modalAutorizarDescuento.addEventListener('hidden.bs.modal', function () {
    modalIdAutorizacion = null;
    const inputPrecio = document.getElementById('input-precio-nuevo-autorizacion');
    if (inputPrecio) {
      inputPrecio.value = '';
    }
  });
  }
  }
});
