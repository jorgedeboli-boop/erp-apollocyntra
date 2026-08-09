
                        <!-- Datos del Cliente (oculto por defecto) -->
                        <div id="datos_cliente_tipo_identificacion" class="desenfocar" style=" margin-top: 0px;">

                            <div class="row">
                                <h5 class="mb-3">Tipo de Identificación</h5>
            
                                <div class="col-md mb-md-0 mb-2">
                                <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="DNI">
                                    <span class="custom-option-body">
                                        <span class="custom-option-title">DNI</span>
                                        <small> Solo DNI Español (DNI de otros paises seleccione la opción OTROS) </small>
                                    </span>
                                    <input
                                        name="tipo_identificacion"
                                        class="form-check-input"
                                        type="radio"
                                        value="1"
                                        id="DNI"
                                        checked />
                                    </label>
                                </div>
                                </div>
                
                                <div class="col-md mb-md-0 mb-2">
                                <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="NIE">
                                    <span class="custom-option-body">
                                        <span class="custom-option-title"> NIE </span>
                                        <small> NIE solo de residentes en España (NIE de otros paises seleccione la opción OTROS) </small>
                                    </span>
                                    <input
                                        name="tipo_identificacion"
                                        class="form-check-input"
                                        type="radio"
                                        value="2"
                                        id="NIE" />
                                    </label>
                                </div>
                                </div>
                
                                <div class="col-md mb-md-0 mb-2">
                                <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="Pasaporte">
                                    <span class="custom-option-body">
                                        <span class="custom-option-title"> Pasaporte </span>
                                        <small> Pasaporte Español (Pasaportes de otros paises seleccione la opción OTROS) </small>
                                    </span>
                                    <input
                                        name="tipo_identificacion"
                                        class="form-check-input"
                                        type="radio"
                                        value="3"
                                        id="Pasaporte" />
                                    </label>
                                </div>
                                </div>
                
                                <div class="col-md mb-md-0 mb-2">
                                <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="CIF">
                                    <span class="custom-option-body">
                                        <span class="custom-option-title"> CIF </span>
                                        <small> CIF Español (CIF de otros paises seleccione la opción OTROS) </small>
                                    </span>
                                    <input
                                        name="tipo_identificacion"
                                        class="form-check-input"
                                        type="radio"
                                        value="4"
                                        id="CIF" />
                                    </label>
                                </div>
                                </div>
                
                                <div class="col-md mb-md-0 mb-2">
                                <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="Otros">
                                    <span class="custom-option-body">
                                        <span class="custom-option-title"> Otros </span>
                                        <small> Todo tipo de documento de otros países </small>
                                    </span>
                                    <input
                                        name="tipo_identificacion"
                                        class="form-check-input"
                                        type="radio"
                                        value="5"
                                        id="Otros" />
                                    </label>
                                </div>
                                </div>
            
                            </div>
                            
                            
                        </div>

                         <!-- Datos del Cliente (oculto por defecto) -->
                         <div id="datos_cliente_identificacion" class="desenfocar" style=" margin-top: 15px;">
                            <div class="row">
                                <h5 class="mb-3">Información de Identificación</h5>
                                <div class="col-md-3">
                                    <div class="form-floating form-floating-outline mb-4">
                                        <input type="text" class="form-control" id="identificacion" name="identificacion" placeholder="Número de identificación" required autocomplete="off" />
                                        <label for="identificacion">Número de Identificación *</label>
                                    </div>  
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating form-floating-outline mb-4">
                                    <?php
                                    generarSelectNacionalidades('', true);
                                    ?>
                                    <label for="nacionalidad">Nacionalidad *</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating form-floating-outline mb-3">
                                    <input type="date" class="form-control" id="f_vencimiento" name="f_vencimiento" required autocomplete="off" />
                                    <label for="f_vencimiento">Fecha vencimiento identificación *</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del Cliente (oculto por defecto) -->
                        <div id="datos_cliente" class="desenfocar" style=" margin-top: 15px;">
                            
                            <div class="row">

                              <!-- Información Personal -->
                                <div class="col-md-6">
                                    <h5 class="mb-3">Información Personal</h5>
                                    
                                    <div class="mb-4 form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" required autocomplete="off" />
                                    <label for="nombre" class="form-label">Nombre *</label>
                                    </div>
                                    
                                    <div class="form-floating form-floating-outline mb-4">
                                    <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Apellido" required autocomplete="off" />
                                    <label for="apellido" class="form-label">Apellido *</label>
                                    </div>
                                    
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
