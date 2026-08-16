<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h5 class="card-title mb-0">Crear artículo</h5>
          <small class="text-muted">Complete el formulario para crear un nuevo artículo</small>
          <button type="button" id="btn_volver_articulos" class="btn btn-text-primary btn-header-card-right">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Artículos
          </button>
        </div>
        <div class="card-body mt-5">
          <form id="formCrearArticulo" method="POST" action="parts/articulos/crear/insertar_articulo.php" class="fv-plugins-bootstrap5 fv-plugins-framework">
            
            <!-- Datos de vinculación -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Datos de vinculación</h5>
              </div>
              
              <div class="col-md-6 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="text" class="form-control" id="lote_origen" name="lote_origen" placeholder="Lote origen" />
                  <label for="lote_origen">Lote origen</label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Datos de artículo -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Datos de artículo</h5>
              </div>
              
              <div class="col-md-6 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_venta" name="precio_venta" placeholder="Precio de venta" required />
                  <label for="precio_venta">Precio de venta *</label>
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_coste" name="precio_coste" placeholder="Precio de coste" />
                  <label for="precio_coste">Precio de coste </label>
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="peso" name="peso" placeholder="Peso" required />
                  <label for="peso">Peso (g) *</label>
                </div>
              </div>
              
              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline" id="container_ley_oro">
                  <select class="form-select select2" id="leyoro" name="ley" >
                    <option value="">Seleccionar...</option>
                    <option value="9kl">9 Quilates</option>
                    <option value="14kl">14 Quilates</option>
                    <option value="16kl">16 Quilates</option>
                    <option value="17kl">17 Quilates</option>
                    <option selected="selected" value="18kl">18 Quilates</option>
                    <option value="19kl">19 Quilates</option>
                    <option value="20kl">20 Quilates</option>
                    <option value="21kl">21 Quilates</option>
                    <option value="22kl">22 Quilates</option>
                    <option value="23kl">23 Quilates</option>
                    <option value="24kl">24 Quilates</option>
                    <option value="216kl">21,6 Quilates</option>
                  </select>
                  <label for="leyoro" class="form-label">Ley </label>
                </div>
                <div class="form-floating form-floating-outline" id="container_ley_plata" style="display: none;">
                  <select class="form-select select2" id="leyplata" name="ley">
                    <option value="">Seleccionar...</option>
                    <option value="925" selected="selected">925</option>
                    <option value="900">900</option>
                    <option value="850">850</option>
                    <option value="999">999</option>
                  </select>
                  <label for="leyplata" class="form-label">Ley </label>
                </div>
              </div>
              
              <div class="col-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <textarea class="form-control" id="descripcion" name="descripcion" placeholder="Descripción" style="min-height: 100px;" required></textarea>
                  <label for="descripcion">Descripción *</label>
                </div>
              </div>
              
              <!-- Inscripciones -->
              <div class="col-12 mb-4">
                <label class="form-label mb-3">Inscripciones</label>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <div class="form-check custom-option custom-option-basic checked">
                      <label class="form-check-label custom-option-content" for="inscripciones_si">
                        <input class="form-check-input" type="radio" name="inscripciones" value="si" id="inscripciones_si">
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Si</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="inscripciones_no">
                        <input class="form-check-input" type="radio" name="inscripciones" value="no" id="inscripciones_no" checked>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">No</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Piedras -->
              <div class="col-12 mb-4">
                <label class="form-label mb-3">Piedras</label>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <div class="form-check custom-option custom-option-basic checked">
                      <label class="form-check-label custom-option-content" for="piedras_si">
                        <input class="form-check-input" type="radio" name="piedras" value="si" id="piedras_si">
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Si</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="piedras_no">
                        <input class="form-check-input" type="radio" name="piedras" value="no" id="piedras_no" checked>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">No</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Tipo de artículo -->
              <div class="col-12 mb-4">
                <label class="form-label mb-3">Tipo de artículo *</label>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic checked">
                      <label class="form-check-label custom-option-content" for="tipo_articulo_oro">
                        <input class="form-check-input" type="radio" name="tipo_articulo" value="oro" id="tipo_articulo_oro" checked required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Oro</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="tipo_articulo_plata">
                        <input class="form-check-input" type="radio" name="tipo_articulo" value="plata" id="tipo_articulo_plata" required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Plata</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="tipo_articulo_acero">
                        <input class="form-check-input" type="radio" name="tipo_articulo" value="acero" id="tipo_articulo_acero" required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Acero</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Régimen fiscal (articulos_venta.system_codigo_regimen) -->
              <div class="col-12 mb-4">
                <label class="form-label mb-3">Régimen fiscal *</label>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic checked">
                      <label class="form-check-label custom-option-content" for="system_codigo_regimen_REBU">
                        <input class="form-check-input" type="radio" name="system_codigo_regimen" value="REBU" id="system_codigo_regimen_REBU" checked required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">REBU</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="system_codigo_regimen_INVERSION">
                        <input class="form-check-input" type="radio" name="system_codigo_regimen" value="INVERSION" id="system_codigo_regimen_INVERSION" required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">ORO INVERSIÓN</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="system_codigo_regimen_GENERAL">
                        <input class="form-check-input" type="radio" name="system_codigo_regimen" value="GENERAL" id="system_codigo_regimen_GENERAL" required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">RÉGIMEN GENERAL</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tipo de IVA (articulos_venta.tipo_iva_articulo: ENUM) -->
              <div class="col-12 mb-4">
                <label class="form-label mb-3">Tipo de IVA *</label>
                <div class="row">
                  <div class="col-md-3 mb-3">
                    <div class="form-check custom-option custom-option-basic checked">
                      <label class="form-check-label custom-option-content" for="tipo_iva_articulo_IVA">
                        <input class="form-check-input" type="radio" name="tipo_iva_articulo" value="IVA" id="tipo_iva_articulo_IVA" checked required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">IVA</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-3 mb-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="tipo_iva_articulo_IPSI">
                        <input class="form-check-input" type="radio" name="tipo_iva_articulo" value="IPSI" id="tipo_iva_articulo_IPSI" required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">IPSI</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-3 mb-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="tipo_iva_articulo_IGIC">
                        <input class="form-check-input" type="radio" name="tipo_iva_articulo" value="IGIC" id="tipo_iva_articulo_IGIC" required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">IGIC</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-3 mb-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="tipo_iva_articulo_OTHER">
                        <input class="form-check-input" type="radio" name="tipo_iva_articulo" value="OTHER" id="tipo_iva_articulo_OTHER" required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Otro</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <textarea class="form-control" id="observaciones" name="observaciones" placeholder="Observaciones" style="min-height: 100px;"></textarea>
                  <label for="observaciones">Observaciones</label>
                </div>
              </div>
            </div>
            
            <!-- Botones -->
            <div class="mt-4">
              <button type="submit" class="btn btn-primary me-2">
                <i class="icon-base ri ri-check-line me-2"></i>Crear artículo
              </button>
              <button type="button" id="btn_cancelar_articulo" class="btn btn-outline-secondary">
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->
