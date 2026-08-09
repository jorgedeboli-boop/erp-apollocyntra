<?php
/**
 * Fragmento HTML: formulario de cliente (crear).
 * Requiere session/functions cargados por la página padre ($app_country_id).
 */
?>
<div id="modulo_cliente_form" data-modo="crear">
    <input type="hidden" id="id_cliente" name="id_cliente" value="false" autocomplete="off">
    <input type="hidden" id="active_code_autorization" name="active_code_autorization" value="false" autocomplete="off">

    <div id="datos_cliente_identificacion" style="margin-top: 0px;">
        <div class="row">
            <h5 class="mb-3">Identificación</h5>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectTipoIdentificacion('', $app_country_id, true); ?>
                    <label for="tipo_identificacion">Tipo de Identificación *</label>
                </div>
            </div>
            <div class="col-md-6 form-control-validation">
                <div class="input-group input-group-merge mb-4 inputgroupidentificacion disabled">
                    <div class="form-floating form-floating-outline flex-grow-1">
                        <input
                            type="text"
                            class="form-control"
                            id="identificacion"
                            name="identificacion"
                            placeholder="Primero seleccione el tipo de identificación"
                            required
                            autocomplete="off"
                            disabled
                            aria-describedby="btn_comprobar_identificacion" />
                        <label for="identificacion">Número de Identificación *</label>
                    </div>
                    <span class="input-group-text cursor-pointer p-1">
                        <button type="button" class="btn btn-primary waves-effect waves-light" id="btn_comprobar_identificacion" disabled>Comprobar</button>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div id="datos_cliente" class="formulario-borroso" style="margin-top: 15px;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectNacionalidades('', true); ?>
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
            <div class="col-md-6" id="container_direccion">
                <?php require_once __DIR__ . '/formulario_direccion_insert.php'; ?>
            </div>
            <div class="col-md-6">
                <h5 class="mb-3">Información de Contacto</h5>
                <input type="hidden" name="not_mobile_get" id="not_mobile_get" value="false">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="0034600000000" required autocomplete="off" />
                    <label for="telefono">Teléfono *</label>
                </div>
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
</div>
