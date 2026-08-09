<?php
$id_servicio = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$servicio = null;
$categorias_serv = [];

if ($id_servicio > 0) {
    $conexion = conectar_bd();
    $stmt = mysqli_prepare($conexion, '
        SELECT s.*, e.nombre_empresa
        FROM servicios s
        LEFT JOIN empresas e ON s.rel_id_empresa = e.id_empresa
        WHERE s.id = ?
        LIMIT 1
    ');
    mysqli_stmt_bind_param($stmt, 'i', $id_servicio);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $servicio = mysqli_fetch_assoc($res);
    }
    mysqli_stmt_close($stmt);

    $qc = mysqli_query($conexion, 'SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC');
    if ($qc) {
        while ($r = mysqli_fetch_assoc($qc)) {
            $categorias_serv[] = $r;
        }
    }
    mysqli_close($conexion);
}

if (!$servicio) {
    echo '<div class="container-fluid flex-grow-1 container-p-y"><div class="alert alert-danger">Servicio no encontrado</div><a href="servicios.php" class="btn btn-primary">Volver al listado</a></div>';
    return;
}
?>

<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h5 class="card-title mb-0">Editar servicio #<?php echo (int)$servicio['id']; ?></h5>
          <small class="text-muted"><?php echo htmlspecialchars($servicio['nombre_empresa'] ?? ''); ?></small>
          <button type="button" id="btn_volver_servicios_edit" class="btn btn-text-primary btn-header-card-right">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Servicios
          </button>
        </div>
        <div class="card-body mt-5">
          <form id="formEditarServicio" method="POST" action="parts/servicios/editar/actualizar_servicio.php" class="fv-plugins-bootstrap5 fv-plugins-framework">
            <input type="hidden" name="id_servicio" value="<?php echo (int)$servicio['id']; ?>" />

            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Datos generales</h5>
              </div>
              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline">
                  <?php generarSelectEmpresas((int)$servicio['rel_id_empresa'], 'rel_id_empresa', 'rel_id_empresa', true); ?>
                  <label for="rel_id_empresa" class="form-label">Empresa *</label>
                </div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="text" class="form-control" id="codigo" name="codigo" value="<?php echo htmlspecialchars($servicio['codigo'] ?? ''); ?>" maxlength="50" />
                  <label for="codigo">Código</label>
                </div>
              </div>
              <div class="col-md-3 mb-3">
                <div class="form-check form-switch mt-3">
                  <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?php echo ((int)$servicio['activo'] === 1) ? 'checked' : ''; ?> />
                  <label class="form-check-label" for="activo">Activo</label>
                </div>
              </div>
              <div class="col-md-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($servicio['nombre'] ?? ''); ?>" required maxlength="255" />
                  <label for="nombre">Nombre *</label>
                </div>
              </div>
              <div class="col-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <textarea class="form-control" id="descripcion" name="descripcion" style="min-height: 80px;"><?php echo htmlspecialchars($servicio['descripcion'] ?? ''); ?></textarea>
                  <label for="descripcion">Descripción</label>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline">
                  <select class="form-select select2" id="id_categoria" name="id_categoria">
                    <option value="0">— Sin categoría —</option>
                    <?php foreach ($categorias_serv as $cat) {
                        $sel = ((int)$servicio['id_categoria'] === (int)$cat['id_categoria']) ? 'selected' : '';
                        ?>
                    <option value="<?php echo (int)$cat['id_categoria']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                    <?php } ?>
                  </select>
                  <label for="id_categoria" class="form-label">Categoría</label>
                </div>
              </div>
            </div>

            <hr class="my-4" />

            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Tiempo</h5>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="duracion_horas" name="duracion_horas" value="<?php echo htmlspecialchars((string)$servicio['duracion_horas']); ?>" />
                  <label for="duracion_horas">Duración (horas)</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="1" class="form-control" id="duracion_minutos" name="duracion_minutos" value="<?php echo (int)$servicio['duracion_minutos']; ?>" />
                  <label for="duracion_minutos">Duración (minutos)</label>
                </div>
              </div>
              <div class="col-md-4 mb-4">
                <div class="form-floating form-floating-outline">
                  <select class="form-select select2" id="unidad_tiempo" name="unidad_tiempo">
                    <?php
                    $ut = $servicio['unidad_tiempo'] ?? 'hora';
                    $opts = ['hora' => 'Hora', 'media_hora' => 'Media hora', 'dia' => 'Día', 'sesion' => 'Sesión'];
                    foreach ($opts as $val => $lab) {
                        $sel = ($ut === $val) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($val) . '" ' . $sel . '>' . htmlspecialchars($lab) . '</option>';
                    }
                    ?>
                  </select>
                  <label for="unidad_tiempo" class="form-label">Unidad de tiempo</label>
                </div>
              </div>
            </div>

            <hr class="my-4" />

            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Precios e IVA</h5>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <select class="form-select select2" id="tipo_facturacion" name="tipo_facturacion">
                    <?php
                    $tf = $servicio['tipo_facturacion'] ?? 'por_hora';
                    $topts = ['por_hora' => 'Por hora', 'precio_fijo' => 'Precio fijo', 'por_sesion' => 'Por sesión'];
                    foreach ($topts as $val => $lab) {
                        $sel = ($tf === $val) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($val) . '" ' . $sel . '>' . htmlspecialchars($lab) . '</option>';
                    }
                    ?>
                  </select>
                  <label for="tipo_facturacion" class="form-label">Tipo de facturación</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_hora" name="precio_hora" value="<?php echo htmlspecialchars((string)$servicio['precio_hora']); ?>" />
                  <label for="precio_hora">Precio hora (venta)</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_coste_hora" name="precio_coste_hora" value="<?php echo htmlspecialchars((string)$servicio['precio_coste_hora']); ?>" />
                  <label for="precio_coste_hora">Precio coste / hora</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_fijo" name="precio_fijo" value="<?php echo htmlspecialchars((string)$servicio['precio_fijo']); ?>" />
                  <label for="precio_fijo">Precio fijo</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="porcentaje_iva" name="porcentaje_iva" value="<?php echo htmlspecialchars((string)$servicio['porcentaje_iva']); ?>" />
                  <label for="porcentaje_iva">IVA %</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="minimo_horas" name="minimo_horas" value="<?php echo htmlspecialchars((string)$servicio['minimo_horas']); ?>" />
                  <label for="minimo_horas">Mínimo horas</label>
                </div>
              </div>
              <div class="col-md-4 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="incremento_horas" name="incremento_horas" value="<?php echo htmlspecialchars((string)$servicio['incremento_horas']); ?>" />
                  <label for="incremento_horas">Incremento horas</label>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <textarea class="form-control" id="notas" name="notas" style="min-height: 90px;"><?php echo htmlspecialchars($servicio['notas'] ?? ''); ?></textarea>
                  <label for="notas">Notas</label>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary me-2">
                <i class="icon-base ri ri-check-line me-2"></i>Guardar cambios
              </button>
              <button type="button" id="btn_cancelar_edit_servicio" class="btn btn-outline-secondary">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
