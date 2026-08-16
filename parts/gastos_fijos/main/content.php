<?php
// `parts/universal/control-vars.php` define $id para páginas tipo "main"
$id_gasto_fijo = isset($id) ? (int)$id : 0;

$conexion = conectar_bd();
if (!$conexion) {
    echo '<div class="alert alert-danger">Error de conexión</div>';
    return;
}

$stmt = mysqli_prepare($conexion, "SELECT * FROM gastos_fijos WHERE id_gasto_fijo = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id_gasto_fijo);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$gf = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$gf) {
    echo '<div class="alert alert-warning">Gasto fijo no encontrado</div>';
    return;
}

$estado = (string)$gf['estado_gasto_fijo'];
$badgeEstadoTexto = ($estado === 'true') ? 'Activo' : 'Inactivo';
?>

<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">

      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start w-100 flex-md-row flex-column gap-3">
            <div class="user-profile-info">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="card-title mb-0">Gasto fijo #<?php echo (int)$gf['id_gasto_fijo']; ?></h5>
                <div id="badge_estado_gasto_fijo" class="badge bg-label-primary rounded-pill lh-xs badget-estados"><?php echo $badgeEstadoTexto; ?></div>
              </div>
            </div>

            <div class="d-flex flex-column align-items-end gap-2">
              <button type="button" class="btn btn-text-primary align-self-end" onclick="window.location.href='gastos_fijos.php?categoria=gastos&page=gastos_fijos&btn=list'">
                <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Gastos fijos
              </button>
              <?php if ($puede_acceder_editar): ?>
              <button id="btnToggleEstado" type="button" class="btn btn-sm <?php echo ($estado === 'true') ? 'btn-success' : 'btn-warning'; ?>">
                <i class="icon-base ri <?php echo ($estado === 'true') ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'; ?> me-1"></i>
                <span><?php echo ($estado === 'true') ? 'Activo' : 'Inactivo'; ?></span>
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="card-body mt-5">
          <div class="card card-form-custom">
            <form id="formEditarGastoFijo" method="POST" action="parts/gastos_fijos/main/actualizar_gasto_fijo.php" autocomplete="off">
              <input type="hidden" name="id_gasto_fijo" id="id_gasto_fijo" value="<?php echo (int)$gf['id_gasto_fijo']; ?>">
              <input type="hidden" name="estado_gasto_fijo" id="estado_gasto_fijo" value="<?php echo htmlspecialchars($estado); ?>">

              <div class="row mb-3">
                <div class="col-12">
                  <h5 class="mb-3">Información</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="descripcion_gasto_fijo" name="descripcion_gasto_fijo" required
                      value="<?php echo htmlspecialchars((string)$gf['descripcion_gasto_fijo']); ?>">
                    <label for="descripcion_gasto_fijo">Descripción *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <h5 class="mb-3">Período</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <select class="form-select select2" id="periodo_gasto_fijo" name="periodo_gasto_fijo" required>
                      <?php $per = (string)$gf['periodo_gasto_fijo']; ?>
                      <option value="diario" <?php echo ($per === 'diario') ? 'selected' : ''; ?>>Diario</option>
                      <option value="semanal" <?php echo ($per === 'semanal') ? 'selected' : ''; ?>>Semanal</option>
                      <option value="quincenal" <?php echo ($per === 'quincenal') ? 'selected' : ''; ?>>Quincenal</option>
                      <option value="mensual" <?php echo ($per === 'mensual') ? 'selected' : ''; ?>>Mensual</option>
                      <option value="trimestral" <?php echo ($per === 'trimestral') ? 'selected' : ''; ?>>Trimestral</option>
                      <option value="semestral" <?php echo ($per === 'semestral') ? 'selected' : ''; ?>>Semestral</option>
                      <option value="anual" <?php echo ($per === 'anual') ? 'selected' : ''; ?>>Anual</option>
                      <option value="bianual" <?php echo ($per === 'bianual') ? 'selected' : ''; ?>>Bianual</option>
                    </select>
                    <label for="periodo_gasto_fijo">Período *</label>
                  </div>
                </div>

                <div class="col-md-4">
                  <h5 class="mb-3">Inicio</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="date" class="form-control" id="fecha_inicio_gasto_fijo" name="fecha_inicio_gasto_fijo"
                      value="<?php echo htmlspecialchars((string)$gf['fecha_inicio_gasto_fijo']); ?>" required>
                    <label for="fecha_inicio_gasto_fijo">Fecha inicio *</label>
                  </div>
                </div>

                <div class="col-md-4">
                  <h5 class="mb-3">Total</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="number" step="0.01" class="form-control" id="total_gasto_fijo" name="total_gasto_fijo"
                      value="<?php echo htmlspecialchars((string)$gf['total_gasto_fijo']); ?>" required>
                    <label for="total_gasto_fijo">Total *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <h5 class="mb-3">Proveedor</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectProveedores((int)$gf['proveedor_gasto_fijo'], 'proveedor_gasto_fijo', 'proveedor_gasto_fijo', true); ?>
                    <label for="proveedor_gasto_fijo">Proveedor *</label>
                  </div>
                </div>
                <div class="col-md-4">
                  <h5 class="mb-3">Forma de pago</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectFormasPago((int)$gf['forma_pago_gasto_fijo'], 'forma_pago_gasto_fijo', 'forma_pago_gasto_fijo', true); ?>
                    <label for="forma_pago_gasto_fijo">Forma de pago *</label>
                  </div>
                </div>
                <div class="col-md-4">
                  <h5 class="mb-3">Tipo de gasto</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectTiposGasto((int)$gf['tipo_de_gasto_fijo'], 'tipo_de_gasto_fijo', 'tipo_de_gasto_fijo', true); ?>
                    <label for="tipo_de_gasto_fijo">Tipo de gasto *</label>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary waves-effect waves-light">
                  <i class="icon-base ri ri-check-line me-2"></i>Actualizar
                </button>
                <button type="button" class="btn btn-outline-secondary waves-effect" onclick="window.location.href='gastos_fijos.php?categoria=gastos&page=gastos_fijos&btn=list'">
                  Cerrar
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>