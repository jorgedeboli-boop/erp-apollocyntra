<?php
/**
 * Modales: solicitud de código de autorización y motivo de devolución tras validar.
 * Incluir solo en ficha de artículo vendido / vendido_web.
 */
?>
<style>
  #codigo_DEVO {
    width: 100%;
    max-width: 376px;
    height: 68px;
    padding: 7px 0 9px;
    line-height: 0;
    font-size: 53px;
    text-align: center;
    letter-spacing: 6px;
    font-weight: bold;
    text-transform: uppercase;
    display: block;
    margin: 0 auto;
  }
  .devolucion-loader-overlay {
    display: none;
    position: fixed;
    width: 100%;
    height: 100%;
    z-index: 99999;
    background: rgba(255, 255, 255, 0.95);
    left: 0;
    top: 0;
    align-items: center;
    justify-content: center;
    flex-direction: column;
  }
  .devolucion-loader-overlay.is-visible {
    display: flex;
  }
  .devolucion-loader-overlay .lds-ring {
    display: inline-block;
    position: relative;
    width: 64px;
    height: 64px;
  }
  .devolucion-loader-overlay .lds-ring div {
    box-sizing: border-box;
    display: block;
    position: absolute;
    width: 64px;
    height: 64px;
    margin: 8px;
    border: 8px solid #999;
    border-radius: 50%;
    animation: devolucion-lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
    border-color: #999 transparent transparent transparent;
  }
  .devolucion-loader-overlay .lds-ring div:nth-child(1) { animation-delay: -0.45s; }
  .devolucion-loader-overlay .lds-ring div:nth-child(2) { animation-delay: -0.3s; }
  .devolucion-loader-overlay .lds-ring div:nth-child(3) { animation-delay: -0.15s; }
  #titleloader_devolucion {
    margin-top: 1rem;
    font-size: 19px;
    color: #999;
    text-align: center;
  }
  @keyframes devolucion-lds-ring {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
</style>

<div id="devolucion_loader_overlay" class="devolucion-loader-overlay" aria-hidden="true">
  <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
  <span id="titleloader_devolucion">…</span>
</div>

<div class="modal fade" id="auth_code_devolucion" tabindex="-1" aria-labelledby="auth_code_devolucion_label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="auth_code_devolucion_label">Autorización de devolución <span id="id_autorization"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="fw-medium mb-2">Introduzca el código de autorización.</p>
        <div>
          <label class="form-label visually-hidden" for="codigo_DEVO">Código</label>
          <input type="text" class="form-control text-center fw-bold" name="codigo_DEVO" id="codigo_DEVO" maxlength="6" autocomplete="off" inputmode="text" />
          <input id="id_auto_DEVO" type="hidden" value="" autocomplete="off" />
        </div>
      </div>
      <div class="modal-footer flex-column gap-2">
        <button type="button" class="btn btn-primary w-100" id="btn_check_code_devo"><i class="icon-base ri ri-checkbox-circle-line icon-20px me-1"></i> Comprobar autorización</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalGenerarDevolucionArticulo" tabindex="-1" aria-labelledby="modalGenerarDevolucionArticulo_label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalGenerarDevolucionArticulo_label">Generar devolución</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="id_autorizacion_para_insert" value="" />
        <input type="hidden" id="id_sucursal_devolucion" value="<?php echo (int) ($sucursal_articulo ?? 0); ?>" />
        <div>
          <label class="form-label" for="motivo_devolucion_modal_art">Motivo de la devolución</label>
          <textarea class="form-control" id="motivo_devolucion_modal_art" rows="4" placeholder="Describa el motivo…" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn_confirmar_devolucion_tras_auth">
          <i class="icon-base ri ri-refund-line me-1"></i>Generar devolución
        </button>
      </div>
    </div>
  </div>
</div>
