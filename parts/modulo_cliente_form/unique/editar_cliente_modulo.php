<?php
/**
 * Fragmento HTML: formulario de cliente (editar).
 * La página padre debe definir: $id_cliente, $cliente, $datos_cliente, $direccion_cliente
 * y tener session/functions cargados ($app_country_id).
 */
if (!isset($cliente)) {
    $cliente = [];
}
if (!isset($datos_cliente)) {
    $datos_cliente = [];
}
if (!isset($direccion_cliente)) {
    $direccion_cliente = [];
}
$id_cliente_modulo = isset($id_cliente) ? (int) $id_cliente : 0;
$tipo_identificacion = isset($cliente['tipo_identificacion_id']) ? $cliente['tipo_identificacion_id'] : '';
$tiene_tipo_identificacion = !empty($tipo_identificacion);
?>
<div id="modulo_cliente_form" data-modo="editar">
    <input type="hidden" id="id_cliente" name="id_cliente" value="<?php echo $id_cliente_modulo; ?>" autocomplete="off">

    <div id="datos_cliente_identificacion" style="margin-top: 0px;">
        <div class="row">
            <h5 class="mb-3">Identificación</h5>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectTipoIdentificacion($tipo_identificacion, $app_country_id, true); ?>
                    <label for="tipo_identificacion">Tipo de Identificación *</label>
                </div>
            </div>
            <div class="col-md-6 form-control-validation">
                <div class="input-group input-group-merge mb-4 inputgroupidentificacion<?php echo $tiene_tipo_identificacion ? '' : ' disabled'; ?>">
                    <div class="form-floating form-floating-outline flex-grow-1">
                        <input
                            type="text"
                            class="form-control"
                            id="identificacion"
                            name="identificacion"
                            placeholder="<?php echo $tiene_tipo_identificacion ? 'Número de identificación' : 'Primero seleccione el tipo de identificación'; ?>"
                            value="<?php echo isset($cliente['identificacion']) ? htmlspecialchars($cliente['identificacion']) : ''; ?>"
                            required
                            autocomplete="off"
                            aria-describedby="btn_comprobar_identificacion"
                            <?php echo $tiene_tipo_identificacion ? '' : ' disabled'; ?> />
                        <label for="identificacion">Número de Identificación *</label>
                    </div>
                    <span class="input-group-text cursor-pointer p-1">
                        <button type="button" class="btn btn-primary waves-effect waves-light" id="btn_comprobar_identificacion"<?php echo $tiene_tipo_identificacion ? '' : ' disabled'; ?>>Comprobar</button>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div id="datos_cliente" class="<?php echo $tiene_tipo_identificacion ? '' : 'formulario-borroso'; ?>" style="margin-top: 15px;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectNacionalidades(isset($cliente['nacionalidad_id']) ? $cliente['nacionalidad_id'] : '', true); ?>
                    <label for="nacionalidad">Nacionalidad *</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-3">
                    <input type="text" class="form-control date-mask" id="f_vencimiento" name="f_vencimiento" value="<?php echo isset($datos_cliente['f_vencimiento']) ? htmlspecialchars($datos_cliente['f_vencimiento']) : ''; ?>" required autocomplete="off" placeholder="DD/MM/YYYY" inputmode="numeric" />
                    <label for="f_vencimiento">Fecha vencimiento identificación *</label>
                </div>
            </div>
        </div>
        <div class="row">
            <h5 class="mb-3">Información Personal</h5>
            <div class="col-md-6">
                <div class="mb-4 form-floating form-floating-outline">
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" value="<?php echo isset($cliente['nombre']) ? htmlspecialchars($cliente['nombre']) : ''; ?>" required autocomplete="off" />
                    <label for="nombre" class="form-label">Nombre *</label>
                </div>
                <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Apellido" value="<?php echo isset($cliente['apellido']) ? htmlspecialchars($cliente['apellido']) : ''; ?>" required autocomplete="off" />
                    <label for="apellido" class="form-label">Apellido *</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control date-mask" id="f_nacimiento" name="f_nacimiento" value="<?php echo isset($datos_cliente['f_nacimiento']) ? htmlspecialchars($datos_cliente['f_nacimiento']) : ''; ?>" required autocomplete="off" placeholder="DD/MM/YYYY" inputmode="numeric" />
                    <label for="f_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                </div>
                <div class="form-floating form-floating-outline mb-4">
                    <select class="form-select select2" id="sexo" name="sexo" required autocomplete="off">
                        <option value="">Seleccionar...</option>
                        <option value="MASCULINO" <?php echo (isset($datos_cliente['sexo']) && $datos_cliente['sexo'] === 'MASCULINO') ? 'selected' : ''; ?>>Masculino</option>
                        <option value="FEMENINO" <?php echo (isset($datos_cliente['sexo']) && $datos_cliente['sexo'] === 'FEMENINO') ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                    <label for="sexo" class="form-label">Sexo *</label>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6" id="container_direccion">
                <?php require_once __DIR__ . '/formulario_direccion_edit.php'; ?>
            </div>
            <div class="col-md-6">
                <h5 class="mb-3">Información de Contacto</h5>
                <div class="form-floating form-floating-outline mb-4">
                    <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="0034600000000" value="<?php echo isset($cliente['telefono']) ? htmlspecialchars($cliente['telefono']) : ''; ?>" required autocomplete="off" />
                    <label for="telefono">Teléfono *</label>
                </div>
                <div class="form-floating form-floating-outline mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="cliente@ejemplo.com" value="<?php echo isset($datos_cliente['email']) ? htmlspecialchars($datos_cliente['email']) : ''; ?>" autocomplete="off" />
                    <label for="email">Email</label>
                </div>
            </div>
        </div>
    </div>
</div>
