<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom card-header-forms">
                    <h5 class="card-title mb-0">Crear lote</h5>
                    <small class="text-muted"><span id="nombre_sucursal"></span> <span id="numero_lote"></span></small>
                    <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='lotes.php'">
                        <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Lotes
                    </button>
                </div>
                <div class="card-body mt-5">
                    <div id="card-form-custom-id" class="card card-form-custom">
                        <form id="formCrearLote" method="POST" action="parts/lotes/crear/insertar_lote.php" class="fv-plugins-bootstrap5 fv-plugins-framework" autocomplete="off">
                        
                        <!-- Campos Hidden 
                        <input type="hidden" id="id_loteasd" name="id_loteasdd" value="" autocomplete="off">-->
                        <input type="hidden" id="id_cliente" name="id_cliente" value="false" autocomplete="off">
                        <input type="hidden" id="intereses" name="intereses" value="false" autocomplete="off">
                        <input type="hidden" id="active_code_autorization" name="active_code_autorization" value="false" autocomplete="off">
                        <input type="hidden" id="active_code_empenyo_autorization" name="active_code_empenyo_autorization" value="false" autocomplete="off">
                        <input type="hidden" id="active_sendTipoPago_contado" name="active_sendTipoPago_contado" value="false" autocomplete="off">
                        <input type="hidden" id="active_sendTipoPago_otros" name="active_sendTipoPago_otros" value="false" autocomplete="off">
                        
                        <!-- Sucursal (recibida por POST) -->
                        <?php
                        $id_sucursal = isset($_POST['id_sucursal']) ? intval($_POST['id_sucursal']) : '';
                        ?>
                        <input type="hidden" id="sucursal_lote" name="sucursal_lote" value="<?php echo htmlspecialchars($id_sucursal); ?>" autocomplete="off">

                        <div id="numeros_lotes" class="d-none desenfocar mt-3">
                            <div class="row mb-3">
                            <div class="col-12">
                                <div class="alert alert-danger fs-6" role="alert">
                                <h6 style="font-size: 17.5px;" class="alert-heading fw-bold mb-0" id="titulo_alert_error_sucursal"></h6>
                                <p class="mb-0" style="font-size: 12px;">Recuerde que puede modificar el número y fecha de compra del lote reemplazando por uno de los disponibles.</p>
                                </div>
                            </div>
                            </div>
                            <div class="row mb-3" >
                                <div class="col-md-6">
                                    <h5 class="mb-3">Número de lote</h5>
                                    <div class="form-floating form-floating-outline mb-4">
                                    <select class="form-select select2" id="id_lote" name="id_lote" required autocomplete="off">
                                    </select>
                                    <label for="id_lote" class="form-label">Seleccione el número de lote *</label>
                                    <!--<input type="text" class="form-control" id="id_loteasd" name="id_loteasd" placeholder="Nº de lote" required  />
                                    <label for="id_loteasd">Nº de lote *</label>-->
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">Fecha de compra</h5>
                                    <div class="form-floating form-floating-outline mb-4">
                                    <input type="date" class="form-control" id="fecha_de_compra" name="fecha_de_compra" value="<?php echo date('Y-m-d'); ?>" placeholder="Ejemplo: 2025-01-01" required  />
                                    <label for="fecha_de_compra">Fecha de compra *</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del Cliente (oculto por defecto) -->
                        <div id="datos_cliente_identificacion" style=" margin-top: 0px;">

                            <div class="row">
                                <h5 class="mb-3">Identificación</h5>
                                <!-- Identificación -->
                                <div class="col-md-6">
                                    
                                    <div class="form-floating form-floating-outline mb-4">
                                    <?php
                                    generarSelectTipoIdentificacion('', $app_country_id, true);
                                    ?>
                                    <label for="tipo_identificacion">Tipo de Identificación *</label>
                                    </div>
                                
                                </div>

                                <!-- Información Personal -->
                                <div class="col-md-6 form-control-validation">
                                    
                                    <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="identificacion" name="identificacion" placeholder="Número de identificación" required autocomplete="off" />
                                    <label for="identificacion">Número de Identificación *</label>
                                    </div>    

                                </div>
                              
                            </div>
                            
                            
                        </div>

                        <!-- Datos del Cliente (oculto por defecto) -->
                        <div id="datos_cliente" style=" margin-top: 15px;">
                            <div class="row">

                            <!-- Identificación -->
                                <div class="col-md-6">
                                    <div class="form-floating form-floating-outline mb-4">
                                    <?php
                                    generarSelectNacionalidades('', true);
                                    ?>
                                    <label for="nacionalidad">Nacionalidad *</label>
                                    </div>
                                </div>
                                <div class="col-md-6">    
                                    <div class="form-floating form-floating-outline mb-3">
                                    <input type="date" class="form-control" id="f_vencimiento" name="f_vencimiento" required autocomplete="off" />
                                    <label for="f_vencimiento">Fecha vencimiento identificación *</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                            <h5 class="mb-3">Información Personal</h5>
                              <div class="col-md-6">
                                
                                <div class="mb-4 form-floating form-floating-outline">
                                  <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" required autocomplete="off" />
                                  <label for="nombre" class="form-label">Nombre *</label>
                                </div>
                                
                                <div class="form-floating form-floating-outline mb-4">
                                  <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Apellido" required autocomplete="off" />
                                  <label for="apellido" class="form-label">Apellido *</label>
                                </div>
                            </div>
                            <div class="col-md-6">   
                                
                                <div class="form-floating form-floating-outline mb-4">
                                  <input type="date" class="form-control" id="f_nacimiento" name="f_nacimiento" required autocomplete="off" />
                                  <label for="f_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                                </div>
                                
                                <div class="form-floating form-floating-outline mb-4">
                                  <select class="form-select select2" id="sexo" name="sexo" required autocomplete="off">
                                    <option value="">Seleccionar...</option>
                                    <option value="MASCULINO">Masculino</option>
                                    <option value="FEMENINO">Femenino</option>
                                  </select>
                                  <label for="sexo" class="form-label">Sexo *</label>
                                </div>
                                
                              </div>
                              
                            </div>
                            
                            <div class="row mt-4">
                              <!-- Dirección (se cargará dinámicamente) -->
                              <div class="col-md-6" id="container_direccion">
                                <?php
                                require_once 'parts/lotes/crear/formulario_direccion_insert.php';
                                ?>
                              </div>
                              
                              <!-- Información de Contacto -->
                              <div class="col-md-6">
                                <h5 class="mb-3">Información de Contacto</h5>
                                <input type="hidden" name="not_mobile_get" id="not_mobile_get" value="false" >
                                <div class="form-floating form-floating-outline mb-4">
                                  <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="0034600000000" required autocomplete="off" />
                                  <label for="telefono">Teléfono *</label>
                                </div>

                                <!-- Botón para solicitar autorización SMS si no posee móvil -->
                                <div class="mb-4 d-none" id="contenedor_btn_solicitar_autorizacion_sms">
                                    <button type="button" class="btn btn-danger w-100" id="btnSolicitarAutorizacionSMS">
                                        Solicitar autorización SMS (si no posee móvil)
                                    </button>
                                    <button type="button" class="btn btn-warning w-100 d-none" id="btnCancelarSolicitudAutorizacionSMS">
                                        Cancelar solicitud de autorización SMS
                                    </button>
                                </div>
                                
                                <div class="form-floating form-floating-outline mb-3">
                                  <input type="email" class="form-control" id="email" name="email" placeholder="cliente@ejemplo.com" autocomplete="off" />
                                  <label for="email">Email</label>
                                </div>

                              </div>
                            </div>
                            
                        </div>

                        <!-- Datos del Lote -->
                        <div id="datos_lote" class="row mb-4 desenfocar" style="">
                            <div class="col-md-12">
                                <h5 class="mb-3">Datos del Lote</h5>
                            </div>
                            
                            <!-- Tipo de lote -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Tipo de lote *</label>
                                <div class="row">
                                    <div class="d-flex gap-3">
                                        <div class="form-check custom-option custom-option-basic">
                                            <label class="form-check-label custom-option-content" for="tipo_lote_oro">
                                                <input class="form-check-input" type="radio" name="tipo_lote" value="oro" id="tipo_lote_oro" checked required>
                                                <span class="custom-option-header">
                                                    <span class="h6 mb-0">Oro</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-check custom-option custom-option-basic">
                                            <label class="form-check-label custom-option-content" for="tipo_lote_plata">
                                                <input class="form-check-input" type="radio" name="tipo_lote" value="plata" id="tipo_lote_plata" required>
                                                <span class="custom-option-header">
                                                    <span class="h6 mb-0">Plata</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-check custom-option custom-option-basic">
                                            <label class="form-check-label custom-option-content" for="tipo_lote_acero">
                                                <input class="form-check-input" type="radio" name="tipo_lote" value="acero" id="tipo_lote_acero" required>
                                                <span class="custom-option-header">
                                                    <span class="h6 mb-0">Acero</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cantidad de artículos -->
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text inputgrouptextnuevolote">Cantidad de artículos</span>
                                        <input type="number" class="form-control" id="cantidad_articulos" name="cantidad_articulos" placeholder="0" min="1" required autocomplete="off" />
                                        <span class="input-group-text inputgrouptextnuevolote-right">uds.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Peso Neto -->
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text inputgrouptextnuevolote">Peso Neto</span>
                                        <input type="number" step="0.01" class="form-control" id="peso_neto" name="peso_neto" placeholder="0.00" min="0" required autocomplete="off" />
                                        <span class="input-group-text inputgrouptextnuevolote-right">grs</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Peso Bruto -->
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text inputgrouptextnuevolote">Peso Bruto</span>
                                        <input type="number" step="0.01" class="form-control" id="peso_bruto" name="peso_bruto" placeholder="0.00" min="0" required autocomplete="off" />
                                        <span class="input-group-text inputgrouptextnuevolote-right">grs</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Precio de compra -->
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text inputgrouptextnuevolote">Precio de compra</span>
                                        <input type="number" step="0.01" class="form-control" id="precio_compra" name="precio_compra" placeholder="0.00" min="0" required autocomplete="off" />
                                        <span class="input-group-text inputgrouptextnuevolote-right">€</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Merma -->
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text inputgrouptextnuevolote">Merma</span>
                                        <input type="number" step="0.01" class="form-control" id="merma" name="merma" placeholder="0.00" min="0" autocomplete="off" />
                                        <span class="input-group-text inputgrouptextnuevolote-right">grs</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Opción de compra -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Opción de recompra *</label>
                                <div class="row">
                                    <div class="d-flex gap-3">
                                        <div class="form-check custom-option custom-option-basic">
                                            <label class="form-check-label custom-option-content" for="opcion_compra_si">
                                                <input class="form-check-input" type="radio" name="opcion_compra" value="si" id="opcion_compra_si" required>
                                                <span class="custom-option-header">
                                                    <span class="h6 mb-0">Si</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-check custom-option custom-option-basic">
                                            <label class="form-check-label custom-option-content" for="opcion_compra_no">
                                                <input class="form-check-input" type="radio" name="opcion_compra" value="no" id="opcion_compra_no" required>
                                                <span class="custom-option-header">
                                                    <span class="h6 mb-0">No</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <!-- Fechas debajo de los botones -->
                                <div class="mt-3">
                                    <?php
                                    // Calcular fecha de vencimiento (1 mes desde hoy)
                                    $fecha_cambiada_vencimiento = mktime(0, 0, 0, date("m") + 1, date("d"), date("Y"));
                                    $proximo_vencimiento = date("d-m-Y", $fecha_cambiada_vencimiento);
                                    $proximo_vencimiento_iso = date("Y-m-d", $fecha_cambiada_vencimiento);
                                    ?>
                                    <input type="date" class="form-control" id="fecha_vencimiento_input" name="fecha_vencimiento_hidden" value="<?php echo $proximo_vencimiento_iso; ?>" readonly style="display: none;" autocomplete="off" />
                                    <span id="fecha_vencimiento_mostrada" class="text-muted mb-2 valuesformnuevolotenoinput" style="display: none;"><strong>Fecha de vencimiento:</strong> <?php echo $proximo_vencimiento; ?></span>
                                    
                                    
                                </div>
                            </div>

                            <!-- Porcentaje de recompra -->
                            <div class="col-md-6" id="contenedor_porcentaje_recompra" style="display: none;">
                                <div class="form-floating form-floating-outline mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text inputgrouptextnuevolote">Porcentaje de recompra</span>
                                        <input type="number" step="1" class="form-control" id="porcentaje_recompra" name="porcentaje_recompra" placeholder="0" min="0" required readonly autocomplete="off" />
                                        <span class="input-group-text inputgrouptextnuevolote-right">%</span>
                                    </div>
                                </div>
                                <!-- Botón para editar porcentaje de recompra -->
                                <div class="mb-4">
                                    <button type="button" class="btn btn-primary w-100" id="btnEditarPorcentaje">
                                    Editar porcentaje
                                    </button>
                                </div>
                            </div>

                            <!-- Precio de recompra -->
                            <div class="col-md-6" id="contenedor_precio_recompra" style="display: none;">
                                <div class="form-floating form-floating-outline mb-4">
                                    <div class="input-group">
                                        <span class="input-group-text inputgrouptextnuevolote">Precio de recompra</span>
                                        <input type="number" step="0.01" class="form-control" id="precio_recompra" name="precio_recompra" placeholder="0.00" min="0" required readonly autocomplete="off" />
                                        <span class="input-group-text inputgrouptextnuevolote-right">€</span>
                                    </div>
                                </div>
                            </div>

                            

                            <!-- Fecha de liberación -->
                            <div class="col-md-12 mb-3 mt-3">
                                <?php
                                // Calcular fecha de liberación (14 días desde hoy)
                                $fecha_cambiada_liberacion = mktime(0, 0, 0, date("m"), date("d") + 14, date("Y"));
                                $proxima_liberacion = date("d-m-Y", $fecha_cambiada_liberacion);
                                $proxima_liberacion_iso = date("Y-m-d", $fecha_cambiada_liberacion);
                                ?>
                                <input type="hidden" id="fecha_liberacion_hidden" name="fecha_liberacion" value="<?php echo $proxima_liberacion_iso; ?>" autocomplete="off" />
                                <span id="fecha_liberacion_mostrada" class="text-muted d-block  valuesformnuevolotenoinput"><strong>Fecha de liberación:</strong> <?php echo $proxima_liberacion; ?></span>
                            </div>

                            <!-- Método de pago -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Método de pago *</label>
                                <div class="row">
                                    <div class="d-flex gap-3">
                                        <div class="form-check custom-option custom-option-basic">
                                            <label class="form-check-label custom-option-content" for="metodo_pago_efectivo">
                                                <input class="form-check-input" type="radio" name="metodo_pago" value="efectivo" id="metodo_pago_efectivo" checked required>
                                                <span class="custom-option-header">
                                                    <span class="h6 mb-0">Efectivo</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="form-check custom-option custom-option-basic">
                                            <label class="form-check-label custom-option-content" for="metodo_pago_transferencia">
                                                <input class="form-check-input" type="radio" name="metodo_pago" value="transferencia" id="metodo_pago_transferencia" required>
                                                <span class="custom-option-header">
                                                    <span class="h6 mb-0">Transferencia</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                        
                        <!-- Botones de Acción -->
                        <div class="d-flex justify-content-between">
                            
                            <button type="submit" class="btn btn-primary" id="btnCrearLote" disabled>
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="loaderCrearLote" role="status" aria-hidden="true"></span>
                                <i class="icon-base ri ri-check-line me-2" id="iconCrearLote"></i>
                                <span id="textCrearLote">Crear Lote</span>
                            </button>
                            
                            <button type="button" class="btn btn-text-danger" id="btnCancelarLote">
                                <i class="icon-base ri ri-close-line me-2"></i>
                                Cancelar
                            </button>
                        </div>

                        </div>

                        

                        </form>
                    </div>
                </div>
            </div>
    </div>
  </div>
</div>

<!-- Modal Solicitar autorización -->
<div class="modal fade" id="modalSolicitarAutorizacion" tabindex="-1" aria-labelledby="modalSolicitarAutorizacionLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header card-header-forms pb-3">
                <h5 class="modal-title" id="modalSolicitarAutorizacionLabel">Solicitar autorización</h5>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="porcentajeActualRecompra" class="form-label">Porcentaje actual</label>
                        <div class="input-group input-group-merge">
                            <input type="number" step="1" class="form-control" id="porcentajeActualRecompra" name="porcentajeActualRecompra" readonly autocomplete="off" />
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="nuevoPorcentajeRecompra" class="form-label">Nuevo porcentaje</label>
                        <div class="input-group input-group-merge">
                            <input type="number" step="1" class="form-control" id="nuevoPorcentajeRecompra" name="nuevoPorcentajeRecompra" placeholder="0" min="0" required autocomplete="off" />
                            <span class="input-group-text">%</span>
                        </div>    
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSolicitarAutorizacion">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="loaderSolicitarAutorizacion" role="status" aria-hidden="true"></span>
                    Solicitar autorización
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Solicitar autorización SMS -->
    <div class="modal fade" id="modalSolicitarAutorizacionSMS" tabindex="-1" aria-labelledby="modalSolicitarAutorizacionSMSLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header-forms pb-3">
                    <h5 class="modal-title" id="modalSolicitarAutorizacionSMSLabel">Solicitar autorización SMS (si no posee móvil)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger alert-dismissible mb-0" role="alert">
                        <h4 class="alert-heading d-flex align-items-center">
                            <span class="alert-icon rounded"><i class="icon-base ri ri-error-warning-line icon-md"></i></span>Atención!
                        </h4>
                        <p>¿Está seguro que desea solicitar autorización? Se realizará la autorización desde central una vez haya completado los datos del lote y el cliente.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarAutorizacionSMS">Solicitar autorización</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cancelar solicitud de autorización SMS -->
    <div class="modal fade" id="modalCancelarAutorizacionSMS" tabindex="-1" aria-labelledby="modalCancelarAutorizacionSMSLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header-forms pb-3">
                    <h5 class="modal-title" id="modalCancelarAutorizacionSMSLabel">Cancelar solicitud de autorización SMS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning alert-dismissible mb-0" role="alert">
                        <h4 class="alert-heading d-flex align-items-center">
                            <span class="alert-icon rounded"><i class="icon-base ri ri-warning-line icon-md"></i></span>Atención!
                        </h4>
                        <p>¿Está seguro que desea cancelar la solicitud de autorización? Se cancelará la solicitud de autorización SMS.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnCancelarAutorizacionSMS">Cancelar solicitud</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTROL CODIGO SMS -->
    <div class="modal fade" id="sms_code" tabindex="-1" aria-labelledby="modalsms_code" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header card-header-forms pb-3">
                    <h5 class="modal-title" id="modalsms_code">Autorización de pago <span id="id_autorization"></span></h5>
                </div>
                <div class="modal-body">
                    <h5 class="text-center">Solicitar código SMS al cliente</h5>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <input type="text" class="form-control" id="codigo_sms" name="codigo_sms" autocomplete="off" />
                            <input id="id_sms" type="hidden" value=""  autocomplete="off" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100" id="btn_check_code_sms">Comprobar autorización</button>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const precio = document.getElementById('precio_compra');
    const efectivo = document.getElementById('metodo_pago_efectivo');
    const transferencia = document.getElementById('metodo_pago_transferencia');
    if (!precio || !efectivo || !transferencia) {
        return;
    }

    const wrapEfectivo = efectivo.closest('.custom-option');
    const wrapTransferencia = transferencia.closest('.custom-option');
    const labelEfectivo = document.querySelector('label[for="metodo_pago_efectivo"]');

    function parsePrecio(raw) {
        const s = String(raw == null ? '' : raw).replace(',', '.').replace(/[^0-9.]/g, '');
        const v = parseFloat(s);
        return isFinite(v) ? v : 0;
    }

    function aplicarReglaMetodoPago() {
        const v = parsePrecio(precio.value);
        if (v > 999) {
            // Forzar transferencia y bloquear efectivo
            if (!transferencia.checked) {
                transferencia.checked = true;
            }
            efectivo.checked = false;
            efectivo.disabled = true;
            if (wrapEfectivo) {
                wrapEfectivo.style.cursor = 'not-allowed';
            }
            if (labelEfectivo) {
                labelEfectivo.style.cursor = 'not-allowed';
            }
            if (wrapTransferencia) {
                wrapTransferencia.classList.add('checked');
            }
            if (wrapEfectivo) {
                wrapEfectivo.classList.remove('checked');
            }
        } else {
            // Rehabilitar efectivo
            efectivo.disabled = false;
            if (wrapEfectivo) {
                wrapEfectivo.style.cursor = '';
            }
            if (labelEfectivo) {
                labelEfectivo.style.cursor = '';
            }
        }
    }

    precio.addEventListener('blur', aplicarReglaMetodoPago);
});
</script>
