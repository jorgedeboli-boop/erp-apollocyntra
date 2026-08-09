'use strict';

/**
 * Buscador navbar para usuarios de sucursal: solo consulta lotes y artículos
 * de la sucursal de sesión ($usuario_sucursal) vía endpoints *_sucursal.php.
 */

(function () {
  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function normalizarValor(valor) {
    return String(valor || '')
      .toUpperCase()
      .replace(/[^0-9A-Z]/g, '')
      .slice(0, 30);
  }

  function parsearBusqueda(valor) {
    var normalizado = normalizarValor(valor);
    return {
      normalizado: normalizado,
      digitos: normalizado.replace(/[^0-9]/g, ''),
    };
  }

  function puedeBuscar(parsed) {
    return parsed.digitos.length >= 1;
  }

  function initScrollFade(listEl) {
    if (!listEl) {
      return function () {};
    }

    function actualizarScrollFade() {
      var fadeSize = getComputedStyle(listEl).getPropertyValue('--scroll-fade-size').trim() || '2rem';
      var scrollable = listEl.scrollHeight > listEl.clientHeight + 1;
      var atTop = listEl.scrollTop <= 1;
      var atBottom = listEl.scrollTop + listEl.clientHeight >= listEl.scrollHeight - 1;

      listEl.style.setProperty('--scroll-fade-top', scrollable && !atTop ? fadeSize : '0px');
      listEl.style.setProperty('--scroll-fade-bottom', scrollable && !atBottom ? fadeSize : '0px');
    }

    listEl.addEventListener('scroll', actualizarScrollFade, { passive: true });
    window.addEventListener('resize', actualizarScrollFade);

    if (typeof ResizeObserver !== 'undefined') {
      var observer = new ResizeObserver(actualizarScrollFade);
      observer.observe(listEl);
    }

    actualizarScrollFade();
    return actualizarScrollFade;
  }

  function initBusquedaNavbarModal(config) {
    var modalEl = document.getElementById(config.modalId);
    var input = document.getElementById(config.inputId);
    var btnBuscar = document.getElementById(config.btnBuscarId);
    var resultsWrap = document.getElementById(config.resultsWrapId);
    var resultsBody = document.getElementById(config.resultsBodyId);
    var noEncontrado = document.getElementById(config.noEncontradoId);

    if (!modalEl || !input || !resultsWrap || !resultsBody) {
      return;
    }

    var peticionId = 0;
    var actualizarScrollFade = initScrollFade(resultsBody);

    function limpiarResultados() {
      resultsBody.innerHTML = '';
      resultsWrap.hidden = true;
      resultsWrap.setAttribute('aria-hidden', 'true');
      if (noEncontrado) {
        noEncontrado.hidden = true;
      }
      actualizarScrollFade();
    }

    function mostrarResultados(visible) {
      resultsWrap.hidden = !visible;
      resultsWrap.setAttribute('aria-hidden', visible ? 'false' : 'true');
    }

    function renderResultados(items) {
      resultsBody.innerHTML = '';
      mostrarResultados(true);

      if (!items.length) {
        if (noEncontrado) {
          noEncontrado.hidden = false;
        }
        requestAnimationFrame(actualizarScrollFade);
        return;
      }

      if (noEncontrado) {
        noEncontrado.hidden = true;
      }

      items.forEach(function (item) {
        var card = document.createElement('button');
        card.type = 'button';
        card.className = 'btn btn-primary waves-effect ' + config.cardClass + ' text-decoration-none';
        card.innerHTML = config.crearFilaHtml(item);
        if (config.obtenerUrl(item)) {
          card.addEventListener('click', function () {
            window.location.href = config.obtenerUrl(item);
          });
        }
        resultsBody.appendChild(card);
      });

      requestAnimationFrame(actualizarScrollFade);
    }

    function ejecutarBusqueda() {
      var parsed = parsearBusqueda(input.value);
      if (!puedeBuscar(parsed)) {
        return;
      }

      var idPeticion = ++peticionId;
      var url = config.apiUrl + encodeURIComponent(parsed.normalizado);

      fetch(url, { credentials: 'same-origin' })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (idPeticion !== peticionId) {
            return;
          }
          if (!data || !data.success) {
            renderResultados([]);
            return;
          }
          renderResultados(config.extraerItems(data));
        })
        .catch(function () {
          if (idPeticion !== peticionId) {
            return;
          }
          renderResultados([]);
        });
    }

    function aplicarFiltro() {
      var normalizado = normalizarValor(input.value);
      if (input.value !== normalizado) {
        input.value = normalizado;
      }
    }

    function onKeydown(event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        ejecutarBusqueda();
        return;
      }
      var teclasPermitidas = [
        'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
        'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
        'Home', 'End',
      ];
      if (teclasPermitidas.indexOf(event.key) !== -1 || event.ctrlKey || event.metaKey) {
        return;
      }
      if (!/^[0-9a-zA-Z]$/.test(event.key)) {
        event.preventDefault();
      }
    }

    modalEl.addEventListener('shown.bs.modal', function () {
      input.focus();
      actualizarScrollFade();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
      peticionId++;
      input.value = '';
      limpiarResultados();
    });

    input.addEventListener('input', function () {
      aplicarFiltro();
      if (!puedeBuscar(parsearBusqueda(input.value))) {
        limpiarResultados();
      }
    });

    input.addEventListener('paste', function () {
      setTimeout(aplicarFiltro, 0);
    });

    input.addEventListener('keydown', onKeydown);

    if (btnBuscar) {
      btnBuscar.addEventListener('click', ejecutarBusqueda);
    }
  }

  function wireNavbarTrigger(triggerId, modalId) {
    var trigger = document.getElementById(triggerId);
    var modalEl = document.getElementById(modalId);

    if (!trigger || !modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
      return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      modal.show();
    });
  }

  function init() {
    wireNavbarTrigger('searchLoteNav', 'modalBuscarLote');
    wireNavbarTrigger('searchArticuloNav', 'modalBuscarArticulo');

    initBusquedaNavbarModal({
      modalId: 'modalBuscarLote',
      inputId: 'searchLote',
      btnBuscarId: 'btnBuscarLote',
      resultsWrapId: 'searchLoteTablaWrap',
      resultsBodyId: 'searchLoteTablaBody',
      noEncontradoId: 'searchLoteNoEncontrado',
      cardClass: 'search-lote-card',
      apiUrl: 'parts/universal/buscar_lote_navbar_sucursal.php?busqueda=',
      extraerItems: function (data) {
        return data.lotes || [];
      },
      obtenerUrl: function (lote) {
        return lote.identificador
          ? 'lote_sucursal.php?id=' + encodeURIComponent(String(lote.identificador))
          : '';
      },
      crearFilaHtml: function (lote) {
        return (
          '<div class="search-lote-fila">' +
            '<div class="search-lote-celda search-lote-celda-lote">' +
              '<span class="search-lote-numero">' + escapeHtml(String(lote.id_lote || '')) + '</span>' +
            '</div>' +
            '<div class="search-lote-celda search-lote-celda-dato search-lote-celda-sucursal">' + escapeHtml(String(lote.nombre_sucursal || '—')) + '</div>' +
            '<div class="search-lote-celda search-lote-celda-dato search-lote-celda-tipo">' + escapeHtml(String(lote.tipo_lote || '—')) + '</div>' +
            '<div class="search-lote-celda search-lote-celda-dato search-lote-celda-fecha-compra">' + escapeHtml(String(lote.fecha_compra || '—')) + '</div>' +
            '<div class="search-lote-celda search-lote-celda-dato search-lote-celda-precio">' + escapeHtml(String(lote.precio_compra || '—')) + '</div>' +
            '<div class="search-lote-celda search-lote-celda-dato search-lote-celda-peso">' + escapeHtml(String(lote.peso || '—')) + '</div>' +
          '</div>'
        );
      },
    });

    initBusquedaNavbarModal({
      modalId: 'modalBuscarArticulo',
      inputId: 'searchArticulo',
      btnBuscarId: 'btnBuscarArticulo',
      resultsWrapId: 'searchArticuloTablaWrap',
      resultsBodyId: 'searchArticuloTablaBody',
      noEncontradoId: 'searchArticuloNoEncontrado',
      cardClass: 'search-articulo-card',
      apiUrl: 'parts/universal/buscar_articulo_navbar_sucursal.php?busqueda=',
      extraerItems: function (data) {
        return data.articulos || [];
      },
      obtenerUrl: function (articulo) {
        return articulo.id
          ? 'articulo_sucursal.php?id=' + encodeURIComponent(String(articulo.id))
          : '';
      },
      crearFilaHtml: function (articulo) {
        return (
          '<div class="search-articulo-fila">' +
            '<div class="search-articulo-celda search-articulo-celda-sku">' +
              '<span class="search-articulo-numero">' + escapeHtml(String(articulo.id || '')) + '</span>' +
            '</div>' +
            '<div class="search-articulo-celda search-articulo-celda-dato search-articulo-celda-descripcion" title="' + escapeHtml(String(articulo.descripcion || '')) + '">' + escapeHtml(String(articulo.descripcion || '—')) + '</div>' +
            '<div class="search-articulo-celda search-articulo-celda-dato search-articulo-celda-estado">' + escapeHtml(String(articulo.estado || '—')) + '</div>' +
            '<div class="search-articulo-celda search-articulo-celda-dato search-articulo-celda-precio">' + escapeHtml(String(articulo.precio || '—')) + '</div>' +
            '<div class="search-articulo-celda search-articulo-celda-dato search-articulo-celda-venta">' + escapeHtml(String(articulo.numero_venta || '—')) + '</div>' +
            '<div class="search-articulo-celda search-articulo-celda-dato search-articulo-celda-fecha-venta">' + escapeHtml(String(articulo.fecha_en_venta || '—')) + '</div>' +
            '<div class="search-articulo-celda search-articulo-celda-dato search-articulo-celda-fecha-vendido">' + escapeHtml(String(articulo.fecha_vendido || '—')) + '</div>' +
          '</div>'
        );
      },
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
