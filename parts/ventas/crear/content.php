<?php
$id_sucursal = isset($_POST['id_sucursal']) ? (int)$_POST['id_sucursal'] : 0;
$id_articulo = isset($_POST['id_articulo']) ? (int)$_POST['id_articulo'] : 0;
$empresa_sesion = function_exists('obtener_datos_empresa_sesion') ? obtener_datos_empresa_sesion() : null;
if (!is_array($empresa_sesion)) {
    $empresa_sesion = [];
}
$empTxt = function ($k, $d = '-') use ($empresa_sesion) {
    $v = trim((string) ($empresa_sesion[$k] ?? ''));
    return $v !== '' ? $v : $d;
};
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">

<div class="row invoice-preview">

  <!-- Invoice -->
  <div class="col-xl-9 col-md-8 col-12 mb-md-0 mb-6">
    <div class="card invoice-preview-card">
      
      <!-- Header integrado -->
      <div class="card-header border-bottom card-header-forms titulos-cards-pages pb-3">
          <h5 class="card-title mb-0">Nueva venta</h5>
          <button type="button" id="btn_volver_ventas" class="btn btn-text-primary btn-header-card-right mt-4">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Ventas
          </button>
      </div>
      
      <!-- Card body único con todo el contenido -->
      <div class="card-body p-5">

        <!-- Input hidden con id_sucursal recibido por POST -->
        <input type="hidden" id="sucursal_venta" name="sucursal_venta" value="<?php echo (int) $id_sucursal; ?>" />
        
        <!-- Input hidden con id_articulo recibido por POST (opcional) -->
        <?php if ($id_articulo): ?>
        <input type="hidden" id="articulo_venta" name="articulo_venta" value="<?php echo htmlspecialchars($id_articulo); ?>" />
        <?php endif; ?>
       
        <!-- Formulario oculto para INSERT de venta -->
        <form id="form_insert_venta" style="display: none;">
         <!-- Datos de la venta -->
         <input type="hidden" id="insert_id_sucursal" name="id_sucursal" value="0" />
         <input type="hidden" value="" id="codigo_autorizacion_correcto">
          <input type="hidden" value="" id="id_autorizacion" name="id_autorizacion">
          <input type="hidden" name="precio_inicial" id="precio_inicial" value="">
          <input type="hidden" name="porcentaje_venta_plazos" id="porcentaje_venta_plazos" value="">
          <input type="hidden" name="interes" value="" id="interes_valor">

         
         <!-- Datos del cliente -->
         <input type="hidden" id="insert_id_cliente" name="id_cliente" value="" />
         <input type="hidden" id="insert_tipo_identificacion" name="tipo_identificacion" value="" />
         <input type="hidden" id="insert_identificacion" name="identificacion" value="" />
         <input type="hidden" id="insert_nombre" name="nombre" value="" />
         <input type="hidden" id="insert_apellido" name="apellido" value="" />
         <input type="hidden" id="insert_telefono" name="telefono" value="" />
         <input type="hidden" id="insert_email" name="email" value="" />
         
         <!-- Datos de dirección -->
         <input type="hidden" id="insert_id_direccion" name="id_direccion" value="" />
         <input type="hidden" id="insert_pais" name="pais" value="" />
         <input type="hidden" id="insert_provincia" name="provincia" value="" />
         <input type="hidden" id="insert_poblacion" name="poblacion" value="" />
         <input type="hidden" id="insert_direccion" name="direccion" value="" />
         <input type="hidden" id="insert_codigo_postal" name="codigo_postal" value="" />
         
         <!-- Artículos (SKUs separados por comas) -->
         <input type="hidden" id="insert_articulos_skus" name="articulos_skus" value="" />
         <input type="hidden" id="insert_articulos_ids" name="articulos_ids" value="" />
       </form>
       
      <div id="cuerpo_venta" class="desenfocar">
      <!-- Info de venta -->
      <div class="invoice-preview-header rounded-4 p-5 mb-2 position-relative">
        <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column text-heading align-items-xl-center align-items-md-start align-items-sm-center flex-wrap gap-6">
          <div>
            <div class="d-flex gap-2 mb-2">
              <h5 class="mb-0" id="nombre_empresa"><?php echo htmlspecialchars($empTxt('nombre_empresa')); ?></h5>
            </div>
            <p class="mb-1 texto_direccion" id="cif_empresa"><?php echo $empTxt('cif_empresa') !== '-' ? 'CIF: ' . htmlspecialchars($empTxt('cif_empresa')) : '-'; ?></p>
            <p class="mb-1 texto_direccion" id="direccion_sucursal"><?php echo htmlspecialchars($empTxt('direccion_empresa')); ?></p>
            <p class="mb-1 texto_direccion" id="otrosdatos_sucursal"><span id="poblacion_sucursal"><?php echo htmlspecialchars($empTxt('poblacion_empresa')); ?></span> <span id="codigo_postal_sucursal"><?php echo htmlspecialchars($empTxt('codigo_postal_empresa')); ?></span></p>
            <p class="mb-1 texto_direccion" id="telefono_sucursal"><?php echo htmlspecialchars($empTxt('telefono_empresa')); ?></p>
            <p class="mb-1 texto_direccion" id="email_empresa"><?php echo htmlspecialchars($empTxt('email_empresa')); ?></p>
          </div>
          
          <!-- Skeleton (mostrar mientras carga) -->
          <div class="text-end align-items-end" id="skeleton-cliente">
            <div class="mb-2">
              <div class="skeleton skeleton-h5"></div>
            </div>
            <div class="skeleton skeleton-line mb-1"></div>
            <div class="skeleton skeleton-line-short mb-1"></div>
            <div class="skeleton skeleton-line-medium mb-1"></div>
            <div class="skeleton skeleton-line-short mb-1"></div>
          </div>

          <!-- Contenido real (ocultar mientras carga) -->
          <div class="text-end align-items-end" id="datos-cliente" style="display: none;">
            <div class="mb-2">
              <h5 class="mb-0" id="nombre_cliente">Nombre cliente</h5>
            </div>
            <p class="mb-1 texto_direccion" id="dni_cliente"><span id="tipo_identificacion_cliente">NIF</span> 123456789</p>
            <p class="mb-1 texto_direccion" id="direccion_cliente">cale los patos 222</p>
            <p class="mb-1 texto_direccion" id="otrosdatos_cliente"><span id="poblacion_cliente">Valencia</span> <span id="codigo_postal_cliente">46001</span></p>
            <p class="mb-1 texto_direccion" id="telefono_cliente">Teléfono: 963 123 456</p>
          </div>
          
        </div>
        
        <!-- Botón posicionado absoluto en la esquina inferior derecha -->
        <a href=" javascript:;" data-bs-toggle="modal" data-bs-target="#datos_cliente" class="btn btn-primary waves-effect waves-light" style="position: absolute; bottom: -1.2rem; right: 1rem;">
          <i class="icon-base ri ri-user-3-line icon-22px me-2"></i>Datos del cliente
        </a>
         
        </div>

      
      <!-- Artículos -->
      <div class="table-responsive border rounded-4 border-bottom-0 mt-10 mb-4">

        <table class="table m-0" id="tabla_articulos_venta">
          <thead>
            <tr>
              <th style="width: auto;">Descripción</th>
              <th style="width: 93px;" class="text-center">Uds.</th>
              <th style="width: 146px;" class="text-start">Precio unitario</th>
              <th style="width: 120px;" class="text-start">Precio</th>
              <th style="width: 70px;"> </th>
            </tr>
          </thead>
          <tbody id="articulos_venta_body">
            <tr>
              <td colspan="5" class="text-center text-muted py-6">
                No hay artículos agregados
              </td>
            </tr>
          </tbody>
        </table>

      </div>
      <div class="d-flex align-items-center">
        <div class="input-group" style="max-width: 250px;">
          <input
            type="text"
            id="input_sku_venta"
            class="form-control input-sku-articulo"
            placeholder="Buscar por SKU"
            autocomplete="off"
          />
          <button
            type="button"
            class="btn btn-primary waves-effect p-3 border-start-0"
            id="btn_buscar_sku_venta"
            title="Buscar artículo"
          >
            <i class="icon-base ri ri-search-line icon-21px"></i>
          </button>
        </div>
      </div>
      <!-- Totales -->
      <div class="table-responsive">

        <table class="table m-0 table-borderless">

          <tbody>

            <tr>
              <td class="align-top px-0 py-6">
                <div class="mb-1">
                  <span class="fw-medium text-heading">Vendedor:</span>
                  <span id="vendedor_nombre_footer"><?php echo isset($_SESSION['usuario_nombre_completo']) ? htmlspecialchars($_SESSION['usuario_nombre_completo']) : '-'; ?></span>
                </div>
                <span class="text-muted">Gracias por su compra</span>
              </td>
              <td class="pe-0 py-6 w-px-100">
                <p class="mb-1">Subtotal:</p>
                <p class="mb-1 border-bottom pb-2">IVA (0%):</p>
                <p class="mb-0 pt-2 fw-medium">Total:</p>
              </td>
              <td class="text-end px-0 py-6 w-px-100">
                <p class="fw-medium mb-1" id="subtotal_venta">0,00 €</p>
                <p class="fw-medium mb-1 border-bottom pb-2" id="iva_venta">0,00 €</p>
                <p class="fw-bold mb-0 pt-2 fs-5 lh-1" id="total_venta">0,00 €</p>
              </td>
            </tr>

          </tbody>

        </table>

      </div>

      <hr class="mt-0 mb-6" />
      
      <!-- Observaciones -->
      <div class="mb-0">
        <label class="form-label fw-medium text-heading">Observaciones:</label>
        <textarea class="form-control" id="observaciones_venta" rows="3" placeholder="Añadir observaciones sobre la venta..."></textarea>
      </div>
      </div>
      
      </div><!-- /card-body -->
    </div>
  </div>
  <!-- /Invoice -->

  <!-- Invoice Actions -->
  <div id="invoice_actions" class="col-xl-3 col-md-4 col-12 invoice-actions desenfocar">
    <div class="card">
      <div class="card-body">
        
        <!-- Tipo de venta -->
        <div class="mb-3">

          <label class="form-label">Tipo de Venta</label>

          <div class="row">
            <div class="col-md-6">
              <div class="form-check custom-option custom-option-basic checked custom-option-tipo_venta">
                <label class="form-check-label custom-option-content" for="tipo_venta_normal">
                  <input class="form-check-input" type="radio" name="tipo_venta" value="normal" id="tipo_venta_normal" checked>
                  <span class="custom-option-header">
                    <span class="h6 mb-0">Normal</span>
                  </span>
                </label>
              </div>
            </div>
            
            <div class="col-md-6">
              <div class="form-check custom-option custom-option-basic custom-option-tipo_venta">
                <label class="form-check-label custom-option-content" for="tipo_venta_plazos">
                  <input class="form-check-input" type="radio" name="tipo_venta" value="plazos" id="tipo_venta_plazos">
                  <span class="custom-option-header">
                    <span class="h6 mb-0">Plazos</span>
                  </span>
                </label>
              </div>
            </div>
          </div>

        </div>

        <!-- Tipo de Pago a Plazos -->
        <div class="mb-3" id="tipo_pago_plazos" style="display: none;">

          <label class="form-label">Número de plazos</label>

          <div class="row">
            <div class="col-md-4 pe-1 ps-3">
              <div class="form-check custom-option custom-option-basic checked custom-option-plazos">
                <label class="form-check-label custom-option-content" for="numero_plazos_3">
                  <input class="form-check-input" type="radio" name="numero_plazos" value="3" id="numero_plazos_3" checked>
                  <span class="custom-option-header">
                    <span class="h6 mb-0"> 3 </span>
                  </span>
                </label>
              </div>
            </div>
            
            <div class="col-md-4 pe-2 ps-2">
              <div class="form-check custom-option custom-option-basic custom-option-plazos">
                <label class="form-check-label custom-option-content" for="numero_plazos_6">
                  <input class="form-check-input" type="radio" name="numero_plazos" value="6" id="numero_plazos_6">
                  <span class="custom-option-header">
                    <span class="h6 mb-0"> 6 </span>
                  </span>
                </label>
              </div>
            </div>

            <div class="col-md-4 pe-3 ps-1">
              <div class="form-check custom-option custom-option-basic custom-option-plazos">
                <label class="form-check-label custom-option-content" for="numero_plazos_12">
                  <input class="form-check-input" type="radio" name="numero_plazos" value="12" id="numero_plazos_12">
                  <span class="custom-option-header">
                    <span class="h6 mb-0"> 12 </span>
                  </span>
                </label>
              </div>
            </div>

          </div>
          <span id="plazos_venta_intereses" style="background: rgba(0, 123, 255, 0.1) !important;color: var(--bs-heading-color) !important;font-size: 13px !important;padding: 9px;border: 1px solid #007bff !important;" class="col-md-12 badge bg-label-info mt-2 fw-medium fs-5 rounded-2 ">0%</span>
          <span id="plazos_venta_info" style="background: rgba(0, 123, 255, 0.1) !important;color: var(--bs-heading-color) !important;font-size: 13px !important;padding: 9px;border: 1px solid #007bff !important;" class="col-md-12 badge bg-label-info mt-2 fw-medium fs-5 rounded-2 ">--</span>
          <button type="button" class="btn btn-warning btn-sm waves-effect waves-light mt-2 w-100" id="btn_solicitar_cambio_intereses_venta" onclick="solicitarCambioInteresesVenta()">
            <i class="icon-base ri ri-percent-line me-1"></i>Solicitar cambio de intereses
          </button>
        </div>

        <!-- Forma de Pago -->
        <div class="mb-3">
          <label class="form-label mb-3">Forma de Pago</label>

          <div class="row">

          <div class="col-md-12 mb-3">
            <div class="btn-group-vertical d-flex justify-content-start align-items-start btnformadepago" role="group" aria-label="Basic radio toggle button group">
             
              <label class="btn btn-outline-primary d-flex justify-content-start align-items-start option-forma-pago" for="venta_forma_de_pago_contado"> <input type="radio" class="btn-check forma_pago_venta" name="forma_pago" id="venta_forma_de_pago_contado"  value="contado" checked="checked" /> Contado</label>
             
              <label class="btn btn-outline-primary d-flex justify-content-start align-items-start option-forma-pago" for="venta_forma_de_pago_tarjeta"> <input type="radio" class="btn-check forma_pago_venta" name="forma_pago" id="venta_forma_de_pago_tarjeta"  value="tarjeta" /> Tarjeta</label>
             
              <label class="btn btn-outline-primary d-flex justify-content-start align-items-start option-forma-pago" for="venta_forma_de_pago_transferencia"> <input type="radio" class="btn-check forma_pago_venta" name="forma_pago" id="venta_forma_de_pago_transferencia"  value="transferencia" /> Transferencia</label>
             
              <label class="btn btn-outline-primary d-flex justify-content-start align-items-start option-forma-pago" for="venta_forma_de_pago_bizum"> <input type="radio" class="btn-check forma_pago_venta" name="forma_pago" id="venta_forma_de_pago_bizum"  value="bizum" /> Bizum</label>
             
              <label class="btn btn-outline-primary d-flex justify-content-start align-items-start option-forma-pago" for="venta_forma_de_pago_combinado"> <input type="radio" class="btn-check forma_pago_venta" name="forma_pago" id="venta_forma_de_pago_combinado"  value="combinado" /> Pago combinado</label>
            </div>
          </div>

          
          </div>
          
        </div>


        <hr class="my-2" />

        <!-- Botones de Acción -->
        <button class="btn btn-primary d-grid w-100 mb-4" id="btn_guardar_venta">
          <span class="d-flex align-items-center justify-content-center text-nowrap">
            <i class="icon-base ri ri-checkbox-circle-fill icon-22px me-2"></i>Generar Venta
          </span>
        </button>
        <!--
        <button class="btn btn-secondary d-grid w-100 mb-4" id="btn_imprimir_ticket">
          <span class="d-flex align-items-center justify-content-center text-nowrap">
            <i class="icon-base ri ri-printer-line icon-22px me-2"></i>Imprimir Ticket
          </span>
        </button>
        -->
        <a href="ventas.php" id="btn_cancelar_venta" class="btn btn-outline-danger d-grid w-100">
          <span class="d-flex align-items-center justify-content-center text-nowrap">
            <i class="icon-base ri ri-close-line icon-22px me-2"></i>Cancelar
          </span>
        </a>
      </div>
    </div>
    
    <!-- Card de Resumen -->
    <div class="card mt-4">
      <div class="card-body">
        <h6 class="mb-4">Resumen</h6>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Artículos:</span>
          <span class="fw-medium" id="total_articulos_resumen">0</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted">Peso Total:</span>
          <span class="fw-medium" id="peso_total_resumen">0,00 g</span>
        </div>
        <hr class="my-3" />
        <div class="d-flex justify-content-between">
          <span class="fw-medium">Total:</span>
          <span class="fw-bold fs-5" id="total_resumen">0,00 €</span>
        </div>
        <div class="d-flex justify-content-between mt-2">
          <span class="text-muted">Debe cobrar ahora:</span>
          <span class="fw-bold" id="total_cobrar">0,00 €</span>
        </div>
        
      </div>
    </div>
  </div>
  <!-- /Invoice Actions -->
</div>

</div>
<!-- / Content -->

<!-- Edit User Modal -->
<div class="modal fade" id="datos_cliente" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-simple modal-edit-user">
                  <div class="modal-content">
                    <div class="modal-body p-0">
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      <div class="text-center mb-6">
                        <h4 class="mb-2">Datos del cliente</h4>
                        <p class="mb-6">Escribe el dni y buscara, o lo puedes crear nuevo desde aqui mismo.</p>
                      </div>
                      <form id="form_datos_cliente" class="row g-5" onsubmit="return false" autocomplete="off">
                        <input type="hidden" id="modal_id_cliente" name="id_cliente" value="" />
                        <input type="hidden" id="modal_id_direccion" name="id_direccion" />
                        
                        <!-- Columna Izquierda -->
                        <div class="col-md-6">
                          <div class="form-floating form-floating-outline mb-4">
                            <select class="form-select select2" id="modal_tipo_identificacion" name="tipo_identificacion" required autocomplete="off">
                              <option value="">Seleccionar...</option>
                            </select>
                            <label for="modal_tipo_identificacion">Tipo de Identificación *</label>
                          </div>
                          
                          <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" id="modal_identificacion" name="doc_id_manual" placeholder="Número de identificación" required autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true" data-form-type="other" />
                            <label for="modal_identificacion">Número de Identificación *</label>
                          </div>
                          
                          <div class="mb-4 form-floating form-floating-outline">
                            <input type="text" class="form-control" id="modal_nombre" name="nombre" placeholder="Nombre" required autocomplete="off" />
                            <label for="modal_nombre" class="form-label">Nombre *</label>
                          </div>
                          
                          <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" id="modal_apellido" name="apellido" placeholder="Apellido" required autocomplete="off" />
                            <label for="modal_apellido" class="form-label">Apellido *</label>
                          </div>
                          
                          <div class="form-floating form-floating-outline mb-4">
                            <input type="tel" class="form-control" id="modal_telefono" name="telefono" placeholder="0034600000000" required autocomplete="off" />
                            <label for="modal_telefono">Teléfono *</label>
                          </div>
                          
                          <div class="form-floating form-floating-outline mb-3">
                            <input type="email" class="form-control" id="modal_email" name="email" placeholder="cliente@ejemplo.com" autocomplete="off" />
                            <label for="modal_email">Email</label>
                          </div>
                        </div>
                        
                        <!-- Columna Derecha -->
                        <div class="col-md-6">
                          <div class="form-floating form-floating-outline mb-4">
                            <select class="form-select select2" id="modal_pais" name="pais" autocomplete="off">
                              <option value="">Seleccionar...</option>
                            </select>
                            <label for="modal_pais">País</label>
                          </div>
                          
                          <div class="form-floating form-floating-outline mb-4">
                            <select class="form-select select2" id="modal_c_provincia" name="c_provincia" autocomplete="off">
                              <option value="">Seleccionar...</option>
                            </select>
                            <label for="modal_c_provincia">Provincia</label>
                          </div>
                          
                          <div class="form-floating form-floating-outline mb-4">
                            <select class="form-select select2" id="modal_c_poblacion" name="c_poblacion" autocomplete="off">
                              <option value="">Seleccionar...</option>
                            </select>
                            <label for="modal_c_poblacion">Población</label>
                          </div>
                          
                          <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" id="modal_direccion" name="direccion" placeholder="Calle, número, etc." autocomplete="off" />
                            <label for="modal_direccion">Dirección</label>
                          </div>
                          
                          <div class="form-floating form-floating-outline mb-4">
                            <input type="text" class="form-control" id="modal_codigo_postal" name="codigo_postal" placeholder="Código postal" autocomplete="off" />
                            <label for="modal_codigo_postal">Código Postal</label>
                          </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 text-center">
                          <button type="submit" class="btn btn-primary me-3">Guardar Cliente</button>
                          <button
                            type="reset"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                            Cancelar
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
              <!--/ Edit User Modal -->