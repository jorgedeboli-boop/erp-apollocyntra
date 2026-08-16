<!-- JAVASCRIPT CUSTOM articulo - main  -->
<?php require_once __DIR__ . '/../../../camera/render-config-script.php'; ?>
<script>
// Compatibilidad: algunos módulos usan `global` (estilo Node). En navegador debe mapear a `window`.
// No toca imagenes_articulo.js; solo asegura que exista la variable global.
window.global = window.global || window;
</script>
<?php
$vQrcodeMin = filemtime(__DIR__ . '/../../../js/qrcode.min.js');
$vCameraQr = filemtime(__DIR__ . '/../../../camera/js/camera-qr.js');
$vCameraDocPanel = filemtime(__DIR__ . '/../../../camera/js/camera-doc-panel.js');
$vImagenesArticulo = filemtime(__DIR__ . '/imagenes_articulo.js');
?>
<script src="js/qrcode.min.js?v=<?php echo $vQrcodeMin; ?>"></script>
<script src="camera/js/camera-qr.js?v=<?php echo $vCameraQr; ?>"></script>
<script src="camera/js/camera-doc-panel.js?v=<?php echo $vCameraDocPanel; ?>"></script>
<?php
if (isset($articulo) && !empty($articulo['id'])) {
    $_qr_suc = (int) (!empty($articulo['id_sucursal_destino']) ? $articulo['id_sucursal_destino'] : $usuario_sucursal);
    if ($_qr_suc <= 0) {
        $_qr_suc = (int) $usuario_sucursal;
    }
    if ($_qr_suc <= 0 && !empty($articulo['id_sucursal_origen'])) {
        $_qr_suc = (int) $articulo['id_sucursal_origen'];
    }
    ?>
<script>
window.articuloFotoMovilIds = {
  idArticulo: <?php echo (int) $articulo['id']; ?>,
  idSucursal: <?php echo $_qr_suc; ?>
};
</script>
<?php } ?>
<script>
/**
 * Función para eliminar artículo
 */
function eliminarArticuloVenta(idArticulo) {
    Swal.fire({
        icon: 'warning',
        title: '¿Está seguro?',
        text: '¿Está seguro que desea borrar este artículo? Esta acción no se puede deshacer.',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar petición para eliminar
            const formData = new FormData();
            formData.append('id_articulo', idArticulo);
            
            fetch('parts/articulos/main/eliminar_articulo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message || 'Artículo eliminado correctamente',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.href = 'articulos.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Error al eliminar el artículo',
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud. Por favor, intente nuevamente.',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

/**
 * Función para pasar artículo a merma
 */
function pasarAMerma(idArticulo) {
    Swal.fire({
        icon: 'question',
        title: '¿Pasar a merma?',
        html: `
            <p class="mb-3">¿Está seguro que desea pasar este artículo a merma?</p>
            <div class="form-group">
                <label for="motivo_merma" class="form-label">Motivo de la merma</label>
                <textarea id="motivo_merma" name="motivo_merma" class="form-control" rows="4" placeholder="Ingrese el motivo de la merma..." style="width: 100%; min-height: 100px;"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Sí, pasar a merma',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        preConfirm: () => {
            const motivo = document.getElementById('motivo_merma').value.trim();
            if (!motivo) {
                Swal.showValidationMessage('El motivo de la merma es obligatorio');
                return false;
            }
            return motivo;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const motivoMerma = result.value;
            
            // Mostrar loading
            Swal.fire({
                title: 'Procesando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar petición para pasar a merma
            const formData = new FormData();
            formData.append('id_articulo', idArticulo);
            formData.append('motivo_merma', motivoMerma);
            
            fetch('parts/articulos/main/pasar_a_merma.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message || 'Artículo pasado a merma correctamente',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Recargar la página para actualizar el estado
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Error al pasar el artículo a merma',
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud. Por favor, intente nuevamente.',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

/**
 * Función para retirar artículo
 */
function retirarArticulo(idArticulo) {
    Swal.fire({
        icon: 'warning',
        title: '¿Está seguro que desea retirar este artículo?',
        html: `
            <div class="form-group text-start">
                <label for="motivo_retirado" class="form-label">Describa comentario</label>
                <textarea id="motivo_retirado" name="motivo_retirado" class="form-control" rows="3" placeholder="Indique el motivo del retiro..." style="width: 100%;"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Sí, retirar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        preConfirm: () => {
            const motivo = document.getElementById('motivo_retirado').value.trim();
            if (!motivo) {
                Swal.showValidationMessage('Debe describir el comentario del retiro');
                return false;
            }
            return motivo;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const motivoRetirado = result.value;

            Swal.fire({
                title: 'Procesando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('id_articulo', idArticulo);
            formData.append('motivo_retirado', motivoRetirado);

            fetch('parts/articulos/main/retirar_articulo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message || 'Artículo retirado correctamente',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Error al retirar el artículo',
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud. Por favor, intente nuevamente.',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

/**
 * Abre nueva venta en crear_venta.php con el artículo.
 */
function enviarAVentaDesdeFichaArticulo(idArticulo) {
  idArticulo = parseInt(idArticulo, 10) || 0;
  if (!idArticulo) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'warning',
        title: 'Datos incompletos',
        text: 'Falta el identificador del artículo.'
      });
    }
    return;
  }
  if (typeof Swal === 'undefined') {
    return;
  }
  Swal.fire({
    icon: 'question',
    title: 'Vender artículo',
    text: '¿Está seguro que desea vender este artículo?',
    showCancelButton: true,
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#28a745',
    cancelButtonColor: '#6c757d',
    reverseButtons: true
  }).then(function (result) {
    if (!result.isConfirmed) {
      return;
    }
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'crear_venta.php';
    var inputArt = document.createElement('input');
    inputArt.type = 'hidden';
    inputArt.name = 'id_articulo';
    inputArt.value = String(idArticulo);
    form.appendChild(inputArt);
    document.body.appendChild(form);
    form.submit();
  });
}

/**
 * Imprimir / Re-imprimir etiqueta:
 * abre la impresión en una pestaña/ventana nueva y recarga la ficha al cerrar,
 * para reflejar el estado actualizado (p.ej. pasar de "no etiquetado" a "en venta").
 */
(function () {
  function buscarLinkEtiqueta(el) {
    if (!el || typeof el.closest !== 'function') {
      return null;
    }
    return el.closest('a[data-etiqueta-action]');
  }

  function abrirPopupImpresion(url) {
    var w = null;
    try {
      // Popup centrado y sin toolbars para impresión.
      var width = 640;
      var height = 720;
      var left = Math.max(0, Math.floor((window.screen.width - width) / 2));
      var top = Math.max(0, Math.floor((window.screen.height - height) / 2));
      var features =
        // Importante: NO usar noopener/noreferrer aquí porque necesitamos `window.opener`
        // para notificar a `articulo.php` y recargar al terminar la impresión.
        'popup=yes' +
        ',width=' +
        width +
        ',height=' +
        height +
        ',left=' +
        left +
        ',top=' +
        top;
      w = window.open(url, 'print_etiqueta_articulo', features);
    } catch (e) {
      w = null;
    }

    // Si el navegador bloquea el popup, dejamos el comportamiento normal (navegar al href).
    if (!w) {
      return false;
    }

    return w;
  }

  function recargarFicha() {
    // Recarga simple para reflejar el estado tras imprimir.
    window.location.reload();
  }

  var recargaProgramada = false;
  function recargarUnaSolaVez() {
    if (recargaProgramada) {
      return;
    }
    recargaProgramada = true;
    recargarFicha();
  }

  window.addEventListener('message', function (event) {
    // Solo aceptamos mensajes desde el mismo origen.
    if (!event || !event.origin || event.origin !== window.location.origin) {
      return;
    }
    if (!event.data || typeof event.data !== 'object') {
      return;
    }
    if (event.data && event.data.type === 'etiqueta:printed') {
      recargarUnaSolaVez();
    }
  });

  document.addEventListener('click', function (ev) {
    var a = buscarLinkEtiqueta(ev.target);
    if (!a) {
      return;
    }
    // Solo click normal (evita romper abrir en nueva pestaña con cmd/ctrl).
    if (ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) {
      return;
    }
    var href = a.getAttribute('href') || '';
    if (!href) {
      return;
    }
    ev.preventDefault();
    var w = abrirPopupImpresion(href);
    if (!w) {
      // fallback: si no se pudo abrir la ventana, navegamos normalmente
      window.location.href = href;
      return;
    }

    // Fallback adicional: si no llega el postMessage, recargamos cuando el popup se cierre.
    var maxMs = 5 * 60 * 1000; // seguridad: 5 minutos
    var startedAt = Date.now();
    var timer = window.setInterval(function () {
      if (Date.now() - startedAt > maxMs) {
        window.clearInterval(timer);
        return;
      }
      if (w.closed) {
        window.clearInterval(timer);
        recargarUnaSolaVez();
      }
    }, 600);
  });
})();
</script>
<?php
if (isset($articulo['id']) && in_array(strtolower((string) ($articulo['estado'] ?? '')), ['vendido', 'vendido_web'], true)) {
    $id_art_devo_js = (int) $articulo['id'];
    ?>
<script>
(function ($) {
  var skuArticuloDevolucion = <?php echo $id_art_devo_js; ?>;
  var urlSolicitarAuth = 'parts/articulos/main/insert_codigo_solicitar.php';
  var urlCheckAuth = 'parts/articulos/main/check_codigo_autorizacion.php';
  var urlInsertDevolucion = 'parts/articulos/main/insertar_devolucion_desde_articulo.php';

  function devolucionLoader(title) {
    var $ov = $('#devolucion_loader_overlay');
    if (!$ov.length) {
      return;
    }
    $('#titleloader_devolucion').text(title || '…');
    $ov.addClass('is-visible').attr('aria-hidden', 'false');
  }

  function devolucionLoaderHide() {
    var $ov = $('#devolucion_loader_overlay');
    $ov.removeClass('is-visible').attr('aria-hidden', 'true');
  }

  function getBsModal(el) {
    if (typeof window.bootstrap === 'undefined' || !el) {
      return null;
    }
    return window.bootstrap.Modal.getOrCreateInstance(el);
  }

  function aplicarUiTrasDevolucionCreada(estadoTexto) {
    estadoTexto = estadoTexto || 'enventa';
    var badgeClass = 'info';
    var $h = $('#articulo_estado_badge_header');
    var $d = $('#articulo_estado_badge_detalle');
    if ($h.length) {
      $h.removeClass(function (i, c) {
        return (c.match(/(^|\s)bg-label-\S+/g) || []).join(' ');
      });
      $h.addClass('bg-label-' + badgeClass).text(estadoTexto);
    }
    if ($d.length) {
      $d.removeClass(function (i, c) {
        return (c.match(/(^|\s)bg-label-\S+/g) || []).join(' ');
      });
      $d.addClass('bg-label-' + badgeClass).text(estadoTexto);
    }
    var $btnDev = $('#btn_get_autorization_devolucion');
    if ($btnDev.length) {
      $btnDev.prop('disabled', true).addClass('disabled');
      $btnDev.html('<i class="icon-base ri ri-checkbox-circle-fill icon-20px me-1"></i>Devolución registrada');
    }
    $('#btn_confirmar_devolucion_tras_auth').prop('disabled', true);
    $('#motivo_devolucion_modal_art').prop('readonly', true);
  }

  $(document).on('click', '#btn_get_autorization_devolucion', function (evento) {
    evento.preventDefault();
    var typeDevolucion = $(this).attr('data-type-devolucion') || 'normal';
    devolucionLoader('Solicitando autorización…');
    $.ajax({
      url: urlSolicitarAuth,
      type: 'POST',
      data: {
        sku_articulo_devolucion: skuArticuloDevolucion,
        tipo_devolucion: typeDevolucion
      },
      dataType: 'json'
    }).done(function (data) {
      devolucionLoaderHide();
      if (!data || data.statelogdevo !== 'ok') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: (data && data.message) ? data.message : 'No se pudo solicitar la autorización.' });
        }
        return;
      }
      $('#id_auto_DEVO').val(data.id_auth);
      $('#id_autorization').text('Nº ' + data.id_auth);
      $('#codigo_DEVO').val('');
      var modalEl = document.getElementById('auth_code_devolucion');
      var m = getBsModal(modalEl);
      if (m) {
        m.show();
      }
    }).fail(function () {
      devolucionLoaderHide();
      if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de red al solicitar la autorización.' });
      }
    });
  });

  $(document).on('click', '#btn_check_code_devo', function (evento) {
    evento.preventDefault();
    devolucionLoader('Comprobando…');
    var idDevo = $('#id_auto_DEVO').val();
    var codigoDevo = $('#codigo_DEVO').val();
    $.ajax({
      url: urlCheckAuth,
      type: 'POST',
      data: {
        id_DEVO: idDevo,
        codigo_DEVO: codigoDevo
      },
      dataType: 'json'
    }).done(function (data) {
      devolucionLoaderHide();
      if (!data || data.same_code !== 'ok') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'warning', title: 'Código incorrecto', text: 'El código no coincide. Inténtelo de nuevo.' });
        } else {
          alert('El código no coincide. Inténtelo de nuevo.');
        }
        return;
      }
      if (parseInt(data.id_articulo, 10) !== skuArticuloDevolucion) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: 'La autorización no corresponde a este artículo.' });
        }
        return;
      }
      var authEl = document.getElementById('auth_code_devolucion');
      var genEl = document.getElementById('modalGenerarDevolucionArticulo');
      var mAuth = getBsModal(authEl);
      if (mAuth) {
        mAuth.hide();
      }
      $('#id_autorizacion_para_insert').val(data.id_autorizacion);
      $('#motivo_devolucion_modal_art').val('');
      var mGen = getBsModal(genEl);
      if (mGen) {
        mGen.show();
      }
    }).fail(function () {
      devolucionLoaderHide();
      if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error al comprobar el código.' });
      }
    });
  });

  $(document).on('click', '#btn_confirmar_devolucion_tras_auth', function (evento) {
    evento.preventDefault();
    var sucDevolucion = $('#id_sucursal_devolucion').val();
    var motivo = ($('#motivo_devolucion_modal_art').val() || '').trim();
    if (!motivo) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Indique el motivo de la devolución.' });
      }
      return;
    }
    var idAuth = $('#id_autorizacion_para_insert').val();
    devolucionLoader('Generando devolución…');
    var fd = new FormData();
    fd.append('id_articulo', String(skuArticuloDevolucion));
    fd.append('motivo_devolucion', motivo);
    fd.append('id_autorizacion', idAuth);
    fd.append('sucursal_devolucion', sucDevolucion);
    fetch(urlInsertDevolucion, { method: 'POST', body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (res) {
        devolucionLoaderHide();
        var genEl = document.getElementById('modalGenerarDevolucionArticulo');
        var mGen = getBsModal(genEl);
        if (mGen) {
          mGen.hide();
        }
        if (res && res.success) {
          var nuevoEst = res.estado_articulo ? String(res.estado_articulo) : 'enventa';
          aplicarUiTrasDevolucionCreada(nuevoEst);
          var msg = res.message || '';
          if (res.id_devolucion) {
            msg = (msg ? msg + ' ' : '') + '(Devolución n.º ' + res.id_devolucion + ')';
          }
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'success', title: 'Devolución creada', text: msg });
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: (res && res.message) ? res.message : 'No se pudo crear la devolución.' });
          }
        }
      })
      .catch(function () {
        devolucionLoaderHide();
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Error al procesar la solicitud.' });
        }
      });
  });
})(jQuery);
</script>
    <?php
}
?>
<script src="parts/articulos/main/imagenes_articulo.js?v=<?php echo $vImagenesArticulo; ?>"></script>
