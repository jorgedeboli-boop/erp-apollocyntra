<div class="container-fluid flex-grow-1 container-p-y">

  <div class="card card-mobile-not-shadow">

    <div class="card-header border-bottom card-header-forms titulos-cards-pages">

        <div class="d-flex justify-content-between align-items-center w-100">
            <h5 class="card-title mb-0">Facturas simplificadas</h5>

            <button type="button" class="btn btn-text btn-sm waves-effect p-0  d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_fs" aria-expanded="false" aria-controls="collapse_filtros_fs"><i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar</button>
        </div>

    </div>

    <div class="card-body pb-0">

        <div class="collapse d-lg-block" id="collapse_filtros_fs">
            <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
                <div class="col-12 col-sm-6 col-md-6 col-lg-2 factura_simplificada_sucursal">
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-2 factura_simplificada_empresa">
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-2 factura_simplificada_tipo_pago">
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-2 factura_simplificada_estado">
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                    <div class="input-group">
                        <input type="text" id="rangeFechasFs" class="form-control flatpickr-input" placeholder="Selecciona fechas">
                        <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde_fs">
                        <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta_fs">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          <li><a class="dropdown-item" id="filtro_por_fecha_compra_fs" href="javascript:void(0);">Por Fecha de Compra</a></li>
                          <li><a class="dropdown-item" id="filtro_por_fecha_vencimiento_fs" href="javascript:void(0);">Por Fecha de Vencimiento</a></li>
                          <li><a class="dropdown-item" id="filtro_dia_fs" href="javascript:void(0);">Día</a></li>
                          <li><a class="dropdown-item" id="filtro_mes_fs" href="javascript:void(0);">Mes</a></li>
                          <li><a class="dropdown-item" id="filtro_todos_fs" href="javascript:void(0);">Todos</a></li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-facturas-simplificadas table border-top">
        <thead>
          <tr>
            <th>Nº</th>
            <th>NÚMERO</th>
            <th>FECHA</th>
            <th>HORA</th>
            <th>SUCURSAL</th>
            <th>EMPRESA</th>
            <th>TOTAL</th>
            <th>ESTADO</th>
            <th>TIPO PAGO</th>
            <th>TIPO</th>
            <th>ACCIONES</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

</div>
