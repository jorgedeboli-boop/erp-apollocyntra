<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom card-header-forms">
                    <h5 class="card-title mb-0">Crear Nuevo Cliente</h5>
                    <small class="text-muted">Complete el formulario para registrar un nuevo cliente en el sistema</small>
                    <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='clientes.php'">
                        <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Clientes
                    </button>
                </div>
                <div class="card-body mt-5">
                    <div id="card-form-custom-id" class="card card-form-custom">
                        <form id="formCrearCliente" method="POST" action="parts/clientes/crear/procesar_cliente.php" class="fv-plugins-bootstrap5 fv-plugins-framework" autocomplete="off">

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
                                    
                                    <div class="input-group input-group-merge mb-4 inputgroupidentificacion">
                                        <div class="form-floating form-floating-outline flex-grow-1">
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="identificacion"
                                                name="identificacion"
                                                placeholder="Número de identificación"
                                                required
                                                autocomplete="off"
                                                aria-describedby="btn_comprobar_identificacion" />
                                            <label for="identificacion">Número de Identificación *</label>
                                        </div>
                                        <span class="input-group-text cursor-pointer p-1">
                                            <button type="button" class="btn btn-primary waves-effect waves-light" id="btn_comprobar_identificacion">Comprobar</button>
                                        </span>
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
                                    <input type="text" class="form-control date-mask" id="f_vencimiento" name="f_vencimiento" required autocomplete="off" placeholder="DD/MM/YYYY" inputmode="numeric" />
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
                                  <input type="text" class="form-control date-mask" id="f_nacimiento" name="f_nacimiento" required autocomplete="off" placeholder="DD/MM/YYYY" inputmode="numeric" />
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
                                require_once __DIR__ . '/formulario_direccion_insert.php';
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
                                  <input type="text" class="form-control" id="email" name="email" placeholder="cliente@ejemplo.com" inputmode="email" autocomplete="email" />
                                  <label for="email">Email</label>
                                </div>

                              </div>
                            </div>
                            
                        </div>

                        <!-- Botones de Acción -->
                        <div class="d-flex justify-content-between">
                            
                            <button type="submit" class="btn btn-primary" id="btnCrearCliente" disabled>
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="loaderCrearCliente" role="status" aria-hidden="true"></span>
                                <i class="icon-base ri ri-check-line me-2" id="iconCrearCliente"></i>
                                <span id="textCrearCliente">Crear Cliente</span>
                            </button>
                            
                            <button type="button" class="btn btn-text-danger" id="btnCancelarCliente">
                                <i class="icon-base ri ri-close-line me-2"></i>
                                Cancelar
                            </button>
                        </div>

                        </form>
                    </div>
                </div>
            </div>
            </div>
    </div>
  </div>
</div>