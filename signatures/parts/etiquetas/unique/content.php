<?php
$sucursal_articulo = isset($_GET['sucursal_articulo']) ? (int) $_GET['sucursal_articulo'] : 0;
$nombre_sucursal_titulo = 'todas las sucursales operativas';

if ($sucursal_articulo > 0) {
    $conexion_etiquetas = conectar_bd();
    if ($conexion_etiquetas) {
        $stmt_nom = mysqli_prepare($conexion_etiquetas, 'SELECT nombre_sucursal FROM sucursal WHERE id_sucursal = ? LIMIT 1');
        if ($stmt_nom) {
            mysqli_stmt_bind_param($stmt_nom, 'i', $sucursal_articulo);
            mysqli_stmt_execute($stmt_nom);
            $res_nom = mysqli_stmt_get_result($stmt_nom);
            if ($row_nom = mysqli_fetch_assoc($res_nom)) {
                $nombre_sucursal_titulo = $row_nom['nombre_sucursal'];
            }
            mysqli_stmt_close($stmt_nom);
        }
        mysqli_close($conexion_etiquetas);
    }
}

$url_imprimir_todo = 'Impresiones/Articulos/etiquetas_articulos.php?varios=true';
if ($sucursal_articulo > 0) {
    $url_imprimir_todo .= '&por_sucursal=' . $sucursal_articulo;
}
?>
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 w-100">
        <h5 class="card-title mb-0">
          Etiquetas pendientes de <span id="texto_etiquetas_sucursal_titulo"><?php echo htmlspecialchars($nombre_sucursal_titulo, ENT_QUOTES, 'UTF-8'); ?></span>
          <span id="texto_etiquetas_filtros_titulo" class="text-muted fs-6"></span>
        </h5>
        <div class="d-flex align-items-center flex-wrap gap-2">
          <a
            href="<?php echo htmlspecialchars($url_imprimir_todo, ENT_QUOTES, 'UTF-8'); ?>"
            target="_blank"
            class="btn btn-success waves-effect waves-light etiqueta-print-link-masivo"
            id="btn_imprimir_etiquetas_masivo"
          >
            <i class="icon-base ri ri-printer-line me-1"></i>
            <span id="texto_btn_imprimir_etiquetas"><?php echo $sucursal_articulo > 0 ? 'Imprimir etiquetas de ' . htmlspecialchars($nombre_sucursal_titulo, ENT_QUOTES, 'UTF-8') : 'Imprimir todo'; ?></span>
          </a>
        </div>
      </div>

      <input type="hidden" id="sucursal_articulo_inicial" value="<?php echo (int) $sucursal_articulo; ?>">

      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 mt-3">
        <div class="col-12 col-md-3 etiquetas_sucursal select2-btn-height"></div>

        <div class="col-12 col-md-4">
          <div class="input-group">
            <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas">
            <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
            <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
            <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" id="filtro_por_fecha_alta_etiquetas" href="javascript:void(0);">Por fecha de alta</a></li>
              <li><a class="dropdown-item" id="filtro_dia_etiquetas" href="javascript:void(0);">Hoy</a></li>
              <li><a class="dropdown-item" id="filtro_mes_etiquetas" href="javascript:void(0);">Mes</a></li>
              <li><a class="dropdown-item" id="filtro_todos_etiquetas" href="javascript:void(0);">Todas</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive pt-0">
      <table class="datatables-etiquetas-pendientes table border-top">
        <thead>
          <tr>
            <th style="width: 40px !important;">SKU</th>
            <th>Descripción</th>
            <th>Sucursal</th>
            <th>Origen alta</th>
            <th>Fecha alta</th>
            <th>Precio</th>
            <th width="93">Peso</th>
            <th>Tipo</th>
            <th>Creado por</th>
            <th>Total etiquetas</th>
            <th>Imprimir</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  <div class="row mt-4">
    <div class="col-12">
      <div class="totales-etiquetas-pendientes text-center">
        <span class="total-etiquetas-label">Total etiquetas</span>
        <span class="total-etiquetas-valor" id="total_etiquetas">0</span>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->
