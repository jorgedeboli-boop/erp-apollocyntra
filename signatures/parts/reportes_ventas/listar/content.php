<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
      <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h5 class="card-title mb-0">Reportes Ventas <span id="texto_reportes_ventas_titulo"></span></h5>
        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_reportes_ventas" aria-expanded="false" aria-controls="collapse_filtros_reportes_ventas">
          <i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar
        </button>
      </div>
    </div>

    <div class="card-body pb-0">
      <div class="collapse d-lg-block" id="collapse_filtros_reportes_ventas">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
          <div class="col-md-2">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_sucursal">
              <option value="">Todas las sucursales</option>
              <?php foreach (obtener_sucursales() as $sucursal): ?>
                <option value="<?php echo (int) $sucursal['id_sucursal']; ?>">
                  <?php echo htmlspecialchars($sucursal['nombre_sucursal']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_tipo">
              <option value="">Oro y plata</option>
              <option value="oro">Oro</option>
              <option value="plata">Plata</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_plazos">
              <option value="">Plazos</option>
              <option value="si">Sí</option>
              <option value="no">No</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_plazos_pendientes">
              <option value="">Plazos pdtes</option>
              <option value="si">Sí</option>
              <option value="no">No</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_tipo_pago">
              <option value="">Tipo pago</option>
              <option value="contado">Efectivo</option>
              <option value="tarjeta">Tarjeta</option>
              <option value="bizum">Bizum</option>
              <option value="combinado">Combinado</option>
              <option value="transferencia">Transferencia</option>
            </select>
          </div>
          <div class="col-md-2">
            <div class="input-group">
              <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Fecha venta">
              <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
              <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
              <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" id="filtro_por_fecha_venta_reportes" href="javascript:void(0);">Por Fecha de Venta</a></li>
                <li><a class="dropdown-item" id="filtro_dia_reportes" href="javascript:void(0);">Día</a></li>
                <li><a class="dropdown-item" id="filtro_mes_reportes" href="javascript:void(0);">Mes</a></li>
                <li><a class="dropdown-item" id="filtro_todos_reportes" href="javascript:void(0);">Todos</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-reportes-ventas table border-top">
        <thead>
          <tr>
            <th style="width: 40px !important;">SKU</th>
            <th>Descripción</th>
            <th>Sucursal</th>
            <th>Fecha</th>
            <th>Venta</th>
            <th style="width: 90px !important;">Factura</th>
            <th style="width: 60px !important;">Coste</th>
            <th width="50">Precio</th>
            <th width="50">Peso</th>
            <th width="50" align="center">Web</th>
            <th width="50">Tipo</th>
            <th width="50">Plazos</th>
            <th width="50">Plazos pdtes</th>
            <th width="50">Tipo pago</th>
            <th width="50">Pagos</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
