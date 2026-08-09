<div class="container-fluid flex-grow-1 container-p-y">
  <!-- Articulos Vendidos List Table -->
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h5 class="card-title mb-0">Artículos Vendidos <span id="texto_articulos_vendidos_titulo"></span></h5>
      </div>

      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 mt-3">
        <div class="col-12 col-md-3 articulo_vendido_sucursal select2-btn-height"></div>
        <div class="col-12 col-md-2 articulo_vendido_tipo select2-btn-height"></div>

        <div class="col-12 col-md-4">
          <div class="input-group">
            <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas">
            <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
            <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
            <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" id="filtro_por_fecha_venta_vendidos" href="javascript:void(0);">Por Fecha de Venta</a></li>
              <li><a class="dropdown-item" id="filtro_dia_vendidos" href="javascript:void(0);">Día</a></li>
              <li><a class="dropdown-item" id="filtro_mes_vendidos" href="javascript:void(0);">Mes</a></li>
              <li><a class="dropdown-item" id="filtro_todos_vendidos" href="javascript:void(0);">Todos</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive pt-0">
      <table class="datatables-articulos-vendidos table border-top">
        <thead>
          <tr>
            <th style="width: 40px !important;">SKU</th>
            <th>Descripción</th>
            <th>Sucursal</th>
            <th>Fecha de venta</th>
            <th>Venta Nº</th>
            <th width="50">Precio</th>
            <th width="50">Coste</th>
            <th width="50">Peso</th>
            <th width="50">Tipo</th>
            <th width="50">Web</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
<!-- / Content -->