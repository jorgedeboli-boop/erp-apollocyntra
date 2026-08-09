<?php
$anio_actual = (int) date('Y');
$anios_disponibles = [];
$semanas_anio = [];

$conexion_filtros = conectar_bd();
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
?>
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
      <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h5 class="card-title mb-0">Configuración de semanas</h5>
        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_semanas_config" aria-expanded="false" aria-controls="collapse_filtros_semanas_config">
          <i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar
        </button>
      </div>
    </div>

    <div class="card-body pb-0">
      <div class="collapse d-lg-block" id="collapse_filtros_semanas_config">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
          <div class="col-md-3">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_anio">
              <?php foreach ($anios_disponibles as $anio): ?>
                <option value="<?php echo $anio; ?>"<?php echo $anio === $anio_actual ? ' selected' : ''; ?>>
                  <?php echo $anio; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_semana">
              <option value="">Todas las semanas</option>
              <?php foreach ($semanas_anio as $semana): ?>
                <?php
                $num_semana = (int) $semana['numero_semana'];
                $label_semana = 'Semana ' . $num_semana;
                if (!empty($semana['fecha_semana_desde']) && !empty($semana['fecha_semana_hasta'])) {
                    $label_semana .= ' (' . date('d/m/Y', strtotime($semana['fecha_semana_desde'])) . ' - ' . date('d/m/Y', strtotime($semana['fecha_semana_hasta'])) . ')';
                }
                ?>
                <option value="<?php echo $num_semana; ?>">
                  <?php echo htmlspecialchars($label_semana); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <div class="input-group">
              <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas">
              <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
              <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
              <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Opciones de periodo</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" id="semanas_config_filtro_dia" href="javascript:void(0);">Día</a></li>
                <li><a class="dropdown-item" id="semanas_config_filtro_mes" href="javascript:void(0);">Mes</a></li>
                <li><a class="dropdown-item" id="semanas_config_filtro_todos" href="javascript:void(0);">Todos</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-semanas-config table border-top">
        <thead>
          <tr>
            <th>Semana</th>
            <th>Desde</th>
            <th>Hasta</th>
            <th>Año</th>
            <th>Precio mercado</th>
            <th>Media % diferencia</th>
            <th>Precio fundición</th>
            <th>Cálculo precio</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditarPreciosSemana" tabindex="-1" aria-labelledby="modalEditarPreciosSemanaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header card-header border-bottom card-header-forms pb-4">
        <h4 class="card-title mb-0" id="modalEditarPreciosSemanaLabel">Editar precios semana</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" style="padding: 16px 28px 7px;">
        <form id="formEditarPreciosSemana" method="post" action="javascript:void(0);" autocomplete="off">
          <input type="hidden" name="id_numero_semana" id="semana_precio_id_numero_semana" value="">
          <p class="mb-3 text-body-secondary" id="semana_precio_resumen"></p>
          <div class="mb-3">
            <label for="semana_precio_24_mercado" class="form-label">Precio mercado (€ / gramo)</label>
            <input type="number" class="form-control" id="semana_precio_24_mercado" name="precio_24_mercado" step="0.01" min="0" required>
          </div>
          <div class="mb-3">
            <label for="semana_precio_gramo_oro" class="form-label">Precio fundición (€ / gramo)</label>
            <input type="number" class="form-control" id="semana_precio_gramo_oro" name="precio_gramo_oro" step="0.01" min="0" required>
          </div>
        </form>
      </div>
      <div class="modal-footer d-flex justify-content-between" style="padding: 17px 0 21px !important;">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" form="formEditarPreciosSemana" class="btn btn-primary" id="btnGuardarPreciosSemana">
          <i class="ri-save-line me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>
