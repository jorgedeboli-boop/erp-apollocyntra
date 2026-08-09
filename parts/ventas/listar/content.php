<div class="container-fluid flex-grow-1 container-p-y">
  
  <!-- Ventas List Table -->
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
    <div class="d-flex justify-content-between align-items-center w-100">
      <h5 class="card-title mb-0">Ventas <span id="texto_ventas_titulo"></span></h5>
      <button type="button" class="btn btn-text btn-sm waves-effect p-0  d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros" aria-expanded="false" aria-controls="collapse_filtros"><i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar</button>
      <?php if ($puede_acceder_crear): ?>
      <a href="javascript:void(0);" type="button" class="btn btn-primary waves-effect waves-light px-3 btn-create-record" id="btn_nueva_venta"><span class="icon-base ri ri-add-fill icon-22px me-1"></span>Nueva venta</a>
      <div style="max-width: 260px; width: 100%; display: none;" id="select_sucursal_nueva_venta_container">
        <select class="form-select select2 select2-custom" id="select_sucursal_nueva_venta" name="sucursal_nueva_venta" autocomplete="off">
          <option value="">Seleccionar sucursal para venta</option>
          <?php obtener_select_sucursales_habilitadas(); ?>
        </select>
      </div>
      <?php endif; ?>
    </div>
    </div>

    <div class="card-body pb-0">
        <div class="collapse d-lg-block" id="collapse_filtros">
            <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
            <div class="col-md-2 venta_sucursal select2-btn-height">
      </div>
      <div class="col-md-2 venta_tipo select2-btn-height">
      </div>
      <div class="col-md-1 venta_web select2-btn-height">
      </div>
      <div class="col-md-2 venta_forma_pago select2-btn-height">
      </div>
      
        <div class="col-md-5">
        
          <div class="input-group">
                        <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas">
                        <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
                        <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                          <li><a class="dropdown-item" id="filtro_por_fecha_venta" href="javascript:void(0);">Por Fecha de Venta</a></li>
                          <li><a class="dropdown-item" id="filtro_dia" href="javascript:void(0);">Día</a></li>
                          <li><a class="dropdown-item" id="filtro_mes" href="javascript:void(0);">Mes</a></li>
                          <li><a class="dropdown-item" id="filtro_todos" href="javascript:void(0);">Todos</a></li>
                        </ul>
            </div>

        </div>
            </div>
        </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-ventas table border-top">
        <thead>
          <tr>
            <th style="width: 40px !important;">Nº venta</th>
            <th>Total venta</th>
            <th>Fecha venta</th>
            <th>Sucursal venta</th>
            <th>Vendido por</th>
            <th width="150">Venta plazos</th>
            <th>Venta web</th>
            <th>Forma de pago</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  <div class="row g-6 mt-1 d-none d-md-flex">

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total Ventas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-ventas">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Según filtros aplicados</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-money-euro-circle-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Importe Total</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-importe">0 €</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Suma de ventas</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded-circle">
                <div class="icon-base ri ri-money-dollar-circle-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Ventas Web</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-web">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Ventas online</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-info rounded-circle">
                <div class="icon-base ri ri-global-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Ventas a Plazos</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-plazos">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Ventas financiadas</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-warning rounded-circle">
                <div class="icon-base ri ri-calendar-schedule-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
</div>
<!-- / Content -->
