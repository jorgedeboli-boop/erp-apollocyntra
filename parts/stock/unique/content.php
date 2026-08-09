<div class="container-fluid flex-grow-1 container-p-y clasenotpaddingcontaierfluid">
<?php require_once __DIR__ . '/../../universal/filtros_opciones_articulos.php'; ?>

  <div class="card card-mobile-not-shadow">

    <div class="card-header border-bottom card-header-forms card-header-whith-buttons">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h5 class="card-title mb-0">Stock <span id="texto_articulos_titulo"></span></h5>
        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros" aria-expanded="false" aria-controls="collapse_filtros"><i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar</button>
      </div>
    </div>

    <div class="card-body pb-0">
      <div class="collapse d-lg-block" id="collapse_filtros">

        <div class="d-flex row gx-5 pt-4 gap-2" id="stock_filtros_container">

          <div class="col-md-2 flex-fill articulo_sucursal select2-btn-height">
            <select id="filtro_sucursal_articulo" class="form-select select2-filter text-capitalize select2-custom">
              <option value="">Sucursales</option>
            </select>
          </div>
          <div class="col-md-2 flex-fill articulo_tipo select2-btn-height">
            <select id="filtro_tipo" class="form-select select2-filter text-capitalize select2-custom">
              <?php articulos_filtro_imprimir_opciones_tipo_venta(); ?>
            </select>
          </div>
          <div class="col-md-2 flex-fill articulo_origen select2-btn-height">
            <select id="filtro_origen" class="form-select select2-filter text-capitalize select2-custom">
              <?php articulos_filtro_imprimir_opciones_origen(); ?>
            </select>
          </div>
          <div class="col-md-3 flex-fill">
            <div class="input-group">
                          <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas">
                          <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
                          <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
                          <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                              <span class="visually-hidden">Toggle Dropdown</span>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" id="filtro_por_fecha_en_venta" href="javascript:void(0);">Por Fecha en Venta</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" id="filtro_dia" href="javascript:void(0);">Día</a></li>
                            <li><a class="dropdown-item" id="filtro_mes" href="javascript:void(0);">Mes</a></li>
                            <li><a class="dropdown-item" id="filtro_todos" href="javascript:void(0);">Todos</a></li>
                          </ul>
            </div>
          </div>

        </div>

      </div>
    </div>

    <div class="card-datatable table-responsive pt-0">
      <table class="datatables-stock table border-top">
        <thead>
          <tr>
            <th width="50">SKU</th>
            <th width="250">Descripción</th>
            <th width="70">Sucursal Origen</th>
            <th width="100">Sucursal</th>
            <th width="90">Peso</th>
            <th width="50">Precio</th>
            <th width="50">Precio Coste</th>
            <th width="70">€/g</th>
            <th width="50">Tipo</th>
            <th width="90">F. Enviado</th>
            <th width="90">F. En Venta</th>
            <th width="100">Creado Por</th>
            <th width="80">Origen</th>
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
              <p class="text-heading mb-1">Total Stock</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-articulos">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Artículos en venta</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-store-2-line icon-26px"></div>
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
              <p class="text-heading mb-1">Oro</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-oro">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Artículos de oro</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-warning rounded-circle">
                <div class="icon-base ri ri-copper-coin-line icon-26px"></div>
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
              <p class="text-heading mb-1">Plata</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-plata">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Artículos de plata</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-secondary rounded-circle">
                <div class="icon-base ri ri-coin-line icon-26px"></div>
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
              <p class="text-heading mb-1">Otros</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-otros">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Acero y otros</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-info rounded-circle">
                <div class="icon-base ri ri-box-3-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
<!-- / Content -->
