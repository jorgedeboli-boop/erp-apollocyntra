/**
 * Page Facturas Rectificativas List
 */

'use strict';

document.addEventListener('DOMContentLoaded', function (e) {
  const dt_table = document.querySelector('.datatables-facturas-rectificativas');
  window.dt_facturas_rectificativas = null;

  function badgeEstadoFiskaly(estado) {
    const raw = (estado || '').toString().trim().toLowerCase();
    if (!raw || raw === '—' || raw === '-') {
      return '<span class="text-muted">—</span>';
    }
    let cls = 'secondary';
    let txt = raw;
    if (raw === 'aceptada') {
      cls = 'success';
      txt = 'Aceptada';
    } else if (raw === 'pendiente') {
      cls = 'warning';
      txt = 'Pendiente';
    } else if (raw === 'enviada') {
      cls = 'info';
      txt = 'Enviada';
    } else if (raw === 'rechazada') {
      cls = 'danger';
      txt = 'Rechazada';
    } else if (raw === 'error') {
      cls = 'danger';
      txt = 'Error';
    } else if (raw === 'sin_cache') {
      cls = 'secondary';
      txt = 'Sin cache';
    } else {
      txt = raw.charAt(0).toUpperCase() + raw.slice(1);
    }
    return '<span class="badge bg-label-' + cls + ' rounded-pill">' + txt + '</span>';
  }

  function reenviarFacturaFiskaly(idFactura, btn) {
    if (!idFactura) {
      return;
    }
    Swal.fire({
      title: '¿Reenviar a Fiskaly?',
      text: 'Se reintentará el envío de la factura rectificativa n.º ' + idFactura,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, reenviar',
      cancelButtonText: 'Cancelar'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      if (btn) {
        btn.disabled = true;
      }

      Swal.fire({
        title: 'Reenviando a Fiskaly...',
        allowOutsideClick: false,
        didOpen: function () {
          Swal.showLoading();
        }
      });

      const formData = new FormData();
      formData.append('id_factura', idFactura);
      formData.append('simplificada', '0');

      fetch('parts/facturas_rectificativas/listar/reenviar_fiskaly.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (r) {
          return r.json().then(function (data) {
            return { ok: r.ok, data: data };
          });
        })
        .then(function (out) {
          const data = out.data || {};
          if (out.ok && data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Reenviado',
              text: data.message || 'Rectificativa reenviada a Fiskaly'
            });
            if (window.dt_facturas_rectificativas) {
              window.dt_facturas_rectificativas.ajax.reload(null, false);
            }
          } else {
            throw new Error(data.message || data.error || 'No se pudo reenviar a Fiskaly');
          }
        })
        .catch(function (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error Fiskaly',
            text: err.message || 'Error al reenviar'
          });
        })
        .finally(function () {
          if (btn) {
            btn.disabled = false;
          }
        });
    });
  }

  if (dt_table) {
    window.dt_facturas_rectificativas = new DataTable(dt_table, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 500,
      timeout: 60000,
      language: DATATABLES_SPANISH,
      ajax: {
        url: 'parts/facturas_rectificativas/listar/load_list.php',
        type: 'POST',
        data: function(d) {
          return d;
        },
        dataSrc: function(json) {
          if (json && json.error) {
            console.error('Error listado facturas rectificativas:', json.error);
          }
          return (json && json.data) ? json.data : [];
        },
        error: function(xhr, error, thrown) {
          let detalle = thrown || error || '';
          try {
            const j = JSON.parse(xhr.responseText);
            if (j && j.error) {
              detalle = j.error;
            }
          } catch (e) {}
          console.error('Error AJAX:', detalle, xhr.responseText);
        }
      },
      columns: [
        {
          data: null,
          orderable: true,
          render: function (data, type, row) {
            if (type === 'sort' || type === 'type') {
              return row[0] != null ? row[0] : '';
            }
            if (row[0] == null || row[0] === '') return '-';
            var texto = row[1] != null && row[1] !== '' ? row[1] : String(row[0]);
            var url = row[10] ? row[10] : ('Impresiones/Facturas/factura_rectificativa.php?id_factura=' + row[0]);
            return '<a href="' + url + '" target="_blank" class="fw-semibold text-primary">' + texto + '</a>';
          }
        },
        { data: 2 }, { data: 3 }, { data: 4 },
        { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 },
        {
          data: 11,
          orderable: false,
          render: function (data) {
            return badgeEstadoFiskaly(data);
          }
        },
        {
          data: null,
          orderable: false,
          searchable: false,
          className: 'text-center',
          render: function (data, type, row) {
            const idFactura = row[0];
            const puede = parseInt(row[14], 10) === 1;
            if (!puede) {
              return '<span class="text-muted">—</span>';
            }
            return (
              '<button type="button" class="btn btn-sm btn-outline-warning waves-effect btn-reenviar-fiskaly" ' +
              'data-id-factura="' + idFactura + '" title="Reenviar a Fiskaly">' +
              '<i class="icon-base ri ri-refresh-line me-1"></i>Reenviar Fiskaly' +
              '</button>'
            );
          }
        }
      ],
      columnDefs: [
        { targets: 1, render: function(data) { return data || '-'; } },
        { targets: 2, render: function(data) { return data || '-'; } },
        { targets: 3, render: function(data) { return data || '-'; } },
        { targets: 4, render: function(data) { return '<span class="fw-semibold text-success">' + (data || '-') + '</span>'; } },
        {
          targets: 5,
          render: function(data) {
            let c = 'secondary';
            if (data === 'pagada') c = 'success'; else if (data === 'anulada') c = 'danger'; else if (data === 'nopagada') c = 'warning';
            return '<span class="badge bg-label-' + c + ' rounded-pill">' + (data || '-') + '</span>';
          }
        },
        {
          targets: 6,
          render: function(data) {
            if (!data || data === '-') return '-';
            const v = (data || '').toLowerCase();
            let c = 'secondary';
            if (v === 'contado' || v === 'efectivo') c = 'success'; else if (v === 'tarjeta') c = 'primary'; else if (v === 'transferencia') c = 'info'; else if (v === 'bizum') c = 'warning';
            return '<span class="badge bg-label-' + c + ' rounded-pill">' + data + '</span>';
          }
        },
        {
          targets: 7,
          render: function (data, type, row) {
            var idOriginal = row[9];
            return idOriginal ? '<a href="Impresiones/Facturas/factura.php?id_factura=' + idOriginal + '" target="_blank" class="fw-semibold text-primary">' + (data || idOriginal) + '</a>' : '-';
          }
        }
      ],
      order: [[1, 'desc']],
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
          display: $.fn.dataTable.Responsive.display.modal({ header: function(row) { var d = row.data(); return 'Factura rectificativa ' + (d[1] || d[0]); } }),
          renderer: $.fn.dataTable.Responsive.renderer.tableAll({ tableClass: 'table' })
        }
      }
    });

    dt_table.addEventListener('click', function (ev) {
      const btn = ev.target.closest('.btn-reenviar-fiskaly');
      if (!btn) {
        return;
      }
      ev.preventDefault();
      ev.stopPropagation();
      const idFactura = parseInt(btn.getAttribute('data-id-factura'), 10) || 0;
      reenviarFacturaFiskaly(idFactura, btn);
    });

    setTimeout(function() {
      document.querySelectorAll('.dt-layout-topStart').forEach(el => el.classList.add('ps-3'));
      document.querySelectorAll('.dt-layout-topEnd').forEach(el => el.classList.add('pe-3'));
    }, 100);
  }

  function exportarTodosLosDatos(tipo, dt) {
    const formData = new FormData();
    formData.append('search', dt.search());
    Swal.fire({ title: 'Generando exportación...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    fetch('parts/facturas_rectificativas/listar/export_all.php', { method: 'POST', body: formData })
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
        div.innerHTML = '<table id="' + tid + '"><thead><tr><th>Nº factura</th><th>FECHA</th><th>HORA</th><th>CLIENTE</th><th>TOTAL</th><th>ESTADO</th><th>TIPO PAGO</th><th>FACT. ORIGINAL</th><th>ESTADO FISKALY</th></tr></thead></table>';
        document.body.appendChild(div);
        const rowsExport = (data.data || []).map(function (r) {
          return [
            (r[0] != null ? r[0] : '') + ' — ' + (r[1] != null ? r[1] : ''),
            r[2], r[3], r[4], r[5], r[6], r[7], r[8], r[9]
          ];
        });
        const $t = $('#' + tid).DataTable({ data: rowsExport, searching: false, ordering: false, dom: 't' });
        $t.button().add(0, { extend: tipo === 'excel' ? 'excelHtml5' : tipo, title: 'Facturas rectificativas' }).trigger();
        setTimeout(function() { $t.destroy(); div.remove(); }, 1000);
      })
      .catch(err => { Swal.close(); Swal.fire({ icon: 'error', title: 'Error', text: err.message }); });
  }

});
