<?php
$conexionOpts = conectar_bd();
$bancosOpts = [];
$empresasOpts = [];
$rb = mysqli_query($conexionOpts, "SELECT id_banco, nombre_banco FROM bancos_config WHERE estado_banco = 'true' ORDER BY nombre_banco ASC");
if ($rb) {
    while ($row = mysqli_fetch_assoc($rb)) {
        $bancosOpts[] = $row;
    }
}
$re = mysqli_query($conexionOpts, 'SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC');
if ($re) {
    while ($row = mysqli_fetch_assoc($re)) {
        $empresasOpts[] = $row;
    }
}
mysqli_close($conexionOpts);
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Crear cuenta bancaria</h4>
          <small class="text-muted">Asocie la cuenta a un banco y una empresa</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='cuentas_banco_config.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Cuentas
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formCrearCuenta" method="POST" action="parts/cuentas_banco_config/crear/procesar_cuenta.php">
            <div class="row">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="numerocuenta" name="numerocuenta" maxlength="124" placeholder="IBAN / número de cuenta" required />
                  <label for="numerocuenta">Número de cuenta *</label>
                </div>

                <div class="mb-8">
                  <label for="banco_cuenta" class="form-label">Banco *</label>
                  <select class="form-select select2" id="banco_cuenta" name="banco_cuenta" required>
                    <option value="">Seleccionar banco</option>
                    <?php foreach ($bancosOpts as $b) : ?>
                      <option value="<?php echo (int) $b['id_banco']; ?>"><?php echo htmlspecialchars($b['nombre_banco'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-8">
                  <label for="empresa_cuenta_id" class="form-label">Empresa *</label>
                  <select class="form-select select2" id="empresa_cuenta_id" name="empresa_cuenta_id" required>
                    <option value="">Seleccionar empresa</option>
                    <?php foreach ($empresasOpts as $e) : ?>
                      <option value="<?php echo (int) $e['id_empresa']; ?>"><?php echo htmlspecialchars($e['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-8">
                  <label for="sucursal_cuenta_id" class="form-label">Sucursal</label>
                  <select class="form-select select2" id="sucursal_cuenta_id" name="sucursal_cuenta_id">
                    <option value="">Sin sucursal (opcional)</option>
                  </select>
                </div>

                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="por_defecto" name="por_defecto" value="true">
                  <label class="form-check-label" for="por_defecto">Cuenta por defecto de la empresa</label>
                </div>
              </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-between">
              <a href="cuentas_banco_config.php" class="btn btn-text-primary">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>Volver
              </a>
              <button type="submit" class="btn btn-primary" id="btnCrearCuenta">
                <i class="icon-base ri ri-check-line me-2"></i>Crear cuenta
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
