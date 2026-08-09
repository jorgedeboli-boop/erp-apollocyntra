<?php
$semana_actual = obtener_numero_semana();
$anio_actual = (int) date('Y');

$conexion_filtros = conectar_bd();
$anios_disponibles = [];
$semanas_anio = [];

if ($conexion_filtros) {
    $result_anios = mysqli_query(
        $conexion_filtros,
        "SELECT DISTINCT anyo_listado FROM listado_numero_semanas ORDER BY anyo_listado DESC"
    );
    if ($result_anios) {
        while ($row_anio = mysqli_fetch_assoc($result_anios)) {
            $anios_disponibles[] = (int) $row_anio['anyo_listado'];
        }
        mysqli_free_result($result_anios);
    }

    if (empty($anios_disponibles)) {
        $anios_disponibles[] = $anio_actual;
    }

    if (!in_array($anio_actual, $anios_disponibles, true)) {
        $anio_actual = $anios_disponibles[0];
    }

    $stmt_semanas = mysqli_prepare(
        $conexion_filtros,
        "SELECT numero_semana, fecha_semana_desde, fecha_semana_hasta
         FROM listado_numero_semanas
         WHERE anyo_listado = ?
         ORDER BY numero_semana ASC"
    );
    if ($stmt_semanas) {
        mysqli_stmt_bind_param($stmt_semanas, 'i', $anio_actual);
        mysqli_stmt_execute($stmt_semanas);
        $result_semanas = mysqli_stmt_get_result($stmt_semanas);
        while ($row_semana = mysqli_fetch_assoc($result_semanas)) {
            $semanas_anio[] = $row_semana;
        }
        mysqli_stmt_close($stmt_semanas);
    }

    mysqli_close($conexion_filtros);
}

if (!$semana_actual && !empty($semanas_anio)) {
    $semana_actual = (int) $semanas_anio[count($semanas_anio) - 1]['numero_semana'];
}
?>
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
      <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h5 class="card-title mb-0">Reportes Semanales</h5>
        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_reportes_semanales" aria-expanded="false" aria-controls="collapse_filtros_reportes_semanales">
          <i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar
        </button>
      </div>
    </div>

    <div class="card-body pb-0">
      <div class="collapse d-lg-block" id="collapse_filtros_reportes_semanales">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
          <div class="col-md-4">
            <label class="form-label" for="filtro_sucursal">Sucursal</label>
            <select class="form-select select2 select2-filter select2-custom" id="filtro_sucursal">
              <option value="">Todas las sucursales</option>
              <?php foreach (obtener_sucursales() as $sucursal): ?>
                <option value="<?php echo (int) $sucursal['id_sucursal']; ?>">
                  <?php echo htmlspecialchars($sucursal['nombre_sucursal']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="filtro_anio">Año</label>
            <select class="form-select select2 select2-filter select2-custom" id="filtro_anio">
              <?php foreach ($anios_disponibles as $anio): ?>
                <option value="<?php echo $anio; ?>"<?php echo $anio === $anio_actual ? ' selected' : ''; ?>>
                  <?php echo $anio; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="filtro_semana">Semana</label>
            <select class="form-select select2 select2-filter select2-custom" id="filtro_semana">
              <?php foreach ($semanas_anio as $semana): ?>
                <?php
                $num_semana = (int) $semana['numero_semana'];
                $label_semana = 'Semana ' . $num_semana;
                if (!empty($semana['fecha_semana_desde']) && !empty($semana['fecha_semana_hasta'])) {
                    $label_semana .= ' (' . date('d/m/Y', strtotime($semana['fecha_semana_desde'])) . ' - ' . date('d/m/Y', strtotime($semana['fecha_semana_hasta'])) . ')';
                }
                ?>
                <option value="<?php echo $num_semana; ?>"<?php echo $num_semana === (int) $semana_actual ? ' selected' : ''; ?>>
                  <?php echo htmlspecialchars($label_semana); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-reportes-semanales table border-top">
        <thead>
          <tr>
            <th style="width: 40px !important;">Semana</th>
            <th>Desde</th>
            <th>Hasta</th>
            <th>Compras € oro</th>
            <th>Compras grs oro</th>
            <th>Compras € plata</th>
            <th>Compras grs plata</th>
            <th>Empeños €</th>
            <th>Empeños grs</th>
            <th>Renovaciones €</th>
            <th>Ventas</th>
            <th>Gastos</th>
            <th>Stock</th>
            <th>Yulinfo</th>
            <th>Beneficio</th>
            <th>Sucursal</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Modal editar informe semanal -->
<div class="modal fade" id="modalEditarInformeSemanal" tabindex="-1" aria-labelledby="modalEditarInformeSemanalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header card-header-forms pb-3">
        <h5 class="modal-title" id="modalEditarInformeSemanalLabel">Editar reporte semanal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarInformeSemanal">
          <input type="hidden" id="editar_informe_id" name="id_informe">
          <div class="mb-3">
            <p class="mb-1 text-muted">Semana / Año</p>
            <h6 id="editar_informe_semana_anio">-</h6>
          </div>
          <div class="mb-3">
            <p class="mb-1 text-muted">Sucursal</p>
            <h6 id="editar_informe_sucursal">-</h6>
          </div>
          <div class="mb-4">
            <label class="form-label" for="editar_informe_total_gastos">Gastos (€)</label>
            <input type="number" class="form-control" id="editar_informe_total_gastos" name="total_gastos" step="0.01" min="0" required>
          </div>
          <div class="mb-2">
            <label class="form-label" for="editar_informe_yulinfo">Yulinfo (€)</label>
            <input type="number" class="form-control" id="editar_informe_yulinfo" name="yulinfo" step="0.01" min="0" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarInformeSemanal">
          <i class="icon-base ri ri-save-line me-1"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>
