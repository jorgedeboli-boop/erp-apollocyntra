<?php
/**
 * Modales Bootstrap para QR “foto desde móvil” en ficha de lote (central / sucursal).
 * Requiere $lote con: id_cliente, id_lote, id_renovacion (opcional), sucursal.
 * Tras incluir: cargar camera/render-config-script.php + js/qrcode + camera/js/camera-qr.js
 * y registrar callbacks si hace falta (recarga de imágenes, spinners, etc.).
 */
if (!isset($lote) || !is_array($lote)) {
    return;
}
$id_cliente = (int) ($lote['id_cliente'] ?? 0);
$id_lote = (int) ($lote['id_lote'] ?? 0);
$id_renovacion = (int) ($lote['id_renovacion'] ?? 0);
$id_sucursal = (int) ($lote['sucursal'] ?? 0);
?>
<!-- Camera: modales QR (móvil) — lote -->
<div class="modal fade" id="modalFotoMovilCliente" tabindex="-1" aria-labelledby="modalFotoMovilClienteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFotoMovilClienteLabel">Hacer foto desde móvil test</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-5">
        <p class="mb-4">Escanee el código QR con su móvil</p>
        <div id="qrcode_container" class="d-flex justify-content-center">
          <div id="qrcode_cliente"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="generarNuevoQR('cliente', <?php echo $id_cliente; ?>, <?php echo $id_sucursal; ?>)">
          <i class="icon-base ri ri-refresh-line me-2"></i>Generar nuevo QR
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalFotoMovilLote" tabindex="-1" aria-labelledby="modalFotoMovilLoteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFotoMovilLoteLabel">Hacer foto desde móvil (lote)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-5">
        <p class="mb-4">Escanee el código QR con su móvil</p>
        <div id="qrcode_container" class="d-flex justify-content-center">
          <div id="qrcode_lote"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="generarNuevoQR('lote', <?php echo $id_lote; ?>, <?php echo $id_sucursal; ?>)">
          <i class="icon-base ri ri-refresh-line me-2"></i>Generar nuevo QR
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalFotoMovilRenovacion" tabindex="-1" aria-labelledby="modalFotoMovilRenovacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFotoMovilRenovacionLabel">Comprobante de pago desde móvil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-5">
        <p class="mb-4">Escanee el código QR con su móvil</p>
        <div id="qrcode_container" class="d-flex justify-content-center">
          <div id="qrcode_renovacion"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="generarNuevoQR('renovacion', <?php echo $id_renovacion; ?>, <?php echo $id_sucursal; ?>)">
          <i class="icon-base ri ri-refresh-line me-2"></i>Generar nuevo QR
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalFotoMovilAdelanto" tabindex="-1" aria-labelledby="modalFotoMovilAdelantoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFotoMovilAdelantoLabel">Comprobante de adelanto desde móvil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-5">
        <p class="mb-4">Escanee el código QR con su móvil</p>
        <div id="qrcode_container" class="d-flex justify-content-center">
          <div id="qrcode_adelanto"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="generarNuevoQR('adelanto', <?php echo $id_lote; ?>, <?php echo $id_sucursal; ?>)">
          <i class="icon-base ri ri-refresh-line me-2"></i>Generar nuevo QR
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
