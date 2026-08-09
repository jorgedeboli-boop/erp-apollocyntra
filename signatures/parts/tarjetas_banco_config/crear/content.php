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

$anoActual = (int) date('Y');
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Crear tarjeta bancaria</h4>
          <small class="text-muted">Asocie la tarjeta a un banco y una empresa</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='tarjetas_banco_config.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Tarjetas
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formCrearTarjeta" method="POST" action="parts/tarjetas_banco_config/crear/procesar_tarjeta.php" novalidate>
            <div class="row">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_tarjeta" name="nombre_tarjeta" maxlength="124" placeholder="Nombre en la tarjeta" required />
                  <label for="nombre_tarjeta">Nombre en la tarjeta *</label>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="numerotarjeta" name="numerotarjeta" inputmode="numeric" autocomplete="cc-number" maxlength="23" placeholder="Número de tarjeta" required />
                  <label for="numerotarjeta">Número de tarjeta *</label>
                  <div class="invalid-feedback" id="numerotarjeta_feedback">Número de tarjeta no válido</div>
                </div>

                <div class="row">
                  <div class="col-6 mb-8">
                    <label for="mes_vencimiento" class="form-label">Mes vencimiento *</label>
                    <select class="form-select select2" id="mes_vencimiento" name="mes_vencimiento" required>
                      <option value="">Mes</option>
                      <?php for ($m = 1; $m <= 12; $m++) :
                          $mesVal = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                          ?>
                        <option value="<?php echo $mesVal; ?>"><?php echo $mesVal; ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                  <div class="col-6 mb-8">
                    <label for="ano_vencimiento" class="form-label">Año vencimiento *</label>
                    <select class="form-select select2" id="ano_vencimiento" name="ano_vencimiento" required>
                      <option value="">Año</option>
                      <?php for ($y = $anoActual; $y <= $anoActual + 20; $y++) : ?>
                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                      <?php endfor; ?>
                    </select>
                  </div>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="password" class="form-control" id="cvv" name="cvv" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="CVV" required />
                  <label for="cvv">CVV *</label>
                  <div class="invalid-feedback" id="cvv_feedback">CVV no válido (3 o 4 dígitos)</div>
                </div>

                <div class="mb-8">
                  <label for="banco_tarjeta" class="form-label">Banco *</label>
                  <select class="form-select select2" id="banco_tarjeta" name="banco_tarjeta" required>
                    <option value="">Seleccionar banco</option>
                    <?php foreach ($bancosOpts as $b) : ?>
                      <option value="<?php echo (int) $b['id_banco']; ?>"><?php echo htmlspecialchars($b['nombre_banco'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-8">
                  <label for="empresa_tarjeta_id" class="form-label">Empresa *</label>
                  <select class="form-select select2" id="empresa_tarjeta_id" name="empresa_tarjeta_id" required>
                    <option value="">Seleccionar empresa</option>
                    <?php foreach ($empresasOpts as $e) : ?>
                      <option value="<?php echo (int) $e['id_empresa']; ?>"><?php echo htmlspecialchars($e['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-8">
                  <label for="sucursal_tarjeta_id" class="form-label">Sucursal</label>
                  <select class="form-select select2" id="sucursal_tarjeta_id" name="sucursal_tarjeta_id">
                    <option value="">Sin sucursal (opcional)</option>
                  </select>
                </div>

                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="por_defecto" name="por_defecto" value="true">
                  <label class="form-check-label" for="por_defecto">Tarjeta por defecto de la empresa</label>
                </div>
              </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-between">
              <a href="tarjetas_banco_config.php" class="btn btn-text-primary">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>Volver
              </a>
              <button type="submit" class="btn btn-primary" id="btnCrearTarjeta">
                <i class="icon-base ri ri-check-line me-2"></i>Crear tarjeta
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
