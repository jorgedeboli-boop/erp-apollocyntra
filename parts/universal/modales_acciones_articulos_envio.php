<!-- Modal Pasar a Stock (se creará dinámicamente para cada artículo) -->
<div class="modal fade" id="modalPasarStock" tabindex="-1" aria-labelledby="modalPasarStockLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
      <div role="alert" id="modalPasarStockLabel" class="alert alert-danger w-100 text-center fs-6 fw-bold mb-0">PASAR ARTÍCULO Nº <span id="modalPasarStockIdArticulo"></span> A STOCK!</div>
      </div>
      <div class="modal-body">
        <form id="formPasarStock">
          <input type="hidden" id="pasarStockIdArticulo" name="id_articulo">
          <input type="hidden" id="pasarStockIdSucursal" name="sucursal_articulo">
          <input type="hidden" id="pasarStockIdEnvio" name="id_envio">
          <input type="hidden" id="pasarStockIdLote" name="id_lote">
          <input type="hidden" name="type_action_articulo" value="pasar_a_stock">
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <input type="number" id="pasarStockPrecioVenta" class="form-control" name="precio_venta" step="1" min="0" placeholder="Precio de venta" required>
              <label for="pasarStockPrecioVenta">Precio de venta <span class="text-danger">*</span></label>
            </div>
          </div>
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <input type="text" id="pasarStockDescripcion" class="form-control" name="descripcion_articulo" placeholder="Descripción del artículo" required>
              <label for="pasarStockDescripcion">Descripción del artículo <span class="text-danger">*</span></label>
            </div>
          </div>
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <textarea id="pasarStockObservaciones" class="form-control" name="observaciones" placeholder="Observaciones" style="height: 100px"></textarea>
              <label for="pasarStockObservaciones">Observaciones</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-sm btn-danger waves-effect waves-light" id="btnConfirmarPasarStock">Pasar a Stock</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Fundir (se creará dinámicamente para cada artículo) -->
<div class="modal fade" id="modalFundir" tabindex="-1" aria-labelledby="modalFundirLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
      <div role="alert" id="modalFundirLabel" class="alert alert-danger w-100 text-center fs-6 fw-bold mb-0">FUNDIR ARTÍCULO Nº <span id="modalFundirIdArticulo"></span>!</div>
      </div>
      <div class="modal-body">
        <form id="formFundir">
          <input type="hidden" id="fundirIdArticulo" name="id_articulo">
          <input type="hidden" id="fundirIdSucursal" name="sucursal_articulo">
          <input type="hidden" id="fundirIdEnvio" name="id_envio">
          <input type="hidden" id="fundirIdLote" name="id_lote">
          <input type="hidden" name="type_action_articulo" value="fundir_articulo">
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <textarea id="fundirObservaciones" class="form-control" name="observaciones" placeholder="Observaciones" style="height: 100px"></textarea>
              <label for="fundirObservaciones">Observaciones</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-sm btn-danger waves-effect waves-light" id="btnConfirmarFundir">Fundir</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: pasar a fundición todos los artículos no auditados del envío -->
<div class="modal fade" id="modalFundirLoteEnvio" tabindex="-1" aria-labelledby="modalFundirLoteEnvioLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-center w-100" id="modalFundirLoteEnvioLabel">¿Está seguro que desea pasar los artículos no auditados de este envío a fundición?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formulario_fundir_lote">
          <input type="hidden" id="fundirLoteIdEnvio" name="id_envio" value="">
          <div class="form-floating form-floating-outline">
            <textarea id="comentario_fundicion" name="comentario_fundicion" class="form-control" placeholder="Comentarios" style="min-height: 100px"></textarea>
            <label for="comentario_fundicion">Comentarios sobre fundición</label>
          </div>
        </form>
      </div>
      <div class="modal-footer flex-nowrap gap-2">
        <button type="button" class="btn btn-secondary waves-effect waves-light" data-bs-dismiss="modal">No, cancelar</button>
        <button type="button" class="btn btn-warning waves-effect waves-light" id="btnConfirmarFundirLoteEnvio">Sí, pasar a fundición</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Faltante (se creará dinámicamente para cada artículo) -->
<div class="modal fade" id="modalFaltante" tabindex="-1" aria-labelledby="modalFaltanteLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
      <div role="alert" id="modalFaltanteLabel" class="alert alert-danger w-100 text-center fs-6 fw-bold mb-0">PASAR ARTÍCULO Nº <span id="modalFaltanteIdArticulo"></span> FALTANTE!</div>
      </div>
      <div class="modal-body">
        <form id="formFaltante">
          <input type="hidden" id="faltanteIdArticulo" name="id_articulo">
          <input type="hidden" id="faltanteIdSucursal" name="sucursal_articulo">
          <input type="hidden" id="faltanteIdEnvio" name="id_envio">
          <input type="hidden" id="faltanteIdLote" name="id_lote">
          <input type="hidden" name="type_action_articulo" value="faltante_articulo">
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <textarea id="faltanteObservaciones" class="form-control" name="observaciones" placeholder="Observaciones" style="height: 100px"></textarea>
              <label for="faltanteObservaciones">Observaciones</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-sm btn-danger waves-effect waves-light" id="btnConfirmarFaltante">Marcar como Faltante</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Mermar (se creará dinámicamente para cada artículo) -->
<div class="modal fade" id="modalMermar" tabindex="-1" aria-labelledby="modalMermarLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
      <div role="alert" id="modalMermarLabel" class="alert alert-danger w-100 text-center fs-6 fw-bold mb-0">MERMAR ARTÍCULO Nº <span id="modalMermarIdArticulo"></span>!</div>
      </div>
      <div class="modal-body">
        <form id="formMermar">
          <input type="hidden" id="mermarIdArticulo" name="id_articulo">
          <input type="hidden" id="mermarIdSucursal" name="sucursal_articulo">
          <input type="hidden" id="mermarIdEnvio" name="id_envio">
          <input type="hidden" id="mermarIdLote" name="id_lote">
          <input type="hidden" name="type_action_articulo" value="mermar_articulo">
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <textarea id="mermarObservaciones" class="form-control" name="observaciones" placeholder="Observaciones" style="height: 100px"></textarea>
              <label for="mermarObservaciones">Observaciones</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-sm btn-danger waves-effect waves-light" id="btnConfirmarMermar">Mermar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Rechazar (se creará dinámicamente para cada artículo) -->
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-labelledby="modalRechazarLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
      <div role="alert" id="modalRechazarLabel" class="alert alert-danger w-100 text-center fs-6 fw-bold mb-0">RECHAZAR ARTÍCULO Nº <span id="modalRechazarIdArticulo"></span>!</div>
      </div>
      <div class="modal-body">
        <form id="formRechazar">
          <input type="hidden" id="rechazarIdArticulo" name="id_articulo">
          <input type="hidden" id="rechazarIdSucursal" name="sucursal_articulo">
          <input type="hidden" id="rechazarIdEnvio" name="id_envio">
          <input type="hidden" id="rechazarIdLote" name="id_lote">
          <input type="hidden" name="type_action_articulo" value="rechazar_articulo">
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <input type="number" id="total_diferencia_euros" class="form-control" name="total_diferencia_euros" step="0.01" min="0" placeholder="Diferencia económica en €" required>
              <label for="total_diferencia_euros">Diferencia económica en € <span class="text-danger">*</span></label>
            </div>
          </div>
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <input type="number" id="total_diferencia_gramos" class="form-control" name="total_diferencia_gramos" step="0.01" min="0" placeholder="Diferencia de peso en gramos" required>
              <label for="total_diferencia_gramos">Diferencia de peso en gramos <span class="text-danger">*</span></label>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Devolver a central</label>

            <div class="row">
              <div class="col-md-4">
                <div class="form-check custom-option custom-option-icon checked">
                  <label class="form-check-label custom-option-content p-1" for="devolver_a_central_SI">
                    <span class="custom-option-body">
                      <span class="custom-option-title titlenumberplazos mb-0"> SI </span>
                    </span>
                    <input class="form-check-input" type="radio" name="devolver_a_central" value="SI" id="devolver_a_central_SI" checked>
                  </label>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="form-check custom-option custom-option-icon">
                  <label class="form-check-label custom-option-content p-1" for="devolver_a_central_NO">
                    <span class="custom-option-body">
                      <span class="custom-option-title titlenumberplazos mb-0"> NO </span>
                    </span>
                    <input class="form-check-input" type="radio" name="devolver_a_central" value="NO" id="devolver_a_central_NO">
                  </label>
                </div>
              </div>

            </div>
          </div>
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <textarea id="rechazarObservaciones" class="form-control" name="observaciones" placeholder="Observaciones" style="height: 100px"></textarea>
              <label for="rechazarObservaciones">Observaciones</label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary waves-effect waves-light" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-sm btn-danger waves-effect waves-light" id="btnConfirmarRechazar">Rechazar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Artículo -->
<div class="modal fade" id="modalEditarArticulo" tabindex="-1" aria-labelledby="modalEditarArticuloLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarArticuloLabel">Editar Artículo Nº <span id="modalEditarArticuloIdArticulo"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarArticulo">
          <input type="hidden" id="editarIdArticulo" name="id_articulo">
          <input type="hidden" id="editarIdSucursal" name="sucursal_articulo">
          <input type="hidden" id="editarIdEnvio" name="id_envio">
          <input type="hidden" id="editarIdLote" name="id_lote">
          <input type="hidden" name="type_action_articulo" value="editar_articulo">
          
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <input type="number" id="editarPrecioCompra" class="form-control" name="precio_compra" step="0.01" min="0" placeholder="Precio de compra" required>
              <label for="editarPrecioCompra">Precio de compra <span class="text-danger">*</span></label>
            </div>
          </div>
          
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <input type="number" id="editarPesoBruto" class="form-control" name="peso_bruto" step="0.01" min="0" placeholder="Peso bruto" required>
              <label for="editarPesoBruto">Peso bruto <span class="text-danger">*</span></label>
            </div>
          </div>
          
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <input type="number" id="editarPesoNeto" class="form-control" name="peso_neto" step="0.01" min="0" placeholder="Peso neto" required>
              <label for="editarPesoNeto">Peso neto <span class="text-danger">*</span></label>
            </div>
          </div>
          
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <input type="number" id="editarMerma" class="form-control" name="merma" step="0.01" min="0" placeholder="Merma">
              <label for="editarMerma">Merma</label>
            </div>
          </div>
          
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <select class="form-select" id="editarLey" name="ley" required>
                <option value="">Seleccione una ley</option>
                <!-- Opciones de oro -->
                <option value="9kl" class="opcion-oro">9 Quilates</option>
                <option value="14kl" class="opcion-oro">14 Quilates</option>
                <option value="16kl" class="opcion-oro">16 Quilates</option>
                <option value="17kl" class="opcion-oro">17 Quilates</option>
                <option value="18kl" class="opcion-oro">18 Quilates</option>
                <option value="19kl" class="opcion-oro">19 Quilates</option>
                <option value="20kl" class="opcion-oro">20 Quilates</option>
                <option value="21kl" class="opcion-oro">21 Quilates</option>
                <option value="22kl" class="opcion-oro">22 Quilates</option>
                <option value="23kl" class="opcion-oro">23 Quilates</option>
                <option value="24kl" class="opcion-oro">24 Quilates</option>
                <option value="216kl" class="opcion-oro">21,6 Quilates</option>
                <!-- Opciones de plata -->
                <option value="925" class="opcion-plata">925</option>
                <option value="900" class="opcion-plata">900</option>
                <option value="850" class="opcion-plata">850</option>
                <option value="999" class="opcion-plata">999</option>
              </select>
              <label for="editarLey">Ley <span class="text-danger">*</span></label>
            </div>
          </div>
          
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <textarea id="editarDescripcion" class="form-control" name="descripcion_articulo" placeholder="Descripción del artículo" style="height: 100px" required></textarea>
              <label for="editarDescripcion">Descripción del artículo <span class="text-danger">*</span></label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btnConfirmarEditarArticulo">Guardar cambios</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Comentario Trazabilidad -->
<div class="modal fade" id="modalEditarComentarioTrazabilidad" tabindex="-1" aria-labelledby="modalEditarComentarioTrazabilidadLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarComentarioTrazabilidadLabel">Editar Comentario de Trazabilidad</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarComentarioTrazabilidad">
          <input type="hidden" id="editarComentarioIdTrazabilidad" name="id_trazabilidad_articulo">
          
          <div class="mb-3">
            <div class="form-floating form-floating-outline">
              <textarea id="editarComentarioTexto" class="form-control" name="comentarios_accion" placeholder="Comentario" style="height: 150px" required></textarea>
              <label for="editarComentarioTexto">Comentario <span class="text-danger">*</span></label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btnConfirmarEditarComentario">Guardar comentario</button>
      </div>
    </div>
  </div>
</div>