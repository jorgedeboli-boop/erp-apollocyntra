<!-- JAVASCRIPT CUSTOM migracion - unique  -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const baseUrl = 'parts/migracion/unique/';
  const listaMigraciones = document.getElementById('listaMigraciones');
  const btnAbrirModal = document.getElementById('btnAbrirModalMigracion');
  const btnGuardarMigracion = document.getElementById('btnGuardarMigracion');
  const formNuevaMigracion = document.getElementById('formNuevaMigracion');
  const modalEl = document.getElementById('modalNuevaMigracion');
  const modalMigracion = modalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalEl) : null;

  const ESTADO_LABEL = {
    pendiente: 'Pendiente',
    en_proceso: 'En proceso',
    migrado: 'Migrado',
    error: 'Error'
  };

  const ESTADO_BADGE = {
    pendiente: 'bg-warning',
    en_proceso: 'bg-info',
    migrado: 'bg-success',
    error: 'bg-danger'
  };

  function escHtml(val) {
    return String(val == null ? '' : val)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  let sesionMigracionTimer = null;

  function extenderSesionMigracion() {
    return fetch(baseUrl + 'migracion_extend_session.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).catch(function () {});
  }

  function iniciarSesionMigracionLarga() {
    if (window.sessionChecker && typeof window.sessionChecker.destroy === 'function') {
      window.sessionChecker.destroy();
      window.sessionChecker = null;
    }
    extenderSesionMigracion();
    sesionMigracionTimer = setInterval(extenderSesionMigracion, 3 * 60 * 1000);
  }

  function finalizarSesionMigracionLarga() {
    if (sesionMigracionTimer) {
      clearInterval(sesionMigracionTimer);
      sesionMigracionTimer = null;
    }
    if (!window.sessionChecker && typeof SessionChecker !== 'undefined') {
      window.sessionChecker = new SessionChecker();
    }
  }

  function mostrarLoader(texto) {
    const loaderContainer = document.getElementById('loaderContainer');
    const textLoader = document.getElementById('textLoader');

    if (loaderContainer) {
      loaderContainer.style.opacity = '0';
      loaderContainer.style.display = 'flex';
      loaderContainer.style.transition = 'opacity 0.4s ease-in-out';
      void loaderContainer.offsetWidth;
      setTimeout(function () {
        loaderContainer.style.opacity = '1';
      }, 10);
    }

    if (textLoader) {
      textLoader.textContent = (texto || 'Procesando') + '...';
    }
  }

  function ocultarLoader() {
    const loaderContainer = document.getElementById('loaderContainer');
    const textLoader = document.getElementById('textLoader');

    if (loaderContainer) {
      loaderContainer.style.transition = 'opacity 0.4s ease-in-out';
      loaderContainer.style.opacity = '0';
      setTimeout(function () {
        loaderContainer.style.display = 'none';
        loaderContainer.style.opacity = '1';
      }, 400);
    }

    if (textLoader) {
      textLoader.textContent = '';
    }
  }

  function mostrarMensaje(titulo, texto, icono) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: titulo,
        text: texto,
        icon: icono || 'success',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: icono === 'error' ? '#dc3545' : '#696cff'
      });
      return;
    }
    alert(titulo + '\n\n' + texto);
  }

  function estadoUi(estado) {
    const key = ESTADO_LABEL[estado] ? estado : 'pendiente';
    return {
      label: ESTADO_LABEL[key],
      badgeClass: ESTADO_BADGE[key] || ESTADO_BADGE.pendiente
    };
  }

  function renderBotonesAccion(nombre, estado) {
    if (estado === 'migrado') {
      return (
        '<button type="button" class="btn btn-primary btn-sm btn-ejecutar-migracion" disabled>' + nombre + '</button>' +
        '<button type="button" class="btn btn-outline-danger btn-sm btn-repetir-migracion ms-2">Repetir accion</button>'
      );
    }

    if (estado === 'en_proceso') {
      return '<button type="button" class="btn btn-primary btn-sm btn-ejecutar-migracion" disabled>' + nombre + '</button>';
    }

    return '<button type="button" class="btn btn-primary btn-sm btn-ejecutar-migracion">' + nombre + '</button>';
  }

  function actualizarBotonesItem(itemEl, estado) {
    if (!itemEl) {
      return;
    }

    const addBtn = itemEl.querySelector('.add-btn');
    const titulo = itemEl.querySelector('h6');
    const nombre = titulo ? titulo.textContent : 'Migración';

    if (addBtn) {
      addBtn.innerHTML = renderBotonesAccion(escHtml(nombre), estado);
    }
  }

  async function confirmarMigracion(nombre, descripcion) {
    if (typeof Swal === 'undefined') {
      return window.confirm('¿Confirma que desea ejecutar "' + nombre + '"?');
    }

    const result = await Swal.fire({
      title: '¿Ejecutar migración?',
      html:
        '<p class="mb-2">Va a ejecutar:</p>' +
        '<p><strong>' + escHtml(nombre) + '</strong></p>' +
        (descripcion ? '<p class="text-body-secondary small mb-0">' + escHtml(descripcion) + '</p>' : ''),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, migrar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#8592a3',
      reverseButtons: true
    });

    return result.isConfirmed;
  }

  function renderItem(item) {
    const ui = estadoUi(item.estado_migracion);
    const id = item.id_migracion;
    const codigo = escHtml(item.codigo_migracion);
    const nombre = escHtml(item.nombre_migracion);
    const descripcion = escHtml(item.descripcion_migracion);
    const script = escHtml(item.script_migracion);
    const estado = escHtml(item.estado_migracion);

    return (
      '<div class="list-group-item list-group-item-action d-flex align-items-center cursor-pointer migracion-item"' +
      ' data-id="' + id + '"' +
      ' data-codigo="' + codigo + '"' +
      ' data-script="' + script + '"' +
      ' data-descripcion="' + descripcion + '"' +
      ' data-estado="' + estado + '">' +
        '<div class="w-100">' +
          '<div class="d-flex justify-content-between align-items-center">' +
            '<div class="user-info">' +
              '<h6 class="mb-1 fw-normal">' + nombre + '</h6>' +
              '<div class="d-flex align-items-center flex-wrap">' +
                '<div class="user-status me-2 d-flex align-items-center">' +
                  '<span class="badge badge-dot ' + ui.badgeClass + ' me-1 migracion-badge"></span>' +
                  '<small class="migracion-status-label">' + escHtml(ui.label) + '</small>' +
                '</div>' +
                '<small class="text-body-secondary ms-1">' + descripcion + '</small>' +
              '</div>' +
            '</div>' +
            '<div class="add-btn ms-3 d-flex align-items-center flex-wrap gap-2">' +
              renderBotonesAccion(nombre, item.estado_migracion) +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  function renderLista(items) {
    if (!listaMigraciones) {
      return;
    }

    if (!Array.isArray(items) || items.length === 0) {
      listaMigraciones.innerHTML = '<div class="list-group-item text-muted">No hay migraciones registradas.</div>';
      return;
    }

    listaMigraciones.innerHTML = items.map(renderItem).join('');
  }

  function actualizarItemDom(itemEl, estado, labelOverride) {
    if (!itemEl) {
      return;
    }

    const ui = estadoUi(estado);
    const badge = itemEl.querySelector('.migracion-badge');
    const label = itemEl.querySelector('.migracion-status-label');

    itemEl.setAttribute('data-estado', estado);

    if (badge) {
      badge.classList.remove('bg-warning', 'bg-info', 'bg-success', 'bg-danger');
      badge.classList.add(ui.badgeClass);
    }

    if (label) {
      label.textContent = labelOverride || ui.label;
    }

    actualizarBotonesItem(itemEl, estado);
  }

  async function cargarMigraciones() {
    if (!listaMigraciones) {
      return [];
    }

    listaMigraciones.innerHTML = '<div class="list-group-item text-muted">Cargando migraciones…</div>';

    try {
      const res = await fetch(baseUrl + 'load_migraciones.php', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json = await res.json();

      if (!json.success) {
        listaMigraciones.innerHTML = '<div class="list-group-item text-danger">' + escHtml(json.message || 'Error al cargar migraciones.') + '</div>';
        return [];
      }

      renderLista(json.items || []);
      return json.items || [];
    } catch (err) {
      listaMigraciones.innerHTML = '<div class="list-group-item text-danger">Error al cargar migraciones.</div>';
      return [];
    }
  }

  async function actualizarEstadoMigracion(payload) {
    const body = new URLSearchParams(payload);
    await fetch(baseUrl + 'actualizar_estado_migracion.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body.toString()
    });
  }

  function extraerMensajeTexto(texto) {
    const lineas = String(texto || '').split('\n').map(function (l) {
      return l.trim();
    }).filter(Boolean);

    const resumenIdx = lineas.findIndex(function (l) {
      return l.indexOf('=== RESUMEN ===') !== -1;
    });

    if (resumenIdx !== -1) {
      return lineas.slice(resumenIdx).join('\n');
    }

    if (lineas.length) {
      return lineas.slice(-5).join('\n');
    }

    return 'Proceso finalizado.';
  }

  function parsearRespuestaMigracion(body, codigo) {
    const mensaje = extraerMensajeTexto(body);
    const hasError = body.indexOf('ERROR:') !== -1
      || body.indexOf('Error en la migración') !== -1
      || body.indexOf('No se pudo conectar') !== -1
      || body.indexOf('Hecho.') === -1;

    let procesados = 0;
    let total = 0;
    const matchMigrados = body.match(/Registros migrados:\s*(\d+)\s*de\s*(\d+)/i);
    if (matchMigrados) {
      procesados = parseInt(matchMigrados[1], 10) || 0;
      total = parseInt(matchMigrados[2], 10) || 0;
    }

    return {
      error: hasError,
      message: mensaje,
      registros_procesados: procesados,
      registros_total: total
    };
  }

  function esMigracionPorPasos(script) {
    return script.indexOf('corregir_poblaciones.php') !== -1;
  }

  function esperarMs(ms) {
    return new Promise(function (resolve) {
      setTimeout(resolve, ms);
    });
  }

  async function fetchPasoMigracion(script, query) {
    const url = script + (script.indexOf('?') >= 0 ? '&' : '?') + query + '&json=1';
    const res = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const body = await res.text();
    let data;
    try {
      data = JSON.parse(body);
    } catch (e) {
      if (!res.ok) {
        throw new Error('Error HTTP ' + res.status);
      }
      return { ok: true, message: body };
    }
    if (!res.ok || data.ok === false) {
      throw new Error(data.message || ('Error HTTP ' + res.status));
    }
    return data;
  }

  /**
   * Si hay 504 el PHP puede seguir en el servidor; esperamos y reintentamos el mismo paso.
   */
  async function fetchPasoMigracionConReintento(script, query, etiquetaLoader) {
    const maxIntentos = 6;
    const esperaReintento = 25000;

    for (let intento = 1; intento <= maxIntentos; intento++) {
      try {
        return await fetchPasoMigracion(script, query);
      } catch (err) {
        const msg = err && err.message ? err.message : '';
        const es504 = msg.indexOf('504') !== -1 || msg.indexOf('502') !== -1 || msg.indexOf('503') !== -1;
        if (!es504 || intento >= maxIntentos) {
          throw err;
        }

        if (etiquetaLoader) {
          mostrarLoader(etiquetaLoader + ' (servidor ocupado, reintento ' + intento + '/' + maxIntentos + ')');
        }
        await extenderSesionMigracion();
        await esperarMs(esperaReintento);

        try {
          const estado = await fetchPasoMigracion(script, 'paso=estado');
          if (etiquetaLoader && estado.message) {
            mostrarLoader(etiquetaLoader + ' — ' + estado.message);
          }
        } catch (estadoErr) {
          // ignorar error de estado
        }
      }
    }

    throw new Error('No se pudo completar el paso tras varios reintentos.');
  }

  async function ejecutarMigracionPoblaciones(itemEl, btn, script, nombre, id, codigo) {
    btn.disabled = true;
    actualizarItemDom(itemEl, 'en_proceso');
    mostrarLoader(nombre);

    await actualizarEstadoMigracion({
      id_migracion: id,
      estado_migracion: 'en_proceso',
      mensaje_resultado: '',
      registros_procesados: 0,
      registros_total: 0
    });

    iniciarSesionMigracionLarga();

    const lineas = [];
    let totalFilas = 0;

    try {
      let desdeId = 0;
      let hayMas = true;
      let loteNorm = 0;
      while (hayMas) {
        loteNorm++;
        const etiquetaNorm = nombre + ' (normalizar lote ' + loteNorm + ')';
        const rNorm = await fetchPasoMigracionConReintento(
          script,
          'paso=1&desde_id=' + encodeURIComponent(desdeId),
          etiquetaNorm
        );
        lineas.push(rNorm.message || ('Paso 1 lote ' + loteNorm));
        totalFilas += parseInt(rNorm.filas, 10) || 0;
        hayMas = !!rNorm.hay_mas;
        if (hayMas && rNorm.ultimo_id) {
          desdeId = parseInt(rNorm.ultimo_id, 10) || desdeId;
        } else {
          hayMas = false;
        }
      }

      mostrarLoader(nombre + ' (match directo)');
      let r = await fetchPasoMigracionConReintento(script, 'paso=2', nombre + ' (match directo)');
      lineas.push(r.message || 'Paso 2 OK');
      totalFilas += parseInt(r.filas, 10) || 0;

      mostrarLoader(nombre + ' (plan IA)');
      const plan = await fetchPasoMigracionConReintento(script, 'paso=ia_plan', nombre + ' (plan IA)');
      lineas.push(plan.message || 'Plan IA');

      if (plan.lotes && plan.lotes.length) {
        for (let i = 0; i < plan.lotes.length; i++) {
          const lote = plan.lotes[i];
          const etiquetaIa = nombre + ' (IA ' + (i + 1) + '/' + plan.lotes.length + ')';
          mostrarLoader(etiquetaIa);
          await extenderSesionMigracion();
          r = await fetchPasoMigracionConReintento(
            script,
            'paso=ia&provincia=' + encodeURIComponent(lote.provincia) + '&idx=' + encodeURIComponent(lote.idx),
            etiquetaIa
          );
          lineas.push(r.message || ('IA lote ' + (i + 1)));
          totalFilas += parseInt(r.filas, 10) || 0;
        }
      }

      mostrarLoader(nombre + ' (asignar IDs)');
      r = await fetchPasoMigracionConReintento(script, 'paso=3', nombre + ' (asignar IDs)');
      lineas.push(r.message || 'Paso 3 OK');
      totalFilas += parseInt(r.filas, 10) || 0;

      const mensaje = lineas.join('\n');
      actualizarItemDom(itemEl, 'migrado');
      await actualizarEstadoMigracion({
        id_migracion: id,
        estado_migracion: 'migrado',
        mensaje_resultado: mensaje,
        registros_procesados: totalFilas,
        registros_total: totalFilas
      });
      mostrarMensaje('Migrado', mensaje, 'success');
    } catch (err) {
      actualizarItemDom(itemEl, 'error');
      let msgError = err.message || 'Error desconocido';
      if (msgError.indexOf('504') !== -1) {
        msgError += '\n\nEl servidor puede haber seguido trabajando en segundo plano. '
          + 'Pulsa «Repetir acción»: continuará desde donde quedó (los pasos ya hechos no se duplican).';
      }
      const resumenParcial = lineas.length ? lineas.join('\n') : '';
      await actualizarEstadoMigracion({
        id_migracion: id,
        estado_migracion: 'error',
        mensaje_resultado: resumenParcial ? (resumenParcial + '\n\n' + msgError) : msgError,
        registros_procesados: totalFilas,
        registros_total: totalFilas
      });
      mostrarMensaje('Error', msgError + (resumenParcial ? '\n\n' + resumenParcial : ''), 'error');
    } finally {
      finalizarSesionMigracionLarga();
      ocultarLoader();
      actualizarBotonesItem(itemEl, itemEl.getAttribute('data-estado') || 'pendiente');
    }
  }

  async function ejecutarMigracion(itemEl, btn) {
    if (!itemEl || !btn || btn.disabled) {
      return;
    }

    const id = itemEl.getAttribute('data-id');
    const codigo = itemEl.getAttribute('data-codigo') || '';
    const script = itemEl.getAttribute('data-script') || '';
    const nombre = itemEl.querySelector('h6') ? itemEl.querySelector('h6').textContent : 'Migración';

    if (esMigracionPorPasos(script)) {
      return ejecutarMigracionPoblaciones(itemEl, btn, script, nombre, id, codigo);
    }

    btn.disabled = true;
    actualizarItemDom(itemEl, 'en_proceso');
    mostrarLoader(nombre);

    await actualizarEstadoMigracion({
      id_migracion: id,
      estado_migracion: 'en_proceso',
      mensaje_resultado: '',
      registros_procesados: 0,
      registros_total: 0
    });

    iniciarSesionMigracionLarga();

    try {
      const res = await fetch(script, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const body = await res.text();

      if (!res.ok) {
        throw new Error('Error HTTP ' + res.status);
      }

      const parsed = parsearRespuestaMigracion(body, codigo);
      const estadoFinal = parsed.error ? 'error' : 'migrado';

      actualizarItemDom(itemEl, estadoFinal);
      await actualizarEstadoMigracion({
        id_migracion: id,
        estado_migracion: estadoFinal,
        mensaje_resultado: parsed.message,
        registros_procesados: parsed.registros_procesados,
        registros_total: parsed.registros_total
      });

      if (parsed.error) {
        mostrarMensaje('Error', parsed.message, 'error');
      } else {
        mostrarMensaje('Migrado', parsed.message, 'success');
      }
    } catch (err) {
      actualizarItemDom(itemEl, 'error');
      await actualizarEstadoMigracion({
        id_migracion: id,
        estado_migracion: 'error',
        mensaje_resultado: err.message || 'Error desconocido',
        registros_procesados: 0,
        registros_total: 0
      });
      mostrarMensaje('Error', err.message || 'No se pudo completar la acción.', 'error');
    } finally {
      finalizarSesionMigracionLarga();
      ocultarLoader();
      actualizarBotonesItem(itemEl, itemEl.getAttribute('data-estado') || 'pendiente');
    }
  }

  if (listaMigraciones) {
    listaMigraciones.addEventListener('click', async function (event) {
      const btnRepetir = event.target.closest('.btn-repetir-migracion');
      const btnEjecutar = event.target.closest('.btn-ejecutar-migracion');
      const btn = btnRepetir || btnEjecutar;

      if (!btn || btn.disabled) {
        return;
      }

      event.preventDefault();
      const itemEl = btn.closest('.migracion-item');
      if (!itemEl) {
        return;
      }

      const nombre = itemEl.querySelector('h6') ? itemEl.querySelector('h6').textContent : 'Migración';
      const descripcion = itemEl.getAttribute('data-descripcion') || '';
      const confirmado = await confirmarMigracion(nombre, descripcion);

      if (!confirmado) {
        return;
      }

      ejecutarMigracion(itemEl, btn);
    });
  }

  if (btnAbrirModal && modalMigracion) {
    btnAbrirModal.addEventListener('click', function () {
      if (formNuevaMigracion) {
        formNuevaMigracion.reset();
      }
      modalMigracion.show();
    });
  }

  if (btnGuardarMigracion && formNuevaMigracion) {
    btnGuardarMigracion.addEventListener('click', async function () {
      if (!formNuevaMigracion.checkValidity()) {
        formNuevaMigracion.reportValidity();
        return;
      }

      btnGuardarMigracion.disabled = true;

      try {
        const body = new URLSearchParams(new FormData(formNuevaMigracion));
        const res = await fetch(baseUrl + 'crear_migracion.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: body.toString()
        });
        const json = await res.json();

        if (!json.success) {
          mostrarMensaje('Error', json.message || 'No se pudo crear la migración.', 'error');
          return;
        }

        if (modalMigracion) {
          modalMigracion.hide();
        }

        formNuevaMigracion.reset();
        await cargarMigraciones();
        mostrarMensaje('Creado', json.message || 'Migración creada correctamente.', 'success');
      } catch (err) {
        mostrarMensaje('Error', err.message || 'No se pudo crear la migración.', 'error');
      } finally {
        btnGuardarMigracion.disabled = false;
      }
    });
  }

  cargarMigraciones();

  const btnSugerirIa = document.getElementById('btnSugerirNacionalidadesIa');
  const iaBody = document.getElementById('nacionalidadesIaBody');
  const iaStatus = document.getElementById('nacionalidadesIaStatus');

  function renderSugerenciasIa(items) {
    if (!iaBody) {
      return;
    }

    if (!Array.isArray(items) || items.length === 0) {
      iaBody.innerHTML = '<tr><td colspan="4" class="text-muted">No hay sugerencias pendientes.</td></tr>';
      return;
    }

    iaBody.innerHTML = items.map(function (item) {
      const sugerencia = item.nombre_nacionalidad
        ? escHtml(item.nombre_nacionalidad)
        : '<span class="text-muted">Sin asignar (null)</span>';
      const motivo = escHtml(item.motivo_ia || '—');

      return (
        '<tr data-id-mapeo="' + item.id_mapeo + '">' +
          '<td><code>' + escHtml(item.valor_original) + '</code></td>' +
          '<td>' + sugerencia + '</td>' +
          '<td><small>' + motivo + '</small></td>' +
          '<td class="text-end text-nowrap">' +
            '<button type="button" class="btn btn-success btn-sm btn-aprobar-ia me-1">Aprobar</button>' +
            '<button type="button" class="btn btn-outline-secondary btn-sm btn-rechazar-ia">Rechazar</button>' +
          '</td>' +
        '</tr>'
      );
    }).join('');
  }

  async function cargarSugerenciasIa() {
    if (!iaBody || !iaStatus) {
      return;
    }

    iaStatus.textContent = 'Cargando sugerencias…';

    try {
      const res = await fetch(baseUrl + 'nacionalidades_ia_api.php?estado=pendiente', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json = await res.json();

      if (!json.success) {
        iaStatus.textContent = json.message || 'No se pudieron cargar las sugerencias.';
        iaBody.innerHTML = '<tr><td colspan="4" class="text-danger">' + escHtml(json.message || 'Error') + '</td></tr>';
        return;
      }

      renderSugerenciasIa(json.items || []);
      iaStatus.textContent = 'Pendientes de revisión: ' + (json.total || 0);
    } catch (err) {
      iaStatus.textContent = 'Error al cargar sugerencias.';
      iaBody.innerHTML = '<tr><td colspan="4" class="text-danger">Error al cargar.</td></tr>';
    }
  }

  async function cambiarEstadoIa(idMapeo, accion) {
    const body = new URLSearchParams({
      id_mapeo: String(idMapeo),
      accion: accion
    });

    const res = await fetch(baseUrl + 'nacionalidades_ia_api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body.toString()
    });

    return res.json();
  }

  if (btnSugerirIa) {
    btnSugerirIa.addEventListener('click', async function () {
      if (typeof Swal !== 'undefined') {
        const confirmar = await Swal.fire({
          title: '¿Solicitar sugerencias IA?',
          text: 'Claude analizará los valores sin mapear y guardará propuestas pendientes de tu revisión.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, solicitar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#696cff'
        });
        if (!confirmar.isConfirmed) {
          return;
        }
      }

      btnSugerirIa.disabled = true;
      mostrarLoader('Consultando Claude');

      try {
        const res = await fetch(baseUrl + 'sugerir_nacionalidades_ia.php', {
          method: 'GET',
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (!json.success) {
          mostrarMensaje('Aviso', json.message || 'No se generaron sugerencias.', 'warning');
        } else {
          mostrarMensaje('Sugerencias IA', json.message || 'Sugerencias guardadas.', 'success');
        }

        await cargarSugerenciasIa();
      } catch (err) {
        mostrarMensaje('Error', err.message || 'Error al solicitar sugerencias.', 'error');
      } finally {
        ocultarLoader();
        btnSugerirIa.disabled = false;
      }
    });
  }

  if (iaBody) {
    iaBody.addEventListener('click', async function (event) {
      const btnAprobar = event.target.closest('.btn-aprobar-ia');
      const btnRechazar = event.target.closest('.btn-rechazar-ia');
      const btn = btnAprobar || btnRechazar;

      if (!btn || btn.disabled) {
        return;
      }

      const row = btn.closest('tr[data-id-mapeo]');
      if (!row) {
        return;
      }

      const idMapeo = row.getAttribute('data-id-mapeo');
      const accion = btnAprobar ? 'aprobar' : 'rechazar';

      btn.disabled = true;

      try {
        const json = await cambiarEstadoIa(idMapeo, accion);
        if (!json.success) {
          mostrarMensaje('Error', json.message || 'No se pudo actualizar.', 'error');
          btn.disabled = false;
          return;
        }

        await cargarSugerenciasIa();
        mostrarMensaje(
          accion === 'aprobar' ? 'Aprobado' : 'Rechazado',
          json.message || 'Actualizado.',
          'success'
        );
      } catch (err) {
        mostrarMensaje('Error', err.message || 'Error de red.', 'error');
        btn.disabled = false;
      }
    });
  }

  cargarSugerenciasIa();
});
</script>
