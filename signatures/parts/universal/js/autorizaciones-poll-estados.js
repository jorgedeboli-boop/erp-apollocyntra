/**
 * Polling cada 5s: refresca filas visibles y recarga página 1 si hay pendientes nuevas (arriba).
 */
(function (global) {
  'use strict';

  var POLL_MS = 5000;
  var pollUrl = 'parts/universal/poll_estados_autorizaciones.php';
  var timers = {};
  var recargando = {};

  function filasIguales(a, b) {
    if (!a || !b || a.length !== b.length) {
      return false;
    }
    for (var i = 0; i < a.length; i++) {
      var va = a[i];
      var vb = b[i];
      if (typeof va === 'object' && va !== null && typeof vb === 'object' && vb !== null) {
        if (JSON.stringify(va) !== JSON.stringify(vb)) {
          return false;
        }
      } else if (String(va) !== String(vb)) {
        return false;
      }
    }
    return true;
  }

  function recogerContextoPagina(api, cfg) {
    var ids = [];
    var maxId = 0;
    api.rows({ page: 'current' }).every(function () {
      var row = this.data();
      if (!row) {
        return;
      }
      var id = parseInt(row[cfg.idColumnIndex], 10);
      if (id > 0) {
        ids.push(id);
        if (id > maxId) {
          maxId = id;
        }
      }
    });
    return { ids: ids, maxId: maxId };
  }

  function leerFiltrosPoll(cfg) {
    if (typeof cfg.getAjaxParams === 'function') {
      return cfg.getAjaxParams();
    }
    var f = {};
    var el =
      document.getElementById('FiltroSucursal') ||
      document.getElementById('FiltroSucursalDescuento');
    if (el && el.value) {
      f.filtro_sucursal = el.value;
    }
    el =
      document.getElementById('FiltroEstado') ||
      document.getElementById('FiltroEstadoDescuento');
    if (el && el.value) {
      f.filtro_estado = el.value;
    }
    el = document.getElementById('FiltroEstadoSMS');
    if (el && el.value) {
      f.filtro_estado_sms = el.value;
    }
    el = document.getElementById('FiltroEstadoAutorizado');
    if (el && el.value) {
      f.filtro_estado_autorizado = el.value;
    }
    return f;
  }

  function aplicarFilasActualizadas(api, cfg, filasMap) {
    var huboCambio = false;
    api.rows({ page: 'current' }).every(function () {
      var rowIdx = this.index();
      var row = this.data();
      if (!row) {
        return;
      }
      var id = parseInt(row[cfg.idColumnIndex], 10);
      var nuevaFila = filasMap[id];
      if (!nuevaFila || !Array.isArray(nuevaFila)) {
        return;
      }
      if (filasIguales(row, nuevaFila)) {
        return;
      }
      api.row(rowIdx).data(nuevaFila);
      api.row(rowIdx).invalidate();
      huboCambio = true;
    });
    return huboCambio;
  }

  function claveTabla(cfg) {
    return cfg.tipo || 'dt';
  }

  function recargarConNuevasArriba(api, cfg) {
    var key = claveTabla(cfg);
    if (recargando[key]) {
      return;
    }
    recargando[key] = true;
    api.page(0).draw(false);
    api.ajax.reload(function () {
      recargando[key] = false;
    }, false);
  }

  function ejecutarPoll(cfg) {
    if (!cfg.dataTable) {
      return;
    }
    var api = cfg.dataTable;
    var ctx = recogerContextoPagina(api, cfg);
    var filtros = leerFiltrosPoll(cfg);

    var fd = new FormData();
    fd.append('tipo', cfg.tipo);
    fd.append('ids', JSON.stringify(ctx.ids));
    fd.append('max_id', String(ctx.maxId));
    Object.keys(filtros).forEach(function (key) {
      fd.append(key, filtros[key]);
    });

    fetch(pollUrl, { method: 'POST', body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data.success) {
          return;
        }
        if (data.filas && ctx.ids.length && aplicarFilasActualizadas(api, cfg, data.filas)) {
          api.draw(false);
        }
        if (data.hay_nuevas) {
          recargarConNuevasArriba(api, cfg);
        }
      })
      .catch(function () {
        /* silencioso */
      });
  }

  function detenerPoll(tipo) {
    if (timers[tipo]) {
      clearInterval(timers[tipo]);
      delete timers[tipo];
    }
  }

  /**
   * @param {object} cfg
   * @param {string} cfg.tipo
   * @param {DataTable} cfg.dataTable
   * @param {number} cfg.idColumnIndex
   * @param {function} [cfg.getAjaxParams] filtros actuales (mismo formato que ajax data)
   */
  function iniciarPollEstadosAutorizaciones(cfg) {
    if (!cfg || !cfg.tipo || !cfg.dataTable) {
      return;
    }
    detenerPoll(cfg.tipo);

    timers[cfg.tipo] = setInterval(function () {
      ejecutarPoll(cfg);
    }, POLL_MS);

    cfg.dataTable.on('destroy', function () {
      detenerPoll(cfg.tipo);
    });
  }

  global.iniciarPollEstadosAutorizaciones = iniciarPollEstadosAutorizaciones;
  global.detenerPollEstadosAutorizaciones = detenerPoll;
})(typeof window !== 'undefined' ? window : this);
