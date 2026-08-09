<?php
$id_cuenta = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$cuenta = null;
$bancosOpts = [];
$empresasOpts = [];

$conexion = conectar_bd();
if ($id_cuenta > 0) {
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id_cuenta_banco, numerocuenta, banco_cuenta, empresa_cuenta_id, por_defecto, fecha_creacion
         FROM cuentas_banco_empresas WHERE id_cuenta_banco = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_cuenta);
    mysqli_stmt_execute($stmt);
    $cuenta = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$rb = mysqli_query($conexion, 'SELECT id_banco, nombre_banco, estado_banco FROM bancos_config ORDER BY nombre_banco ASC');
if ($rb) {
    while ($row = mysqli_fetch_assoc($rb)) {
        $bancosOpts[] = $row;
    }
}
$re = mysqli_query($conexion, 'SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC');
if ($re) {
    while ($row = mysqli_fetch_assoc($re)) {
        $empresasOpts[] = $row;
    }
}
mysqli_close($conexion);

$id_banco_sel = $cuenta ? (int) $cuenta['banco_cuenta'] : 0;
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar cuenta bancaria</h4>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='cuentas_banco_config.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Cuentas
          </button>
        </div>
        <div class="card-body mt-4">
          <?php if (!$cuenta) : ?>
            <div class="alert alert-danger">Cuenta no encontrada</div>
          <?php else : ?>
          <form id="formEditarCuenta" method="POST" action="parts/cuentas_banco_config/editar/procesar_editar_cuenta.php">
            <input type="hidden" name="id_cuenta_banco" value="<?php echo (int) $id_cuenta; ?>" />
            <div class="row">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="numerocuenta" name="numerocuenta" maxlength="124" value="<?php echo htmlspecialchars($cuenta['numerocuenta'], ENT_QUOTES, 'UTF-8'); ?>" required />
                  <label for="numerocuenta">Número de cuenta *</label>
                </div>

                <div class="mb-8">
                  <label for="banco_cuenta" class="form-label">Banco *</label>
                  <select class="form-select select2" id="banco_cuenta" name="banco_cuenta" required>
                    <option value="">Seleccionar banco</option>
                    <?php foreach ($bancosOpts as $b) :
                        $bid = (int) $b['id_banco'];
                        $label = $b['nombre_banco'];
                        if (($b['estado_banco'] ?? '') !== 'true') {
                            $label .= ' (inactivo)';
                        }
                        ?>
                      <option value="<?php echo $bid; ?>" <?php echo $bid === $id_banco_sel ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-8">
                  <label for="empresa_cuenta_id" class="form-label">Empresa *</label>
                  <select class="form-select select2" id="empresa_cuenta_id" name="empresa_cuenta_id" required>
                    <option value="">Seleccionar empresa</option>
                    <?php foreach ($empresasOpts as $e) :
                        $eid = (int) $e['id_empresa'];
                        ?>
                      <option value="<?php echo $eid; ?>" <?php echo $eid === (int) $cuenta['empresa_cuenta_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($e['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="por_defecto" name="por_defecto" value="true" <?php echo ($cuenta['por_defecto'] === 'true') ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="por_defecto">Cuenta por defecto de la empresa</label>
                </div>
              </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-between">
              <a href="cuenta_banco_config.php?id=<?php echo (int) $id_cuenta; ?>" class="btn btn-text-primary">Volver</a>
              <button type="submit" class="btn btn-primary" id="btnEditarCuenta">
                <i class="icon-base ri ri-check-line me-2"></i>Actualizar cuenta
              </button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
