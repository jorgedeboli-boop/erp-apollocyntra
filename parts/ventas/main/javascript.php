<!-- JAVASCRIPT CUSTOM venta - main -->
<?php
require_once __DIR__ . '/../../../camera/render-config-script.php';
require __DIR__ . '/../inc_max_total_factura_simplificada.php';

$total_venta_ficha_factura_simplificada = 0.0;
if (isset($total_ticket) && (float) $total_ticket > 0) {
    $total_venta_ficha_factura_simplificada = (float) $total_ticket;
} elseif (isset($venta_principal) && is_array($venta_principal) && isset($venta_principal['precio'])) {
    $total_venta_ficha_factura_simplificada = (float) $venta_principal['precio'];
}
?>
<script>
window.MAX_TOTAL_FACTURA_SIMPLIFICADA = <?php echo json_encode($max_total_factura_simplificada); ?>;
window.totalVentaFichaFacturaSimplificada = <?php echo json_encode($total_venta_ficha_factura_simplificada); ?>;
</script>
<?php
$vQrcodeMin = filemtime(__DIR__ . '/../../../js/qrcode.min.js');
$vCameraQr = filemtime(__DIR__ . '/../../../camera/js/camera-qr.js');
$vCameraDocPanel = filemtime(__DIR__ . '/../../../camera/js/camera-doc-panel.js');
?>
<script src="js/qrcode.min.js?v=<?php echo $vQrcodeMin; ?>"></script>
<script src="camera/js/camera-qr.js?v=<?php echo $vCameraQr; ?>"></script>
<script src="camera/js/camera-doc-panel.js?v=<?php echo $vCameraDocPanel; ?>"></script>
<script>
(function () {
  const idVentaComprobantes = <?php echo (isset($hay_datos) && $hay_datos && !empty($id_comprobantes)) ? (int) $id_comprobantes : 0; ?>;
  const idVentaFotosTicket = <?php echo (isset($hay_datos) && $hay_datos && !empty($id_venta)) ? (int) $id_venta : 0; ?>;
  const idSucursalVenta = <?php echo (isset($hay_datos) && $hay_datos && !empty($venta_principal['id_sucursal'])) ? (int) $venta_principal['id_sucursal'] : 0; ?>;
  const idArticuloVentaAncla = <?php echo (isset($hay_datos) && $hay_datos) ? (int) ($id_articulo_venta_ancla ?? 0) : 0; ?>;
  const idClienteFichaVenta = <?php echo (isset($cliente_ficha) && is_array($cliente_ficha) && !empty($cliente_ficha['id_cliente'])) ? (int) $cliente_ficha['id_cliente'] : 0; ?>;
  const idSucursalClienteDocsVenta = <?php
    if (isset($cliente_ficha) && is_array($cliente_ficha)) {
        $sc = (int) ($cliente_ficha['sucursal'] ?? 0);
        echo $sc > 0 ? $sc : (isset($venta_principal['id_sucursal']) ? (int) $venta_principal['id_sucursal'] : 0);
    } else {
        echo '0';
    }
    ?>;
  const idVentaFichaPage = <?php echo (isset($hay_datos) && $hay_datos && !empty($id_venta)) ? (int) $id_venta : 0; ?>;
  const ventaEsPlazosFicha = <?php echo (isset($venta_principal) && is_array($venta_principal) && ($venta_principal['venta_plazos'] ?? '') === 'si') ? 'true' : 'false'; ?>;
  let ventaPermiteAdelantoCapital = <?php echo !empty($venta_permite_adelanto_capital) ? 'true' : 'false'; ?>;
  let ventaPermiteAnularPlazos = <?php echo !empty($venta_permite_anular_plazos) ? 'true' : 'false'; ?>;

  window.cargarAccionesVentaFicha = function () {
    if (idVentaFichaPage <= 0) {
      return Promise.resolve();
    }
    const cont = document.getElementById('contenedor_acciones_venta_ficha');
    if (!cont) {
      return Promise.resolve();
    }
    const fd = new URLSearchParams();
    fd.set('id_venta', String(idVentaFichaPage));
    return fetch(ventaApiUrl('parts/ventas/main/load_acciones_venta_ficha.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString(),
      credentials: 'same-origin'
    })
      .then(function (r) {
        return ventaParseRespuestaJsonFetch(r);
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.message || 'No se pudieron cargar las acciones');
        }
        cont.innerHTML = data.html || '';
        ventaPermiteAdelantoCapital = !!data.venta_permite_adelanto_capital;
        ventaPermiteAnularPlazos = !!data.venta_permite_anular_plazos;
      })
      .catch(function (err) {
        console.error('cargarAccionesVentaFicha:', err);
      });
  };

  window.enviarFacturaEmailVentaFicha = function (btnEl) {
    if (!btnEl) {
      return;
    }
    const idFactura = btnEl.getAttribute('data-id-factura');
    const urlEnvio = btnEl.getAttribute('data-url-envio');
    const tituloEnvio = btnEl.getAttribute('data-titulo-envio') || 'Enviar factura';
    if (!idFactura || !urlEnvio) {
      return;
    }
    if (typeof Swal === 'undefined') {
      alert('No está disponible el cuadro de diálogo (SweetAlert).');
      return;
    }

    Swal.fire({
      title: tituloEnvio,
      input: 'email',
      inputLabel: 'Correo electrónico',
      inputPlaceholder: 'email@ejemplo.com',
      showCancelButton: true,
      confirmButtonText: 'Enviar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-outline-secondary waves-effect'
      },
      buttonsStyling: false,
      inputValidator: function (value) {
        if (!value) {
          return 'Introduzca un correo';
        }
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!re.test(value)) {
          return 'Correo no válido';
        }
        return null;
      }
    }).then(function (result) {
      if (!result.isConfirmed || !result.value) {
        return;
      }

      const fd = new FormData();
      fd.append('id_factura', idFactura);
      fd.append('email', result.value);

      fetch(ventaApiUrl(urlEnvio), {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      })
        .then(function (r) {
          return ventaParseRespuestaJsonFetch(r);
        })
        .then(function (res) {
          if (res.success) {
            Swal.fire({
              icon: 'success',
              title: 'Enviado',
              text: res.message || 'Factura enviada correctamente.'
            });
            return;
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: res.message || 'Error al enviar'
          });
        })
        .catch(function (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: (err && err.message) ? err.message : 'Error de conexión'
          });
        });
    });
  };

  /** Mismo tope que nueva venta: PHP obtenerMaximoTotalFacturaSimplificada → window.MAX_TOTAL_FACTURA_SIMPLIFICADA */
  function obtenerMaximoTotalFacturaSimplificada() {
    var v = typeof window.MAX_TOTAL_FACTURA_SIMPLIFICADA !== 'undefined'
      ? Number(window.MAX_TOTAL_FACTURA_SIMPLIFICADA)
      : NaN;
    return isFinite(v) && v > 0 ? v : <?php echo json_encode($max_total_factura_simplificada); ?>;
  }

  function ventaSuperaTopeFacturaSimplificada() {
    var total = typeof window.totalVentaFichaFacturaSimplificada !== 'undefined'
      ? Number(window.totalVentaFichaFacturaSimplificada)
      : 0;
    return isFinite(total) && total > obtenerMaximoTotalFacturaSimplificada();
  }

  /**
   * Tras cerrar venta a plazos: factura completa automática si supera tope, o elección simplificada/completa.
   */
  window.gestionarFacturaTrasCierreVentaPlazos = function (idVenta, totalVenta, apiBasePath) {
    var maxSimplificada = obtenerMaximoTotalFacturaSimplificada();
    var total = Number(totalVenta);
    if (!isFinite(total) || total <= 0) {
      total = typeof window.totalVentaFichaFacturaSimplificada !== 'undefined'
        ? Number(window.totalVentaFichaFacturaSimplificada)
        : 0;
    }

    function activarLoaderGenerarFacturaPlazos(tipoFactura) {
      var texto = tipoFactura === 'simplificada'
        ? 'Generando factura simplificada'
        : 'Generando factura';
      if (typeof mostrarLoaderUniversal === 'function') {
        mostrarLoaderUniversal(texto);
      }
    }

    function generarFacturaPlazos(tipoFactura) {
      activarLoaderGenerarFacturaPlazos(tipoFactura);
      var fd = new FormData();
      fd.append('id_venta', String(idVenta));
      fd.append('tipo_factura', tipoFactura);
      return fetch(ventaApiUrl(apiBasePath + '/generar_factura_venta_plazos.php'), {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      }).then(function (r) {
        return ventaParseRespuestaJsonFetch(r);
      }).then(function (res) {
        if (!res || !res.success) {
          throw new Error((res && res.message) ? res.message : 'No se pudo generar la factura');
        }
        return res;
      });
    }

    if (total > maxSimplificada) {
      return generarFacturaPlazos('completa').then(function () {
        window.location.reload();
      }).catch(function (err) {
        if (typeof ocultarLoaderUniversal === 'function') {
          ocultarLoaderUniversal();
        }
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err && err.message ? err.message : 'No se pudo generar la factura'
          });
        }
      });
    }

    return Swal.fire({
      title: 'Seleccione tipo de factura',
      html: '<p class="mb-2">Debe elegir el tipo de factura para esta venta.</p>'
        + '<p class="mb-1"><strong>Factura completa:</strong> incluye los datos del cliente.</p>'
        + '<p class="mb-0"><strong>Factura simplificada:</strong> no incluye los datos del cliente.</p>',
      icon: 'question',
      showConfirmButton: true,
      confirmButtonText: 'Factura completa',
      showDenyButton: true,
      denyButtonText: 'Factura simplificada',
      showCancelButton: false,
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      customClass: {
        confirmButton: 'btn btn-primary me-2',
        denyButton: 'btn btn-outline-primary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.isConfirmed) {
        return generarFacturaPlazos('completa');
      }
      if (result.isDenied) {
        return generarFacturaPlazos('simplificada');
      }
      return Promise.reject(new Error('Debe seleccionar un tipo de factura'));
    }).then(function () {
      window.location.reload();
    }).catch(function (err) {
      if (typeof ocultarLoaderUniversal === 'function') {
        ocultarLoaderUniversal();
      }
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: err && err.message ? err.message : 'No se pudo generar la factura'
        });
      }
    });
  };

  /**
   * Genera ticket (factura simplificada) para venta a plazos ya cerrada sin factura.
   */
  window.generarTicketVentaPlazosFicha = function () {
    if (idVentaFichaPage <= 0) {
      return;
    }
    var apiBasePath = 'parts/ventas/main';
    var total = typeof window.totalVentaFichaFacturaSimplificada !== 'undefined'
      ? Number(window.totalVentaFichaFacturaSimplificada)
      : 0;
    var maxSimplificada = obtenerMaximoTotalFacturaSimplificada();

    if (isFinite(total) && total > maxSimplificada) {
      return window.gestionarFacturaTrasCierreVentaPlazos(idVentaFichaPage, total, apiBasePath);
    }

    if (typeof Swal === 'undefined') {
      return;
    }

    Swal.fire({
      title: 'Generar ticket',
      text: 'Se generará una factura simplificada (ticket) para esta venta a plazos. ¿Continuar?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, generar ticket',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success me-2',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }
      if (typeof mostrarLoaderUniversal === 'function') {
        mostrarLoaderUniversal('Generando factura simplificada');
      }
      var fd = new FormData();
      fd.append('id_venta', String(idVentaFichaPage));
      fd.append('tipo_factura', 'simplificada');
      return fetch(ventaApiUrl(apiBasePath + '/generar_factura_venta_plazos.php'), {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      })
        .then(function (r) {
          return ventaParseRespuestaJsonFetch(r);
        })
        .then(function (res) {
          if (!res || !res.success) {
            throw new Error((res && res.message) ? res.message : 'No se pudo generar el ticket');
          }
          window.location.reload();
        })
        .catch(function (err) {
          if (typeof ocultarLoaderUniversal === 'function') {
            ocultarLoaderUniversal();
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err && err.message ? err.message : 'No se pudo generar el ticket'
          });
        });
    });
  };

  /** Oculta efectivo/contado en modal si el total de la venta supera el tope de factura simplificada. */
  function aplicarLimiteFacturaSimplificadaRadioEfectivo(idRadioEfectivo, idRadioTarjeta, marcarRadioFn) {
    var re = document.getElementById(idRadioEfectivo);
    var rt = document.getElementById(idRadioTarjeta);
    var colEfectivo = re ? re.closest('.col-md') : null;
    var ocultar = ventaSuperaTopeFacturaSimplificada();
    if (colEfectivo) {
      colEfectivo.style.display = ocultar ? 'none' : '';
    }
    var elegido = ocultar && rt ? rt : re;
    if (!elegido) {
      return;
    }
    elegido.checked = true;
    if (typeof marcarRadioFn === 'function') {
      marcarRadioFn(elegido);
    }
    elegido.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function ventaApiUrl(relPath) {
    try {
      return new URL(relPath, window.location.href).href;
    } catch (e) {
      return relPath;
    }
  }

  function generarTokenVenta() {
    return 'tok_' + Math.random().toString(36).substr(2, 28) + Date.now().toString(36);
  }

  /** Comprobante venta: modal y QR los gestiona camera/ (CameraDocPanel + camera-qr.js). */
  window.generarNuevoQRVenta = function () {
    if (idVentaComprobantes <= 0 || idSucursalVenta <= 0) {
      return;
    }
    if (window.CameraDocPanel && typeof window.CameraDocPanel.open === 'function') {
      window.CameraDocPanel.open({
        tipo: 'venta',
        id: idVentaComprobantes,
        idSucursal: idSucursalVenta
      }).catch(function (e) {
        Swal.fire({ title: 'Error', text: e.message || 'No se pudo generar el QR', icon: 'error' });
      });
    }
  };

  let qrAdelantoVentaPollTimer = null;
  let qrAdelantoVentaExpireTimer = null;

  function adelantoVentaAplicarPreviewComprobante(nombreArchivo) {
    const cont = document.getElementById('contenedor_imagenes_comprobante_adelanto_venta');
    if (!cont) {
      return;
    }
    // Si llegamos aquí es porque hay comprobante desde móvil (cache) o ya lo tenemos.
    const fc = document.getElementById('foto_camara_adelanto_venta');
    if (fc) {
      fc.value = 'true';
    }
    const inpFile = document.getElementById('comprobante_adelanto_venta_archivo');
    if (inpFile) {
      inpFile.value = '';
    }
    const raw = nombreArchivo != null ? String(nombreArchivo).trim() : '';
    if (raw === '') {
      window._adelantoVentaComprobanteNombre = '';
      cont.innerHTML =
        '<i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>' +
        '<p class="text-body-secondary mb-0">No hay comprobante cargado</p>';
      actualizarEstadoBotonAdelantoCapitalVenta();
      return;
    }
    const safe = raw.replace(/[^a-zA-Z0-9_\-\.]/g, '');
    window._adelantoVentaComprobanteNombre = safe;
    const src = 'photos/' + safe + '?t=' + Date.now();
    if (safe.toLowerCase().endsWith('.pdf')) {
      cont.innerHTML =
        '<div class="text-center">' +
        '<i class="icon-base ri ri-file-pdf-line icon-48px text-danger mb-3"></i>' +
        '<p class="text-body-secondary mb-2">' +
        safe +
        '</p>' +
        '<a class="btn btn-sm btn-primary" href="' +
        src +
        '" target="_blank" rel="noopener noreferrer">Abrir PDF</a>' +
        '</div>';
      actualizarEstadoBotonAdelantoCapitalVenta();
      return;
    }
    cont.innerHTML =
      '<div class="position-relative">' +
      '<img src="' +
      src +
      '" alt="Comprobante" class="img-fluid" style="cursor:pointer;" data-src="photos/' +
      safe +
      '">' +
      '</div>';
    const img = cont.querySelector('img[data-src]');
    if (img) {
      img.addEventListener('click', function () {
        window.ampliarImagenVenta(this.getAttribute('data-src'));
      });
    }
    actualizarEstadoBotonAdelantoCapitalVenta();
  }

  function consultarTokenAdelantoVenta(token, idToken, idFotoCache) {
    console.log('[adelanto_venta][poll]', { idToken: idToken, token: token, idFoto: idFotoCache, t: new Date().toISOString() });
    fetch('parts/lotes/main/procesar_consultar_token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ token: token, id_token: idToken })
    })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      })
      .then(function (data) {
        console.log('[adelanto_venta][poll][resp]', data);
        const idFoto = parseInt(String(idFotoCache || 0), 10) || 0;
        if (idFoto > 0 && idSucursalVenta > 0 && idVentaFichaPage > 0) {
          fetch(ventaApiUrl('parts/ventas/main/get_foto_cache_adelanto_venta.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ id_foto: idFoto, id_sucursal: idSucursalVenta, id_venta: idVentaFichaPage })
          })
            .then(function (r2) { return r2.json(); })
            .then(function (j) {
              if (j && j.success && j.tiene_foto) {
                console.log('[adelanto_venta][poll] cache tiene foto', j.nombre_foto);
                adelantoVentaAplicarPreviewComprobante(j.nombre_foto);
                // QR dinámico (CameraDocPanel): no existe modalFotoMovilAdelantoVenta
              }
            })
            .catch(function (e) {
              console.warn('[adelanto_venta][poll] get_foto_cache error', e);
            });
        }
        if (data.success && data.utilizado) {
          console.log('[adelanto_venta][poll] token utilizado -> cerrar modal QR');
          if (qrAdelantoVentaPollTimer) {
            clearInterval(qrAdelantoVentaPollTimer);
            qrAdelantoVentaPollTimer = null;
          }
          if (qrAdelantoVentaExpireTimer) {
            clearTimeout(qrAdelantoVentaExpireTimer);
            qrAdelantoVentaExpireTimer = null;
          }
          // QR dinámico (CameraDocPanel): el cierre lo gestiona camera-qr.js
          // El backend leerá la foto cache al guardar el adelanto (id_foto_cache_adelanto_venta)
        }
      })
      .catch(function (err) {
        console.warn('consultarTokenAdelantoVenta', err);
      });
  }

  window.generarNuevoQRAdelantoVenta = function (idFotoCache) {
    const idFoto = parseInt(String(idFotoCache || 0), 10) || 0;
    if (idFoto <= 0 || idSucursalVenta <= 0) {
      return;
    }
    if (window.CameraDocPanel && typeof window.CameraDocPanel.open === 'function') {
      window.CameraDocPanel.open({
        tipo: 'adelanto_venta',
        id: idFoto,
        idSucursal: idSucursalVenta
      }).catch(function (e) {
        Swal.fire({ title: 'Error', text: e.message || 'No se pudo generar el QR', icon: 'error' });
      });
    }
  };

  window.abrirModalFotoMovilAdelantoVenta = function () {
    if (idVentaFichaPage <= 0 || idSucursalVenta <= 0) {
      Swal.fire({ title: 'Atención', text: 'No hay datos de venta para el adelanto.', icon: 'warning' });
      return;
    }
    const hidFoto = document.getElementById('foto_camara_adelanto_venta');
    if (hidFoto) {
      hidFoto.value = 'true';
    }
    fetch(ventaApiUrl('parts/ventas/main/procesar_insertar_foto_camara_adelanto_venta.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ id_venta: idVentaFichaPage, id_sucursal: idSucursalVenta })
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'No se pudo crear el registro de foto');
        }
        const hidId = document.getElementById('id_foto_cache_adelanto_venta');
        if (hidId) {
          hidId.value = String(data.id_foto || '');
        }
        // QR dinámico (CameraDocPanel): no ocultar/cerrar modal padre ni usar modalFotoMovilAdelantoVenta
        window.generarNuevoQRAdelantoVenta(data.id_foto);
      })
      .catch(function (e) {
        Swal.fire({ title: 'Error', text: e.message || 'Error', icon: 'error' });
      });
  };

  window.abrirModalFotoMovilVenta = function () {
    if (idVentaComprobantes <= 0 || idSucursalVenta <= 0) {
      Swal.fire({ title: 'Atención', text: 'No hay datos de venta para el comprobante.', icon: 'warning' });
      return;
    }
    window.generarNuevoQRVenta();
  };

  let qrArticuloVentaPollTimer = null;
  let qrArticuloVentaExpireTimer = null;

  function consultarTokenArticuloVentaTicket(token, idToken) {
    fetch('parts/lotes/main/procesar_consultar_token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ token: token, id_token: idToken })
    })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      })
      .then(function (data) {
        if (data.success && data.utilizado) {
          if (qrArticuloVentaPollTimer) {
            clearInterval(qrArticuloVentaPollTimer);
            qrArticuloVentaPollTimer = null;
          }
          if (qrArticuloVentaExpireTimer) {
            clearTimeout(qrArticuloVentaExpireTimer);
            qrArticuloVentaExpireTimer = null;
          }
          const modalElement = document.getElementById('modalFotoMovilArticulosVenta');
          const modalInstance = modalElement ? bootstrap.Modal.getInstance(modalElement) : null;
          if (modalInstance) {
            modalInstance.hide();
          }
          cargarFotosArticulosVentaTicket(idVentaFotosTicket, false);
        }
      })
      .catch(function (err) {
        console.warn('consultarTokenArticuloVentaTicket', err);
      });
  }

  window.generarNuevoQRArticuloVentaTicket = function () {
    if (idVentaFotosTicket <= 0 || idSucursalVenta <= 0 || idArticuloVentaAncla <= 0) {
      return;
    }
    if (window.CameraDocPanel && typeof window.CameraDocPanel.open === 'function') {
      window.CameraDocPanel.open({
        tipo: 'articulo_venta',
        id: idVentaFotosTicket,
        idSucursal: idSucursalVenta
      }).catch(function (e) {
        Swal.fire({ title: 'Error', text: e.message || 'No se pudo generar el QR', icon: 'error' });
      });
    }
  };

  window.abrirModalFotoMovilArticulosVentaTicket = function () {
    if (idVentaFotosTicket <= 0 || idSucursalVenta <= 0 || idArticuloVentaAncla <= 0) {
      Swal.fire({ title: 'Atención', text: 'No hay artículos en el ticket para fotos.', icon: 'warning' });
      return;
    }
    if (window.CameraDocPanel && typeof window.CameraDocPanel.open === 'function') {
      window.CameraDocPanel.open({
        tipo: 'articulo_venta',
        id: idVentaFotosTicket,
        idSucursal: idSucursalVenta
      }).catch(function (e) {
        Swal.fire({ title: 'Error', text: e.message || 'No se pudo generar el QR', icon: 'error' });
      });
    }
  };

  function escapeJsString(s) {
    return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
  }

  let visorQrArticuloVentaPollTimer = null;

  function refrescarComprobanteAdelantoVentaDesdeCamera() {
    const hid = document.getElementById('id_foto_cache_adelanto_venta');
    const idFoto = hid ? parseInt(String(hid.value || 0), 10) || 0 : 0;
    if (idFoto <= 0) {
      return;
    }
    const fd = new FormData();
    fd.append('tipo', 'adelanto_venta');
    fd.append('id', String(idFoto));
    fd.append('id_sucursal', String(idSucursalVenta || 0));
    fetch('camera/api/list_imagenes.php', { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.success && data.imagenes && data.imagenes.length > 0 && data.imagenes[0].foto) {
          adelantoVentaAplicarPreviewComprobante(data.imagenes[0].foto);
        }
      })
      .catch(function () {});
  }

  function refrescarComprobanteCobrarPlazoVentaDesdeCamera() {
    const hid = document.getElementById('id_foto_cache_plazo_venta');
    const idFoto = hid ? parseInt(String(hid.value || 0), 10) || 0 : 0;
    if (idFoto <= 0) {
      return;
    }
    const fd = new FormData();
    fd.append('tipo', 'plazo_venta');
    fd.append('id', String(idFoto));
    fd.append('id_sucursal', String(idSucursalVenta || 0));
    fetch('camera/api/list_imagenes.php', { method: 'POST', credentials: 'same-origin', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.success && data.imagenes && data.imagenes.length > 0 && data.imagenes[0].foto) {
          cobrarPlazoVentaAplicarPreviewComprobante(data.imagenes[0].foto);
          const btnOk = document.getElementById('btnCobrarPlazoVentaModal');
          if (btnOk) {
            btnOk.disabled = false;
          }
        }
      })
      .catch(function () {});
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Centralizado: cuando CameraQR detecta token utilizado (foto guardada), refrescar el visor correspondiente.
    if (window.CameraQR && typeof window.CameraQR.init === 'function') {
      window.CameraQR.init({
        callbacks: {
          onTokenUtilizado: function (tipo_qr) {
            if (tipo_qr === 'cliente') {
              if (idClienteFichaVenta > 0) {
                cargarImagenesClienteVenta(idClienteFichaVenta);
              }
              return;
            }
            if (tipo_qr === 'venta') {
              if (idVentaComprobantes > 0) {
                cargarComprobantesVenta(idVentaComprobantes, true);
              }
              return;
            }
            if (tipo_qr === 'articulo_venta') {
              if (idVentaFotosTicket > 0) {
                cargarFotosArticulosVentaTicket(idVentaFotosTicket, true);
              }
              return;
            }
            if (tipo_qr === 'adelanto_venta') {
              refrescarComprobanteAdelantoVentaDesdeCamera();
              return;
            }
            if (tipo_qr === 'plazo_venta') {
              refrescarComprobanteCobrarPlazoVentaDesdeCamera();
              return;
            }
          }
        }
      });
    }

    if (idVentaComprobantes > 0) {
      cargarComprobantesVenta(idVentaComprobantes, false);
    }
    if (idVentaFotosTicket > 0) {
      cargarFotosArticulosVentaTicket(idVentaFotosTicket, false);
    }

    if (idVentaFichaPage > 0) {
      cargarAccionesVentaFicha();
    }

    const modalQrArt = document.getElementById('modalFotoMovilArticulosVenta');
    if (modalQrArt) {
      modalQrArt.addEventListener('shown.bs.modal', function () {
        if (visorQrArticuloVentaPollTimer) {
          clearInterval(visorQrArticuloVentaPollTimer);
        }
        visorQrArticuloVentaPollTimer = setInterval(function () {
          if (idVentaFotosTicket > 0) {
            cargarFotosArticulosVentaTicket(idVentaFotosTicket, true);
          }
        }, 2500);
      });
      modalQrArt.addEventListener('hidden.bs.modal', function () {
        if (visorQrArticuloVentaPollTimer) {
          clearInterval(visorQrArticuloVentaPollTimer);
          visorQrArticuloVentaPollTimer = null;
        }
      });
    }
  });

  function cargarComprobantesVenta(idVenta, silent) {
    silent = !!silent;
    const contenedor = document.getElementById('contenedor_imagenes');
    const sinImagenes = document.getElementById('sin_imagenes');
    const loading = document.getElementById('loading_imagenes');
    if (!contenedor || !loading) {
      return;
    }
    if (!silent) {
      loading.style.display = 'block';
      contenedor.innerHTML = '';
      if (sinImagenes) {
        sinImagenes.style.display = 'none';
      }
    }

    var fdImg = new FormData();
    fdImg.append('tipo', 'venta');
    fdImg.append('id', String(idVenta));
    fdImg.append('id_sucursal', String(idSucursalVenta || 0));

    fetch('camera/api/list_imagenes.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: fdImg
    })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      })
      .then(function (data) {
        if (!silent) {
          loading.style.display = 'none';
        }
        if (data.success && data.imagenes && data.imagenes.length > 0) {
          if (sinImagenes) {
            sinImagenes.style.display = 'none';
          }
          mostrarComprobantesVenta(data.imagenes, idVenta);
        } else if (sinImagenes) {
          if (!silent) {
            sinImagenes.style.display = 'block';
          }
        }
      })
      .catch(function () {
        if (!silent) {
          loading.style.display = 'none';
          if (sinImagenes) {
            sinImagenes.style.display = 'block';
          }
        }
      });
  }

  function mostrarComprobantesVenta(imagenes, idVenta) {
    const contenedor = document.getElementById('contenedor_imagenes');
    if (!contenedor) {
      return;
    }
    contenedor.innerHTML = '';
    imagenes.forEach(function (imagen) {
      const columna = document.createElement('div');
      columna.className = 'col-md-6';
      const esPDF = imagen.foto.toLowerCase().endsWith('.pdf');
      const iconoArchivo = esPDF ? 'ri-file-text-line' : 'ri-image-line';
      const fotoEsc = escapeJsString(imagen.foto);
      const mediaHtml = esPDF
        ? '<div class="pdf-preview" onclick="window.descargarPDFVenta(\'photos/' +
          fotoEsc +
          "', '" +
          fotoEsc +
          '\')"><i class="icon-base ri ' +
          iconoArchivo +
          ' icon-48px text-primary"></i><div class="mt-2"><small class="text-muted">' +
          imagen.foto.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
          '</small></div></div>'
        : '<img src="photos/' +
          imagen.foto.replace(/"/g, '&quot;') +
          '" alt="Comprobante" class="img-fluid w-100" style="cursor:pointer;" onclick="window.ampliarImagenVenta(\'photos/' +
          fotoEsc +
          '\')">';
      columna.innerHTML =
        '<div class="card h-100">' +
        '<div class="card-body p-0">' +
        '<div class="position-relative">' +
        mediaHtml +
        '<div class="position-absolute top-0 end-0 p-2">' +
        '<button type="button" class="btn btn-danger btn-sm" onclick="window.eliminarComprobanteVenta(' +
        imagen.id_foto +
        ", '" +
        fotoEsc +
        "', " +
        idVenta +
        ')" title="Eliminar"><i class="icon-base ri ri-delete-bin-line icon-14px"></i></button>' +
        '</div></div></div></div>';
      contenedor.appendChild(columna);
    });
  }

  window.abrirModalSubirComprobanteVenta = function () {
    const form = document.getElementById('formSubirComprobanteVenta');
    if (form) {
      form.reset();
    }
    const modalEl = document.getElementById('modalSubirComprobanteVenta');
    if (modalEl) {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }
  };

  window.subirComprobanteVenta = function () {
    if (idVentaComprobantes <= 0) {
      return;
    }
    const archivoInput = document.getElementById('archivo_comprobante_venta');
    const modalEl = document.getElementById('modalSubirComprobanteVenta');
    const btnSubir = modalEl ? modalEl.querySelector('.btn-primary') : null;
    const spinner = btnSubir ? btnSubir.querySelector('.spinner-border') : null;

    if (!archivoInput || !archivoInput.files[0]) {
      Swal.fire({ title: 'Atención', text: 'Seleccione un archivo.', icon: 'warning' });
      return;
    }
    const archivo = archivoInput.files[0];
    if (archivo.size > 5 * 1024 * 1024) {
      Swal.fire({ title: 'Error', text: 'Máximo 5 MB.', icon: 'error' });
      return;
    }
    const ext = archivo.name.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'gif', 'png', 'pdf'].indexOf(ext) === -1) {
      Swal.fire({ title: 'Error', text: 'Formato no permitido.', icon: 'error' });
      return;
    }

    const formData = new FormData();
    formData.append('archivo_foto', archivo);
    formData.append('id_venta', String(idVentaComprobantes));

    if (btnSubir) {
      btnSubir.disabled = true;
    }
    if (spinner) {
      spinner.classList.remove('d-none');
    }

    fetch('parts/ventas/main/subir_comprobante_venta.php', { method: 'POST', body: formData })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (btnSubir) {
          btnSubir.disabled = false;
        }
        if (spinner) {
          spinner.classList.add('d-none');
        }
        if (data.success) {
          const inst = bootstrap.Modal.getInstance(modalEl);
          if (inst) {
            inst.hide();
          }
          Swal.fire({ title: 'Listo', text: data.message || 'Subido', icon: 'success', timer: 2500, showConfirmButton: false });
          cargarComprobantesVenta(idVentaComprobantes);
        } else {
          throw new Error(data.error || 'Error');
        }
      })
      .catch(function (e) {
        if (btnSubir) {
          btnSubir.disabled = false;
        }
        if (spinner) {
          spinner.classList.add('d-none');
        }
        Swal.fire({ title: 'Error', text: e.message || 'No se pudo subir', icon: 'error' });
      });
  };

  window.ampliarImagenVenta = function (ruta) {
    const img = document.getElementById('imagen_ampliada_venta');
    const modalEl = document.getElementById('modalAmpliarImagenVenta');
    if (img && modalEl) {
      // Asegura que el modal esté al nivel del body para evitar problemas de z-index/stacking context
      if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
      }
      const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
      img.onload = function () {
        inst.show();
      };
      img.onerror = function () {
        try {
          inst.hide();
        } catch (e) {}
        if (typeof Swal !== 'undefined') {
          Swal.fire({ title: 'Error', text: 'No se pudo cargar la imagen', icon: 'error' });
        }
      };
      const raw = ruta != null ? String(ruta) : '';
      const sep = raw.includes('?') ? '&' : '?';
      img.src = raw + sep + 't=' + Date.now();
    }
  };

  window.ampliarComprobanteAdelantoVentaFicha = function (nombreArchivo) {
    const raw = nombreArchivo != null ? String(nombreArchivo).trim() : '';
    if (raw === '' || !/^[a-zA-Z0-9_\-\.]+$/.test(raw)) {
      return;
    }
    const ruta = 'photos/' + raw;
    if (raw.toLowerCase().endsWith('.pdf')) {
      window.descargarPDFVenta(ruta, raw);
      return;
    }
    window.ampliarImagenVenta(ruta);
  };

  window.ampliarComprobantePlazoVentaFicha = function (nombreArchivo) {
    const raw = nombreArchivo != null ? String(nombreArchivo).trim() : '';
    if (raw === '' || !/^[a-zA-Z0-9_\-\.]+$/.test(raw)) {
      return;
    }
    const ruta = 'photos/' + raw;
    if (raw.toLowerCase().endsWith('.pdf')) {
      window.descargarPDFVenta(ruta, raw);
      return;
    }
    window.ampliarImagenVenta(ruta);
  };

  window._datosAdelantoVenta = null;

  function fmtNumberDot2(n) {
    const x = Number(n);
    if (!isFinite(x)) {
      return '—';
    }
    return (Math.round(x * 100) / 100).toFixed(2);
  }

  function fmtEuroAdelantoVenta(n) {
    const s = fmtNumberDot2(n);
    return s === '—' ? '—' : s + ' €';
  }

  function normalizarPayloadAdelantoVenta(raw) {
    if (!raw || typeof raw !== 'object') {
      return null;
    }
    return {
      success: !!raw.success,
      capital_actual: parseFloat(raw.capital_actual) || 0,
      total_pendiente: parseFloat(raw.total_pendiente) || 0,
      plazos_pagados: parseInt(String(raw.plazos_pagados || 0), 10) || 0,
      numero_plazos: parseInt(String(raw.numero_plazos || 0), 10) || 0,
      plazos_pendientes: parseInt(String(raw.plazos_pendientes || 0), 10) || 0,
      importe_plazo_antiguo: parseFloat(raw.importe_plazo_antiguo) || 0,
      porcentaje_gastos_adelantos: parseFloat(raw.porcentaje_gastos_adelantos) || 0,
      id_venta_sucursal: parseInt(String(raw.id_venta_sucursal || 0), 10) || 0,
      id_sucursal: parseInt(String(raw.id_sucursal || 0), 10) || 0
    };
  }

  function aplicarResumenAdelantoCapitalVentaAlModal() {
    const d = window._datosAdelantoVenta;
    if (!d) {
      return;
    }
    const cap = document.getElementById('capital_actual_view_venta');
    const capPend = document.getElementById('capital_pendiente_view_venta');
    const cuo = document.getElementById('importe_cuota_actual_view_venta');
    const plz = document.getElementById('plazos_info_view_venta');
    const det = document.getElementById('detalle_gastos_adelanto_venta');
    if (cap) {
      cap.innerHTML = fmtEuroAdelantoVenta(d.capital_actual);
    }
    if (capPend) {
      capPend.innerHTML = fmtEuroAdelantoVenta(d.total_pendiente);
    }
    if (cuo) {
      cuo.innerHTML = fmtEuroAdelantoVenta(d.importe_plazo_antiguo);
    }
    if (plz) {
      plz.innerHTML =
        String(d.plazos_pagados || 0) + ' de ' + String(d.numero_plazos || 0);
    }
  }

  function ventaParseRespuestaJsonFetch(r) {
    const ct = (r.headers.get('content-type') || '').toLowerCase();
    if (ct.includes('application/json')) {
      return r.json();
    }
    return r.text().then(function (txt) {
      const s = (txt || '').replace(/^\uFEFF/, '').trim();
      if (!s) {
        throw new Error('Respuesta vacía del servidor');
      }
      try {
        return JSON.parse(s);
      } catch (ignore) {
        throw new Error(s.slice(0, 200));
      }
    });
  }

  window.anularVentaPlazos = function () {
    if (!ventaPermiteAnularPlazos || idVentaFichaPage <= 0) {
      return;
    }

    Swal.fire({
      icon: 'warning',
      title: 'Anular venta a plazos!',
      html: ''
        + '<p class="mb-3">¿Está seguro que desea anular esta venta a plazos?</p>'
        + '<div class="form-group text-start">'
        + '<label for="motivo_anulacion_venta_plazos" class="form-label">Motivo de la anulación</label>'
        + '<textarea id="motivo_anulacion_venta_plazos" name="motivo_anulacion" class="form-control" rows="4" '
        + 'placeholder="Ingrese el motivo de la anulación..." style="width: 100%; min-height: 100px;"></textarea>'
        + '</div>',
      showCancelButton: true,
      confirmButtonText: 'Anular venta a plazos',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      reverseButtons: true,
      focusCancel: true,
      preConfirm: function () {
        const motivo = (document.getElementById('motivo_anulacion_venta_plazos') || {}).value;
        const texto = String(motivo || '').trim();
        if (!texto) {
          Swal.showValidationMessage('El motivo de la anulación es obligatorio');
          return false;
        }
        return texto;
      }
    }).then(function (result) {
      if (!result.isConfirmed || !result.value) {
        return;
      }

      Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: function () {
          Swal.showLoading();
        }
      });

      const formData = new FormData();
      formData.append('id_venta', String(idVentaFichaPage));
      formData.append('motivo_anulacion', result.value);

      fetch(ventaApiUrl('parts/ventas/main/anular_venta_plazos.php'), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (response) {
          return ventaParseRespuestaJsonFetch(response);
        })
        .then(function (data) {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: '¡Éxito!',
              text: data.message || 'Venta a plazos anulada correctamente',
              confirmButtonText: 'Aceptar'
            }).then(function () {
              window.location.reload();
            });
            return;
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || data.error || 'Error al anular la venta a plazos',
            confirmButtonText: 'Aceptar'
          });
        })
        .catch(function (error) {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al procesar la solicitud. Por favor, intente nuevamente.',
            confirmButtonText: 'Aceptar'
          });
        });
    });
  };

  window.recuperarPlazoVenta = function (idPlazo) {
    if (!idPlazo || idVentaFichaPage <= 0) {
      return;
    }

    Swal.fire({
      title: 'Recuperar plazo',
      text: 'Se revertirá el cobro de este plazo.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, recuperar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-danger me-3 waves-effect waves-light',
        cancelButton: 'btn btn-outline-secondary waves-effect'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      const formData = new FormData();
      formData.append('id_venta', String(idVentaFichaPage));
      formData.append('id_plazo', String(idPlazo));

      fetch(ventaApiUrl('parts/ventas/main/recuperar_plazo_venta.php'), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (response) {
          return ventaParseRespuestaJsonFetch(response);
        })
        .then(function (data) {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Plazo recuperado',
              text: data.message || 'Operación completada'
            }).then(function () {
              window.location.reload();
            });
            return;
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'No se pudo recuperar el plazo'
          });
        })
        .catch(function (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al procesar la solicitud'
          });
        });
    });
  };

  window.eliminarPlazoVenta = function (idPlazo) {
    if (!idPlazo || idVentaFichaPage <= 0) {
      return;
    }

    Swal.fire({
      title: 'Eliminar plazo',
      text: '¿Está seguro de que desea eliminar este plazo?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      const formData = new FormData();
      formData.append('id_venta', String(idVentaFichaPage));
      formData.append('id_plazo', String(idPlazo));

      fetch(ventaApiUrl('parts/ventas/main/eliminar_plazo_venta.php'), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (response) {
          return ventaParseRespuestaJsonFetch(response);
        })
        .then(function (data) {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Plazo eliminado',
              text: data.message || 'Operación completada'
            }).then(function () {
              window.location.reload();
            });
            return;
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'No se pudo eliminar el plazo'
          });
        })
        .catch(function (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al procesar la solicitud'
          });
        });
    });
  };

  function ventaPlazosToggleBtnAnadir(mostrar) {
    const btn = document.getElementById('btnAnadirPlazoVenta');
    if (!btn) {
      return;
    }
    if (window._anadirPlazoVentaOcultoPermanente) {
      btn.classList.add('d-none');
      return;
    }
    if (mostrar) {
      btn.classList.remove('d-none');
    } else {
      btn.classList.add('d-none');
    }
  }

  window.anadirPlazoVenta = function () {
    if (idVentaFichaPage <= 0 || window._anadirPlazoVentaOcultoPermanente) {
      return;
    }

    Swal.fire({
      title: 'Añadir plazo',
      text: 'Se generará la siguiente cuota con la misma lógica que tras cobrar un plazo (importe del último cobrado, vencimiento +1 mes). ¿Desea continuar?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, añadir',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-outline-secondary waves-effect'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }

      const formData = new FormData();
      formData.append('id_venta', String(idVentaFichaPage));

      fetch(ventaApiUrl('parts/ventas/main/anadir_plazo_venta.php'), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (response) {
          return ventaParseRespuestaJsonFetch(response);
        })
        .then(function (prep) {
          if (!prep.success) {
            throw new Error(prep.message || 'No se pudo preparar la venta');
          }
          const fdComp = new FormData();
          fdComp.append('id_venta', String(idVentaFichaPage));
          return fetch(ventaApiUrl('parts/ventas/main/comprobar_plazos.php'), {
            method: 'POST',
            body: fdComp,
            credentials: 'same-origin'
          }).then(function (response) {
            return ventaParseRespuestaJsonFetch(response);
          });
        })
        .then(function (comp) {
          if (!comp.success) {
            throw new Error(comp.message || 'No se pudo comprobar los plazos');
          }
          if (comp.accion !== 'plazo_creado') {
            throw new Error(comp.message || 'No se generó ningún plazo nuevo');
          }
          window._anadirPlazoVentaOcultoPermanente = true;
          ventaPlazosToggleBtnAnadir(false);
          Swal.fire({
            icon: 'success',
            title: 'Plazo añadido',
            text: comp.message || 'Siguiente cuota pendiente generada'
          }).then(function () {
            window.location.reload();
          });
        })
        .catch(function (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al procesar la solicitud'
          });
        });
    });
  };

  function vfpFmtFechaPlazoModal(valor) {
    const v = String(valor || '').trim();
    if (!v || v.indexOf('0000-00-00') === 0) {
      return '—';
    }
    const parte = v.length >= 10 ? v.substring(0, 10) : v;
    const p = parte.split('-');
    if (p.length !== 3) {
      return v;
    }
    return p[2] + '/' + p[1] + '/' + p[0];
  }

  function vfpFmtFechaHoraPlazoModal(valor) {
    const v = String(valor || '').trim();
    if (!v || v.indexOf('0000-00-00') === 0) {
      return '—';
    }
    if (v.length <= 10) {
      return vfpFmtFechaPlazoModal(v);
    }
    const fecha = vfpFmtFechaPlazoModal(v.substring(0, 10));
    const hora = v.substring(11, 16);
    return hora ? fecha + ' ' + hora : fecha;
  }

  function vfpBadgeEstadoPlazoModal(estado) {
    const e = String(estado || '');
    if (e === 'Pagado') {
      return '<div class="badge bg-success rounded-pill lh-xs badget-estados-tablas">Pagado</div>';
    }
    if (e === 'Pendiente') {
      return '<div class="badge bg-label-primary rounded-pill lh-xs badget-estados-tablas">Pendiente</div>';
    }
    if (e === 'Vencido') {
      return '<div class="badge bg-label-warning rounded-pill lh-xs badget-estados-tablas">Vencido</div>';
    }
    return '<div class="badge bg-label-secondary rounded-pill lh-xs badget-estados-tablas">' + (e || '—') + '</div>';
  }

  function vfpLabelMetodoPagoPlazo(metodo) {
    const m = String(metodo || '').toLowerCase().trim();
    if (m === 'tarjeta') return 'Tarjeta';
    if (m === 'contado') return 'Contado';
    if (m === 'bizum') return 'Bizum';
    if (m === 'transferencia') return 'Transferencia';
    if (m === 'combinado') return 'Combinado';
    return metodo || '—';
  }

  window.abrirModalEditarPlazoVenta = function (idPlazo) {
    if (!idPlazo || idVentaFichaPage <= 0) {
      return;
    }

    const modalEl = document.getElementById('modalEditarPlazoVenta');
    if (!modalEl) {
      return;
    }

    const formData = new FormData();
    formData.append('id_venta', String(idVentaFichaPage));
    formData.append('id_plazo', String(idPlazo));

    fetch(ventaApiUrl('parts/ventas/main/get_datos_plazo_venta.php'), {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
      .then(function (response) {
        return ventaParseRespuestaJsonFetch(response);
      })
      .then(function (data) {
        if (!data.success || !data.plazo) {
          throw new Error(data.message || 'No se pudieron cargar los datos del plazo');
        }
        const plazo = data.plazo;
        const hidPlazo = document.getElementById('id_plazo_editar_venta');
        const inpImporte = document.getElementById('editar_plazo_importe');
        if (hidPlazo) {
          hidPlazo.value = String(plazo.id);
        }
        const elNum = document.getElementById('editar_plazo_numero_cuota');
        if (elNum) {
          elNum.textContent = String(plazo.numero_cuota || '—');
        }
        const elEst = document.getElementById('editar_plazo_estado');
        if (elEst) {
          elEst.innerHTML = vfpBadgeEstadoPlazoModal(plazo.estado);
        }
        const elFv = document.getElementById('editar_plazo_fecha_vencimiento');
        if (elFv) {
          elFv.textContent = vfpFmtFechaPlazoModal(plazo.fecha_vencimiento);
        }
        const elFc = document.getElementById('editar_plazo_fecha_cobrado');
        if (elFc) {
          elFc.textContent = vfpFmtFechaHoraPlazoModal(plazo.fecha_cobrado);
        }
        const elMp = document.getElementById('editar_plazo_metodo_pago');
        if (elMp) {
          elMp.textContent = vfpLabelMetodoPagoPlazo(plazo.metodo_pago);
        }
        if (inpImporte) {
          inpImporte.value = Number(plazo.importe || 0).toFixed(2);
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
      })
      .catch(function (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message || 'Error al cargar el plazo'
        });
      });
  };

  const formEditarPlazoVentaModal = document.getElementById('formEditarPlazoVentaModal');
  if (formEditarPlazoVentaModal) {
    formEditarPlazoVentaModal.addEventListener('submit', function (ev) {
      ev.preventDefault();

      const idPlazo = parseInt(document.getElementById('id_plazo_editar_venta')?.value || '0', 10);
      const importe = parseFloat(document.getElementById('editar_plazo_importe')?.value || '0');
      if (!idPlazo || idVentaFichaPage <= 0 || !(importe > 0)) {
        Swal.fire({
          icon: 'warning',
          title: 'Datos incompletos',
          text: 'Indique un importe válido'
        });
        return;
      }

      const btnGuardar = document.getElementById('btnGuardarEditarPlazoVenta');
      if (btnGuardar) {
        btnGuardar.disabled = true;
      }

      const formData = new FormData();
      formData.append('id_venta', String(idVentaFichaPage));
      formData.append('id_plazo', String(idPlazo));
      formData.append('importe', String(importe));

      fetch(ventaApiUrl('parts/ventas/main/editar_plazo_venta.php'), {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (response) {
          return ventaParseRespuestaJsonFetch(response);
        })
        .then(function (data) {
          if (btnGuardar) {
            btnGuardar.disabled = false;
          }
          if (data.success) {
            const modalEl = document.getElementById('modalEditarPlazoVenta');
            if (modalEl) {
              const inst = bootstrap.Modal.getInstance(modalEl);
              if (inst) {
                inst.hide();
              }
            }
            Swal.fire({
              icon: 'success',
              title: 'Plazo actualizado',
              text: data.message || 'Operación completada'
            }).then(function () {
              window.location.reload();
            });
            return;
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'No se pudo actualizar el plazo'
          });
        })
        .catch(function (error) {
          if (btnGuardar) {
            btnGuardar.disabled = false;
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al procesar la solicitud'
          });
        });
    });
  }

  window.abrirModalAdelantoCapitalVenta = function () {
    if (!ventaPermiteAdelantoCapital || idVentaFichaPage <= 0) {
      return;
    }
    const fd = new URLSearchParams();
    fd.set('id_venta', String(idVentaFichaPage));
    fetch(ventaApiUrl('parts/ventas/main/get_datos_adelanto_capital_venta.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: fd.toString(),
      credentials: 'same-origin'
    })
      .then(function (r) {
        if (!r.ok) {
          return r.text().then(function (txt) {
            throw new Error(txt ? txt.slice(0, 200) : 'HTTP ' + r.status);
          });
        }
        return ventaParseRespuestaJsonFetch(r);
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.message || 'No se pudieron cargar los datos');
        }
        const norm = normalizarPayloadAdelantoVenta(data);
        window._datosAdelantoVenta = norm;
        const m = document.getElementById('modalAdelantoCapitalVenta');
        if (!m) {
          throw new Error('No se encontró el modal de adelanto en la página');
        }
        aplicarResumenAdelantoCapitalVentaAlModal();
        const hid = document.getElementById('id_venta_adelanto_capital');
        if (hid) {
          hid.value = String(idVentaFichaPage);
        }
        const inp = document.getElementById('adelanto_cliente_venta');
        if (inp) {
          inp.value = '';
        }
        const tot = document.getElementById('total_cobrar_adelanto_venta');
        if (tot) {
          tot.innerHTML = '0 €';
        }
        const nc = document.getElementById('nuevo_capital_view_venta');
        const ni = document.getElementById('nuevo_importe_plazo_view_venta');
        if (nc) {
          nc.innerHTML = '—';
        }
        if (ni) {
          ni.innerHTML = '—';
        }
        const btn = document.getElementById('btnAdelantoCapitalVentaModal');
        if (btn) {
          btn.disabled = true;
        }
        bootstrap.Modal.getOrCreateInstance(m).show();
        aplicarResumenAdelantoCapitalVentaAlModal();
        window.calcularAdelantoCapitalVenta();
      })
      .catch(function (e) {
        Swal.fire({ title: 'Error', text: e.message || 'Error', icon: 'error' });
      });
  };

  window.calcularAdelantoCapitalVenta = function () {
    const d = window._datosAdelantoVenta;
    const inp = document.getElementById('adelanto_cliente_venta');
    if (!d || !inp) {
      return;
    }
    const adelanto = parseFloat(String(inp.value).replace(',', '.')) || 0;
    const totalPend = Number(d.total_pendiente) || 0;
    const plzPend = Math.max(1, parseInt(String(d.plazos_pendientes), 10) || 1);
    const porc = Number(d.porcentaje_gastos_adelantos) || 0;
    let nuevoCapital = 0;
    let nuevoImportePlazo = 0;
    let gastos = 0;
    let totalCobrar = 0;

    const capitalPendienteCent = Math.round(totalPend * 100);
    const adelantoCent = Math.round(adelanto * 100);
    const adelantoValido = adelantoCent > 0 && adelantoCent <= capitalPendienteCent;
    if (adelantoValido) {
      nuevoCapital = Math.round((totalPend - adelanto) * 100) / 100;
      const resto = Math.round((totalPend - adelanto) * 100) / 100;
      nuevoImportePlazo = Math.round((resto / plzPend) * 100) / 100;
      gastos = Math.round(((adelanto * porc) / 100) * 100) / 100;
      totalCobrar = Math.round((adelanto + gastos) * 100) / 100;
    }

    const elNc = document.getElementById('nuevo_capital_view_venta');
    const elNi = document.getElementById('nuevo_importe_plazo_view_venta');
    const elTot = document.getElementById('total_cobrar_adelanto_venta');
    if (elNc) {
      elNc.innerHTML = adelantoValido ? fmtEuroAdelantoVenta(nuevoCapital) : '—';
    }
    if (elNi) {
      elNi.innerHTML = adelantoValido ? fmtEuroAdelantoVenta(nuevoImportePlazo) : '—';
    }
    if (elTot) {
      elTot.innerHTML = adelantoValido ? fmtEuroAdelantoVenta(totalCobrar) : '0 €';
    }

    const contFp = document.getElementById('forma_de_pago_adelanto_venta_container');
    if (adelantoValido) {
      if (contFp) {
        contFp.classList.remove('display_none');
        aplicarLimiteFacturaSimplificadaRadioEfectivo(
          'adelanto_venta_forma_de_pago_efectivo',
          'adelanto_venta_forma_de_pago_tarjeta',
          null
        );
      }
    } else {
      if (contFp) {
        contFp.classList.add('display_none');
      }
    }
    actualizarEstadoBotonAdelantoCapitalVenta();
  };

  function actualizarEstadoBotonAdelantoCapitalVenta() {
    const d = window._datosAdelantoVenta;
    const inp = document.getElementById('adelanto_cliente_venta');
    const btnOk = document.getElementById('btnAdelantoCapitalVentaModal');
    if (!btnOk) {
      return;
    }
    if (!d || !inp) {
      btnOk.disabled = true;
      return;
    }
    const adelanto = parseFloat(String(inp.value).replace(',', '.')) || 0;
    const totalPend = Number(d.total_pendiente) || 0;
    const capitalPendienteCent = Math.round(totalPend * 100);
    const adelantoCent = Math.round(adelanto * 100);
    const okMonto = adelantoCent > 0 && adelantoCent <= capitalPendienteCent;
    if (!okMonto) {
      btnOk.disabled = true;
      return;
    }
    const hidFp = document.getElementById('forma_de_pago_adelanto_venta');
    const fp = hidFp ? hidFp.value : 'efectivo';
    const archivoInput = document.getElementById('comprobante_adelanto_venta_archivo');
    if (fp === 'efectivo') {
      btnOk.disabled = false;
      return;
    }
    const fc = document.getElementById('foto_camara_adelanto_venta');
    const fotoCamara = fc ? String(fc.value) === 'true' : false;
    if (fotoCamara) {
      const hidCache = document.getElementById('id_foto_cache_adelanto_venta');
      const idCache = hidCache ? parseInt(String(hidCache.value || 0), 10) || 0 : 0;
      const cont = document.getElementById('contenedor_imagenes_comprobante_adelanto_venta');
      const hayVista =
        !!(window._adelantoVentaComprobanteNombre && String(window._adelantoVentaComprobanteNombre).trim() !== '') ||
        !!(cont && (cont.querySelector('img') || cont.querySelector('a[href]')));
      btnOk.disabled = !(idCache > 0 && hayVista);
      return;
    }
    btnOk.disabled = !(archivoInput && archivoInput.files && archivoInput.files[0]);
  }

  function initModalAdelantoCapitalVentaListeners() {
    const modalAdelantoCapitalVenta = document.getElementById('modalAdelantoCapitalVenta');
    if (!modalAdelantoCapitalVenta || modalAdelantoCapitalVenta.dataset._adelantoListeners === '1') {
      return;
    }
    modalAdelantoCapitalVenta.dataset._adelantoListeners = '1';

    modalAdelantoCapitalVenta.addEventListener('keydown', function (e) {
      const el = e.target;
      if (!el || el.id !== 'adelanto_cliente_venta') {
        return;
      }
      if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
        (e.keyCode === 65 && e.ctrlKey === true) ||
        (e.keyCode === 67 && e.ctrlKey === true) ||
        (e.keyCode === 86 && e.ctrlKey === true) ||
        (e.keyCode === 88 && e.ctrlKey === true) ||
        (e.keyCode >= 35 && e.keyCode <= 39)) {
        return;
      }
      if (e.key === 'e' || e.key === 'E' || e.key === '+' || e.key === '-') {
        e.preventDefault();
        return;
      }
      const sepKey =
        e.key === '.' ||
        e.key === ',' ||
        e.keyCode === 190 ||
        e.keyCode === 188 ||
        e.keyCode === 110;
      if (sepKey) {
        const v = String(el.value || '');
        const a = el.selectionStart != null ? el.selectionStart : v.length;
        const b = el.selectionEnd != null ? el.selectionEnd : v.length;
        const sinSel = v.slice(0, a) + v.slice(b);
        const sinSelNorm = sinSel.replace(/,/g, '.');
        if (sinSelNorm.indexOf('.') !== -1) {
          e.preventDefault();
        }
        return;
      }
      if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
        e.preventDefault();
      }
    });
    modalAdelantoCapitalVenta.addEventListener('input', function (e) {
      const t = e.target;
      if (!t || t.id !== 'adelanto_cliente_venta') {
        return;
      }
      let s = String(t.value || '').replace(/,/g, '.');
      s = s.replace(/[^0-9.]/g, '');
      const d = s.indexOf('.');
      if (d !== -1) {
        s = s.slice(0, d + 1) + s.slice(d + 1).replace(/\./g, '');
      }
      const partes = s.split('.');
      if (partes.length === 2 && partes[1].length > 2) {
        s = partes[0] + '.' + partes[1].slice(0, 2);
      }
      t.value = s;
      window.calcularAdelantoCapitalVenta();
    });

    function adelantoVentaMarcarRadioChecked(radio) {
      modalAdelantoCapitalVenta.querySelectorAll('.forma_de_pago_adelanto_venta').forEach(function (r) {
        const wrap = r.closest('.custom-option-blue');
        if (wrap) {
          wrap.classList.remove('checked');
        }
      });
      if (radio) {
        radio.checked = true;
        const w = radio.closest('.custom-option-blue');
        if (w) {
          w.classList.add('checked');
        }
      }
    }

    function adelantoVentaResetVistaComprobante() {
      window._adelantoVentaComprobanteNombre = '';
      const cont = modalAdelantoCapitalVenta.querySelector('#contenedor_imagenes_comprobante_adelanto_venta');
      if (cont) {
        cont.innerHTML =
          '<i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>' +
          '<p class="text-body-secondary mb-0">No hay comprobante cargado</p>';
      }
      const inp = modalAdelantoCapitalVenta.querySelector('#comprobante_adelanto_venta_archivo');
      if (inp) {
        inp.value = '';
      }
      const hidId = modalAdelantoCapitalVenta.querySelector('#id_foto_cache_adelanto_venta');
      if (hidId) {
        hidId.value = '';
      }
      const fc = modalAdelantoCapitalVenta.querySelector('#foto_camara_adelanto_venta');
      if (fc) {
        fc.value = 'false';
      }
      actualizarEstadoBotonAdelantoCapitalVenta();
    }

    modalAdelantoCapitalVenta.addEventListener('shown.bs.modal', function () {
      adelantoVentaResetVistaComprobante();
      aplicarResumenAdelantoCapitalVentaAlModal();
      window.calcularAdelantoCapitalVenta();
      aplicarLimiteFacturaSimplificadaRadioEfectivo(
        'adelanto_venta_forma_de_pago_efectivo',
        'adelanto_venta_forma_de_pago_tarjeta',
        adelantoVentaMarcarRadioChecked
      );
    });

    modalAdelantoCapitalVenta.querySelectorAll('.forma_de_pago_adelanto_venta').forEach(function (input) {
      input.addEventListener('change', function () {
        const forma = this.value;
        const hidForma = modalAdelantoCapitalVenta.querySelector('#forma_de_pago_adelanto_venta');
        const contComp = modalAdelantoCapitalVenta.querySelector('#comprobante_adelanto_venta_container');
        const lbl = modalAdelantoCapitalVenta.querySelector('#modalAdelantoCapitalVentaLabel');
        adelantoVentaMarcarRadioChecked(this);
        if (hidForma) {
          hidForma.value = forma;
        }
        if (forma === 'efectivo') {
          if (lbl) {
            lbl.textContent = 'Adelanto de capital (efectivo)';
          }
          if (contComp) {
            contComp.classList.add('display_none');
          }
          adelantoVentaResetVistaComprobante();
        } else {
          if (lbl) {
            lbl.textContent =
              forma === 'tarjeta'
                ? 'Adelanto de capital (tarjeta)'
                : forma === 'transferencia'
                  ? 'Adelanto de capital (transferencia)'
                  : 'Adelanto de capital (bizum)';
          }
          if (contComp) {
            contComp.classList.remove('display_none');
          }
        }
        actualizarEstadoBotonAdelantoCapitalVenta();
      });
    });

    const inpCompAdel = modalAdelantoCapitalVenta.querySelector('#comprobante_adelanto_venta_archivo');
    if (inpCompAdel) {
      inpCompAdel.addEventListener('change', function () {
        const archivoInput = modalAdelantoCapitalVenta.querySelector('#comprobante_adelanto_venta_archivo');
        const contenedor_imagenes = modalAdelantoCapitalVenta.querySelector('#contenedor_imagenes_comprobante_adelanto_venta');
        const btnOk = modalAdelantoCapitalVenta.querySelector('#btnAdelantoCapitalVentaModal');
        if (!archivoInput || !archivoInput.files[0] || !contenedor_imagenes) {
          actualizarEstadoBotonAdelantoCapitalVenta();
          return;
        }
        const archivo = archivoInput.files[0];
        if (archivo.size > 5 * 1024 * 1024) {
          Swal.fire({
            title: 'Error',
            text: 'El archivo no puede superar los 5MB',
            icon: 'error',
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#007bff'
          });
          archivoInput.value = '';
          actualizarEstadoBotonAdelantoCapitalVenta();
          return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
          if (archivo.type === 'application/pdf') {
            contenedor_imagenes.innerHTML =
              '<div class="text-center">' +
              '<i class="icon-base ri ri-file-pdf-line icon-48px text-danger mb-3"></i>' +
              '<p class="text-body-secondary mb-0">' +
              (archivo.name || '') +
              '</p></div>';
          } else {
            contenedor_imagenes.innerHTML =
              '<div class="position-relative">' +
              '<img src="' +
              e.target.result +
              '" alt="Comprobante" class="img-fluid">' +
              '</div>';
          }
          const fc = modalAdelantoCapitalVenta.querySelector('#foto_camara_adelanto_venta');
          if (fc) {
            fc.value = 'false';
          }
          if (btnOk) {
            btnOk.disabled = false;
          }
          actualizarEstadoBotonAdelantoCapitalVenta();
        };
        reader.readAsDataURL(archivo);
      });
    }

    const formAdelanto = modalAdelantoCapitalVenta.querySelector('#formAdelantoCapitalVentaModal');
    if (formAdelanto) {
      formAdelanto.addEventListener('submit', function (e) {
        e.preventDefault();
        const form = modalAdelantoCapitalVenta.querySelector('#formAdelantoCapitalVentaModal');
        const btnAd = modalAdelantoCapitalVenta.querySelector('#btnAdelantoCapitalVentaModal');
        const archivoInput = modalAdelantoCapitalVenta.querySelector('#comprobante_adelanto_venta_archivo');
        if (!form.checkValidity()) {
          form.classList.add('was-validated');
          return;
        }
        if (!btnAd) {
          return;
        }
        btnAd.disabled = true;
        const btnTextoOriginal = btnAd.innerHTML;
        btnAd.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

        const formData = new FormData();
        formData.append('id_venta', modalAdelantoCapitalVenta.querySelector('#id_venta_adelanto_capital').value);
        formData.append('adelanto_cliente', modalAdelantoCapitalVenta.querySelector('#adelanto_cliente_venta').value);
        formData.append('forma_de_pago', modalAdelantoCapitalVenta.querySelector('#forma_de_pago_adelanto_venta').value);
        formData.append('foto_camara', modalAdelantoCapitalVenta.querySelector('#foto_camara_adelanto_venta').value);
        formData.append('id_foto_cache_adelanto_venta', modalAdelantoCapitalVenta.querySelector('#id_foto_cache_adelanto_venta').value);
        if (archivoInput && archivoInput.files[0]) {
          formData.append('comprobante_adelanto_venta_archivo', archivoInput.files[0]);
        }

        fetch(ventaApiUrl('parts/ventas/main/insertar_adelanto_capital_venta.php'), { method: 'POST', body: formData, credentials: 'same-origin' })
          .then(function (r) {
            return r.json();
          })
          .then(function (data) {
            if (data.success) {
              Swal.fire({
                title: 'Listo',
                text: data.message || 'Adelanto registrado',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#007bff'
              }).then(function () {
                const inst = bootstrap.Modal.getInstance(modalAdelantoCapitalVenta);
                if (inst) {
                  inst.hide();
                }
                cargarAccionesVentaFicha();
                if (window.dtVentasPlazosFicha && typeof window.dtVentasPlazosFicha.ajax === 'object') {
                  window.dtVentasPlazosFicha.ajax.reload(null, false);
                }
                if (window.dtAdelantosCapitalVentaFicha && typeof window.dtAdelantosCapitalVentaFicha.ajax === 'object') {
                  window.dtAdelantosCapitalVentaFicha.ajax.reload(null, false);
                }
              });
            } else {
              throw new Error(data.message || 'Error al registrar el adelanto');
            }
          })
          .catch(function (err) {
            Swal.fire({
              title: 'Error',
              text: err.message || 'Error al registrar el adelanto',
              icon: 'error',
              confirmButtonText: 'Aceptar',
              confirmButtonColor: '#007bff'
            });
          })
          .finally(function () {
            btnAd.disabled = false;
            btnAd.innerHTML = btnTextoOriginal;
            actualizarEstadoBotonAdelantoCapitalVenta();
          });
      });
    }
  }

  function scheduleInitModalAdelantoCapitalVentaListeners() {
    function go() {
      initModalAdelantoCapitalVentaListeners();
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', go);
    } else {
      go();
    }
  }
  scheduleInitModalAdelantoCapitalVentaListeners();

  function fmtEuroVentaFicha(n) {
    var v = Math.round((Number(n) || 0) * 100) / 100;
    var parts = v.toFixed(2).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return parts.join(',') + ' €';
  }

  window.actualizarBadgesResumenVentaFicha = function (resumen) {
    if (!resumen) {
      return;
    }
    var elEst = document.getElementById('badge_estado_venta_ficha');
    if (elEst) {
      elEst.className = 'badge bg-label-' + (resumen.estado_class || 'secondary') + ' rounded-pill lh-xs badget-estados';
      elEst.textContent = 'Estado: ' + String(resumen.estado_texto || '');
    }
    var elPag = document.getElementById('badge_plazos_pagados_venta_ficha');
    if (elPag) {
      elPag.textContent = 'Plazos pagados: ' + (parseInt(resumen.plazos_pagados, 10) || 0);
    }
    var elPend = document.getElementById('badge_plazos_pendientes_venta_ficha');
    if (elPend) {
      elPend.textContent = 'Plazos pendientes: ' + (parseInt(resumen.plazos_pendientes, 10) || 0);
    }
    var elTotPag = document.getElementById('badge_total_pagado_venta_ficha');
    if (elTotPag) {
      elTotPag.textContent = 'Total pagado: ' + fmtEuroVentaFicha(resumen.total_pagado);
    }
    var elTotPend = document.getElementById('badge_total_pendiente_venta_ficha');
    if (elTotPend) {
      elTotPend.textContent = 'Total pendiente: ' + fmtEuroVentaFicha(resumen.total_pendiente);
    }
  };

  window.abrirModalCobrarPlazoVenta = function (importe, idPlazo) {
    const idPz = parseInt(String(idPlazo), 10) || 0;
    let imp =
      typeof importe === 'number' && isFinite(importe)
        ? importe
        : parseFloat(String(importe != null ? importe : '').replace(',', '.'));
    if (!isFinite(imp)) {
      imp = 0;
    }
    if (idPz <= 0 || imp <= 0) {
      return;
    }
    const hidPz = document.getElementById('id_plazo_cobrar_venta');
    const hidImp = document.getElementById('importe_plazo_cobrar_venta');
    const hidV = document.getElementById('id_venta_cobrar_plazo');
    if (!hidPz || !hidImp || !hidV) {
      return;
    }
    hidPz.value = String(idPz);
    hidImp.value = String(imp);
    hidV.value = String(idVentaFichaPage);
    const modalEl = document.getElementById('modalCobrarPlazoVenta');
    if (modalEl) {
      new bootstrap.Modal(modalEl).show();
    }
  };

  let qrPlazoVentaPollTimer = null;
  let qrPlazoVentaExpireTimer = null;

  function cobrarPlazoVentaAplicarPreviewComprobante(nombreArchivo) {
    const cont = document.getElementById('contenedor_imagenes_comprobante_cobrar_plazo_venta');
    if (!cont) {
      return;
    }
    const fc = document.getElementById('foto_camara_cobrar_plazo');
    if (fc) {
      fc.value = 'true';
    }
    const inpFile = document.getElementById('comprobante_cobrar_plazo_venta_archivo');
    if (inpFile) {
      inpFile.value = '';
    }
    const raw = nombreArchivo != null ? String(nombreArchivo).trim() : '';
    if (raw === '') {
      window._plazoVentaComprobanteNombre = '';
      cont.innerHTML =
        '<i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>' +
        '<p class="text-body-secondary mb-0">No hay comprobante cargado</p>';
      return;
    }
    const safe = raw.replace(/[^a-zA-Z0-9_\-\.]/g, '');
    window._plazoVentaComprobanteNombre = safe;
    const src = 'photos/' + safe + '?t=' + Date.now();
    if (safe.toLowerCase().endsWith('.pdf')) {
      cont.innerHTML =
        '<div class="text-center">' +
        '<i class="icon-base ri ri-file-pdf-line icon-48px text-danger mb-3"></i>' +
        '<p class="text-body-secondary mb-2">' +
        safe +
        '</p>' +
        '<a class="btn btn-sm btn-primary" href="' +
        src +
        '" target="_blank" rel="noopener noreferrer">Abrir PDF</a>' +
        '</div>';
      return;
    }
    cont.innerHTML =
      '<div class="position-relative">' +
      '<img src="' +
      src +
      '" alt="Comprobante" class="img-fluid" style="cursor:pointer;" data-src="photos/' +
      safe +
      '">' +
      '</div>';
    const img = cont.querySelector('img[data-src]');
    if (img) {
      img.addEventListener('click', function () {
        window.ampliarImagenVenta(this.getAttribute('data-src'));
      });
    }
  }

  function consultarTokenPlazoVenta(token, idToken, idFotoCache) {
    fetch('parts/lotes/main/procesar_consultar_token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ token: token, id_token: idToken })
    })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      })
      .then(function () {
        const idFoto = parseInt(String(idFotoCache || 0), 10) || 0;
        const idVenta = parseInt(String(document.getElementById('id_venta_cobrar_plazo')?.value || 0), 10) || 0;
        const idPlazo = parseInt(String(document.getElementById('id_plazo_cobrar_venta')?.value || 0), 10) || 0;
        if (idFoto > 0 && idSucursalVenta > 0 && idVenta > 0 && idPlazo > 0) {
          fetch(ventaApiUrl('parts/ventas/main/get_foto_cache_plazo_venta.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ id_foto: idFoto, id_sucursal: idSucursalVenta, id_venta: idVenta, id_plazo: idPlazo })
          })
            .then(function (r2) { return r2.json(); })
            .then(function (j) {
              if (j && j.success && j.tiene_foto) {
                cobrarPlazoVentaAplicarPreviewComprobante(j.nombre_foto);
                const btnOk = document.getElementById('btnCobrarPlazoVentaModal');
                if (btnOk) {
                  btnOk.disabled = false;
                }
              }
            })
            .catch(function (e) {
              console.warn('[plazo_venta][poll] get_foto_cache error', e);
            });
        }
      })
      .catch(function (err) {
        console.warn('consultarTokenPlazoVenta', err);
      });
  }

  // QR de plazo: migrado a CameraDocPanel (modal dinámico). No usar generarNuevoQRPlazoVenta.

  window.abrirModalFotoMovilCobrarPlazoVenta = function () {
    const idVenta = parseInt(String(document.getElementById('id_venta_cobrar_plazo')?.value || 0), 10) || 0;
    const idPlazo = parseInt(String(document.getElementById('id_plazo_cobrar_venta')?.value || 0), 10) || 0;
    if (idVenta <= 0 || idPlazo <= 0 || idSucursalVenta <= 0) {
      Swal.fire({ title: 'Atención', text: 'No hay datos de venta/plazo.', icon: 'warning' });
      return;
    }
    const modalCobro = document.getElementById('modalCobrarPlazoVenta');
    const hidFoto = document.getElementById('foto_camara_cobrar_plazo');
    if (hidFoto) {
      hidFoto.value = 'true';
    }
    fetch(ventaApiUrl('parts/ventas/main/procesar_insertar_foto_camara_plazo_venta.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ id_venta: idVenta, id_plazo: idPlazo, id_sucursal: idSucursalVenta })
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'No se pudo crear el registro de foto');
        }
        const hidId = document.getElementById('id_foto_cache_plazo_venta');
        if (hidId) {
          hidId.value = String(data.id_foto || '');
        }
        if (window.CameraDocPanel && typeof window.CameraDocPanel.open === 'function') {
          window.CameraDocPanel.open({
            tipo: 'plazo_venta',
            id: parseInt(String(data.id_foto || 0), 10) || 0,
            idSucursal: idSucursalVenta
          }).catch(function (e) {
            Swal.fire({ title: 'Error', text: e.message || 'No se pudo generar el QR', icon: 'error' });
          });
        }
      })
      .catch(function (e) {
        Swal.fire({ title: 'Error', text: e.message || 'Error', icon: 'error' });
      });
  };

  window.descargarPDFVenta = function (url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  function cargarFotosArticulosVentaTicket(idVenta, silent) {
    silent = !!silent;
    const contenedor = document.getElementById('contenedor_imagenes_articulos_venta');
    const sinImagenes = document.getElementById('sin_imagenes_articulos_venta');
    const loading = document.getElementById('loading_imagenes_articulos_venta');
    if (!contenedor || !loading) {
      return;
    }
    if (!silent) {
      loading.style.display = 'block';
      contenedor.innerHTML = '';
      if (sinImagenes) {
        sinImagenes.style.display = 'none';
      }
    }

    var fdImg = new FormData();
    fdImg.append('tipo', 'articulo_venta');
    fdImg.append('id', String(idVenta));
    fdImg.append('id_sucursal', String(idSucursalVenta || 0));

    fetch('camera/api/list_imagenes.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: fdImg
    })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      })
      .then(function (data) {
        if (!silent) {
          loading.style.display = 'none';
        }
        if (data.success && data.imagenes && data.imagenes.length > 0) {
          if (sinImagenes) {
            sinImagenes.style.display = 'none';
          }
          mostrarFotosArticulosVentaTicket(data.imagenes, idVenta);
        } else if (sinImagenes) {
          if (!silent) {
            sinImagenes.style.display = 'block';
          }
        }
      })
      .catch(function () {
        if (!silent) {
          loading.style.display = 'none';
          if (sinImagenes) {
            sinImagenes.style.display = 'block';
          }
        }
      });
  }

  function mostrarFotosArticulosVentaTicket(imagenes, idVentaRef) {
    const contenedor = document.getElementById('contenedor_imagenes_articulos_venta');
    if (!contenedor) {
      return;
    }
    contenedor.innerHTML = '';
    imagenes.forEach(function (imagen) {
      const columna = document.createElement('div');
      columna.className = 'col-md-6';
      const esPDF = imagen.foto.toLowerCase().endsWith('.pdf');
      const iconoArchivo = esPDF ? 'ri-file-text-line' : 'ri-image-line';
      const fotoEsc = escapeJsString(imagen.foto);
      const mediaHtml = esPDF
        ? '<div class="pdf-preview" onclick="window.descargarPDFVenta(\'photos/' +
          fotoEsc +
          "', '" +
          fotoEsc +
          '\')"><i class="icon-base ri ' +
          iconoArchivo +
          ' icon-48px text-primary"></i><div class="mt-2"><small class="text-muted">' +
          imagen.foto.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
          '</small></div></div>'
        : '<img src="photos/' +
          imagen.foto.replace(/"/g, '&quot;') +
          '" alt="Foto ticket" class="img-fluid w-100" style="cursor:pointer;" onclick="window.ampliarImagenArticulosVentaTicket(\'photos/' +
          fotoEsc +
          '\')">';
      columna.innerHTML =
        '<div class="card h-100">' +
        '<div class="card-body p-0">' +
        '<div class="position-relative">' +
        mediaHtml +
        '<div class="position-absolute top-0 end-0 p-2">' +
        '<button type="button" class="btn btn-danger btn-sm" onclick="window.eliminarFotoArticulosVentaTicket(' +
        imagen.id_foto +
        ", '" +
        fotoEsc +
        "', " +
        idVentaRef +
        ')" title="Eliminar"><i class="icon-base ri ri-delete-bin-line icon-14px"></i></button>' +
        '</div></div></div></div>';
      contenedor.appendChild(columna);
    });
  }

  window.abrirModalSubirFotoArticulosVentaTicket = function () {
    if (idArticuloVentaAncla <= 0) {
      Swal.fire({ title: 'Atención', text: 'No hay artículos en el ticket.', icon: 'warning' });
      return;
    }
    const form = document.getElementById('formSubirFotoArticulosVentaTicket');
    if (form) {
      form.reset();
    }
    const modalEl = document.getElementById('modalSubirFotoArticulosVentaTicket');
    if (modalEl) {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }
  };

  window.subirFotoArticulosVentaTicket = function () {
    if (idVentaFotosTicket <= 0 || idArticuloVentaAncla <= 0) {
      return;
    }
    const archivoInput = document.getElementById('archivo_foto_articulos_venta_ticket');
    const modalEl = document.getElementById('modalSubirFotoArticulosVentaTicket');
    const btnSubir = modalEl ? modalEl.querySelector('.btn-primary') : null;
    const spinner = btnSubir ? btnSubir.querySelector('.spinner-border') : null;

    if (!archivoInput || !archivoInput.files[0]) {
      Swal.fire({ title: 'Atención', text: 'Seleccione un archivo.', icon: 'warning' });
      return;
    }
    const archivo = archivoInput.files[0];
    if (archivo.size > 5 * 1024 * 1024) {
      Swal.fire({ title: 'Error', text: 'Máximo 5 MB.', icon: 'error' });
      return;
    }
    const ext = archivo.name.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'gif', 'png', 'pdf'].indexOf(ext) === -1) {
      Swal.fire({ title: 'Error', text: 'Formato no permitido.', icon: 'error' });
      return;
    }

    const formData = new FormData();
    formData.append('archivo_foto', archivo);
    formData.append('id_venta', String(idVentaFotosTicket));

    if (btnSubir) {
      btnSubir.disabled = true;
    }
    if (spinner) {
      spinner.classList.remove('d-none');
    }

    fetch('parts/ventas/main/subir_foto_articulos_venta_ticket.php', { method: 'POST', body: formData })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (btnSubir) {
          btnSubir.disabled = false;
        }
        if (spinner) {
          spinner.classList.add('d-none');
        }
        if (data.success) {
          const inst = bootstrap.Modal.getInstance(modalEl);
          if (inst) {
            inst.hide();
          }
          Swal.fire({ title: 'Listo', text: data.message || 'Subido', icon: 'success', timer: 2500, showConfirmButton: false });
          cargarFotosArticulosVentaTicket(idVentaFotosTicket, false);
        } else {
          throw new Error(data.error || 'Error');
        }
      })
      .catch(function (e) {
        if (btnSubir) {
          btnSubir.disabled = false;
        }
        if (spinner) {
          spinner.classList.add('d-none');
        }
        Swal.fire({ title: 'Error', text: e.message || 'No se pudo subir', icon: 'error' });
      });
  };

  window.ampliarImagenArticulosVentaTicket = function (ruta) {
    const img = document.getElementById('imagen_ampliada_articulos_venta');
    const modalEl = document.getElementById('modalAmpliarImagenArticulosVenta');
    if (img && modalEl) {
      img.src = ruta;
      new bootstrap.Modal(modalEl).show();
    }
  };

  window.eliminarFotoArticulosVentaTicket = function (idFoto, nombreFoto, idVentaRef) {
    Swal.fire({
      title: '¿Eliminar foto?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }
      fetch('parts/ventas/main/eliminar_imagen_articulo_venta_ticket.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:
          'id_foto=' +
          encodeURIComponent(String(idFoto)) +
          '&nombre_foto=' +
          encodeURIComponent(nombreFoto) +
          '&id_venta=' +
          encodeURIComponent(String(idVentaRef))
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success) {
            Swal.fire({ title: 'Eliminado', icon: 'success', timer: 2000, showConfirmButton: false });
            cargarFotosArticulosVentaTicket(idVentaFotosTicket, false);
          } else {
            throw new Error(data.error || 'Error');
          }
        })
        .catch(function (e) {
          Swal.fire({ title: 'Error', text: e.message || 'No se pudo eliminar', icon: 'error' });
        });
    });
  };

  window.eliminarComprobanteVenta = function (idFoto, nombreFoto, idVenta) {
    Swal.fire({
      title: '¿Eliminar comprobante?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }
      fetch('parts/ventas/main/eliminar_imagen_venta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:
          'id_foto=' +
          encodeURIComponent(String(idFoto)) +
          '&nombre_foto=' +
          encodeURIComponent(nombreFoto) +
          '&id_venta=' +
          encodeURIComponent(String(idVenta))
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success) {
            Swal.fire({ title: 'Eliminado', icon: 'success', timer: 2000, showConfirmButton: false });
            cargarComprobantesVenta(idVentaComprobantes);
          } else {
            throw new Error(data.error || 'Error');
          }
        })
        .catch(function (e) {
          Swal.fire({ title: 'Error', text: e.message || 'No se pudo eliminar', icon: 'error' });
        });
    });
  };

  let cantidadFotosClienteVenta = 0;
  let qrClienteVentaPollTimer = null;
  let qrClienteVentaExpireTimer = null;

  function generarTokenClienteVenta() {
    return 'tok_' + Math.random().toString(36).substr(2, 28) + Date.now().toString(36);
  }

  function consultarTokenClienteVenta(token, idToken) {
    fetch('parts/lotes/main/procesar_consultar_token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ token: token, id_token: idToken })
    })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('HTTP ' + r.status);
        }
        return r.json();
      })
      .then(function (data) {
        if (data.success && data.utilizado) {
          if (qrClienteVentaPollTimer) {
            clearInterval(qrClienteVentaPollTimer);
            qrClienteVentaPollTimer = null;
          }
          if (qrClienteVentaExpireTimer) {
            clearTimeout(qrClienteVentaExpireTimer);
            qrClienteVentaExpireTimer = null;
          }
          const modalElement = document.getElementById('modalFotoMovilClienteVenta');
          const modalInstance = modalElement ? bootstrap.Modal.getInstance(modalElement) : null;
          if (modalInstance) {
            modalInstance.hide();
          }
          if (idClienteFichaVenta > 0) {
            cargarImagenesClienteVenta(idClienteFichaVenta);
          }
        }
      })
      .catch(function (err) {
        console.warn('consultarTokenClienteVenta', err);
      });
  }

  window.generarNuevoQRClienteVenta = function () {
    if (idClienteFichaVenta <= 0 || idSucursalClienteDocsVenta <= 0) {
      Swal.fire({ title: 'Atención', text: 'No hay datos de cliente para el QR.', icon: 'warning' });
      return;
    }
    if (qrClienteVentaPollTimer) {
      clearInterval(qrClienteVentaPollTimer);
      qrClienteVentaPollTimer = null;
    }
    if (qrClienteVentaExpireTimer) {
      clearTimeout(qrClienteVentaExpireTimer);
      qrClienteVentaExpireTimer = null;
    }
    const token = generarTokenClienteVenta();
    fetch('parts/lotes/main/procesar_guardar_token.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        tipo_qr: 'cliente',
        id_item: idClienteFichaVenta,
        token: token,
        id_sucursal: idSucursalClienteDocsVenta
      })
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.error || 'No se pudo generar el QR');
        }
        const idToken = data.id_token;
        const url =
          APP_URL + '/get_photo/index.php?id_sucursal=' +
          encodeURIComponent(String(idSucursalClienteDocsVenta)) +
          '&id_token=' +
          encodeURIComponent(String(idToken)) +
          '&type=cliente&id=' +
          encodeURIComponent(String(idClienteFichaVenta)) +
          '&token=' +
          encodeURIComponent(token);
        const qrcodeDiv = document.getElementById('qrcode_cliente_venta');
        if (!qrcodeDiv || typeof QRCode === 'undefined') {
          throw new Error('No se pudo inicializar el código QR');
        }
        qrcodeDiv.innerHTML = '';
        new QRCode(qrcodeDiv, {
          text: url,
          width: 200,
          height: 200,
          colorDark: '#000000',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.H
        });
        consultarTokenClienteVenta(token, idToken);
        qrClienteVentaPollTimer = setInterval(function () {
          consultarTokenClienteVenta(token, idToken);
        }, 2000);
        qrClienteVentaExpireTimer = setTimeout(function () {
          if (qrClienteVentaPollTimer) {
            clearInterval(qrClienteVentaPollTimer);
            qrClienteVentaPollTimer = null;
          }
          qrClienteVentaExpireTimer = null;
          const modalElement = document.getElementById('modalFotoMovilClienteVenta');
          const modalInstance = modalElement ? bootstrap.Modal.getInstance(modalElement) : null;
          if (modalInstance) {
            modalInstance.hide();
          }
          fetch('parts/lotes/main/procesar_borrar_token.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              id_token: idToken,
              tipo_qr: 'cliente',
              id_item: idClienteFichaVenta,
              id_sucursal: idSucursalClienteDocsVenta
            })
          });
        }, 60000);
      })
      .catch(function (e) {
        Swal.fire({ title: 'Error', text: e.message || 'No se pudo generar el QR', icon: 'error' });
      });
  };

  window.abrirModalFotoMovilClienteVenta = function () {
    if (idClienteFichaVenta <= 0 || idSucursalClienteDocsVenta <= 0) {
      Swal.fire({ title: 'Atención', text: 'No hay datos de cliente para la cámara.', icon: 'warning' });
      return;
    }
    if (window.CameraDocPanel && typeof window.CameraDocPanel.open === 'function') {
      window.CameraDocPanel.open({
        tipo: 'cliente',
        id: idClienteFichaVenta,
        idSucursal: idSucursalClienteDocsVenta
      }).catch(function (e) {
        Swal.fire({ title: 'Error', text: e.message || 'No se pudo generar el QR', icon: 'error' });
      });
    } else {
      window.generarNuevoQRClienteVenta();
    }
  };

  function cargarImagenesClienteVenta(idCliente) {
    const contenedor = document.getElementById('contenedor_imagenes_cliente_venta');
    const sinImagenes = document.getElementById('sin_imagenes_cliente_venta');
    const loading = document.getElementById('loading_imagenes_cliente_venta');
    if (!contenedor || !sinImagenes || !loading) {
      return;
    }
    loading.style.display = 'block';
    contenedor.innerHTML = '';
    sinImagenes.style.display = 'none';
    var fdImg = new FormData();
    fdImg.append('tipo', 'cliente');
    fdImg.append('id', String(idCliente));
    fdImg.append('id_sucursal', String(idSucursalClienteDocsVenta || 0));

    fetch('camera/api/list_imagenes.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: fdImg
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        loading.style.display = 'none';
        if (data.success && data.imagenes && data.imagenes.length > 0) {
          cantidadFotosClienteVenta = data.imagenes.length;
          mostrarImagenesClienteVenta(data.imagenes);
        } else {
          cantidadFotosClienteVenta = 0;
          sinImagenes.style.display = 'block';
        }
      })
      .catch(function (err) {
        console.error('cargarImagenesClienteVenta', err);
        loading.style.display = 'none';
        sinImagenes.style.display = 'block';
      });
  }

  function mostrarImagenesClienteVenta(imagenes) {
    const contenedor = document.getElementById('contenedor_imagenes_cliente_venta');
    if (!contenedor) {
      return;
    }
    contenedor.innerHTML = '';
    imagenes.forEach(function (imagen) {
      const nombre = String(imagen.foto || '');
      const idFoto = parseInt(String(imagen.id_foto), 10);
      const rutaPhotos = 'photos/' + nombre;
      const esPDF = nombre.toLowerCase().endsWith('.pdf');
      const altText = esPDF ? 'Documento PDF del cliente' : 'Documento del cliente';

      const columna = document.createElement('div');
      columna.className = 'col-md-6';
      const card = document.createElement('div');
      card.className = 'card h-100';
      const cardBody = document.createElement('div');
      cardBody.className = 'card-body p-0';
      const wrap = document.createElement('div');
      wrap.className = 'position-relative';

      if (esPDF) {
        const pdfBox = document.createElement('div');
        pdfBox.className = 'pdf-preview';
        pdfBox.addEventListener('click', function () {
          window.descargarPDFVenta(rutaPhotos, nombre);
        });
        const icon = document.createElement('i');
        icon.className = 'icon-base ri ri-file-text-line icon-48px text-primary';
        pdfBox.appendChild(icon);
        const mt = document.createElement('div');
        mt.className = 'mt-2';
        const small = document.createElement('small');
        small.className = 'text-muted';
        small.textContent = nombre;
        mt.appendChild(small);
        pdfBox.appendChild(mt);
        wrap.appendChild(pdfBox);
      } else {
        const img = document.createElement('img');
        img.src = rutaPhotos;
        img.alt = altText;
        img.className = 'img-fluid w-100';
        img.style.cursor = 'pointer';
        img.addEventListener('click', function () {
          window.ampliarImagenClienteVenta(rutaPhotos);
        });
        wrap.appendChild(img);
      }

      const btnWrap = document.createElement('div');
      btnWrap.className = 'position-absolute top-0 end-0 p-2';
      const btnDel = document.createElement('button');
      btnDel.type = 'button';
      btnDel.className = 'btn btn-danger btn-sm';
      btnDel.title = 'Eliminar';
      btnDel.addEventListener('click', function () {
        window.eliminarFotoClienteVenta(idFoto, nombre);
      });
      const iconDel = document.createElement('i');
      iconDel.className = 'icon-base ri ri-delete-bin-line icon-14px';
      btnDel.appendChild(iconDel);
      btnWrap.appendChild(btnDel);

      wrap.appendChild(btnWrap);
      cardBody.appendChild(wrap);
      card.appendChild(cardBody);
      columna.appendChild(card);
      contenedor.appendChild(columna);
    });
  }

  window.abrirModalSubirFotoClienteVenta = function () {
    const form = document.getElementById('formSubirFotoClienteVenta');
    if (form) {
      form.reset();
    }
    const modalEl = document.getElementById('modalSubirFotoClienteVenta');
    if (modalEl) {
      new bootstrap.Modal(modalEl).show();
    }
  };

  window.subirFotoClienteVenta = function () {
    const archivoInput = document.getElementById('archivo_foto_cliente_venta');
    const modalEl = document.getElementById('modalSubirFotoClienteVenta');
    const btnSubir = modalEl ? modalEl.querySelector('.btn-primary') : null;
    const spinner = btnSubir ? btnSubir.querySelector('.spinner-border') : null;
    if (!archivoInput || !archivoInput.files[0]) {
      Swal.fire({ title: 'Error', text: 'Por favor selecciona un archivo', icon: 'error' });
      return;
    }
    const archivo = archivoInput.files[0];
    if (archivo.size > 5 * 1024 * 1024) {
      Swal.fire({ title: 'Error', text: 'El archivo es demasiado grande. Máximo 5MB', icon: 'error' });
      return;
    }
    const extension = archivo.name.split('.').pop().toLowerCase();
    const formatosPermitidos = ['jpg', 'jpeg', 'gif', 'png', 'pdf'];
    if (!formatosPermitidos.includes(extension)) {
      Swal.fire({
        title: 'Error',
        text: 'Formato no permitido. Solo JPG, JPEG, GIF, PNG y PDF',
        icon: 'error'
      });
      return;
    }
    const formData = new FormData();
    formData.append('archivo_foto', archivo);
    formData.append('id_cliente', String(idClienteFichaVenta));
    if (btnSubir) {
      btnSubir.disabled = true;
    }
    if (spinner) {
      spinner.classList.remove('d-none');
    }
    fetch('parts/clientes/main/subir_foto.php', { method: 'POST', body: formData })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (btnSubir) {
          btnSubir.disabled = false;
        }
        if (spinner) {
          spinner.classList.add('d-none');
        }
        if (data.success) {
          const modalInstance = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
          if (modalInstance) {
            modalInstance.hide();
          }
          Swal.fire({
            title: 'Archivo subido',
            text: data.message || 'OK',
            icon: 'success',
            timer: 2500,
            showConfirmButton: false
          });
          cargarImagenesClienteVenta(idClienteFichaVenta);
        } else {
          throw new Error(data.error || 'Error desconocido');
        }
      })
      .catch(function (e) {
        if (btnSubir) {
          btnSubir.disabled = false;
        }
        if (spinner) {
          spinner.classList.add('d-none');
        }
        Swal.fire({ title: 'Error', text: e.message || 'No se pudo subir', icon: 'error' });
      });
  };

  window.ampliarImagenClienteVenta = function (rutaImagen) {
    const img = document.getElementById('imagen_ampliada_cliente_venta');
    const modalEl = document.getElementById('modalAmpliarImagenClienteVenta');
    if (img && modalEl) {
      img.src = rutaImagen;
      new bootstrap.Modal(modalEl).show();
    }
  };

  window.eliminarFotoClienteVenta = function (idFoto, nombreFoto) {
    Swal.fire({
      title: 'Confirmar eliminación',
      text: '¿Eliminar este documento? Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar'
    }).then(function (result) {
      if (!result.isConfirmed) {
        return;
      }
      fetch('parts/clientes/main/eliminar_foto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:
          'id_foto=' +
          encodeURIComponent(String(idFoto)) +
          '&nombre_foto=' +
          encodeURIComponent(nombreFoto)
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success) {
            Swal.fire({
              title: 'Eliminado',
              text: data.message || '',
              icon: 'success',
              timer: 2000,
              showConfirmButton: false
            });
            cargarImagenesClienteVenta(idClienteFichaVenta);
          } else {
            throw new Error(data.error || 'Error');
          }
        })
        .catch(function (e) {
          Swal.fire({ title: 'Error', text: e.message || 'No se pudo eliminar', icon: 'error' });
        });
    });
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (window.location.hash === '#navs-pills-venta-historial-plazos') {
        var tab = document.querySelector('[data-bs-target="#navs-pills-venta-historial-plazos"]');
        if (tab) {
            new bootstrap.Tab(tab).show();
        }
    }
    if (window.location.hash === '#navs-pills-venta-adelantos-capital') {
        var tab = document.querySelector('[data-bs-target="#navs-pills-venta-adelantos-capital"]');
        if (tab) {
            new bootstrap.Tab(tab).show();
        }
    }
    const modalQrClienteVenta = document.getElementById('modalFotoMovilClienteVenta');
    if (modalQrClienteVenta) {
      modalQrClienteVenta.addEventListener('hidden.bs.modal', function () {
        if (qrClienteVentaPollTimer) {
          clearInterval(qrClienteVentaPollTimer);
          qrClienteVentaPollTimer = null;
        }
        if (qrClienteVentaExpireTimer) {
          clearTimeout(qrClienteVentaExpireTimer);
          qrClienteVentaExpireTimer = null;
        }
      });
    }
    const btnCam = document.getElementById('btnFotoMovilClienteVenta');
    if (btnCam) {
      btnCam.addEventListener('click', function () {
        window.abrirModalFotoMovilClienteVenta();
      });
    }
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(function (btn) {
      btn.addEventListener('shown.bs.tab', function (e) {
        if (
          e.target.getAttribute('data-bs-target') === '#navs-pills-venta-cliente' &&
          idClienteFichaVenta > 0
        ) {
          cargarImagenesClienteVenta(idClienteFichaVenta);
        }
        const tgt = e.target.getAttribute('data-bs-target') || '';
        if (tgt === '#navs-pills-venta-articulos' && window.dtLineasVentaFicha) {
          window.dtLineasVentaFicha.columns.adjust();
        }
        if (tgt === '#navs-pills-venta-historial-plazos' && window.dtVentasPlazosFicha) {
          window.dtVentasPlazosFicha.columns.adjust();
        }
        if (tgt === '#navs-pills-venta-adelantos-capital' && window.dtAdelantosCapitalVentaFicha) {
          window.dtAdelantosCapitalVentaFicha.columns.adjust();
        }
      });
    });

    if (idVentaFichaPage > 0 && typeof DataTable !== 'undefined') {
      (function initTablaLineasVentaFicha() {
        const el = document.getElementById('tabla_articulos_venta_ficha');
        if (!el) {
          return;
        }
        function escapeHtmlVentaFicha(s) {
          const t = document.createElement('div');
          t.textContent = s == null ? '' : String(s);
          return t.innerHTML;
        }
        window.dtLineasVentaFicha = new DataTable(el, {
          processing: true,
          serverSide: false,
          autoWidth: false,
          order: [[0, 'asc']],
          pageLength: 25,
          lengthChange: false,
          language: typeof DATATABLES_SPANISH !== 'undefined' ? DATATABLES_SPANISH : {},
          layout: {
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
          ajax: {
            url: 'parts/ventas/main/datatable_lineas_venta_ficha.php',
            type: 'POST',
            data: { id_venta: idVentaFichaPage },
            dataSrc: function (json) {
              if (json && json.data) {
                return json.data;
              }
              return [];
            }
          },
          columns: [
            { data: 'id_articulo_venta', className: 'text-nowrap' },
            { data: 'descripcion', className: 'min-w-200' },
            {
              data: 'tipo',
              render: function (d) {
                const raw = d != null ? String(d) : '';
                const t = raw.trim().toLowerCase();
                const label = escapeHtmlVentaFicha(raw !== '' ? raw : '—');
                const bg = t === 'oro' ? 'bg-label-warning' : 'bg-label-secondary';
                return (
                  '<span class="badge ' + bg + ' rounded-pill badget-estados-tablas">' + label + '</span>'
                );
              }
            },
            {
              data: 'peso',
              className: 'text-end',
              render: function (d) {
                return fmtNumberDot2(d);
              }
            },
            {
              data: 'precio',
              className: 'text-end fw-medium',
              render: function (d) {
                const s = fmtNumberDot2(d);
                return s === '—' ? '—' : s + ' €';
              }
            },
            {
              data: 'estado_articulo_av',
              render: function (d) {
                const raw = d != null ? String(d) : '';
                const t = raw.trim().toLowerCase();
                const bg = t === 'vendido' ? 'bg-label-success' : (t === 'devuelto' ? 'bg-label-danger' : 'bg-label-info');
                return (
                  '<div class="badge ' + bg + ' rounded-pill lh-xs badget-estados-tablas">' +
                  escapeHtmlVentaFicha(raw !== '' ? raw : '—') +
                  '</div>'
                );
              }
            },
            {
              data: 'id_articulo_venta',
              orderable: false,
              searchable: false,
              className: 'text-nowrap',
              render: function (id) {
                if (!id) {
                  return '';
                }
                return (
                  '<a href="articulo.php?id=' +
                  encodeURIComponent(String(id)) +
                  '" class="btn btn-xs btn-xs-tablas btn-primary waves-effect waves-light" target="_blank">Ver ficha</a>'
                );
              }
            }
          ]
        });
      })();

      if (ventaEsPlazosFicha) {
        (function initTablaVentasPlazosFicha() {
          const elPl = document.getElementById('tabla_ventas_plazos_ficha');
          if (!elPl) {
            return;
          }
          window.dtVentasPlazosFicha = new DataTable(elPl, {
            processing: true,
            serverSide: false,
            autoWidth: false,
            order: [[1, 'asc']],
            pageLength: 10,
            lengthChange: false,
            language: typeof DATATABLES_SPANISH !== 'undefined' ? DATATABLES_SPANISH : {},
            layout: {
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
            ajax: {
              url: 'parts/ventas/main/datatable_ventas_plazos_ficha.php',
              type: 'POST',
              data: { id_venta: idVentaFichaPage },
              dataSrc: function (json) {
                ventaPlazosToggleBtnAnadir(!!(json && json.mostrar_boton_anadir_plazo));
                if (json && Array.isArray(json.data)) {
                  return json.data;
                }
                return [];
              }
            },
            columnDefs: [{ orderable: false, targets: [0, 7, 8, 9, 10] }],
            columns: [
              {
                data: 'menu',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (d) {
                  return d || '';
                }
              },
              { data: 'id', className: 'text-nowrap' },
              { data: 'fecha_creado' },
              { data: 'importe_fmt', className: 'fw-medium text-end' },
              { data: 'fecha_cobrado' },
              { data: 'fecha_vencido' },
              { data: 'fecha_vencimiento' },
              {
                data: 'estado_badge',
                render: function (d, type, row) {
                  if (type === 'display' || type === 'filter') {
                    return d;
                  }
                  if (type === 'sort' || type === 'type') {
                    return row && row.estado != null ? row.estado : '';
                  }
                  return d;
                }
              },
              {
                data: 'metodo_badge',
                render: function (d, type, row) {
                  if (type === 'display' || type === 'filter') {
                    return d;
                  }
                  if (type === 'sort' || type === 'type') {
                    return row && row.metodo_pago != null ? row.metodo_pago : '';
                  }
                  return d;
                }
              },
              {
                data: 'comprobante_pago',
                orderable: false,
                searchable: false,
                className: 'text-nowrap',
                render: function (d) {
                  return d;
                }
              },
              {
                data: 'acciones',
                orderable: false,
                searchable: false,
                className: 'text-nowrap',
                render: function (d) {
                  return d;
                }
              }
            ]
          });

          const btnAnadirPlazo = document.getElementById('btnAnadirPlazoVenta');
          if (btnAnadirPlazo && btnAnadirPlazo.dataset._listener !== '1') {
            btnAnadirPlazo.dataset._listener = '1';
            btnAnadirPlazo.addEventListener('click', function () {
              window.anadirPlazoVenta();
            });
          }
        })();

        (function initTablaAdelantosCapitalVentaFicha() {
          const elAc = document.getElementById('tabla_adelantos_capital_venta_ficha');
          if (!elAc) {
            return;
          }
          window.dtAdelantosCapitalVentaFicha = new DataTable(elAc, {
            processing: true,
            serverSide: false,
            autoWidth: false,
            order: [[0, 'asc']],
            pageLength: 10,
            lengthChange: false,
            language: typeof DATATABLES_SPANISH !== 'undefined' ? DATATABLES_SPANISH : {},
            layout: {
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
            ajax: {
              url: 'parts/ventas/main/datatable_adelantos_capital_venta_ficha.php',
              type: 'POST',
              data: { id_venta: idVentaFichaPage },
              dataSrc: function (json) {
                if (json && Array.isArray(json.data)) {
                  return json.data;
                }
                return [];
              }
            },
            columnDefs: [{ orderable: false, targets: [8, 9] }],
            columns: [
              { data: 'num', className: 'text-nowrap' },
              {
                data: 'fecha_adelanto',
                render: function (d, type, row) {
                  if (type === 'sort' || type === 'type') {
                    return row && row.fecha_adelanto_raw != null ? row.fecha_adelanto_raw : '';
                  }
                  return d;
                }
              },
              {
                data: 'importe_adelanto',
                className: 'text-end fw-medium',
                render: function (d, type, row) {
                  if (type === 'display' || type === 'filter') {
                    return row ? row.importe_adelanto_fmt : '';
                  }
                  return d;
                }
              },
              {
                data: 'capital_antiguo',
                className: 'text-end',
                render: function (d, type, row) {
                  if (type === 'display' || type === 'filter') {
                    return row ? row.capital_antiguo_fmt : '';
                  }
                  return d;
                }
              },
              {
                data: 'importe_plazo_antiguo',
                className: 'text-end',
                render: function (d, type, row) {
                  if (type === 'display' || type === 'filter') {
                    return row ? row.importe_plazo_antiguo_fmt : '';
                  }
                  return d;
                }
              },
              {
                data: 'nuevo_capital',
                className: 'text-end',
                render: function (d, type, row) {
                  if (type === 'display' || type === 'filter') {
                    return row ? row.nuevo_capital_fmt : '';
                  }
                  return d;
                }
              },
              {
                data: 'nuevo_importe_plazo',
                className: 'text-end',
                render: function (d, type, row) {
                  if (type === 'display' || type === 'filter') {
                    return row ? row.nuevo_importe_plazo_fmt : '';
                  }
                  return d;
                }
              },
              {
                data: 'forma_badge',
                render: function (d, type, row) {
                  if (type === 'display' || type === 'filter') {
                    return d;
                  }
                  if (type === 'sort' || type === 'type') {
                    return row && row.forma_de_pago != null ? row.forma_de_pago : '';
                  }
                  return d;
                }
              },
              {
                data: 'comprobante_pago',
                orderable: false,
                searchable: false,
                className: 'text-nowrap',
                render: function (d) {
                  return d;
                }
              },
              {
                data: 'comprobante_cliente',
                orderable: false,
                searchable: false,
                className: 'text-muted text-nowrap'
              }
            ]
          });
        })();
      }
    }

    const modalCobrarPlazoVenta = document.getElementById('modalCobrarPlazoVenta');
    if (modalCobrarPlazoVenta) {
      function cobrarPlazoVentaMarcarRadioChecked(radio) {
        document.querySelectorAll('.forma_de_pago_cobrar_plazo_venta').forEach(function (r) {
          const wrap = r.closest('.custom-option-blue');
          if (wrap) {
            wrap.classList.remove('checked');
          }
        });
        if (radio) {
          radio.checked = true;
          const w = radio.closest('.custom-option-blue');
          if (w) {
            w.classList.add('checked');
          }
        }
      }

      function cobrarPlazoVentaResetVistaComprobante() {
        const cont = document.getElementById('contenedor_imagenes_comprobante_cobrar_plazo_venta');
        if (cont) {
          cont.innerHTML =
            '<i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>' +
            '<p class="text-body-secondary mb-0">No hay comprobante cargado</p>';
        }
        const inp = document.getElementById('comprobante_cobrar_plazo_venta_archivo');
        if (inp) {
          inp.value = '';
        }
        const hid = document.getElementById('id_foto_cache_plazo_venta');
        if (hid) {
          hid.value = '';
        }
        window._plazoVentaComprobanteNombre = '';
        const fc = document.getElementById('foto_camara_cobrar_plazo');
        if (fc) {
          fc.value = 'false';
        }
      }

      modalCobrarPlazoVenta.addEventListener('shown.bs.modal', function () {
        const idPz = document.getElementById('id_plazo_cobrar_venta');
        const impEl = document.getElementById('importe_plazo_cobrar_venta');
        const viewPz = document.getElementById('id_plazo_view_cobrar_venta');
        const tot = document.getElementById('totales_modal_cobrar_plazo_venta');
        const hidForma = document.getElementById('forma_de_pago_cobrar_plazo_venta');
        const btnOk = document.getElementById('btnCobrarPlazoVentaModal');
        const contComp = document.getElementById('comprobante_cobrar_plazo_venta_container');
        const lbl = document.getElementById('modalCobrarPlazoVentaLabel');
        if (viewPz && idPz) {
          viewPz.textContent = idPz.value;
        }
        if (tot && impEl) {
          const n = parseFloat(String(impEl.value).replace(',', '.')) || 0;
          const s = fmtNumberDot2(n);
          tot.textContent = s === '—' ? '—' : s + ' €';
        }
        cobrarPlazoVentaResetVistaComprobante();
        if (contComp) {
          contComp.classList.add('display_none');
        }
        if (hidForma) {
          hidForma.value = 'efectivo';
        }
        if (lbl) {
          lbl.textContent = 'Cobrar plazo en efectivo';
        }
        const radioEfec = document.getElementById('cobrar_plazo_forma_de_pago_efectivo');
        if (radioEfec) {
          cobrarPlazoVentaMarcarRadioChecked(radioEfec);
        }
        aplicarLimiteFacturaSimplificadaRadioEfectivo(
          'cobrar_plazo_forma_de_pago_efectivo',
          'cobrar_plazo_forma_de_pago_tarjeta',
          cobrarPlazoVentaMarcarRadioChecked
        );
        if (btnOk && (hidForma ? hidForma.value === 'efectivo' : true)) {
          btnOk.disabled = false;
        }
      });

      document.querySelectorAll('.forma_de_pago_cobrar_plazo_venta').forEach(function (input) {
        input.addEventListener('change', function () {
          const forma = this.value;
          const hidForma = document.getElementById('forma_de_pago_cobrar_plazo_venta');
          const contComp = document.getElementById('comprobante_cobrar_plazo_venta_container');
          const lbl = document.getElementById('modalCobrarPlazoVentaLabel');
          const btnOk = document.getElementById('btnCobrarPlazoVentaModal');
          cobrarPlazoVentaMarcarRadioChecked(this);
          if (hidForma) {
            hidForma.value = forma;
          }
          if (forma === 'efectivo') {
            if (lbl) {
              lbl.textContent = 'Cobrar plazo en efectivo';
            }
            if (contComp) {
              contComp.classList.add('display_none');
            }
            if (btnOk) {
              btnOk.disabled = false;
            }
            cobrarPlazoVentaResetVistaComprobante();
          } else {
            if (lbl) {
              lbl.textContent =
                forma === 'tarjeta'
                  ? 'Cobrar plazo con tarjeta'
                  : forma === 'transferencia'
                    ? 'Cobrar plazo con transferencia'
                    : 'Cobrar plazo con bizum';
            }
            if (contComp) {
              contComp.classList.remove('display_none');
            }
            if (btnOk) {
              btnOk.disabled = true;
            }
          }
        });
      });

      const inpComp = document.getElementById('comprobante_cobrar_plazo_venta_archivo');
      if (inpComp) {
        inpComp.addEventListener('change', function () {
          const archivoInput = document.getElementById('comprobante_cobrar_plazo_venta_archivo');
          const contenedor_imagenes = document.getElementById('contenedor_imagenes_comprobante_cobrar_plazo_venta');
          const btnOk = document.getElementById('btnCobrarPlazoVentaModal');
          if (!archivoInput || !archivoInput.files[0] || !contenedor_imagenes) {
            return;
          }
          const archivo = archivoInput.files[0];
          if (archivo.size > 5 * 1024 * 1024) {
            Swal.fire({
              title: 'Error',
              text: 'El archivo no puede superar los 5MB',
              icon: 'error',
              confirmButtonText: 'Aceptar',
              confirmButtonColor: '#007bff'
            });
            archivoInput.value = '';
            return;
          }
          const reader = new FileReader();
          reader.onload = function (e) {
            if (archivo.type === 'application/pdf') {
              contenedor_imagenes.innerHTML =
                '<div class="text-center">' +
                '<i class="icon-base ri ri-file-pdf-line icon-48px text-danger mb-3"></i>' +
                '<p class="text-body-secondary mb-0">' +
                (archivo.name || '') +
                '</p></div>';
            } else {
              contenedor_imagenes.innerHTML =
                '<div class="position-relative">' +
                '<img src="' +
                e.target.result +
                '" alt="Comprobante" class="img-fluid">' +
                '</div>';
            }
            const fc = document.getElementById('foto_camara_cobrar_plazo');
            if (fc) {
              fc.value = 'false';
            }
            const hidCache = document.getElementById('id_foto_cache_plazo_venta');
            if (hidCache) {
              hidCache.value = '';
            }
            if (btnOk) {
              btnOk.disabled = false;
            }
          };
          reader.readAsDataURL(archivo);
        });
      }

      const formCobrarPlazo = document.getElementById('formCobrarPlazoVentaModal');
      if (formCobrarPlazo) {
        formCobrarPlazo.addEventListener('submit', function (e) {
          e.preventDefault();
          const form = document.getElementById('formCobrarPlazoVentaModal');
          const btnCobrar = document.getElementById('btnCobrarPlazoVentaModal');
          const archivoInput = document.getElementById('comprobante_cobrar_plazo_venta_archivo');
          if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
          }
          if (!btnCobrar) {
            return; 
          }
          btnCobrar.disabled = true;
          const btnTextoOriginal = btnCobrar.innerHTML;
          btnCobrar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

          const formData = new FormData();
          formData.append('id_venta', document.getElementById('id_venta_cobrar_plazo').value);
          formData.append('id_plazo', document.getElementById('id_plazo_cobrar_venta').value);
          formData.append('importe_plazo', document.getElementById('importe_plazo_cobrar_venta').value);
          formData.append('forma_de_pago', document.getElementById('forma_de_pago_cobrar_plazo_venta').value);
          formData.append('foto_camara', document.getElementById('foto_camara_cobrar_plazo').value);
          formData.append('id_foto_cache_plazo_venta', document.getElementById('id_foto_cache_plazo_venta').value);
          if (window._plazoVentaComprobanteNombre) {
            formData.append('comprobante_plazo', window._plazoVentaComprobanteNombre);
          }
          if (archivoInput && archivoInput.files[0]) {
            formData.append('comprobante_cobrar_plazo_venta_archivo', archivoInput.files[0]);
          }

          fetch('parts/ventas/main/cobrar_plazo_venta.php', { method: 'POST', body: formData })
            .then(function (r) {
              return r.json();
            })
            .then(function (data) {
              if (!data.success) {
                throw new Error(data.message || 'Error al cobrar el plazo');
              }
              const fdComp = new FormData();
              fdComp.append('id_venta', document.getElementById('id_venta_cobrar_plazo').value);
              return fetch('parts/ventas/main/comprobar_plazos.php', {
                method: 'POST',
                body: fdComp,
                credentials: 'same-origin'
              })
                .then(function (r) {
                  return r.json();
                })
                .then(function (comp) {
                  if (comp && comp.success) {
                    data._compPlazos = comp;
                    if (comp.resumen_badges) {
                      data.resumen_badges = comp.resumen_badges;
                    }
                    if (comp.accion === 'vendido') {
                      data.message = comp.message || 'Último plazo cobrado. Venta cerrada.';
                      data.ventaPlazosCerrada = true;
                      data.precioVentaPlazos = comp.precio_venta;
                      data.tieneFacturaPlazos = !!comp.tiene_factura;
                    } else if (comp.accion === 'plazo_creado') {
                      data.message = (data.message || 'Plazo cobrado correctamente') + ' ' + (comp.message || '');
                    }
                  }
                  return data;
                })
                .catch(function () {
                  return data;
                });
            })
            .then(function (data) {
              if (data.ventaPlazosCerrada && !data.tieneFacturaPlazos) {
                const inst = bootstrap.Modal.getInstance(modalCobrarPlazoVenta);
                if (inst) {
                  inst.hide();
                }
                return window.gestionarFacturaTrasCierreVentaPlazos(
                  document.getElementById('id_venta_cobrar_plazo').value,
                  data.precioVentaPlazos,
                  'parts/ventas/main'
                );
              }

              Swal.fire({
                title: 'Listo',
                text: data.message || 'Plazo cobrado correctamente',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#007bff'
              }).then(function () {
                const inst = bootstrap.Modal.getInstance(modalCobrarPlazoVenta);
                if (inst) {
                  inst.hide();
                }
                if (data.resumen_badges) {
                  window.actualizarBadgesResumenVentaFicha(data.resumen_badges);
                }
                cargarAccionesVentaFicha();
                if (data.ventaPlazosCerrada && data.tieneFacturaPlazos) {
                  window.location.reload();
                  return;
                }
                if (window.dtVentasPlazosFicha && typeof window.dtVentasPlazosFicha.ajax === 'object') {
                  window.dtVentasPlazosFicha.ajax.reload(null, false);
                }
              });
            })
            .catch(function (err) {
              Swal.fire({
                title: 'Error',
                text: err.message || 'Error al cobrar el plazo',
                icon: 'error',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#007bff'
              });
            })
            .finally(function () {
              btnCobrar.disabled = false;
              btnCobrar.innerHTML = btnTextoOriginal;
            });
        });
      }
    }
  });

  // Filter form control to default size
  setTimeout(() => {
    const elementsToModify = [
      { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
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
      { selector: '.row.mt-2.justify-content-between', classToRemove: 'mt-2', classToAdd: 'mt-0' },
      {
        selector: '.row.justify-content-between.dt-layout-table.mt-0',
        classToRemove: 'row justify-content-between dt-layout-table mt-0',
        classToAdd: 'justify-content-between dt-layout-table'
      },
      { selector: '.dt-container .table', classToAdd: 'table-responsive' }
    ];

    elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
      document.querySelectorAll(selector).forEach(element => {
        if (classToRemove) {
          classToRemove.split(' ').forEach(className => element.classList.remove(className));
        }
        if (classToAdd) {
          classToAdd.split(' ').forEach(className => element.classList.add(className));
        }
      });
    });
  }, 100);

})();
</script>
