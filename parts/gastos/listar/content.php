<div class="container-fluid flex-grow-1 container-p-y">
<?php require_once __DIR__ . '/filtros_opciones.php'; ?>

  <!-- Gastos List Table -->
  <div class="card card-mobile-not-shadow">

    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
      <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h5 class="card-title mb-0">Gastos</h5>

        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_gastos" aria-expanded="false" aria-controls="collapse_filtros_gastos"><i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar</button>

        <?php if ($puede_acceder_crear): ?>
        <a href="crear_gasto.php" class="btn btn-primary waves-effect waves-light px-3 ms-sm-auto"><span class="icon-base ri ri-add-fill icon-22px me-1"></span>Nuevo gasto</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-body pb-0">

      <div class="collapse d-lg-block" id="collapse_filtros_gastos">
        <div class="d-flex gx-1 pt-4 gap-2 select2-btn-height flex-wrap">
          <div class="flex-fill gasto_empresa">
            <select id="filtro_empresa" class="form-select select2-filter text-capitalize form-select-sm select2-custom">
              <?php gastos_listar_imprimir_opciones_empresas(); ?>
            </select>
          </div>
          <div class="flex-fill gasto_proveedor">
            <select id="filtro_proveedor" class="form-select select2-filter text-capitalize form-select-sm select2-custom">
              <?php gastos_listar_imprimir_opciones_proveedores(); ?>
            </select>
          </div>
          <div class="flex-fill gasto_estado">
            <select id="filtro_estado" class="form-select select2-filter text-capitalize form-select-sm select2-custom">
              <?php gastos_listar_imprimir_opciones_estados(); ?>
            </select>
          </div>
          <div class="flex-fill gasto_tipo_gasto">
            <select id="filtro_tipo_gasto" class="form-select select2-filter text-capitalize form-select-sm select2-custom">
              <?php gastos_listar_imprimir_opciones_tipos_gasto(); ?>
            </select>
          </div>
          <div class="flex-fill gasto_forma_pago">
            <select id="filtro_forma_pago" class="form-select select2-filter text-capitalize form-select-sm select2-custom">
              <?php gastos_listar_imprimir_opciones_formas_pago(); ?>
            </select>
          </div>
          <div class="flex-fill">
            <div class="input-group">
              <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas">
              <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
              <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
              <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Opciones de periodo</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" id="gasto_filtro_dia" href="javascript:void(0);">Día</a></li>
                <li><a class="dropdown-item" id="gasto_filtro_mes" href="javascript:void(0);">Mes</a></li>
                <li><a class="dropdown-item" id="gasto_filtro_todos" href="javascript:void(0);">Todos</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-gastos table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>DESCRIPCIÓN</th>
            <th>FECHA GASTO</th>
            <th>EMPRESA</th>
            <th>PROVEEDOR</th>
            <th>TIPO GASTO</th>
            <th>TOTAL</th>
            <th>ESTADO</th>
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
              <p class="text-heading mb-1">Total Gastos</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-2" id="total-gastos">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Registrados en el sistema</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-file-list-line icon-26px"></div>
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
              <p class="text-heading mb-1">Total en Euros</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-euros">0€</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Suma total de gastos</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded">
                <div class="icon-base ri ri-money-euro-circle-fill icon-26px"></div>
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
              <p class="text-heading mb-1">Media por Gasto</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="media-gasto">0€</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Promedio por gasto</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-info rounded-circle">
                <div class="icon-base ri ri-calculator-line icon-26px"></div>
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
              <p class="text-heading mb-1">Gastos Pendientes</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="gastos-pendientes">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Por pagar</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-warning rounded-circle">
                <div class="icon-base ri ri-time-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>
