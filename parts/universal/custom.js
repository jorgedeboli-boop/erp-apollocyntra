/**
 * Script personalizado para manejo de menús
 * - Activación automática de menús según la página actual
 * - Manejo de menús desplegables
 * - Prevención de conflictos con el template
 */

window.TpvFecha = window.TpvFecha || {};

/**
 * Parsea DD/MM/YYYY o YYYY-MM-DD como fecha local (día, mes, año).
 */
window.TpvFecha.parseLocal = function (valor) {
    if (!valor || typeof valor !== 'string') {
        return null;
    }
    const v = valor.trim();

    const dmY = v.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (dmY) {
        const d = parseInt(dmY[1], 10);
        const m = parseInt(dmY[2], 10) - 1;
        const y = parseInt(dmY[3], 10);
        if (isNaN(y) || isNaN(m) || isNaN(d)) {
            return null;
        }
        const fecha = new Date(y, m, d);
        if (fecha.getFullYear() !== y || fecha.getMonth() !== m || fecha.getDate() !== d) {
            return null;
        }
        return fecha;
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) {
        const partes = v.split('-');
        const y = parseInt(partes[0], 10);
        const m = parseInt(partes[1], 10) - 1;
        const d = parseInt(partes[2], 10);
        if (isNaN(y) || isNaN(m) || isNaN(d)) {
            return null;
        }
        const fecha = new Date(y, m, d);
        if (fecha.getFullYear() !== y || fecha.getMonth() !== m || fecha.getDate() !== d) {
            return null;
        }
        return fecha;
    }

    return null;
};

window.TpvFecha.hoyLocal = function () {
    const ahora = new Date();
    return new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate());
};

window.TpvFecha.toIso = function (valor) {
    const fecha = window.TpvFecha.parseLocal(valor);
    if (!fecha) {
        return '';
    }
    const y = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, '0');
    const d = String(fecha.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
};

window.TpvFecha.setValor = function (input, valorYmd) {
    if (!input) {
        return;
    }
    if (!valorYmd) {
        input.value = '';
        return;
    }
    const fecha = window.TpvFecha.parseLocal(valorYmd);
    if (fecha) {
        const d = String(fecha.getDate()).padStart(2, '0');
        const m = String(fecha.getMonth() + 1).padStart(2, '0');
        const y = fecha.getFullYear();
        input.value = d + '/' + m + '/' + y;
    } else {
        input.value = valorYmd;
    }
};

function initDateMaskInput(dateMask) {
    if (!dateMask || dateMask.dataset.dateMaskInit === '1') {
        return;
    }
    if (typeof formatDate !== 'function') {
        return;
    }

    dateMask.dataset.dateMaskInit = '1';

    dateMask.addEventListener('input', function (event) {
        dateMask.value = formatDate(event.target.value, {
          date: true,
            delimiter: '/',
            datePattern: ['d', 'm', 'Y']
        });
    });

    if (typeof registerCursorTracker === 'function') {
        registerCursorTracker({
            input: dateMask,
            delimiter: '/'
        });
    }

    dateMask.addEventListener('blur', function () {
        if (window.TpvFecha.parseLocal(dateMask.value)) {
            dateMask.dispatchEvent(new CustomEvent('tpv-fecha-completa', { bubbles: true }));
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
  const currentPath = window.location.pathname;
  const currentPage = currentPath.split('/').pop() || 'dashboard';

  const ctx = typeof window.__menuPageContext === 'object' && window.__menuPageContext !== null
    ? window.__menuPageContext
    : null;

  document.querySelectorAll('.menu-item').forEach(item => {
    item.classList.remove('active', 'open');
  });

  let targetMenuItem = null;

  // Activación según itemsSections: id resuelto en PHP (listar padre si editar/crear/main)
  if (ctx && ctx.idTypeItem) {
    targetMenuItem = document.querySelector('.menu-item a[data-id-type-item="' + ctx.idTypeItem + '"]');
  }

  // Respaldo por url_item de la fila actual
  if (!targetMenuItem && ctx && ctx.urlItem) {
    const base = String(ctx.urlItem).replace(/\.php$/i, '');
    const hrefExact = base + '.php';
    targetMenuItem = document.querySelector('.menu-item a[href="' + hrefExact + '"]')
      || document.querySelector('.menu-item a[href*="' + base + '"]');
  }

  // Respaldo por nombre de script en la URL
  if (!targetMenuItem && currentPage) {
    targetMenuItem = document.querySelector('.menu-item a[href="' + currentPage + '"]')
      || document.querySelector('.menu-item a[href*="' + currentPage.replace(/\.php$/i, '') + '"]');
  }
  
  // Si se encuentra el elemento, activar su li padre
  if (targetMenuItem) {
    const menuItem = targetMenuItem.closest('.menu-item');
    const menuItemFhater = targetMenuItem.closest('.menu-fhater');
    if (menuItemFhater) {
      menuItemFhater.classList.add('active', 'open');
      
    }
    menuItem.classList.add('active');
  } else {
    const dashboardItem = document.querySelector('.menu-item a[href="dashboard.php"]')
      || document.querySelector('.menu-item a[href*="dashboard"]');
    if (dashboardItem) {
      const li = dashboardItem.closest('.menu-item');
      if (li) {
        li.classList.add('active', 'open');
      }
    }
  }
  
  // Función mejorada para manejar menús desplegables
  function setupMenuToggles() {
    document.querySelectorAll('.menu-toggle').forEach(toggle => {
      // Remover eventos previos para evitar duplicados
      toggle.removeEventListener('click', handleMenuToggle);
      toggle.addEventListener('click', handleMenuToggle);
    });
  }
  
  // Manejador del toggle del menú
  function handleMenuToggle(e) {
    e.preventDefault();
    e.stopPropagation(); // Evitar propagación del evento
    
    const menuItem = this.closest('.menu-item');
    const isOpen = menuItem.classList.contains('open');
    
    // Cerrar todos los otros menús
    document.querySelectorAll('.menu-item').forEach(item => {
      if (item !== menuItem) {
        item.classList.remove('open');
      }
    });
    
    // Toggle del menú actual
    if (isOpen) {
      menuItem.classList.remove('open');
    } else {
      menuItem.classList.add('open');
    }
    
    // Prevenir que se cierre automáticamente
    setTimeout(() => {
      if (menuItem.classList.contains('open')) {
        menuItem.classList.add('open');
      }
    }, 10);
  }
  
  // Configurar los toggles inicialmente
  setupMenuToggles();
  
  // Configurar después de un pequeño delay para asegurar que el template esté listo
  setTimeout(setupMenuToggles, 100);
  setTimeout(setupMenuToggles, 500);
  
  // Interceptar clicks en el documento para evitar cierre no deseado
  document.addEventListener('click', function(e) {
    // Si el click no es en un menú o toggle, no hacer nada
    if (!e.target.closest('.menu-item')) {
      return;
    }
    
    // Si es un toggle, manejarlo
    if (e.target.closest('.menu-toggle')) {
      return;
    }
    
    // Si es un enlace del submenú, mantener abierto
    if (e.target.closest('.menu-sub')) {
      const menuItem = e.target.closest('.menu-item');
      if (menuItem) {
        menuItem.classList.add('open');
      }
    }
  });
/*
  $('.select2-custom').select2({
    containerCssClass: 'select2-custom'
  });
*/

  document.querySelectorAll('.date-mask').forEach(initDateMaskInput);


});

(function initModalIAChatTheme() {
  var modalIAChat = document.getElementById('modalIAChat');
  if (!modalIAChat) return;

  var metaThemeColor = document.querySelector('meta[name="theme-color"]');
  var layoutNavbar = document.querySelector('.layout-navbar');

  modalIAChat.addEventListener('show.bs.modal', function () {
    document.body.classList.add('ia-chat-modal-open');
    if (metaThemeColor) metaThemeColor.setAttribute('content', '#282a42');
    document.body.style.backgroundColor = '#282a42';
    if (layoutNavbar) layoutNavbar.style.backgroundColor = '#282a42';
  });

  modalIAChat.addEventListener('hide.bs.modal', function () {
    document.body.classList.remove('ia-chat-modal-open');
    if (metaThemeColor) metaThemeColor.setAttribute('content', '#f7f7f9');
    document.body.style.backgroundColor = '#f7f7f9';
    if (layoutNavbar) layoutNavbar.style.backgroundColor = '';
  });
})();

(function disableWavesOnBtnAsistenteIA() {
  var btn = document.getElementById('btnAsistenteIA');
  if (!btn || typeof Waves === 'undefined') return;
  if (typeof Waves.detach === 'function') Waves.detach(btn);
  if (typeof Waves.calm === 'function') Waves.calm(btn);
})();

/**
 * Ejecuta el cierre de sesión (limpiar storage y redirigir).
 */
function ejecutarCerrarSesion() {
    if (typeof window.iaChatPersistirAntesDeLogout === 'function') {
        try {
            window.iaChatPersistirAntesDeLogout();
        } catch (e) {}
    }
    // Conservar conversaciones del chat IA al limpiar localStorage
    var iaKeys = [];
    try {
        for (var i = 0; i < localStorage.length; i++) {
            var k = localStorage.key(i);
            if (k && k.indexOf('ia_chat_ui_v1_') === 0) {
                iaKeys.push({ key: k, val: localStorage.getItem(k) });
            }
        }
    } catch (e2) {}
    localStorage.clear();
    try {
        for (var j = 0; j < iaKeys.length; j++) {
            localStorage.setItem(iaKeys[j].key, iaKeys[j].val);
        }
    } catch (e3) {}
    window.location.href = '../include/logout.php';
}

/**
 * Función para cerrar sesión
 * Limpia el localStorage y redirige a logout.php
 */
function cerrarSesion() {
    if (window.REQUIERE_ARQUEO_CAJA === true) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                text: 'Realize el arqueo para poder cerrar la caja',
                showConfirmButton: false,
                timer: 3000,
                allowOutsideClick: true,
                allowEscapeKey: true
            });
        }
        return;
    }
    confirmarCerrarSesion();
    /*ejecutarCerrarSesion();*/
}

/**
 * Pide confirmación antes de cerrar sesión (navbar blank page / kiosk).
 */
function confirmarCerrarSesion() {
    if (window.REQUIERE_ARQUEO_CAJA === true) {
        cerrarSesion();
        return false;
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: '¿Desea cerrar la sesión?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                ejecutarCerrarSesion();
            }
        });
    } else if (confirm('¿Desea cerrar la sesión?')) {
        ejecutarCerrarSesion();
    }

    return false;
}

function mostrarLoaderUniversal(texto_loader_parsed) {
  const loaderContainer = document.getElementById('loaderContainer');
  const textLoader = document.getElementById('textLoader');
  
  if (loaderContainer) {
      // Establecer opacity inicial a 0 y display a flex
      loaderContainer.style.opacity = '0';
      loaderContainer.style.display = 'flex';
      loaderContainer.style.transition = 'opacity 0.4s ease-in-out';
      
      // Forzar reflow para que la transición funcione
      void loaderContainer.offsetWidth;
      
      // Hacer fadeIn
      setTimeout(function() {
          loaderContainer.style.opacity = '1';
      }, 10);
  }
  
  if (textLoader) {
      textLoader.textContent = '' + texto_loader_parsed + '...';
  }
}


function ocultarLoaderUniversal() {
  const loaderContainer = document.getElementById('loaderContainer');
  const textLoader = document.getElementById('textLoader');
  
  if (loaderContainer) {
      // Asegurar que la transición esté configurada
      loaderContainer.style.transition = 'opacity 0.4s ease-in-out';
      
      // Hacer fadeOut
      loaderContainer.style.opacity = '0';
      
      // Ocultar después de que termine la animación
      setTimeout(function() {
          loaderContainer.style.display = 'none';
          loaderContainer.style.opacity = '1'; // Resetear para la próxima vez
      }, 400);
  }
  
  if (textLoader) {
      textLoader.textContent = '';
  }
}

// (Eliminado) Hook enableListImagenesLoaderFetchHook/disableListImagenesLoaderFetchHook:
// ya no se utiliza en el proyecto.

/**
 * Oculta botones de acción en una tabla reemplazándolos por "------" (DataTables u otras que redibujan filas).
 *
 * @param {string} selectorTabla  Selector CSS de la tabla (ej. '.datatables-incidencias-lotes')
 * @param {object} [opciones]
 * @param {string} [opciones.selectorBotones='.button-actions-datatable'] Selector de botones a reemplazar
 * @param {string} [opciones.contenedor] Contenedor a observar (por defecto: .card-datatable padre o la tabla)
 * @param {string} [opciones.textoReemplazo='------'] Texto que sustituye al botón
 * @param {string[]} [opciones.selectoresExtra] Selectores adicionales fuera de la tabla (ej. modal)
 */
function deshabilitarBotonesTablaAcciones(selectorTabla, opciones) {
  opciones = opciones || {};
  var selectorBotones = opciones.selectorBotones || '.button-actions-datatable';
  var selectoresExtra = Array.isArray(opciones.selectoresExtra) ? opciones.selectoresExtra : [];
  var textoReemplazo = opciones.textoReemplazo != null ? String(opciones.textoReemplazo) : '------';
  var marcarAttr = 'data-tpv-accion-reemplazada';
  var claveObservador = '__tpvObservadorBotonesTabla';

  function reemplazarElemento(btn) {
    if (!btn || btn.getAttribute(marcarAttr) === '1') {
      return;
    }
    var placeholder = document.createElement('span');
    placeholder.className = 'text-muted';
    placeholder.textContent = textoReemplazo;
    placeholder.setAttribute(marcarAttr, '1');
    btn.replaceWith(placeholder);
  }

  function aplicar(contenedor) {
    if (!contenedor) {
      return;
    }
    contenedor.querySelectorAll(selectorBotones).forEach(reemplazarElemento);
    selectoresExtra.forEach(function (sel) {
      document.querySelectorAll(sel).forEach(reemplazarElemento);
    });
  }

  function iniciarObservador(contenedor) {
    if (!contenedor || contenedor[claveObservador]) {
      return;
    }
    aplicar(contenedor);
    var obs = new MutationObserver(function () {
      aplicar(contenedor);
    });
    obs.observe(contenedor, { childList: true, subtree: true });
    contenedor[claveObservador] = obs;
  }

  function buscarContenedor() {
    var tabla = document.querySelector(selectorTabla);
    if (!tabla) {
      return opciones.contenedor ? document.querySelector(opciones.contenedor) : null;
    }
    return tabla.closest('.card-datatable') || tabla.parentElement || tabla;
  }

  function intentarIniciar(reintentos) {
    var contenedor = buscarContenedor();
    if (!contenedor) {
      if (reintentos > 0) {
        setTimeout(function () {
          intentarIniciar(reintentos - 1);
        }, 100);
      }
      return;
    }
    iniciarObservador(contenedor);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      intentarIniciar(30);
    });
  } else {
    intentarIniciar(30);
  }
}

/**
 * Actualiza el badge de precio oro del navbar y alterna precio / vigencia.
 */
(function initPrecioOroNavbarPoll() {
  var INTERVALO_POLL_MS = 2 * 1000;
  var TIEMPO_VIGENCIA_MS = 4 * 1000;
  var TIEMPO_PRECIO_MS = Math.round(TIEMPO_VIGENCIA_MS * 1.75);
  var SLIDE_TRANSITION_MS = 450;
  var URL_PRECIO_ORO = 'parts/universal/obtener_precio_oro_update.php';
  var ultimoIdPrecioOroNavbar = null;
  var mostrandoPrecio = true;
  var animando = false;
  var alternarTimer = null;

  function actualizarDatosPrecioOro(data) {
    if (!data || !data.success) {
      return;
    }

    var elPrecio = document.getElementById('precio_oro_update');
    if (elPrecio && data.precio_oro_fmt != null) {
      elPrecio.textContent = data.precio_oro_fmt + ' ';
    }

    var elVigencia = document.getElementById('last_update');
    if (elVigencia && data.last_update != null) {
      elVigencia.textContent = data.last_update;
    }

    var idPrecioOro = parseInt(data.id_precio_oro, 10) || 0;
    if (idPrecioOro > 0 && idPrecioOro !== ultimoIdPrecioOroNavbar) {
      ultimoIdPrecioOroNavbar = idPrecioOro;
      document.dispatchEvent(
        new CustomEvent('precioOroActualizado', {
          detail: data
        })
      );
    }
  }

  function actualizarPrecioOroNavbar() {
    if (!document.getElementById('precio_oro_viewport')) {
      return;
    }

    fetch(URL_PRECIO_ORO, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (res) {
        if (!res.ok) {
          throw new Error('HTTP ' + res.status);
        }
        return res.json();
      })
      .then(function (data) {
        actualizarDatosPrecioOro(data);
      })
      .catch(function () {});
  }

  function alternarPanelPrecioOro() {
    var panelPrecio = document.getElementById('precio_oro_panel_precio');
    var panelVigencia = document.getElementById('precio_oro_panel_vigencia');
    if (!panelPrecio || !panelVigencia || animando) {
      return;
    }

    var actual = mostrandoPrecio ? panelPrecio : panelVigencia;
    var siguiente = mostrandoPrecio ? panelVigencia : panelPrecio;

    animando = true;
    actual.classList.remove('is-visible');
    actual.classList.add('is-exit-right');

    siguiente.classList.remove('is-exit-right', 'is-visible');
    siguiente.classList.add('is-enter-from-left');

    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () {
        siguiente.classList.remove('is-enter-from-left');
        siguiente.classList.add('is-visible');
      });
    });

    window.setTimeout(function () {
      actual.classList.remove('is-exit-right', 'is-enter-from-left');
      animando = false;
    }, SLIDE_TRANSITION_MS);

    mostrandoPrecio = !mostrandoPrecio;
    programarSiguienteAlternancia();
  }

  function programarSiguienteAlternancia() {
    if (alternarTimer) {
      window.clearTimeout(alternarTimer);
    }

    var delay = mostrandoPrecio ? TIEMPO_PRECIO_MS : TIEMPO_VIGENCIA_MS;
    alternarTimer = window.setTimeout(alternarPanelPrecioOro, delay);
  }

  function iniciar() {
    if (!document.getElementById('precio_oro_viewport')) {
      return;
    }

    setInterval(actualizarPrecioOroNavbar, INTERVALO_POLL_MS);
    programarSiguienteAlternancia();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();

/**
 * Badge precio oro 24k por proveedor de fundición (rotación por proveedor).
 */
(function initPrecioOroProveedoresNavbarPoll() {
  var INTERVALO_POLL_MS = 2 * 1000;
  var TIEMPO_PANEL_MS = Math.round(4 * 1000 * 1.75);
  var SLIDE_TRANSITION_MS = 450;
  var URL_PRECIO_ORO_PROVEEDORES = 'parts/universal/obtener_precio_oro_proveedores.php';
  var ultimaSignatureProveedores = null;
  var panelIndexProveedor = 0;
  var animandoProveedor = false;
  var alternarTimerProveedor = null;

  function obtenerPanelesProveedor() {
    var viewport = document.getElementById('precio_oro_viewport_proveedor');
    if (!viewport) {
      return [];
    }
    return Array.prototype.slice.call(
      viewport.querySelectorAll('.precio-oro-badge-panel-proveedor')
    );
  }

  function renderPanelesProveedor(proveedores) {
    var viewport = document.getElementById('precio_oro_viewport_proveedor');
    if (!viewport) {
      return;
    }

    viewport.innerHTML = '';
    panelIndexProveedor = 0;

    if (!proveedores || !proveedores.length) {
      var vacio = document.createElement('span');
      vacio.className = 'precio-oro-badge-panel-proveedor is-visible';
      vacio.setAttribute('data-proveedor-id', '0');
      vacio.setAttribute('data-id-precio', '0');
      vacio.textContent = '—';
      viewport.appendChild(vacio);
      return;
    }

    proveedores.forEach(function (item, index) {
      var panel = document.createElement('span');
      panel.className = 'precio-oro-badge-panel-proveedor' + (index === 0 ? ' is-visible' : '');
      panel.setAttribute('data-proveedor-id', String(item.id_proveedor || 0));
      panel.setAttribute('data-id-precio', String(item.id_precio || 0));
      panel.innerHTML = item.texto_panel || '—';
      viewport.appendChild(panel);
    });
  }

  function actualizarDatosPrecioOroProveedores(data) {
    if (!data || !data.success) {
      return;
    }

    var signature = data.ids_signature != null ? String(data.ids_signature) : '';
    if (signature !== ultimaSignatureProveedores) {
      ultimaSignatureProveedores = signature;
      renderPanelesProveedor(data.proveedores || []);
    } else {
      var paneles = obtenerPanelesProveedor();
      var lista = data.proveedores || [];
      lista.forEach(function (item) {
        var panel = paneles.find(function (el) {
          return String(el.getAttribute('data-proveedor-id')) === String(item.id_proveedor);
        });
        if (panel) {
          if (item.texto_panel != null) {
            panel.innerHTML = item.texto_panel;
          } else if (item.precio_fmt != null) {
            var precioEl = panel.querySelector('#precio_24k_proveedor');
            if (precioEl) {
              precioEl.textContent = item.precio_fmt;
            }
          }
        }
      });
    }
  }

  function actualizarPrecioOroProveedoresNavbar() {
    if (!document.getElementById('precio_oro_viewport_proveedor')) {
      return;
    }

    fetch(URL_PRECIO_ORO_PROVEEDORES, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (res) {
        if (!res.ok) {
          throw new Error('HTTP ' + res.status);
        }
        return res.json();
      })
      .then(function (data) {
        actualizarDatosPrecioOroProveedores(data);
      })
      .catch(function () {});
  }

  function alternarPanelPrecioOroProveedor() {
    var paneles = obtenerPanelesProveedor();
    if (paneles.length <= 1 || animandoProveedor) {
      programarSiguienteAlternanciaProveedor();
      return;
    }

    var actual = paneles[panelIndexProveedor];
    var nextIndex = (panelIndexProveedor + 1) % paneles.length;
    var siguiente = paneles[nextIndex];

    animandoProveedor = true;
    actual.classList.remove('is-visible');
    actual.classList.add('is-exit-right');

    siguiente.classList.remove('is-exit-right', 'is-visible');
    siguiente.classList.add('is-enter-from-left');

    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () {
        siguiente.classList.remove('is-enter-from-left');
        siguiente.classList.add('is-visible');
      });
    });

    window.setTimeout(function () {
      actual.classList.remove('is-exit-right', 'is-enter-from-left');
      animandoProveedor = false;
    }, SLIDE_TRANSITION_MS);

    panelIndexProveedor = nextIndex;
    programarSiguienteAlternanciaProveedor();
  }

  function programarSiguienteAlternanciaProveedor() {
    if (alternarTimerProveedor) {
      window.clearTimeout(alternarTimerProveedor);
    }
    alternarTimerProveedor = window.setTimeout(alternarPanelPrecioOroProveedor, TIEMPO_PANEL_MS);
  }

  function iniciarProveedores() {
    if (!document.getElementById('precio_oro_viewport_proveedor')) {
      return;
    }

    setInterval(actualizarPrecioOroProveedoresNavbar, INTERVALO_POLL_MS);
    programarSiguienteAlternanciaProveedor();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarProveedores);
  } else {
    iniciarProveedores();
  }

  document.addEventListener('precioOroProveedoresActualizado', function () {
    actualizarPrecioOroProveedoresNavbar();
  });
})();

/**
 * Modal actualizar precio oro proveedores (navbar).
 */
(function initModalActualizarPrecioProveedor() {
  var URL_ACTUALIZAR = 'parts/universal/actualizar_precio_oro_proveedor.php';
  var liNavbar = document.getElementById('liNavbarPrecioOroProveedor');
  var modalEl = document.getElementById('modalActualizarPrecioProveedor');
  var form = document.getElementById('formActualizarPrecioProveedor');
  var btnSubmit = document.getElementById('btnActualizarPrecioProveedor');

  function abrirModal() {
    if (!modalEl || typeof bootstrap === 'undefined') {
      return;
    }
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  function initDateMasksModal() {
    if (!modalEl) {
      return;
    }
    modalEl.querySelectorAll('.date-mask').forEach(function (input) {
      if (typeof initDateMaskInput === 'function') {
        initDateMaskInput(input);
      }
    });
  }

  if (liNavbar) {
    liNavbar.addEventListener('click', function () {
      abrirModal();
    });
    liNavbar.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        abrirModal();
      }
    });
  }

  if (modalEl) {
    modalEl.addEventListener('shown.bs.modal', initDateMasksModal);
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      var fechaInputs = form.querySelectorAll('.date-mask');
      fechaInputs.forEach(function (input) {
        if (input.value.trim() !== '' && window.TpvFecha && !window.TpvFecha.parseLocal(input.value)) {
          input.setCustomValidity('Fecha no válida (DD/MM/YYYY)');
        } else {
          input.setCustomValidity('');
        }
      });

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      if (btnSubmit) {
        btnSubmit.disabled = true;
      }

      fetch(URL_ACTUALIZAR, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
      })
        .then(function (res) {
          return res.json().then(function (data) {
            if (!res.ok || !data.success) {
              throw new Error(data.message || 'Error al actualizar el precio');
            }
            return data;
          });
        })
        .then(function (data) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Actualizado',
              text: data.message || 'El precio fue actualizado con éxito',
              timer: 2200,
              showConfirmButton: false
            });
          }

          document.dispatchEvent(new CustomEvent('precioOroProveedoresActualizado'));

          if (modalEl && typeof bootstrap !== 'undefined') {
            var instancia = bootstrap.Modal.getInstance(modalEl);
            if (instancia) {
              instancia.hide();
            }
          }
        })
        .catch(function (err) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: err.message || 'No se pudo actualizar el precio'
            });
          }
        })
        .finally(function () {
          if (btnSubmit) {
            btnSubmit.disabled = false;
          }
        });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDateMasksModal);
  } else {
    initDateMasksModal();
  }
})();

(function () {
  function initNavbarBackBtn() {
    var btn = document.getElementById('btnNavbarBack');
    if (!btn) {
      return;
    }

    btn.addEventListener('click', function () {
      window.history.back();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNavbarBackBtn);
  } else {
    initNavbarBackBtn();
  }
})();

(function () {
  function initCerrarSesionNavbar() {
    var btn = document.getElementById('btnCerrarSesionNavbar');
    if (!btn) {
      return;
    }

    btn.addEventListener('click', function (event) {
      event.preventDefault();
      cerrarSesion();
    });
  }

  function initFooterActualizarBtn() {
    var btn = document.getElementById('btnFooterActualizar');
    if (!btn) {
      return;
    }

    btn.addEventListener('click', function () {
      mostrarLoaderUniversal('Recargando');
      setTimeout(function () {
        window.location.reload();
      }, 2000);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initCerrarSesionNavbar();
      initFooterActualizarBtn();
    });
  } else {
    initCerrarSesionNavbar();
    initFooterActualizarBtn();
  }
})();
