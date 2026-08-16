'use strict';

document.addEventListener('DOMContentLoaded', function () {
  var dtTable = document.querySelector('.datatables-correccion-cajas');
  var modalEl = document.getElementById('modalCorreccionCaja');
  var modalInstance = modalEl ? new bootstrap.Modal(modalEl) : null;
  var FD = window.FiltrosDinamicosListar;
  var sortableMovimientos = null;
  var modalNuevoApunteEl = document.getElementById('modalNuevoApunteCorreccion');
  var modalNuevoApunteInstance = modalNuevoApunteEl ? new bootstrap.Modal(modalNuevoApunteEl) : null;
  var modalEditarApunteEl = document.getElementById('modalEditarApunteCorreccion');
  var modalEditarApunteInstance = modalEditarApunteEl ? new bootstrap.Modal(modalEditarApunteEl) : null;
  var guardandoImporteInline = false;
  var estadoModal = {
    id_tabla: 0,
    fecha: '',
    falta_apertura: false,
    falta_cierre: false,
    apertura_id_erroneo: false,
    cierre_id_erroneo: false,
    cierre_no_coincide: false,
    id_cierre: null,
    cierreMovimiento: null,
    min_id: null,
    max_id: null,
    movimientosPorId: {},
  };

  var modalCorreccionActualizado = false;

  function resetModalCorreccionEstado() {
    modalCorreccionActualizado = false;
    var btnCancelar = document.getElementById('btn-cancelar-correccion-caja');
    if (btnCancelar) {
      btnCancelar.textContent = 'Cancelar';
    }
  }

  function marcarModalCorreccionActualizado() {
    modalCorreccionActualizado = true;
    var btnCancelar = document.getElementById('btn-cancelar-correccion-caja');
    if (btnCancelar) {
      btnCancelar.textContent = 'Cerrar';
    }
  }

  function actualizarModalTrasCambio(peticionPromise) {
    if (typeof mostrarLoaderUniversal === 'function') {
      mostrarLoaderUniversal('Actualizando');
    }

    return Promise.resolve(peticionPromise)
      .then(function (data) {
        if (data && data.success === false) {
          throw new Error(data.message || data.error || 'No se pudo completar la operación');
        }
        return cargarMovimientosModal();
      })
      .then(function () {
        if (dtCorreccionCajas) {
          dtCorreccionCajas.ajax.reload(null, false);
        }
        marcarModalCorreccionActualizado();
        var btnGuardar = document.getElementById('btn-guardar-orden-correccion');
        if (btnGuardar) {
          btnGuardar.hidden = true;
        }
      })
      .finally(function () {
        if (typeof ocultarLoaderUniversal === 'function') {
          ocultarLoaderUniversal();
        }
      });
  }

  function formatearFecha(valor) {
    if (!valor) {
      return '—';
    }
    var partes = String(valor).trim().split('-');
    if (partes.length === 3) {
      return partes[2] + '/' + partes[1] + '/' + partes[0];
    }
    return String(valor);
  }

  function formatearEuro(valor) {
    var numero = Number(valor || 0);
    return numero.toLocaleString('es-ES', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }) + ' €';
  }

  function escapeHtml(texto) {
    var div = document.createElement('div');
    div.textContent = texto == null ? '' : String(texto);
    return div.innerHTML;
  }

  function toggleCorreccionInputs() {
    var checkApertura = document.getElementById('check-agregar-apertura');
    var checkCierre = document.getElementById('check-agregar-cierre');
    var wrapApertura = document.getElementById('wrap-input-apertura');
    var wrapCierre = document.getElementById('wrap-input-cierre');

    if (wrapApertura && checkApertura) {
      wrapApertura.hidden = !checkApertura.checked;
    }
    if (wrapCierre && checkCierre) {
      wrapCierre.hidden = !checkCierre.checked;
    }
  }

  function construirTextoConflicto(meta) {
    if (meta.conflicto_texto) {
      return meta.conflicto_texto;
    }
    var mensajes = [];
    if (meta.falta_apertura) {
      mensajes.push('falta apertura');
    } else if (meta.apertura_id_erroneo) {
      mensajes.push('apertura con id erróneo');
    }
    if (meta.falta_cierre) {
      mensajes.push('falta cierre');
    } else if (meta.cierre_id_erroneo) {
      mensajes.push('cierre con id erróneo');
    }
    if (meta.cierre_no_coincide) {
      mensajes.push('Caja final no coincide con la diferencia en entradas y salidas');
    }
    return mensajes.length ? 'Caja en conflicto: ' + mensajes.join(' y ') : '';
  }

  function destruirSortableMovimientos() {
    if (sortableMovimientos) {
      sortableMovimientos.destroy();
      sortableMovimientos = null;
    }
  }

  function obtenerOrdenMovimientosDom() {
    var tbody = document.getElementById('modal-correccion-movimientos');
    if (!tbody) {
      return [];
    }
    return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-id-movimiento]')).map(function (row) {
      return parseInt(row.getAttribute('data-id-movimiento'), 10);
    });
  }

  function initSortableMovimientos() {
    var tbody = document.getElementById('modal-correccion-movimientos');
    if (!tbody || typeof Sortable === 'undefined') {
      return;
    }

    destruirSortableMovimientos();
    sortableMovimientos = new Sortable(tbody, {
      animation: 150,
      handle: '.drag-handle',
      draggable: 'tr[data-id-movimiento]',
      ghostClass: 'sortable-ghost',
      onEnd: function () {
        var btnGuardar = document.getElementById('btn-guardar-orden-correccion');
        if (btnGuardar) {
          btnGuardar.hidden = false;
        }
      },
    });
  }

  function actualizarBotonesModal(tieneMovimientos) {
    var puedeInsertar = estadoModal.falta_apertura || estadoModal.falta_cierre;
    var puedeEditarCierre = estadoModal.cierre_no_coincide && !!estadoModal.cierreMovimiento;
    var wrapInsertar = document.getElementById('wrap-correccion-insertar');
    var wrapEditarCierre = document.getElementById('wrap-correccion-editar-cierre');
    var btnAplicar = document.getElementById('btn-aplicar-correccion-caja');
    var btnGuardar = document.getElementById('btn-guardar-orden-correccion');
    var btnGuardarCierre = document.getElementById('btn-guardar-cierre-correccion');
    var ayudaOrden = document.getElementById('modal-correccion-ayuda-orden');

    if (wrapInsertar) {
      wrapInsertar.hidden = !puedeInsertar;
    }
    if (wrapEditarCierre) {
      wrapEditarCierre.hidden = !puedeEditarCierre;
    }
    if (btnAplicar) {
      btnAplicar.hidden = !puedeInsertar;
    }
    if (btnGuardarCierre) {
      btnGuardarCierre.hidden = !puedeEditarCierre;
    }
    if (ayudaOrden) {
      ayudaOrden.hidden = !tieneMovimientos;
    }
    if (btnGuardar && !tieneMovimientos) {
      btnGuardar.hidden = true;
    }
  }

  function renderMovimientosModal(data) {
    var tbody = document.getElementById('modal-correccion-movimientos');
    if (!tbody) {
      return;
    }

    destruirSortableMovimientos();

    estadoModal.falta_apertura = !!data.falta_apertura;
    estadoModal.falta_cierre = !!data.falta_cierre;
    estadoModal.apertura_id_erroneo = !!data.apertura_id_erroneo;
    estadoModal.cierre_id_erroneo = !!data.cierre_id_erroneo;
    estadoModal.cierre_no_coincide = !!data.cierre_no_coincide;
    estadoModal.min_id = data.min_id;
    estadoModal.max_id = data.max_id;
    estadoModal.id_cierre = data.id_cierre || null;
    estadoModal.cierreMovimiento = null;
    estadoModal.movimientosPorId = {};

    if (data.movimientos && data.movimientos.length) {
      for (var i = 0; i < data.movimientos.length; i++) {
        var movimiento = data.movimientos[i];
        estadoModal.movimientosPorId[movimiento.id_movimientos] = movimiento;
        if (movimiento.es_cierre) {
          estadoModal.cierreMovimiento = movimiento;
        }
      }
    }

    if (data.conflicto) {
      document.getElementById('modal-correccion-conflicto').textContent = data.conflicto;
    }

    if (!data.movimientos || !data.movimientos.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Sin movimientos en este día</td></tr>';
    } else {
      tbody.innerHTML = data.movimientos.map(function (mov) {
        var idErroneo = (mov.es_apertura && estadoModal.apertura_id_erroneo)
          || (mov.es_cierre && (estadoModal.cierre_id_erroneo || estadoModal.cierre_no_coincide));
        var clases = ['movimiento-arrastrable'];
        if (idErroneo) {
          clases.push('movimiento-id-erroneo');
        }

        var accionesHtml =
          '<button type="button" class="btn btn-sm btn-icon btn-text-warning btn-editar-movimiento-correccion me-1" ' +
          'data-id-movimiento="' + escapeHtml(mov.id_movimientos) + '" title="Editar apunte">' +
          '<i class="icon-base ri ri-edit-line"></i>' +
          '</button>';
        accionesHtml +=
          '<button type="button" class="btn btn-sm btn-icon btn-text-danger btn-eliminar-movimiento-correccion" ' +
          'data-id-movimiento="' + escapeHtml(mov.id_movimientos) + '" title="Eliminar movimiento">' +
          '<i class="icon-base ri ri-delete-bin-line"></i>' +
          '</button>';

        return (
          '<tr class="' + clases.join(' ') + '" data-id-movimiento="' + escapeHtml(mov.id_movimientos) + '">' +
            '<td class="drag-handle"><i class="icon-base ri ri-drag-move-2-line"></i></td>' +
            '<td>' + escapeHtml(mov.id_movimientos) + '</td>' +
            '<td>' + escapeHtml(formatearFecha(mov.fecha_apunte)) + '</td>' +
            '<td>' + escapeHtml(mov.hora_de_apunte) + '</td>' +
            '<td>' + escapeHtml(mov.grupos) + '</td>' +
            '<td>' + escapeHtml(mov.concepto) + '</td>' +
            '<td class="text-end p-1">' +
              '<input type="number" class="form-control form-control-sm text-end input-correccion-importe" ' +
              'data-campo="entrada" data-id-movimiento="' + escapeHtml(mov.id_movimientos) + '" ' +
              'value="' + Number(mov.entrada || 0).toFixed(2) + '" step="0.01" min="0">' +
            '</td>' +
            '<td class="text-end p-1">' +
              '<input type="number" class="form-control form-control-sm text-end input-correccion-importe" ' +
              'data-campo="salida" data-id-movimiento="' + escapeHtml(mov.id_movimientos) + '" ' +
              'value="' + Number(mov.salida || 0).toFixed(2) + '" step="0.01" min="0">' +
            '</td>' +
            '<td class="text-center">' + accionesHtml + '</td>' +
          '</tr>'
        );
      }).join('');
      initSortableMovimientos();
    }

    document.getElementById('modal-correccion-total-entradas').textContent = formatearEuro(data.totales.entradas);
    document.getElementById('modal-correccion-total-salidas').textContent = formatearEuro(data.totales.salidas);
    document.getElementById('modal-correccion-total-balance').textContent = formatearEuro(data.totales.total);

    var checkApertura = document.getElementById('check-agregar-apertura');
    var checkCierre = document.getElementById('check-agregar-cierre');
    var wrapCheckApertura = document.getElementById('wrap-check-apertura');
    var wrapCheckCierre = document.getElementById('wrap-check-cierre');
    var inputApertura = document.getElementById('input-importe-apertura');
    var inputCierre = document.getElementById('input-importe-cierre');

    if (wrapCheckApertura) {
      wrapCheckApertura.hidden = !data.falta_apertura;
    }
    if (wrapCheckCierre) {
      wrapCheckCierre.hidden = !data.falta_cierre;
    }

    if (checkApertura) {
      checkApertura.checked = !!data.falta_apertura;
      checkApertura.disabled = !data.falta_apertura;
    }
    if (checkCierre) {
      checkCierre.checked = !!data.falta_cierre;
      checkCierre.disabled = !data.falta_cierre;
    }
    if (inputApertura) {
      inputApertura.value = Number(data.sugeridos.apertura || 0).toFixed(2);
    }
    if (inputCierre) {
      inputCierre.value = Number(data.sugeridos.cierre || 0).toFixed(2);
    }

    var inputEditarCierre = document.getElementById('input-editar-importe-cierre');
    var detalleCierre = document.getElementById('correccion-cierre-detalle');
    var esperadoCierre = document.getElementById('correccion-cierre-esperado');

    if (data.cierre_no_coincide && estadoModal.cierreMovimiento) {
      if (inputEditarCierre) {
        inputEditarCierre.value = Number(data.importe_cierre_esperado || 0).toFixed(2);
      }
      if (detalleCierre) {
        detalleCierre.textContent =
          'Registrado: ' + formatearEuro(data.importe_cierre) +
          ' · Debería ser: ' + formatearEuro(data.importe_cierre_esperado);
      }
      if (esperadoCierre) {
        esperadoCierre.textContent = formatearEuro(data.importe_cierre_esperado);
      }
    } else if (inputEditarCierre) {
      inputEditarCierre.value = '';
    }

    toggleCorreccionInputs();
    actualizarBotonesModal(!!(data.movimientos && data.movimientos.length));
  }

  function cargarMovimientosModal() {
    return fetch('parts/correccion_de_cajas/unique/load_movimientos_dia.php?id_tabla=' +
      encodeURIComponent(estadoModal.id_tabla) +
      '&fecha=' + encodeURIComponent(estadoModal.fecha), {
      credentials: 'same-origin',
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data || !data.success) {
          throw new Error((data && data.message) || 'No se pudieron cargar los movimientos');
        }
        renderMovimientosModal(data);
        return data;
      });
  }

  function abrirModalCorreccion(meta) {
    if (!modalInstance) {
      return;
    }

    resetModalCorreccionEstado();

    estadoModal = {
      id_tabla: meta.id_tabla,
      fecha: meta.fecha,
      falta_apertura: meta.falta_apertura,
      falta_cierre: meta.falta_cierre,
      apertura_id_erroneo: meta.apertura_id_erroneo,
      cierre_id_erroneo: meta.cierre_id_erroneo,
      cierre_no_coincide: meta.cierre_no_coincide,
      id_cierre: null,
      cierreMovimiento: null,
      min_id: null,
      max_id: null,
      movimientosPorId: {},
    };

    document.getElementById('modal-correccion-fecha').textContent = meta.fecha_texto;
    document.getElementById('modal-correccion-conflicto').textContent = construirTextoConflicto(meta);

    var inputAperturaInicial = document.getElementById('input-importe-apertura');
    if (inputAperturaInicial && meta.falta_apertura && meta.importe_apertura_sugerido != null) {
      inputAperturaInicial.value = Number(meta.importe_apertura_sugerido).toFixed(2);
    }

    document.getElementById('modal-correccion-movimientos').innerHTML =
      '<tr><td colspan="9" class="text-center text-muted">Cargando movimientos...</td></tr>';

    modalInstance.show();

    cargarMovimientosModal().catch(function (error) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: error.message || 'Error al cargar movimientos',
      });
    });
  }

  function cargarGruposSelect(selectId, valorSeleccionado) {
    return fetch('parts/movimientos_de_caja/listar/get_grupos.php', { credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        var select = document.getElementById(selectId);
        if (!select || !data || !data.success) {
          return;
        }
        select.innerHTML = '<option value="">Seleccionar grupo...</option>';
        (data.grupos || []).forEach(function (grupo) {
          var option = document.createElement('option');
          option.value = grupo;
          option.textContent = grupo;
          select.appendChild(option);
        });
        if (valorSeleccionado) {
          select.value = valorSeleccionado;
        }
      });
  }

  function cargarGruposNuevoApunte() {
    return cargarGruposSelect('nuevo-correccion-grupo');
  }

  function enviarActualizacionMovimiento(datos) {
    var formData = new FormData();
    formData.append('id_movimiento', String(datos.id_movimiento));
    formData.append('id_tabla', String(datos.id_tabla));
    formData.append('grupos', datos.grupos);
    formData.append('concepto', datos.concepto);
    formData.append('salida', String(datos.salida));
    formData.append('entrada', String(datos.entrada));

    return fetch('parts/movimientos_de_caja/listar/update_movimiento.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    }).then(function (response) { return response.json(); });
  }

  function guardarImporteInline(input) {
    if (guardandoImporteInline) {
      return;
    }

    var idMovimiento = parseInt(input.getAttribute('data-id-movimiento'), 10);
    var campo = input.getAttribute('data-campo');
    var mov = estadoModal.movimientosPorId[idMovimiento];
    if (!mov || !estadoModal.id_tabla || !campo) {
      return;
    }

    var entrada = campo === 'entrada' ? (parseFloat(input.value) || 0) : Number(mov.entrada || 0);
    var salida = campo === 'salida' ? (parseFloat(input.value) || 0) : Number(mov.salida || 0);
    var valorAnterior = Number(mov[campo] || 0);
    var valorNuevo = campo === 'entrada' ? entrada : salida;

    if (Math.abs(valorAnterior - valorNuevo) < 0.005) {
      return;
    }

    if (entrada === 0 && salida === 0) {
      input.value = valorAnterior.toFixed(2);
      Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: 'Debe ingresar un valor en Salida o Entrada',
      });
      return;
    }

    guardandoImporteInline = true;
    input.disabled = true;

    actualizarModalTrasCambio(
      enviarActualizacionMovimiento({
        id_movimiento: idMovimiento,
        id_tabla: estadoModal.id_tabla,
        grupos: mov.grupos || '',
        concepto: mov.concepto || '',
        salida: salida,
        entrada: entrada,
      })
    )
      .catch(function (error) {
        input.value = valorAnterior.toFixed(2);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'Error al actualizar el importe',
        });
      })
      .finally(function () {
        guardandoImporteInline = false;
        input.disabled = false;
      });
  }

  function abrirModalEditarApunteCorreccion(idMovimiento) {
    if (!modalEditarApunteInstance) {
      return;
    }

    var mov = estadoModal.movimientosPorId[idMovimiento];
    if (!mov) {
      return;
    }

    document.getElementById('editar-correccion-id-movimiento').value = String(mov.id_movimientos);
    document.getElementById('editar-correccion-tabla').value = String(estadoModal.id_tabla);
    document.getElementById('editar-correccion-concepto').value = mov.concepto || '';
    document.getElementById('editar-correccion-entrada').value = Number(mov.entrada || 0).toFixed(2);
    document.getElementById('editar-correccion-salida').value = Number(mov.salida || 0).toFixed(2);

    cargarGruposSelect('editar-correccion-grupo', mov.grupos || '').finally(function () {
      modalEditarApunteInstance.show();
    });
  }

  function abrirModalNuevoApunteCorreccion() {
    if (!modalNuevoApunteInstance) {
      return;
    }

    var form = document.getElementById('formNuevoApunteCorreccion');
    if (form) {
      form.reset();
    }

    document.getElementById('nuevo-correccion-tabla').value = String(estadoModal.id_tabla);
    document.getElementById('nuevo-correccion-fecha').value = estadoModal.fecha;

    cargarGruposNuevoApunte().finally(function () {
      modalNuevoApunteInstance.show();
    });
  }

  function onFiltroCorreccionChange() {
    if (dtCorreccionCajas) {
      dtCorreccionCajas.ajax.reload();
    }
  }

  function stripHtmlExport(data) {
    if (typeof data !== 'string') {
      return data == null ? '' : data;
    }
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = data;
    return tempDiv.textContent || tempDiv.innerText || data;
  }

  function exportarCorreccionCajas(tipo) {
    if (!dtCorreccionCajas) {
      return;
    }

    Swal.fire({
      title: 'Generando exportación...',
      text: 'Obteniendo todos los registros',
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      },
    });

    var formData = new FormData();
    formData.append('draw', '1');
    formData.append('start', '0');
    formData.append('length', '50000');
    formData.append('export_all', '1');
    formData.append('search[value]', dtCorreccionCajas.search() || '');

    fetch('parts/correccion_de_cajas/unique/load_list.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    })
      .then(function (response) { return response.json(); })
      .then(function (responseData) {
        Swal.close();

        if (responseData.error) {
          throw new Error(responseData.error);
        }
        if (!responseData.data || !responseData.data.length) {
          Swal.fire({
            title: 'Sin datos',
            text: 'No hay datos para exportar con los filtros aplicados',
            icon: 'info',
            confirmButtonText: 'Aceptar',
          });
          return;
        }

        var exportRows = responseData.data.map(function (row) {
          return [
            stripHtmlExport(row[0]),
            stripHtmlExport(row[1]),
            stripHtmlExport(row[2]),
          ];
        });

        var tempTableId = 'temp-export-correccion-cajas-' + Date.now();
        var tempDiv = document.createElement('div');
        tempDiv.style.display = 'none';
        tempDiv.innerHTML =
          '<table id="' + tempTableId + '"><thead><tr>' +
          '<th>ID</th><th>Fecha</th><th>Conflicto</th>' +
          '</tr></thead></table>';
        document.body.appendChild(tempDiv);

        var tempTable = new DataTable('#' + tempTableId, {
          data: exportRows,
          columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
          ],
          paging: false,
          searching: false,
          ordering: false,
          layout: { topStart: null, topEnd: null, bottomStart: null, bottomEnd: null },
        });

        var exportConfig = { exportOptions: { columns: ':visible' } };
        if (tipo === 'pdf') {
          exportConfig.orientation = 'landscape';
          exportConfig.customize = function (doc) {
            doc.pageOrientation = 'landscape';
            doc.pageSize = 'LEGAL';
            doc.defaultStyle.fontSize = 8;
            doc.styles.tableHeader.fontSize = 9;
            doc.styles.tableHeader.fillColor = '#2d4154';
            doc.styles.tableHeader.bold = true;
            doc.styles.tableHeader.color = 'white';
            doc.content[0].text = 'Corrección cajas';
            doc.content[0].alignment = 'center';
            doc.content[0].fontSize = 14;
            doc.content[0].margin = [0, 0, 0, 10];
            doc.pageMargins = [10, 10, 10, 10];
            if (doc.content[1] && doc.content[1].table) {
              doc.content[1].table.widths = Array(doc.content[1].table.body[0].length).fill('*');
            }
          };
        }

        var buttonType = 'excelHtml5';
        if (tipo === 'pdf') {
          buttonType = 'pdfHtml5';
        } else if (tipo === 'copy') {
          buttonType = 'copyHtml5';
        }
        var tempButton = tempTable.button().add(0, Object.assign({ extend: buttonType }, exportConfig));
        tempButton.trigger();
        setTimeout(function () {
          tempTable.destroy();
          tempDiv.remove();
        }, 2000);
      })
      .catch(function (error) {
        Swal.close();
        Swal.fire({
          title: 'Error',
          text: error.message || 'Ha ocurrido un error al exportar',
          icon: 'error',
          confirmButtonText: 'Aceptar',
        });
      });
  }

  var dtCorreccionCajas;
  if (dtTable) {
    dtCorreccionCajas = new DataTable(dtTable, {
      processing: true,
      serverSide: true,
      deferRender: true,
      searchDelay: 400,
      pageLength: 25,
      lengthChange: false,
      language: DATATABLES_SPANISH,
      order: [[1, 'asc']],
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
      ],
      columnDefs: [
        {
          targets: 0,
          render: function (data) {
            return '<span class="fw-medium">' + escapeHtml(data) + '</span>';
          },
        },
        {
          targets: 2,
          render: function (data) {
            return '<span class="badge bg-label-danger rounded-pill">' + escapeHtml(data) + '</span>';
          },
        },
        {
          targets: 3,
          orderable: false,
          searchable: false,
          render: function (data) {
            if (!data) {
              return '';
            }
            return (
              '<button type="button" class="btn btn-sm btn-primary btn-corregir-caja" ' +
              'data-id-tabla="' + escapeHtml(data.id_tabla) + '" ' +
              'data-fecha="' + escapeHtml(data.fecha) + '" ' +
              'data-fecha-texto="' + escapeHtml(data.fecha_texto) + '" ' +
              'data-falta-apertura="' + (data.falta_apertura ? '1' : '0') + '" ' +
              'data-falta-cierre="' + (data.falta_cierre ? '1' : '0') + '" ' +
              'data-apertura-id-erroneo="' + (data.apertura_id_erroneo ? '1' : '0') + '" ' +
              'data-cierre-id-erroneo="' + (data.cierre_id_erroneo ? '1' : '0') + '" ' +
              'data-cierre-no-coincide="' + (data.cierre_no_coincide ? '1' : '0') + '" ' +
              'data-importe-apertura-sugerido="' + escapeHtml(
                data.importe_apertura_sugerido != null ? data.importe_apertura_sugerido : ''
              ) + '">' +
              '<i class="icon-base ri ri-tools-line me-1"></i>Corregir</button>'
            );
          },
        },
      ],
      layout: {
        topStart: {
          rowClass: 'row m-2 my-0 mt-0 justify-content-between',
          features: [
            {
              buttons: [
                {
                  extend: 'collection',
                  className: 'btn buttons-collection btn-outline-secondary dropdown-toggle waves-effect button-exportar',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ri ri-upload-2-line icon-16px me-sm-1"></i> <span class="d-none d-sm-inline-block">Exportar</span></span>',
                  buttons: [
                    {
                      extend: 'excel',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-excel-line me-1"></i>Excel</span>',
                      className: 'dropdown-item',
                      action: function () {
                        exportarCorreccionCajas('excel');
                      },
                    },
                    {
                      extend: 'pdf',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ri ri-file-pdf-line me-1"></i>PDF</span>',
                      className: 'dropdown-item',
                      action: function () {
                        exportarCorreccionCajas('pdf');
                      },
                    },
                    {
                      extend: 'copy',
                      text: '<i class="icon-base ri ri-file-copy-line me-1"></i>Copiar',
                      className: 'dropdown-item',
                      action: function () {
                        exportarCorreccionCajas('copy');
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
          rowClass: 'row mx-0 justify-content-between w-100',
          features: ['info'],
        },
        bottomEnd: 'paging',
      },
      ajax: {
        url: 'parts/correccion_de_cajas/unique/load_list.php',
        type: 'POST',
        data: function (d) {
        },
      },
    });

    window.dtCorreccionCajas = dtCorreccionCajas;

    dtTable.addEventListener('click', function (event) {
      var btn = event.target.closest('.btn-corregir-caja');
      if (!btn) {
        return;
      }
      abrirModalCorreccion({
        id_tabla: parseInt(btn.getAttribute('data-id-tabla'), 10),
        fecha: btn.getAttribute('data-fecha'),
        fecha_texto: btn.getAttribute('data-fecha-texto'),
        falta_apertura: btn.getAttribute('data-falta-apertura') === '1',
        falta_cierre: btn.getAttribute('data-falta-cierre') === '1',
        apertura_id_erroneo: btn.getAttribute('data-apertura-id-erroneo') === '1',
        cierre_id_erroneo: btn.getAttribute('data-cierre-id-erroneo') === '1',
        cierre_no_coincide: btn.getAttribute('data-cierre-no-coincide') === '1',
        importe_apertura_sugerido: (function () {
          var valor = btn.getAttribute('data-importe-apertura-sugerido');
          if (valor === '' || valor == null) {
            return null;
          }
          var numero = parseFloat(valor);
          return isNaN(numero) ? null : numero;
        })(),
      });
    });
  }

  if (modalEl) {
    modalEl.addEventListener('hidden.bs.modal', resetModalCorreccionEstado);
  }

  document.getElementById('btn-nuevo-apunte-correccion')?.addEventListener('click', abrirModalNuevoApunteCorreccion);

  document.getElementById('modal-correccion-movimientos')?.addEventListener('focusout', function (event) {
    var input = event.target.closest('.input-correccion-importe');
    if (input) {
      guardarImporteInline(input);
    }
  });

  document.getElementById('modal-correccion-movimientos')?.addEventListener('keydown', function (event) {
    if (event.key === 'Enter' && event.target.closest('.input-correccion-importe')) {
      event.preventDefault();
      event.target.blur();
    }
  });

  document.getElementById('modal-correccion-movimientos')?.addEventListener('click', function (event) {
    var btnEditar = event.target.closest('.btn-editar-movimiento-correccion');
    if (btnEditar) {
      var idEditar = parseInt(btnEditar.getAttribute('data-id-movimiento'), 10);
      if (idEditar) {
        abrirModalEditarApunteCorreccion(idEditar);
      }
      return;
    }

    var btn = event.target.closest('.btn-eliminar-movimiento-correccion');
    if (!btn) {
      return;
    }

    var idMovimiento = parseInt(btn.getAttribute('data-id-movimiento'), 10);
    if (!idMovimiento || !estadoModal.id_tabla) {
      return;
    }

    Swal.fire({
      title: '¿Eliminar movimiento?',
      text: 'Se eliminará el movimiento #' + idMovimiento + ' de forma permanente.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d33',
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      var formData = new FormData();
      formData.append('id_movimiento', String(idMovimiento));
      formData.append('id_tabla', String(estadoModal.id_tabla));

      actualizarModalTrasCambio(
        fetch('parts/movimientos_de_caja/listar/delete_movimiento.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
        }).then(function (response) { return response.json(); })
      ).catch(function (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'Error al eliminar el movimiento',
        });
      });
    });
  });

  document.getElementById('btn-guardar-cierre-correccion')?.addEventListener('click', function () {
    if (!estadoModal.cierreMovimiento || !estadoModal.id_tabla) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'No hay cierre para actualizar' });
      return;
    }

    var inputEditarCierre = document.getElementById('input-editar-importe-cierre');
    var salida = parseFloat(inputEditarCierre && inputEditarCierre.value) || 0;
    if (salida <= 0) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'Indique un importe de cierre válido' });
      return;
    }

    var mov = estadoModal.cierreMovimiento;
    var btn = this;
    btn.disabled = true;

    actualizarModalTrasCambio(
      enviarActualizacionMovimiento({
        id_movimiento: mov.id_movimientos,
        id_tabla: estadoModal.id_tabla,
        grupos: mov.grupos || 'CAJA FINAL',
        concepto: mov.concepto || 'Cierre de caja',
        salida: salida,
        entrada: 0,
      })
    )
      .catch(function (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'Error al guardar el cierre',
        });
      })
      .finally(function () {
        btn.disabled = false;
      });
  });

  document.getElementById('btn-guardar-orden-correccion')?.addEventListener('click', function () {
    var orden = obtenerOrdenMovimientosDom();
    if (!orden.length) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'No hay movimientos para reordenar' });
      return;
    }

    var formData = new FormData();
    formData.append('id_tabla', String(estadoModal.id_tabla));
    formData.append('fecha', estadoModal.fecha);
    orden.forEach(function (id) {
      formData.append('orden[]', String(id));
    });

    var btn = this;
    btn.disabled = true;

    actualizarModalTrasCambio(
      fetch('parts/correccion_de_cajas/unique/reordenar_movimientos_dia.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      }).then(function (response) { return response.json(); })
    )
      .catch(function (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'Error al guardar el orden',
        });
      })
      .finally(function () {
        btn.disabled = false;
      });
  });

  document.getElementById('btnGuardarApunteCorreccion')?.addEventListener('click', function () {
    var form = document.getElementById('formEditarApunteCorreccion');
    var salida = parseFloat(document.getElementById('editar-correccion-salida').value) || 0;
    var entrada = parseFloat(document.getElementById('editar-correccion-entrada').value) || 0;

    if (salida === 0 && entrada === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Advertencia',
        text: 'Debe ingresar un valor en Salida o Entrada',
      });
      return;
    }

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

    actualizarModalTrasCambio(
      enviarActualizacionMovimiento({
        id_movimiento: parseInt(document.getElementById('editar-correccion-id-movimiento').value, 10),
        id_tabla: estadoModal.id_tabla,
        grupos: document.getElementById('editar-correccion-grupo').value,
        concepto: document.getElementById('editar-correccion-concepto').value,
        salida: salida,
        entrada: entrada,
      })
    )
      .then(function () {
        if (modalEditarApunteInstance) {
          modalEditarApunteInstance.hide();
        }
      })
      .catch(function (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'Error al guardar el apunte',
        });
      })
      .finally(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-save-line me-1"></i> Guardar cambios';
      });
  });

  document.getElementById('btnCrearApunteCorreccion')?.addEventListener('click', function () {
    var form = document.getElementById('formNuevoApunteCorreccion');
    var salida = parseFloat(document.getElementById('nuevo-correccion-salida').value) || 0;
    var entrada = parseFloat(document.getElementById('nuevo-correccion-entrada').value) || 0;

    if (salida === 0 && entrada === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Advertencia',
        text: 'Debe ingresar un valor en Salida o Entrada',
      });
      return;
    }

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creando...';

    actualizarModalTrasCambio(
      fetch('parts/correccion_de_cajas/unique/create_movimiento_correccion.php', {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
      }).then(function (response) { return response.json(); })
    )
      .then(function () {
        if (modalNuevoApunteInstance) {
          modalNuevoApunteInstance.hide();
        }
        form.reset();
      })
      .catch(function (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'Error al crear el apunte',
        });
      })
      .finally(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-save-line me-1"></i> Crear Apunte';
      });
  });

  document.getElementById('check-agregar-apertura')?.addEventListener('change', toggleCorreccionInputs);

  document.getElementById('check-agregar-cierre')?.addEventListener('change', toggleCorreccionInputs);

  document.getElementById('btn-aplicar-correccion-caja')?.addEventListener('click', function () {
    var agregarApertura = document.getElementById('check-agregar-apertura')?.checked;
    var agregarCierre = document.getElementById('check-agregar-cierre')?.checked;

    if (!agregarApertura && !agregarCierre) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'Seleccione al menos una corrección' });
      return;
    }

    if (agregarCierre && !agregarApertura && estadoModal.falta_apertura) {
      Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: 'Si faltan ambos, primero debe agregar la apertura de caja',
      });
      return;
    }

    var formData = new FormData();
    formData.append('id_tabla', String(estadoModal.id_tabla));
    formData.append('fecha', estadoModal.fecha);
    if (agregarApertura) {
      formData.append('agregar_apertura', '1');
      formData.append('importe_apertura', document.getElementById('input-importe-apertura').value || '0');
    }
    if (agregarCierre) {
      formData.append('agregar_cierre', '1');
      formData.append('importe_cierre', document.getElementById('input-importe-cierre').value || '0');
    }

    var btn = this;
    btn.disabled = true;

    actualizarModalTrasCambio(
      fetch('parts/correccion_de_cajas/unique/aplicar_correccion.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      }).then(function (response) { return response.json(); })
    )
      .catch(function (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'Error al aplicar la corrección',
        });
      })
      .finally(function () {
        btn.disabled = false;
      });
  });
});
