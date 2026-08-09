/**
 * Modal SweetAlert2 para elegir columnas de exportación DataTables.
 *
 * Uso:
 *   pedirColumnasExportacion({
 *     columnas: [{ index: 0, label: 'ID', required: true }, ...],
 *     idPrefix: 'lotes',
 *     columnasObligatorias: [0, 11],
 *     mensajeObligatorias: 'ID Lote y Sucursal son obligatorios'
 *   }).then(function (indices) { ... });
 */
(function () {
  'use strict';

  function obtenerColumnasObligatorias(columnas, extra) {
    const indices = new Set();

    (columnas || []).forEach(function (col) {
      if (col && col.required) {
        indices.add(col.index);
      }
    });

    (extra || []).forEach(function (idx) {
      indices.add(idx);
    });

    return Array.from(indices);
  }

  function escapeHtml(texto) {
    return String(texto)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  window.pedirColumnasExportacion = function (options) {
    options = options || {};

    const columnas = options.columnas || [];
    const idPrefix = options.idPrefix || ('export_' + Date.now());
    const checkboxClass = options.checkboxClass || ('swal-export-col-' + idPrefix);
    const title = options.title || 'Seleccione las columnas que desea exportar';
    const confirmButtonText = options.confirmButtonText || 'Exportar';
    const cancelButtonText = options.cancelButtonText || 'Cancelar';
    const mensajeSinColumnas = options.mensajeSinColumnas || 'Seleccione al menos una columna';
    const mensajeObligatorias = options.mensajeObligatorias || '';
    const marcarTodasPorDefecto = options.marcarTodasPorDefecto !== false;
    const maxHeight = options.maxHeight || '300px';
    const btnTodasClass = options.btnTodasClass || 'btn btn-sm btn-label-primary me-1';
    const btnNingunaClass = options.btnNingunaClass || 'btn btn-sm btn-label-secondary';
    const columnasObligatorias = obtenerColumnasObligatorias(columnas, options.columnasObligatorias);

    if (!columnas.length) {
      return Promise.reject(new Error('No hay columnas configuradas para exportar'));
    }

    if (typeof Swal === 'undefined') {
      return Promise.reject(new Error('SweetAlert2 no está disponible'));
    }

    const btnTodasId = 'swal_export_' + idPrefix + '_todas';
    const btnNingunaId = 'swal_export_' + idPrefix + '_ninguna';

    const checkboxesHtml = columnas.map(function (col) {
      const esObligatoria = columnasObligatorias.indexOf(col.index) !== -1;
      const checked = esObligatoria || marcarTodasPorDefecto;
      const attrs = checked ? ' checked' : '';
      const disabled = esObligatoria ? ' disabled' : '';
      const requiredBadge = esObligatoria ? ' <span class="text-muted">(obligatorio)</span>' : '';
      const inputId = 'export_col_' + idPrefix + '_' + col.index;

      return '<div class="form-check text-start mb-2">' +
        '<input class="form-check-input ' + checkboxClass + '" type="checkbox" value="' + col.index + '" id="' + inputId + '"' + attrs + disabled + '>' +
        '<label class="form-check-label" for="' + inputId + '">' + escapeHtml(col.label) + requiredBadge + '</label>' +
        '</div>';
    }).join('');

    return new Promise(function (resolve, reject) {
      Swal.fire({
        title: title,
        html: '<div class="mb-4 text-start">' +
          '<button type="button" class="' + btnTodasClass + '" id="' + btnTodasId + '">Todas</button>' +
          '<button type="button" class="' + btnNingunaClass + '" id="' + btnNingunaId + '">Ninguna</button>' +
          '</div>' +
          '<div style="max-height:' + maxHeight + ';overflow-y:auto">' + checkboxesHtml + '</div>',
        showCancelButton: true,
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText,
        reverseButtons: true,
        customClass: {
          container: 'swalExport',
          confirmButton: 'btn btn-label-success',
          cancelButton: 'btn btn-label-danger'
        },
        didOpen: function () {
          const btnTodas = document.getElementById(btnTodasId);
          const btnNinguna = document.getElementById(btnNingunaId);

          if (btnTodas) {
            btnTodas.addEventListener('click', function () {
              document.querySelectorAll('.' + checkboxClass).forEach(function (cb) {
                cb.checked = true;
              });
            });
          }

          if (btnNinguna) {
            btnNinguna.addEventListener('click', function () {
              document.querySelectorAll('.' + checkboxClass).forEach(function (cb) {
                if (!cb.disabled) {
                  cb.checked = false;
                }
              });
            });
          }
        },
        preConfirm: function () {
          const selected = Array.from(document.querySelectorAll('.' + checkboxClass + ':checked'))
            .map(function (cb) {
              return parseInt(cb.value, 10);
            });

          columnasObligatorias.forEach(function (idx) {
            if (selected.indexOf(idx) === -1) {
              selected.push(idx);
            }
          });

          if (selected.length === 0) {
            Swal.showValidationMessage(mensajeSinColumnas);
            return false;
          }

          if (mensajeObligatorias) {
            const faltanObligatorias = columnasObligatorias.some(function (idx) {
              return selected.indexOf(idx) === -1;
            });
            if (faltanObligatorias) {
              Swal.showValidationMessage(mensajeObligatorias);
              return false;
            }
          }

          return selected.sort(function (a, b) {
            return a - b;
          });
        }
      }).then(function (result) {
        if (result.isConfirmed) {
          resolve(result.value);
        } else {
          reject(new Error('cancelled'));
        }
      });
    });
  };
})();
