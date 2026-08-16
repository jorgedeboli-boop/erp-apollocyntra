<?php
$url_imprimir_todo = 'Impresiones/Articulos/etiquetas_articulos.php?varios=true';
?>
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 w-100">
        <h5 class="card-title mb-0">
          Etiquetas pendientes
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
            <span id="texto_btn_imprimir_etiquetas">Imprimir todo</span>
          </a>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 mt-3">
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
