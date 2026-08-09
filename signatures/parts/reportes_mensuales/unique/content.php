<?php
$mes_actual = (int) date('n');
$anio_actual = (int) date('Y');

$conexion_filtros = conectar_bd();
$anios_disponibles = [];

if ($conexion_filtros) {
    $result_anios = mysqli_query(
        $conexion_filtros,
        "SELECT DISTINCT year_informe FROM informe_mensual ORDER BY year_informe DESC"
    );
    if ($result_anios) {
        while ($row_anio = mysqli_fetch_assoc($result_anios)) {
            $anios_disponibles[] = (int) $row_anio['year_informe'];
        }
        mysqli_free_result($result_anios);
    }

    mysqli_close($conexion_filtros);
}

if (empty($anios_disponibles)) {
    $anios_disponibles[] = $anio_actual;
}

if (!in_array($anio_actual, $anios_disponibles, true)) {
    $anio_actual = $anios_disponibles[0];
}

$meses_nombre = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
?>
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
      <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h5 class="card-title mb-0">Reportes Mensuales</h5>
        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_reportes_mensuales" aria-expanded="false" aria-controls="collapse_filtros_reportes_mensuales">
          <i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar
        </button>
      </div>
    </div>

    <div class="card-body pb-0">
      <div class="collapse d-lg-block" id="collapse_filtros_reportes_mensuales">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
          <div class="col-md-4">
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
            <select class="form-select select2 select2-filter select2-custom" id="filtro_anio">
              <?php foreach ($anios_disponibles as $anio): ?>
                <option value="<?php echo $anio; ?>"<?php echo $anio === $anio_actual ? ' selected' : ''; ?>>
                  <?php echo $anio; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_mes">
              <?php foreach ($meses_nombre as $num_mes => $nombre_mes): ?>
                <option value="<?php echo $num_mes; ?>"<?php echo $num_mes === $mes_actual ? ' selected' : ''; ?>>
                  <?php echo htmlspecialchars($nombre_mes); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-reportes-mensuales table border-top">
        <thead>
          <tr>
            <th style="width: 40px !important;">Mes</th>
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

<div class="modal fade" id="modalEditarInformeMensual" tabindex="-1" aria-labelledby="modalEditarInformeMensualLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header card-header-forms pb-3">
        <h5 class="modal-title" id="modalEditarInformeMensualLabel">Editar reporte mensual</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarInformeMensual">
          <input type="hidden" id="editar_informe_id" name="id_informe">
          <div class="mb-3">
            <p class="mb-1 text-muted">Mes / Año</p>
            <h6 id="editar_informe_mes_anio">-</h6>
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
        <button type="button" class="btn btn-primary" id="btnGuardarInformeMensual">
          <i class="icon-base ri ri-save-line me-1"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>
