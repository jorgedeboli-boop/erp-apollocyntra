<?php
$id_tarjeta = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$tarjeta = null;
$bancosOpts = [];
$empresasOpts = [];

$conexion = conectar_bd();
if ($id_tarjeta > 0) {
    $stmt = mysqli_prepare(
        $conexion,
        'SELECT id_tarjeta_banco, numerotarjeta, banco_tarjeta, empresa_tarjeta_id, por_defecto, fecha_creacion
         FROM tarjetas_banco_empresas WHERE id_tarjeta_banco = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id_tarjeta);
    mysqli_stmt_execute($stmt);
    $tarjeta = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
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

$id_banco_sel = $tarjeta ? (int) $tarjeta['banco_tarjeta'] : 0;
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar tarjeta bancaria</h4>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='tarjetas_banco_config.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Tarjetas
          </button>
        </div>
        <div class="card-body mt-4">
          <?php if (!$tarjeta) : ?>
            <div class="alert alert-danger">Tarjeta no encontrada</div>
          <?php else : ?>
          <form id="formEditarTarjeta" method="POST" action="parts/tarjetas_banco_config/editar/procesar_editar_tarjeta.php">
            <input type="hidden" name="id_tarjeta_banco" value="<?php echo (int) $id_tarjeta; ?>" />
            <div class="row">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="numerotarjeta" name="numerotarjeta" maxlength="124" value="<?php echo htmlspecialchars($tarjeta['numerotarjeta'], ENT_QUOTES, 'UTF-8'); ?>" required />
                  <label for="numerotarjeta">Número de tarjeta *</label>
                </div>

                <div class="mb-8">
                  <label for="banco_tarjeta" class="form-label">Banco *</label>
                  <select class="form-select select2" id="banco_tarjeta" name="banco_tarjeta" required>
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
                  <label for="empresa_tarjeta_id" class="form-label">Empresa *</label>
                  <select class="form-select select2" id="empresa_tarjeta_id" name="empresa_tarjeta_id" required>
                    <option value="">Seleccionar empresa</option>
                    <?php foreach ($empresasOpts as $e) :
                        $eid = (int) $e['id_empresa'];
                        ?>
                      <option value="<?php echo $eid; ?>" <?php echo $eid === (int) $tarjeta['empresa_tarjeta_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($e['nombre_empresa'], ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="por_defecto" name="por_defecto" value="true" <?php echo ($tarjeta['por_defecto'] === 'true') ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="por_defecto">Tarjeta por defecto de la empresa</label>
                </div>
              </div>
            </div>
            <hr class="my-4">
            <div class="d-flex justify-content-between">
              <a href="tarjeta_banco_config.php?id=<?php echo (int) $id_tarjeta; ?>" class="btn btn-text-primary">Volver</a>
              <button type="submit" class="btn btn-primary" id="btnEditarTarjeta">
                <i class="icon-base ri ri-check-line me-2"></i>Actualizar tarjeta
              </button>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
