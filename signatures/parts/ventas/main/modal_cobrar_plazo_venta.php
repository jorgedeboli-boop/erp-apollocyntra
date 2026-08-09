<?php
/**
 * Modal cobro de cuota (ventas a plazos) — patrón similar a modal_renovar_empeno (lotes).
 */
?>
<div class="modal modal-top fade" id="modalCobrarPlazoVenta" tabindex="-1" aria-labelledby="modalCobrarPlazoVentaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header card-header border-bottom card-header-forms pb-4">
        <h4 class="modal-title text-center" id="modalCobrarPlazoVentaLabel">Cobrar plazo en efectivo</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formCobrarPlazoVentaModal" method="POST" novalidate="novalidate" class="fv-plugins-bootstrap5 fv-plugins-framework">
          <input type="hidden" name="foto_camara_cobrar_plazo" id="foto_camara_cobrar_plazo" value="false">
          <input type="hidden" name="id_foto_cache_plazo_venta" id="id_foto_cache_plazo_venta" value="">
          <input type="hidden" id="id_plazo_cobrar_venta" name="id_plazo_cobrar_venta" value="" />
          <input type="hidden" id="id_venta_cobrar_plazo" name="id_venta_cobrar_plazo" value="<?php echo (int) ($id_venta ?? 0); ?>" />
          <input type="hidden" id="forma_de_pago_cobrar_plazo_venta" name="forma_de_pago_cobrar_plazo_venta" value="efectivo" />
          <input type="hidden" id="importe_plazo_cobrar_venta" name="importe_plazo_cobrar_venta" value="" />

          <div class="row">
            <div class="col-12 mb-3">
              <div class="alert alert-primary alert-dismissible mb-0" role="alert">
                <h4 class="alert-heading d-flex align-items-center mb-0" style="font-size: 32px; letter-spacing: -2px;">
                  <span class="alert-icon rounded"><i class="icon-base ri ri-error-warning-line icon-md"></i></span>Debe cobrar ahora
                  <span id="totales_modal_cobrar_plazo_venta" class="ms-2 fw-bold"></span>
                </h4>
              </div>
            </div>

            <div class="col-12">
              <div class="mt-3">
                <div class="row gy-4 mb-5">
                  <div class="col-md">
                    <div class="form-check custom-option custom-option-icon custom-option-blue">
                      <label class="form-check-label custom-option-content" for="cobrar_plazo_forma_de_pago_efectivo">
                        <span class="custom-option-body">
                          <i class="mdi mdi-briefcase-account-outline"></i>
                          <span class="custom-option-title"> Efectivo </span>
                        </span>
                        <input name="forma_de_pago_cobrar_plazo_radio" class="form-check-input forma_de_pago_cobrar_plazo_venta" type="radio" value="efectivo" id="cobrar_plazo_forma_de_pago_efectivo" />
                      </label>
                    </div>
                  </div>
                  <div class="col-md">
                    <div class="form-check custom-option custom-option-icon custom-option-blue">
                      <label class="form-check-label custom-option-content" for="cobrar_plazo_forma_de_pago_tarjeta">
                        <span class="custom-option-body">
                          <i class="mdi mdi-send-outline"></i>
                          <span class="custom-option-title"> Tarjeta </span>
                        </span>
                        <input name="forma_de_pago_cobrar_plazo_radio" class="form-check-input forma_de_pago_cobrar_plazo_venta" type="radio" value="tarjeta" id="cobrar_plazo_forma_de_pago_tarjeta" />
                      </label>
                    </div>
                  </div>
                  <div class="col-md">
                    <div class="form-check custom-option custom-option-icon custom-option-blue">
                      <label class="form-check-label custom-option-content" for="cobrar_plazo_forma_de_pago_transferencia">
                        <span class="custom-option-body">
                          <i class="mdi mdi-crown-outline"></i>
                          <span class="custom-option-title"> Transferencia </span>
                        </span>
                        <input name="forma_de_pago_cobrar_plazo_radio" class="form-check-input forma_de_pago_cobrar_plazo_venta" type="radio" value="transferencia" id="cobrar_plazo_forma_de_pago_transferencia" />
                      </label>
                    </div>
                  </div>
                  <div class="col-md">
                    <div class="form-check custom-option custom-option-icon custom-option-blue">
                      <label class="form-check-label custom-option-content" for="cobrar_plazo_forma_de_pago_bizum">
                        <span class="custom-option-body">
                          <i class="mdi mdi-crown-outline"></i>
                          <span class="custom-option-title"> Bizum </span>
                        </span>
                        <input name="forma_de_pago_cobrar_plazo_radio" class="form-check-input forma_de_pago_cobrar_plazo_venta" type="radio" value="bizum" id="cobrar_plazo_forma_de_pago_bizum" />
                      </label>
                    </div>
                  </div>
                </div>

                <div class="row gy-2 mb-3 display_none" id="comprobante_cobrar_plazo_venta_container">
                  <div class="col-md-12 text-center">
                    <label for="comprobante_cobrar_plazo_venta_archivo" class="btn btn-primary btn-sm mb-4 btn_subir_comprobante_clase_cobrar_plazo" style="margin-right: 10px;" tabindex="0">
                      <i class="icon-base ri ri-upload-line icon-16px me-2"></i><span class="d-none d-sm-block">Subir comprobante de pago</span>
                      <input type="file" id="comprobante_cobrar_plazo_venta_archivo" name="comprobante_cobrar_plazo_venta_archivo" class="account-file-input"
                        hidden accept="image/png,image/jpeg,image/jpg,application/pdf,.pdf" />
                    </label>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-4 btn_subir_comprobante_clase_cobrar_plazo" onclick="window.abrirModalFotoMovilCobrarPlazoVenta()">
                      <i class="icon-base ri ri-camera-line icon-16px me-2"></i>Hacer Foto desde Móvil
                    </button>
                  </div>
                  <div class="col-md-12">
                    <div id="contenedor_imagenes_comprobante_cobrar_plazo_venta" class="text-center">
                      <i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>
                      <p class="text-body-secondary mb-0">No hay comprobante cargado</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4" />

          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
              <i class="icon-base ri ri-close-line me-2"></i>
              Cancelar
            </button>
            <button type="submit" class="btn btn-primary" id="btnCobrarPlazoVentaModal">
              <i class="icon-base ri ri-check-line me-2"></i>
              Cobrar plazo
            </button>
          </div>
        </form>
        <legend style="text-align: left !important;font-size: 9px !important;">Cuota id: <span id="id_plazo_view_cobrar_venta"></span></legend>
      </div>
    </div>
  </div>
</div>

<?php /* QR móvil: ahora se genera con CameraDocPanel (modal dinámico), no usar modalFotoMovilCobrarPlazoVenta */ ?>
