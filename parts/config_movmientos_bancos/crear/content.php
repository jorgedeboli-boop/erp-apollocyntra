<?php
$tiposConfigOpts = [
    'transferencia_saliente' => 'Transferencia saliente',
    'transferencia_entrante' => 'Transferencia entrante',
    'cobro_tarjeta' => 'Cobro tarjeta',
    'pago_tarjeta' => 'Pago tarjeta',
    'retiro_tarjeta' => 'Retiro tarjeta',
    'retiro_cuenta' => 'Retiro cuenta',
    'ingreso_cuenta' => 'Ingreso cuenta',
];

$conexionOpts = conectar_bd();
$gruposOpts = [];
$rg = mysqli_query($conexionOpts, 'SELECT id_grupo, nombre_grupo, tipo_grupo FROM grupos_movimientos ORDER BY nombre_grupo ASC');
if ($rg) {
    while ($row = mysqli_fetch_assoc($rg)) {
        $gruposOpts[] = $row;
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
          <h4 class="card-title mb-0">Crear configuración de movimiento</h4>
          <small class="text-muted">Relacione el tipo de config con un grupo de movimiento</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='config_movmientos_bancos.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Configs
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formCrearConfig" method="POST" action="parts/config_movmientos_bancos/crear/procesar_config.php">
            <div class="row">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_config" name="nombre_config" maxlength="124" placeholder="Nombre" required />
                  <label for="nombre_config">Nombre *</label>
                </div>

                <div class="mb-8">
                  <label for="rel_id_tipo_movimiento" class="form-label">Grupo de movimiento *</label>
                  <select class="form-select select2" id="rel_id_tipo_movimiento" name="rel_id_tipo_movimiento" required>
                    <option value="">Seleccionar grupo</option>
                    <?php foreach ($gruposOpts as $g) : ?>
                      <option value="<?php echo (int) $g['id_grupo']; ?>">
                        <?php echo htmlspecialchars($g['nombre_grupo'] . ' (' . $g['tipo_grupo'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-8">
                  <label for="tipo_config" class="form-label">Tipo de configuración *</label>
                  <select class="form-select select2" id="tipo_config" name="tipo_config" required>
                    <option value="">Seleccionar tipo</option>
                    <?php foreach ($tiposConfigOpts as $val => $label) : ?>
                      <option value="<?php echo htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="estado_config" name="estado_config" value="true" checked>
                  <label class="form-check-label" for="estado_config">Configuración activa</label>
                </div>
              </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-between">
              <a href="config_movmientos_bancos.php" class="btn btn-text-primary">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>Volver
              </a>
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ri ri-check-line me-2"></i>Crear configuración
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
