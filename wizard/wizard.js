(function () {
  'use strict';

  var cfg = window.FormacionWizard;
  if (!cfg || !cfg.apiBase || !cfg.codigos) {
    return;
  }

  var C = cfg.codigos;

  function pageBase() {
    var path = window.location.pathname || '';
    var seg = path.split('/').pop() || '';
    return seg.replace(/\.php$/i, '');
  }

  function getQueryParam(name) {
    var m = new RegExp('[?&]' + encodeURIComponent(name) + '=([^&]*)').exec(window.location.search);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }

  function fetchEstado(cb) {
    var url = cfg.apiBase + 'estado_pasos.php';
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data || !data.ok || !Array.isArray(data.pasos)) {
          cb(new Set());
          return;
        }
        cb(new Set(data.pasos));
      })
      .catch(function () {
        cb(new Set());
      });
  }

  function idClienteDesdeUrlActual() {
    var id = parseInt(getQueryParam('id'), 10);
    return id > 0 ? id : null;
  }

  function idClienteParaGuardar(codigo, idForzado) {
    if (idForzado !== undefined && idForzado !== null && !isNaN(idForzado) && parseInt(idForzado, 10) > 0) {
      return parseInt(idForzado, 10);
    }
    if (
      codigo.indexOf('formacion_ficha_') === 0 ||
      codigo.indexOf('formacion_editar_') === 0
    ) {
      return idClienteDesdeUrlActual();
    }
    return null;
  }

  function guardarPaso(codigo, cb, idClienteForzado) {
    var idc = idClienteParaGuardar(codigo, idClienteForzado);
    var body = 'codigo_paso=' + encodeURIComponent(codigo);
    if (idc && idc > 0) {
      body += '&id_cliente_context=' + encodeURIComponent(String(idc));
    }
    fetch(cfg.apiBase + 'guardar_paso.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body,
      credentials: 'same-origin',
    })
      .then(function (r) {
        return r.text().then(function (text) {
          var data = null;
          try {
            data = text ? JSON.parse(text) : null;
          } catch (e) {
            data = null;
          }
          return { data: data, raw: text, status: r.status };
        });
      })
      .then(function (res) {
        var data = res.data;
        if (data && data.ok === true) {
          cb(true);
          return;
        }
        var msg =
          (data && data.detalle) ||
          (data && data.error) ||
          (res.raw && res.raw.length < 400 ? res.raw : '') ||
          ('HTTP ' + res.status);
        cb(false, msg);
      })
      .catch(function (err) {
        cb(false, (err && err.message) || 'fetch');
      });
  }

  function expandMenuParents(el) {
    var node = el;
    while (node && node !== document.body) {
      if (node.classList && node.classList.contains('menu-item')) {
        node.classList.add('open');
      }
      node = node.parentElement;
    }
  }

  function findClienteMenuLink() {
    var menu = document.getElementById('layout-menu');
    if (!menu) {
      return null;
    }
    var byHref = menu.querySelector('a.menu-link[href="clientes_sucursal.php"]');
    if (byHref) {
      return byHref;
    }
    return menu.querySelector('a.menu-link[data-id-type-item="179"]');
  }

  function layoutMaskRects(target) {
    var pad = 8;
    var rect = target.getBoundingClientRect();
    var x = Math.max(0, rect.left - pad);
    var y = Math.max(0, rect.top - pad);
    var w = Math.min(window.innerWidth - x, rect.width + pad * 2);
    var h = Math.min(window.innerHeight - y, rect.height + pad * 2);
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    return {
      top: { top: 0, left: 0, width: vw, height: y },
      left: { top: y, left: 0, width: x, height: h },
      right: { top: y, left: x + w, width: Math.max(0, vw - x - w), height: h },
      bottom: { top: y + h, left: 0, width: vw, height: Math.max(0, vh - y - h) },
    };
  }

  function applyMask(masks, rects) {
    ['top', 'left', 'right', 'bottom'].forEach(function (k) {
      var r = rects[k];
      var el = masks[k];
      el.style.top = r.top + 'px';
      el.style.left = r.left + 'px';
      el.style.width = r.width + 'px';
      el.style.height = r.height + 'px';
    });
  }

  function createOverlay() {
    var root = document.createElement('div');
    root.className = 'fw-root';
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');

    var masks = {
      top: document.createElement('div'),
      left: document.createElement('div'),
      right: document.createElement('div'),
      bottom: document.createElement('div'),
    };
    Object.keys(masks).forEach(function (k) {
      masks[k].className = 'fw-mask fw-mask-' + k;
      root.appendChild(masks[k]);
    });

    var tooltip = document.createElement('div');
    tooltip.className = 'fw-tooltip';
    root.appendChild(tooltip);

    document.body.appendChild(root);

    return { root: root, masks: masks, tooltip: tooltip };
  }

  function destroy(ui) {
    if (ui && ui.root && ui.root.parentNode) {
      ui.root.parentNode.removeChild(ui.root);
    }
  }

  function positionLoop(ui, target, onReposition) {
    function tick() {
      if (!document.body.contains(target)) {
        return;
      }
      var rects = layoutMaskRects(target);
      applyMask(ui.masks, rects);
      if (typeof onReposition === 'function') {
        onReposition();
      }
    }
    tick();
    window.addEventListener('resize', tick);
    window.addEventListener('scroll', tick, true);
    return function () {
      window.removeEventListener('resize', tick);
      window.removeEventListener('scroll', tick, true);
    };
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function alertErr(msg) {
    window.alert(msg || 'Error');
  }

  /* ---------- Dashboard ---------- */

  function runDashboard(pasos) {
    if (pasos.has(C.menuClientes)) {
      return;
    }
    var link = findClienteMenuLink();
    if (!link) {
      return;
    }

    expandMenuParents(link);
    link.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

    var ui = createOverlay();
    link.classList.add('fw-highlight');

    var nombre = cfg.saludoNombre || '';
    ui.tooltip.innerHTML =
      '<h2>¡Hola' +
      (nombre ? ', ' + escapeHtml(nombre) : '') +
      '!</h2>' +
      '<p>Te voy a explicar cómo utilizar la aplicación de formación. Empezamos por <strong>listar clientes</strong> de tu tienda.</p>' +
      '<p>En el menú lateral, pulsa en <strong>Clientes</strong> (icono de grupo). El enlace está resaltado: ábrelo para continuar.</p>';

    var unbind = positionLoop(ui, link);

    function onNav(ev) {
      ev.preventDefault();
      ev.stopPropagation();
      var href = link.getAttribute('href');
      guardarPaso(C.menuClientes, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo registrar el paso en el servidor.\n\n' + (errMsg || ''));
          return;
        }
        unbind();
        link.classList.remove('fw-highlight');
        destroy(ui);
        if (href) {
          window.location.href = href;
        }
      });
    }

    link.addEventListener('click', onNav, true);
  }

  /* ---------- clientes_sucursal (lista) ---------- */

  function runClientesListar(pasos) {
    if (!pasos.has(C.menuClientes) || pasos.has(C.buscarCliente)) {
      return;
    }
    var input = document.getElementById('buscar-cliente');
    if (!input) {
      return;
    }

    var ui = createOverlay();
    input.classList.add('fw-highlight');
    input.focus({ preventScroll: true });

    ui.tooltip.innerHTML =
      '<h2>Buscar clientes</h2>' +
      '<p>Aquí puedes localizar un cliente escribiendo su <strong>DNI</strong>, <strong>NIE</strong>, <strong>pasaporte</strong>, o bien parte del <strong>nombre</strong>, <strong>apellidos</strong> o el <strong>ID de cliente</strong>.</p>' +
      '<p>Prueba a escribir en el campo resaltado; la lista se filtrará según vayas escribiendo.</p>' +
      '<div class="fw-actions"><button type="button" class="fw-btn fw-btn-primary" id="fw-btn-buscar-ok">Entendido</button></div>';

    var unbind = positionLoop(ui, input);

    document.getElementById('fw-btn-buscar-ok').addEventListener('click', function () {
      guardarPaso(C.buscarCliente, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        unbind();
        input.classList.remove('fw-highlight');
        destroy(ui);
        pasos.add(C.buscarCliente);
        runClientesEjemploBusqueda(pasos);
      });
    });
  }

  function runClientesEjemploBusqueda(pasos) {
    if (!pasos.has(C.buscarCliente) || pasos.has(C.ejemploBusqueda)) {
      return;
    }
    var input = document.getElementById('buscar-cliente');
    if (!input) {
      return;
    }

    var ui = createOverlay();
    input.classList.add('fw-highlight');
    input.focus({ preventScroll: true });

    ui.tooltip.innerHTML =
      '<h2>Prueba de búsqueda</h2>' +
      '<p>Escribe en el buscador, por ejemplo el nombre <strong>Juan Gonzalez Ruiz</strong> (o adapta el ejemplo a un cliente que exista en tu base de datos de formación).</p>' +
      '<p>Cuando veas al cliente en la lista, te indicaremos el botón del ojo para abrir su ficha.</p>' +
      '<div class="fw-actions"><button type="button" class="fw-btn fw-btn-primary" id="fw-btn-ejemplo-ok">Continuar</button></div>';

    var unbind = positionLoop(ui, input);

    document.getElementById('fw-btn-ejemplo-ok').addEventListener('click', function () {
      guardarPaso(C.ejemploBusqueda, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        unbind();
        input.classList.remove('fw-highlight');
        destroy(ui);
        pasos.add(C.ejemploBusqueda);
        runClientesVerFicha(pasos);
      });
    });
  }

  function findVerClienteLink() {
    var tbody = document.getElementById('clientes-sucursal');
    if (!tbody) {
      return null;
    }
    return tbody.querySelector('a[href^="cliente_sucursal.php?id="][title="Ver cliente"]');
  }

  function runClientesVerFicha(pasos) {
    if (!pasos.has(C.ejemploBusqueda) || pasos.has(C.abrirFicha)) {
      return;
    }

    var tbody = document.getElementById('clientes-sucursal');
    if (!tbody) {
      return;
    }

    var waitUi = null;
    var waitUnbind = null;
    var obs = null;

    function cleanupWait() {
      if (waitUnbind) {
        waitUnbind();
        waitUnbind = null;
      }
      if (waitUi) {
        destroy(waitUi);
        waitUi = null;
      }
    }

    function attachEyeLink(a) {
      cleanupWait();
      if (obs) {
        obs.disconnect();
        obs = null;
      }

      a.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      var ui = createOverlay();
      a.classList.add('fw-highlight');
      ui.tooltip.innerHTML =
        '<h2>Abrir la ficha</h2>' +
        '<p>Cuando hayas encontrado al cliente, pulsa el icono del <strong>ojo</strong> para ver su ficha completa.</p>';

      var unbind = positionLoop(ui, a);

      function onEyeClick(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var href = a.getAttribute('href');
        var idDesdeHref = null;
        var m = /[?&]id=(\d+)/.exec(href || '');
        if (m) {
          idDesdeHref = parseInt(m[1], 10);
        }
        guardarPaso(
          C.abrirFicha,
          function (ok, errMsg) {
            if (!ok) {
              alertErr('No se pudo registrar el paso.\n\n' + (errMsg || ''));
              return;
            }
            unbind();
            a.classList.remove('fw-highlight');
            destroy(ui);
            if (href) {
              window.location.href = href;
            }
          },
          idDesdeHref
        );
      }

      a.addEventListener('click', onEyeClick, true);
    }

    function scan() {
      var a = findVerClienteLink();
      if (a) {
        attachEyeLink(a);
      }
    }

    waitUi = createOverlay();
    var anchor = document.getElementById('buscar-cliente') || tbody;
    waitUi.tooltip.innerHTML =
      '<h2>Busca un cliente</h2>' +
      '<p>Escribe en el buscador (por ejemplo <strong>Juan Gonzalez Ruiz</strong>) hasta que aparezca una fila con resultados.</p>' +
      '<p>Cuando exista un resultado, resaltaremos el botón del ojo para abrir la ficha.</p>';
    waitUnbind = positionLoop(waitUi, anchor);

    obs = new MutationObserver(scan);
    obs.observe(tbody, { childList: true, subtree: true });
    scan();
  }

  /* ---------- cliente_sucursal (ficha) ---------- */

  function clickTabById(id) {
    var btn = document.getElementById(id);
    if (btn) {
      btn.click();
    }
  }

  function waitForSelector(selector, timeoutMs, stepMs, cb) {
    var t0 = Date.now();
    function tick() {
      var el = document.querySelector(selector);
      if (el) {
        cb(el);
        return;
      }
      if (Date.now() - t0 > timeoutMs) {
        cb(null);
        return;
      }
      setTimeout(tick, stepMs);
    }
    tick();
  }

  function tbodyHasDataRows(tableId) {
    var tb = document.querySelector('#' + tableId + ' tbody');
    if (!tb) {
      return false;
    }
    if (tb.querySelector('td.dataTables_empty')) {
      return false;
    }
    var trs = tb.querySelectorAll('tr');
    return trs.length > 0;
  }

  function onceShownBsTab(tabButtonId, fn) {
    function handler(e) {
      var el = e.target && e.target.closest ? e.target.closest('#' + tabButtonId) : null;
      if (el && el.id === tabButtonId) {
        document.body.removeEventListener('shown.bs.tab', handler);
        fn();
      }
    }
    document.body.addEventListener('shown.bs.tab', handler);
    return function cancel() {
      document.body.removeEventListener('shown.bs.tab', handler);
    };
  }

  function runClienteFichaFlow(pasos) {
    var idCliente = getQueryParam('id');
    if (!idCliente || parseInt(idCliente, 10) < 1) {
      return;
    }
    if (!pasos.has(C.abrirFicha)) {
      return;
    }

    if (!pasos.has(C.fichaPerfil)) {
      fichaFasePerfil(pasos);
      return;
    }
    if (!pasos.has(C.fichaLotesTab)) {
      fichaFaseInvitarLotes(pasos);
      return;
    }
    if (!pasos.has(C.fichaLotesBuscador)) {
      fichaFaseLotesBuscador(pasos);
      return;
    }
    if (!pasos.has(C.fichaEmpenosTab)) {
      fichaFaseInvitarEmpenos(pasos);
      return;
    }
    if (!pasos.has(C.fichaEmpenosBuscador)) {
      fichaFaseEmpenosContenido(pasos);
      return;
    }
    if (!pasos.has(C.fichaVentasTab)) {
      fichaFaseInvitarVentas(pasos);
      return;
    }
    if (!pasos.has(C.fichaVentasCompletado)) {
      fichaFaseVentasCierre(pasos);
      return;
    }
    if (!pasos.has(C.fichaEditarClienteLink)) {
      fichaFaseInvitarEditar(pasos);
    }
  }

  function findEditarClienteLink() {
    return (
      document.querySelector('a.btn.btn-primary[href^="editar_clientes_sucursal.php?id="]') ||
      document.querySelector('a.btn-primary[href^="editar_clientes_sucursal.php?id="]') ||
      document.querySelector('a[href^="editar_clientes_sucursal.php?id="]')
    );
  }

  function fichaFaseInvitarEditar(pasos) {
    var a = findEditarClienteLink();
    if (!a) {
      return;
    }

    a.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    var ui = createOverlay();
    a.classList.add('fw-highlight');
    ui.tooltip.innerHTML =
      '<h2>Editar cliente</h2>' +
      '<p>Para modificar los datos del cliente, pulsa el botón <strong>Editar Cliente</strong> (arriba a la derecha en la cabecera).</p>' +
      '<p>Te llevaremos al formulario de edición y te explicaremos los campos obligatorios y cómo guardar.</p>';

    var unbind = positionLoop(ui, a);

    function onClick(ev) {
      ev.preventDefault();
      ev.stopPropagation();
      var href = a.getAttribute('href');
      guardarPaso(C.fichaEditarClienteLink, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo registrar el paso.\n\n' + (errMsg || ''));
          return;
        }
        unbind();
        a.classList.remove('fw-highlight');
        destroy(ui);
        pasos.add(C.fichaEditarClienteLink);
        if (href) {
          window.location.href = href;
        }
      });
    }

    a.addEventListener('click', onClick, true);
  }

  function runEditarClienteFlow(pasos) {
    if (!pasos.has(C.fichaVentasCompletado)) {
      return;
    }
    var idEd = getQueryParam('id');
    if (!idEd || parseInt(idEd, 10) < 1) {
      return;
    }
    if (!pasos.has(C.editarCamposObligatorios)) {
      editarFaseCamposObligatorios(pasos);
      return;
    }
    if (!pasos.has(C.editarGuardarInfo)) {
      editarFaseBotonGuardar(pasos);
    }
  }

  function editarFaseCamposObligatorios(pasos) {
    var form = document.getElementById('formEditarCliente');
    if (!form) {
      return;
    }

    form.scrollIntoView({ block: 'start', behavior: 'smooth' });
    var ui = createOverlay();
    form.classList.add('fw-highlight');
    ui.tooltip.innerHTML =
      '<h2>Campos obligatorios</h2>' +
      '<p>El servidor exige completar correctamente (marcados con * en el formulario):</p>' +
      '<ul style="margin:0 0 12px 18px;padding:0;line-height:1.45">' +
      '<li><strong>Tipo de identificación</strong> y <strong>número</strong></li>' +
      '<li><strong>Nacionalidad</strong></li>' +
      '<li><strong>Fecha de vencimiento</strong> del documento</li>' +
      '<li><strong>Nombre</strong> y <strong>apellido</strong></li>' +
      '<li><strong>Fecha de nacimiento</strong> y <strong>sexo</strong></li>' +
      '<li><strong>Teléfono</strong></li>' +
      '</ul>' +
      '<p>Revisa también el bloque de <strong>dirección</strong> y los datos opcionales (email, observaciones) si aplica.</p>' +
      '<div class="fw-actions"><button type="button" class="fw-btn fw-btn-primary" id="fw-editar-campos-ok">Entendido</button></div>';

    var unbind = positionLoop(ui, form);

    document.getElementById('fw-editar-campos-ok').addEventListener('click', function () {
      guardarPaso(C.editarCamposObligatorios, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        unbind();
        form.classList.remove('fw-highlight');
        destroy(ui);
        pasos.add(C.editarCamposObligatorios);
        runEditarClienteFlow(pasos);
      });
    });
  }

  function editarFaseBotonGuardar(pasos) {
    var btn = document.getElementById('btnEditarCliente');
    if (!btn) {
      return;
    }

    btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    var ui = createOverlay();
    btn.classList.add('fw-highlight');
    ui.tooltip.innerHTML =
      '<h2>Guardar cambios</h2>' +
      '<p>Cuando hayas revisado los datos, pulsa <strong>Actualizar Cliente</strong> para enviar el formulario al servidor.</p>' +
      '<p>Si falta algún obligatorio o hay un error de validación, el sistema te lo indicará.</p>' +
      '<div class="fw-actions"><button type="button" class="fw-btn fw-btn-primary" id="fw-editar-guardar-ok">Entendido</button></div>';

    var unbind = positionLoop(ui, btn);

    document.getElementById('fw-editar-guardar-ok').addEventListener('click', function () {
      guardarPaso(C.editarGuardarInfo, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        unbind();
        btn.classList.remove('fw-highlight');
        destroy(ui);
        pasos.add(C.editarGuardarInfo);
      });
    });
  }

  function fichaFasePerfil(pasos) {
    var tab = document.getElementById('tab-perfil');
    if (!tab) {
      return;
    }

    var ui = createOverlay();
    tab.classList.add('fw-highlight');
    ui.tooltip.innerHTML =
      '<h2>Pestaña Perfil</h2>' +
      '<p>En <strong>Perfil</strong> tienes la información básica del cliente (datos personales, identificación, contacto, etc.).</p>' +
      '<p>En unos segundos te indicaremos la siguiente pestaña.</p>';
    var unbind = positionLoop(ui, tab);

    setTimeout(function () {
      guardarPaso(C.fichaPerfil, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        pasos.add(C.fichaPerfil);
        unbind();
        tab.classList.remove('fw-highlight');
        destroy(ui);
        runClienteFichaFlow(pasos);
      });
    }, 5000);
  }

  function tabPaneVisible(tabBtnId, paneId) {
    var t = document.getElementById(tabBtnId);
    var p = document.getElementById(paneId);
    if (!t || !p) {
      return false;
    }
    return t.classList.contains('active') && p.classList.contains('active') && p.classList.contains('show');
  }

  function fichaFaseInvitarLotes(pasos) {
    if (tabPaneVisible('tab-lotes', 'pane-lotes')) {
      guardarPaso(C.fichaLotesTab, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        pasos.add(C.fichaLotesTab);
        runClienteFichaFlow(pasos);
      });
      return;
    }

    var tab = document.getElementById('tab-lotes');
    if (!tab) {
      return;
    }

    var ui = createOverlay();
    tab.classList.add('fw-highlight');
    ui.tooltip.innerHTML =
      '<h2>Pestaña Lotes</h2>' +
      '<p>Ahora pulsa en la pestaña <strong>Lotes</strong>. Allí verás los lotes que esta tienda ha comprado a este cliente.</p>';
    var unbind = positionLoop(ui, tab);
    var cancelTab = onceShownBsTab('tab-lotes', function () {
      guardarPaso(C.fichaLotesTab, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        cancelTab();
        unbind();
        tab.classList.remove('fw-highlight');
        destroy(ui);
        pasos.add(C.fichaLotesTab);
        runClienteFichaFlow(pasos);
      });
    });
  }

  function fichaFaseLotesBuscador(pasos) {
    waitForSelector('#pane-lotes .dt-search input[type="search"]', 12000, 200, function (inp) {
      if (!inp) {
        alertErr('No se encontró el buscador de la tabla de lotes. Recarga la página e inténtalo de nuevo.');
        return;
      }

      var ui = createOverlay();
      inp.classList.add('fw-highlight');
      ui.tooltip.innerHTML =
        '<h2>Buscar entre los lotes</h2>' +
        '<p>En este campo puedes filtrar los lotes comprados por <strong>número de lote</strong>, <strong>peso</strong>, <strong>importe en euros</strong>, etc.</p>' +
        '<div class="fw-actions"><button type="button" class="fw-btn fw-btn-primary" id="fw-lotes-bus-ok">Entendido</button></div>';
      var unbind = positionLoop(ui, inp);

      document.getElementById('fw-lotes-bus-ok').addEventListener('click', function () {
        guardarPaso(C.fichaLotesBuscador, function (ok, errMsg) {
          if (!ok) {
            alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
            return;
          }
          unbind();
          inp.classList.remove('fw-highlight');
          destroy(ui);
          pasos.add(C.fichaLotesBuscador);
          runClienteFichaFlow(pasos);
        });
      });
    });
  }

  function fichaFaseInvitarEmpenos(pasos) {
    if (tabPaneVisible('tab-empenos', 'pane-empenos')) {
      guardarPaso(C.fichaEmpenosTab, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        pasos.add(C.fichaEmpenosTab);
        runClienteFichaFlow(pasos);
      });
      return;
    }

    var tab = document.getElementById('tab-empenos');
    if (!tab) {
      return;
    }

    var ui = createOverlay();
    tab.classList.add('fw-highlight');
    ui.tooltip.innerHTML =
      '<h2>Pestaña Empeños</h2>' +
      '<p>Pulsa en <strong>Empeños</strong> para ver los empeños asociados a este cliente.</p>';
    var unbind = positionLoop(ui, tab);
    var cancelTab = onceShownBsTab('tab-empenos', function () {
      guardarPaso(C.fichaEmpenosTab, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        cancelTab();
        unbind();
        tab.classList.remove('fw-highlight');
        destroy(ui);
        pasos.add(C.fichaEmpenosTab);
        runClienteFichaFlow(pasos);
      });
    });
  }

  function fichaFaseEmpenosContenido(pasos) {
    function finishEmpenosBuscador() {
      guardarPaso(C.fichaEmpenosBuscador, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        pasos.add(C.fichaEmpenosBuscador);
        runClienteFichaFlow(pasos);
      });
    }

    waitForSelector('#pane-empenos', 8000, 100, function () {
      setTimeout(function () {
        var hasData = tbodyHasDataRows('tabla_empenos_cliente');
        if (hasData) {
          waitForSelector('#pane-empenos .dt-search input[type="search"]', 12000, 200, function (inp) {
            if (!inp) {
              finishEmpenosBuscador();
              return;
            }
            var ui = createOverlay();
            inp.classList.add('fw-highlight');
            ui.tooltip.innerHTML =
              '<h2>Buscar empeños</h2>' +
              '<p>Como en lotes, aquí puedes usar el buscador para localizar empeños por identificador, importe, fechas, etc.</p>' +
              '<div class="fw-actions"><button type="button" class="fw-btn fw-btn-primary" id="fw-emp-bus-ok">Entendido</button></div>';
            var unbind = positionLoop(ui, inp);
            document.getElementById('fw-emp-bus-ok').addEventListener('click', function () {
              unbind();
              inp.classList.remove('fw-highlight');
              destroy(ui);
              finishEmpenosBuscador();
            });
          });
        } else {
          var ui = createOverlay();
          var pane = document.getElementById('pane-empenos');
          var target = pane || document.getElementById('tab-empenos');
          target.classList.add('fw-highlight');
          ui.tooltip.innerHTML =
            '<h2>Empeños</h2>' +
            '<p>Esta pestaña muestra los <strong>empeños del cliente</strong>. En este ejemplo no hay filas; cuando existan datos, podrás filtrarlos con el buscador de la tabla igual que en Lotes.</p>' +
            '<div class="fw-actions"><button type="button" class="fw-btn fw-btn-primary" id="fw-emp-sin-ok">Entendido</button></div>';
          var unbind = positionLoop(ui, target);
          document.getElementById('fw-emp-sin-ok').addEventListener('click', function () {
            unbind();
            target.classList.remove('fw-highlight');
            destroy(ui);
            finishEmpenosBuscador();
          });
        }
      }, 600);
    });
  }

  function fichaFaseInvitarVentas(pasos) {
    if (tabPaneVisible('tab-ventas', 'pane-ventas')) {
      guardarPaso(C.fichaVentasTab, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        pasos.add(C.fichaVentasTab);
        runClienteFichaFlow(pasos);
      });
      return;
    }

    var tab = document.getElementById('tab-ventas');
    if (!tab) {
      return;
    }

    var ui = createOverlay();
    tab.classList.add('fw-highlight');
    ui.tooltip.innerHTML =
      '<h2>Pestaña Ventas</h2>' +
      '<p>Pulsa en <strong>Ventas</strong> para ver el listado de ventas realizadas a este cliente.</p>';
    var unbind = positionLoop(ui, tab);
    var cancelTab = onceShownBsTab('tab-ventas', function () {
      guardarPaso(C.fichaVentasTab, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        cancelTab();
        unbind();
        tab.classList.remove('fw-highlight');
        destroy(ui);
        pasos.add(C.fichaVentasTab);
        runClienteFichaFlow(pasos);
      });
    });
  }

  function fichaFaseVentasCierre(pasos) {
    var pane = document.getElementById('pane-ventas');
    var tab = document.getElementById('tab-ventas');
    var target = pane && pane.classList.contains('active') ? pane : tab;
    if (!target) {
      return;
    }

    var ui = createOverlay();
    target.classList.add('fw-highlight');
    ui.tooltip.innerHTML =
      '<h2>Ventas del cliente</h2>' +
      '<p>Aquí tienes el <strong>listado de ventas</strong> que se le han hecho a este cliente en la tienda (totales, fechas, estado, plazos, etc.).</p>' +
      '<p>A continuación te guiaremos para <strong>Editar cliente</strong>: revisar los campos obligatorios y guardar los cambios.</p>' +
      '<div class="fw-actions"><button type="button" class="fw-btn fw-btn-primary" id="fw-ventas-fin-ok">Entendido</button></div>';
    var unbind = positionLoop(ui, target);

    document.getElementById('fw-ventas-fin-ok').addEventListener('click', function () {
      guardarPaso(C.fichaVentasCompletado, function (ok, errMsg) {
        if (!ok) {
          alertErr('No se pudo guardar el progreso.\n\n' + (errMsg || ''));
          return;
        }
        unbind();
        target.classList.remove('fw-highlight');
        destroy(ui);
        pasos.add(C.fichaVentasCompletado);
        runClienteFichaFlow(pasos);
      });
    });
  }

  function runClientesFlow(pasos) {
    if (!pasos.has(C.menuClientes)) {
      return;
    }
    if (!pasos.has(C.buscarCliente)) {
      runClientesListar(pasos);
      return;
    }
    if (!pasos.has(C.ejemploBusqueda) && !pasos.has(C.abrirFicha)) {
      runClientesEjemploBusqueda(pasos);
      return;
    }
    if (!pasos.has(C.abrirFicha)) {
      runClientesVerFicha(pasos);
    }
  }

  function start() {
    var base = pageBase();
    fetchEstado(function (pasos) {
      if (base === 'dashboard_sucursal') {
        runDashboard(pasos);
      } else if (base === 'clientes_sucursal') {
        runClientesFlow(pasos);
      } else if (base === 'cliente_sucursal') {
        runClienteFichaFlow(pasos);
      } else if (base === 'editar_clientes_sucursal') {
        runEditarClienteFlow(pasos);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
