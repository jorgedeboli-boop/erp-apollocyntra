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
  <div class="card card-action card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages position-relative">
      <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h5 class="card-title mb-0">Reportes Semanales</h5>
        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_reportes_semanales" aria-expanded="false" aria-controls="collapse_filtros_reportes_semanales">
          <i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar
        </button>
      </div>
      <div class="card-action-element btn-header-card-right" style="right: 22px;top: 11px;">
        <ul class="list-inline mb-0">
          <li class="list-inline-item">
            <a href="javascript:void(0);" class="card-expand" aria-label="Pantalla completa"><i class="icon-base ri ri-fullscreen-fill icon-sm"></i></a>
          </li>
        </ul>
      </div>
    </div>

    <div class="card-body pb-0 totales">
      <div class="collapse d-lg-block" id="collapse_filtros_reportes_semanales">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
          <div class="col-md-3">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_empresa">
              <option value="">Todas las empresas</option>
              <?php foreach (obtener_empresas() as $empresa): ?>
                <option value="<?php echo (int) $empresa['id_empresa']; ?>">
                  <?php echo htmlspecialchars($empresa['nombre_empresa']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_sucursal">
              <option value="">Todas las sucursales</option>
              <?php foreach (obtener_sucursales() as $sucursal): ?>
                <option value="<?php echo (int) $sucursal['id_sucursal']; ?>">
                  <?php echo htmlspecialchars($sucursal['nombre_sucursal']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
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
      <table class="dt-fixedcolumns datatables-reportes-semanales table border-top">
        <thead>
          <tr>
            <th colspan="2" class="text-bg-Dark bg-label-Dark rs-grupo-top text-aling-left" data-dt-order="disable">Sucursal</th>
            <th colspan="3" class="text-bg-Dark rs-grupo-top" data-dt-order="disable">Semana</th>
            <th colspan="5" class="text-bg-warning rs-grupo-top" data-dt-order="disable">Compras oro</th>
            <th colspan="4" class="text-bg-secondary rs-grupo-top" data-dt-order="disable">Compras plata</th>
            <th class="text-bg-success reportes-semanales-th-beneficio-oro-plata reportes-semanales-th-beneficio-top rs-grupo-top text-center" data-dt-order="disable"><span class="dt-column-title">Beneficio</span></th> <!-- beneficio_fundicion -->
            <th colspan="5" class="text-bg-Dark rs-grupo-top" data-dt-order="disable">Ingresos</th>
            <th colspan="2" class="text-bg-danger rs-grupo-top" data-dt-order="disable">Gastos</th>
            <th colspan="2" class="text-bg-success reportes-semanales-th-utilidad rs-grupo-utilidad rs-grupo-top" style="width: 152px !important;" data-dt-order="disable">Utilidad</th>
            <th rowspan="2" class="reportes-semanales-col-oculta" aria-hidden="true">ID</th>
            <th rowspan="2" class="reportes-semanales-col-oculta" aria-hidden="true">Meta</th>
            <th rowspan="2" class="reportes-semanales-col-oculta" aria-hidden="true">Empresa</th>
          </tr>
          <tr>
            <th class="text-bg-Dark bg-label-Dark border-0 rs-grupo-bottom">Nº</th>
            <th class="text-bg-Dark bg-label-Dark border-0 rs-grupo-bottom">Nombre</th>
            <th class="text-bg-Dark border-0 rs-grupo-bottom rs-grupo-full">Número</th>
            <th class="text-bg-Dark border-0 rs-grupo-bottom rs-grupo-full">Desde</th>
            <th class="text-bg-Dark border-0 rs-grupo-bottom rs-grupo-full">Hasta</th>
            <th class="text-bg-warning border-0 rs-grupo-bottom rs-grupo-full">Pagado</th>
            <th class="text-bg-warning border-0 rs-grupo-bottom rs-grupo-full">Abonados</th>
            <th class="text-bg-warning border-0 rs-grupo-bottom rs-grupo-full">Peso</th>
            <th class="text-bg-warning border-0 rs-grupo-bottom rs-grupo-full">Fundición</th>
            <th class="text-bg-warning border-0  rs-grupo-bottom rs-grupo-full">Beneficio</th>
            <th class="text-bg-secondary border-0 rs-grupo-bottom rs-grupo-fullm">Pagado</th>
            <th class="text-bg-secondary border-0 rs-grupo-bottom rs-grupo-full">Peso</th>
            <th class="text-bg-secondary border-0 rs-grupo-bottom rs-grupo-full">Fundición</th>
            <th class="text-bg-secondary border-0 rs-grupo-bottom rs-grupo-full">Beneficio</th>
            <th class="text-bg-success reportes-semanales-th-beneficio-oro-plata reportes-semanales-th-beneficio-bottom border-0 rs-grupo-bottom rs-grupo-full text-center"><span class="dt-column-title">Oro/Plata</span></th>
            <th class="text-bg-Dark border-0  rs-grupo-bottom rs-grupo-full">Beneficios Renovaciones</th>
            <th class="text-bg-Dark border-0 rs-grupo-bottom rs-grupo-full">Beneficios Ventas</th>
            <th class="text-bg-Dark border-0 rs-grupo-bottom rs-grupo-full">Gramos ofina</th>
            <th class="text-bg-Dark border-0 rs-grupo-bottom rs-grupo-full">Imp. stock fundición</th>
            <th class="text-bg-Dark border-0 rs-grupo-bottom rs-grupo-full">Arreglos joyería</th>
            <th class="text-bg-danger border-0  rs-grupo-bottom rs-grupo-full">Sucursal</th>
            <th class="text-bg-danger border-0  rs-grupo-bottom rs-grupo-full">Empresa</th>
            <th class="text-bg-success border-0 rs-grupo-start">%</th>
            <th class="text-bg-success border-0  rs-grupo-end">€</th>
          </tr>
        </thead>
      </table>
    </div>

    <!-- Modal editar informe semanal -->
    <div class="modal fade modal-draggable" id="modalEditarInformeSemanal" tabindex="-1" aria-labelledby="modalEditarInformeSemanalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header card-header-forms pb-3 modal-draggable-handle">
            <h5 class="modal-title" id="modalEditarInformeSemanalLabel">Editar reporte semana <span id="editar_informe_semana_anio">-</span> <span id="editar_semanas_desde_hasta">-</span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form id="formEditarInformeSemanal">
              <input type="hidden" id="editar_informe_id" name="id_informe">
             
              <div class="row mb-3 px-1">
                <div class="col-5 ">
                  <p class="mb-1 text-muted">Sucursal</p>
                  <p class="fw-bold" id="editar_informe_sucursal">-</p>
                </div>
                <div class="col-7 ">
                  <p class="mb-1 text-muted">Empresa</p>
                  <p class="fw-bold" id="editar_nombre_empresa">-</p>
                </div>
              </div>

              <div class="row mb-3 px-1">
                <div class="col-4 mb-4">
                  <label class="form-label" for="editar_precio_fundicion_gramo_oro">Precio fundición oro</label>
                  <div class="input-group input-group-merge input-group-sm">
                    <input type="text" class="form-control" id="editar_precio_fundicion_gramo_oro" name="precio_fundicion_gramo_oro" placeholder="0.00">
                    <span class="input-group-text">€/gr</span>
                  </div>
                </div>
                <div class="col-4 mb-4">
                  <label class="form-label" for="editar_precio_fundicion_precio_fundicion_gramo_platagramo_oro">Precio fundición plata</label>
                  <div class="input-group input-group-merge input-group-sm">
                    <input type="text" class="form-control" id="editar_precio_fundicion_gramo_plata" name="precio_fundicion_gramo_plata" placeholder="0.00">
                    <span class="input-group-text">€/gr</span>
                  </div>
                </div>
                <div class="col-4 mb-4">
                  <label class="form-label" for="editar_informe_beneficio_stock">Importe stock en oficina</label>
                  <div class="input-group input-group-merge input-group-sm">
                    <input type="text" class="form-control" id="editar_informe_beneficio_stock" name="beneficio_stock_fundicion" placeholder="0.00">
                    <span class="input-group-text">€</span>
                  </div>
                </div>
              </div>

              <div class="row mb-3 px-1">
                <div class="col-4 mb-4">
                  <label class="form-label" for="editar_total_gramos_oficina">Gramos oficina</label>
                  <div class="input-group input-group-merge input-group-sm">
                    <input type="text" class="form-control" id="editar_total_gramos_oficina" name="total_gramos_oficina" placeholder="0.00">
                    <span class="input-group-text">€</span>
                  </div>
                </div>
                <div class="col-4 mb-3 ">
                  <label class="form-label" for="editar_informe_total_gastos">Gastos sucursal</label>
                  <div class="input-group input-group-merge input-group-sm">
                    <input type="text" class="form-control" id="editar_informe_total_gastos" name="total_gastos" placeholder="0.00" required>
                    <span class="input-group-text">€</span>
                  </div>
                </div>
                <div class="col-4 mb-3 ">
                  <label class="form-label" for="editar_informe_yulinfo">Gastos empresa</label>
                  <div class="input-group input-group-merge input-group-sm">
                    <input type="text" class="form-control" id="editar_informe_yulinfo" name="yulinfo" placeholder="0.00" required>
                    <span class="input-group-text">€</span>
                  </div>
                </div>
              </div>
            </form>

          <div class="alert alert-solid-warning d-flex align-items-center flex-wrap row-gap-2 mb-0" role="alert" style="font-size: 14px;">
            <span class="alert-icon rounded">
              <i class="icon-base ri ri-alert-line icon-md"></i>
            </span>
            Al guardar estos cambios el sistema actualizará todos los valores
          </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary px-7" id="btnGuardarInformeSemanal">
              <i class="icon-base ri ri-check-line me-1"></i>Actualizar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
