<div class="modal modal-top fade" id="modalAdelantoCapitalVenta" tabindex="-1" aria-labelledby="modalAdelantoCapitalVentaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header card-header border-bottom card-header-forms pb-4">
        <h4 class="modal-title text-center" id="modalAdelantoCapitalVentaLabel">Adelanto de capital</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formAdelantoCapitalVentaModal" method="POST" novalidate="novalidate" class="fv-plugins-bootstrap5 fv-plugins-framework">
          <input type="hidden" id="id_venta_adelanto_capital" name="id_venta_adelanto_capital" value="<?php echo (int) ($id_venta ?? 0); ?>" />
          <input type="hidden" id="forma_de_pago_adelanto_venta" name="forma_de_pago_adelanto_venta" value="efectivo" />
          <input type="hidden" id="foto_camara_adelanto_venta" name="foto_camara_adelanto_venta" value="false" />
          <input type="hidden" id="id_foto_cache_adelanto_venta" name="id_foto_cache_adelanto_venta" value="" />

          <div class="row">
            <div class="col-12 mb-3">
              <div class="row text-muted">
                <div class="col-md-3 text-center">
                  <span class="badge-adelanto badge-grey">Capital actual: <span class="totales_textos" id="capital_actual_view_venta">0 €</span></span>
                </div>
                <div class="col-md-3 text-center">
                  <span class="badge-adelanto badge-grey">Capital pendiente: <span class="totales_textos" id="capital_pendiente_view_venta">0 €</span></span>
                </div>
                <div class="col-md-3 text-center">
                  <span class="badge-adelanto badge-grey">Importe cuota actual: <span class="totales_textos" id="importe_cuota_actual_view_venta">0 €</span></span>
                </div>
                <div class="col-md-3 text-center">
                  <span class="badge-adelanto badge-grey">Plazos pagados: <span class="totales_textos" id="plazos_info_view_venta">0 de 0</span></span>
                </div>
              </div>
            </div>

            <div class="col-12 mb-4">
              <div class="input-group input-group-lg adelanto_cliente-input-group">
                <span class="input-group-text adelanto_cliente-input-group-text" style="font-size: 31px; letter-spacing: -2px; ">
                  <span style="display:flex;align-items:center;justify-content:center;background-color:#555;width:1.875rem;height:1.875rem;margin-inline-end:1rem;border-radius:5px;">
                    <i class="icon-base ri ri-money-euro-circle-fill icon-md" style="color:#e8e9ed !important;"></i>
                  </span>
                  El cliente adelanta
                </span>
                <input type="text" inputmode="decimal" class="form-control text-end" id="adelanto_cliente_venta" name="adelanto_cliente_venta" placeholder="0" required style="font-size: 31px; font-weight: 700 !important;">
                <span class="input-group-text adelanto_cliente-input-group-text">€</span>
              </div>
            </div>

            <div class="col-12 mb-3">
              <div class="alert alert-primary alert-dismissible mb-0" role="alert">
                <h4 class="alert-heading d-flex align-items-center flex-wrap mb-0" style="font-size: 31px; letter-spacing: -2px;">
                  <span class="alert-icon rounded"><i class="icon-base ri ri-error-warning-line icon-md"></i></span>
                  <span>Debe cobrar ahora</span>
                  <span id="total_cobrar_adelanto_venta" class="ms-2 fw-bold">0 €</span>
                  <small class="w-100 mt-2 text-body-secondary" id="detalle_gastos_adelanto_venta"></small>
                </h4>
              </div>
            </div>

            <div class="col-12 mb-4">
              <div class="row">
                <div class="col-md-6 text-center">
                  <span class="badge-blue badge-adelanto">Nuevo capital pendiente: <span class="totales_textos" id="nuevo_capital_view_venta">—</span></span>
                </div>
                <div class="col-md-6 text-center">
                  <span class="badge-blue badge-adelanto">Nuevo importe por plazo: <span class="totales_textos" id="nuevo_importe_plazo_view_venta">—</span></span>
                </div>
              </div>
            </div>

            <div class="col-12 display_none" id="forma_de_pago_adelanto_venta_container">
              <div class="mt-0">
                <div class="row gy-4 mb-5">
                  <div class="col-md">
                    <div class="form-check custom-option custom-option-icon custom-option-blue">
                      <label class="form-check-label custom-option-content" for="adelanto_venta_forma_de_pago_efectivo">
                        <span class="custom-option-body">
                          <i class="mdi mdi-briefcase-account-outline"></i>
                          <span class="custom-option-title"> Efectivo </span>
                        </span>
                        <input name="forma_de_pago_adelanto_venta_radio" class="form-check-input forma_de_pago_adelanto_venta" type="radio" value="efectivo" id="adelanto_venta_forma_de_pago_efectivo" />
                      </label>
                    </div>
                  </div>
                  <div class="col-md">
                    <div class="form-check custom-option custom-option-icon custom-option-blue">
                      <label class="form-check-label custom-option-content" for="adelanto_venta_forma_de_pago_tarjeta">
                        <span class="custom-option-body">
                          <i class="mdi mdi-send-outline"></i>
                          <span class="custom-option-title"> Tarjeta </span>
                        </span>
                        <input name="forma_de_pago_adelanto_venta_radio" class="form-check-input forma_de_pago_adelanto_venta" type="radio" value="tarjeta" id="adelanto_venta_forma_de_pago_tarjeta" />
                      </label>
                    </div>
                  </div>
                  <div class="col-md">
                    <div class="form-check custom-option custom-option-icon custom-option-blue">
                      <label class="form-check-label custom-option-content" for="adelanto_venta_forma_de_pago_transferencia">
                        <span class="custom-option-body">
                          <i class="mdi mdi-crown-outline"></i>
                          <span class="custom-option-title"> Transferencia </span>
                        </span>
                        <input name="forma_de_pago_adelanto_venta_radio" class="form-check-input forma_de_pago_adelanto_venta" type="radio" value="transferencia" id="adelanto_venta_forma_de_pago_transferencia" />
                      </label>
                    </div>
                  </div>
                  <div class="col-md">
                    <div class="form-check custom-option custom-option-icon custom-option-blue">
                      <label class="form-check-label custom-option-content" for="adelanto_venta_forma_de_pago_bizum">
                        <span class="custom-option-body">
                          <i class="mdi mdi-crown-outline"></i>
                          <span class="custom-option-title"> Bizum </span>
                        </span>
                        <input name="forma_de_pago_adelanto_venta_radio" class="form-check-input forma_de_pago_adelanto_venta" type="radio" value="bizum" id="adelanto_venta_forma_de_pago_bizum" />
                      </label>
                    </div>
                  </div>
                </div>

                <div class="row gy-2 mb-3 display_none" id="comprobante_adelanto_venta_container">
                  <div class="col-md-12 text-center">
                    <label for="comprobante_adelanto_venta_archivo" class="btn btn-primary btn-sm mb-4" style="margin-right: 10px;" tabindex="0">
                      <i class="icon-base ri ri-upload-line icon-16px me-2"></i><span class="d-none d-sm-block">Subir comprobante de pago</span>
                      <input type="file" id="comprobante_adelanto_venta_archivo" name="comprobante_adelanto_venta_archivo" class="account-file-input" hidden accept="image/png, image/jpeg, image/pdf" />
                    </label>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-4" onclick="window.abrirModalFotoMovilAdelantoVenta()">
                      <i class="icon-base ri ri-camera-line icon-16px me-2"></i>Hacer Foto desde Móvil
                    </button>
                  </div>
                  <div class="col-md-12">
                    <div id="contenedor_imagenes_comprobante_adelanto_venta" class="text-center">
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
            <button type="submit" class="btn btn-success" id="btnAdelantoCapitalVentaModal">
              <i class="icon-base ri ri-check-line me-2"></i>
              Cobrado, guardar ahora
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php /* QR móvil: ahora se genera con CameraDocPanel (modal dinámico), no usar modalFotoMovilAdelantoVenta */ ?>
