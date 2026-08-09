<div class="container-fluid flex-grow-1 container-p-y">
  
  <!-- Stats Cards -->
  <div class="row g-6 mb-6">
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
  
  <!-- Gastos List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Gastos</h5>
        <a href="crear_gasto.php" class="btn btn-primary waves-effect waves-light">
          <i class="icon-base ri ri-add-line icon-16px me-2"></i>Nuevo Gasto
        </a>
      </div>
      <!-- Primera fila: Empresa, Sucursal, Proveedor, Estado -->
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0">
        <div class="col-md-3 user_empresa">
          <!-- El filtro de empresa se creará dinámicamente -->
        </div>
        <div class="col-md-3 user_sucursal">
          <!-- El filtro de sucursal se creará dinámicamente -->
        </div>
        <div class="col-md-3 user_proveedor">
          <!-- El filtro de proveedor se creará dinámicamente -->
        </div>
        <div class="col-md-3 user_estado">
          <!-- El filtro de estado se creará dinámicamente -->
        </div>
      </div>
      
      <!-- Segunda fila: Tipo de Gasto, Forma de Pago, Fecha Desde, Fecha Hasta -->
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-3 gap-5 gap-md-0">
        <div class="col-md-3 user_tipo_gasto">
          <!-- El filtro de tipo de gasto se creará dinámicamente -->
        </div>
        <div class="col-md-3 user_forma_pago">
          <!-- El filtro de forma de pago se creará dinámicamente -->
        </div>
        <div class="col-md-3">
          <label class="form-label">Desde</label>
          <input type="date" class="form-control" id="filtro_fecha_desde">
        </div>
        <div class="col-md-3">
          <label class="form-label">Hasta</label>
          <input type="date" class="form-control" id="filtro_fecha_hasta">
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
            <th>SUCURSAL</th>
            <th>PROVEEDOR</th>
            <th>TIPO GASTO</th>
            <th>TOTAL</th>
            <th>ESTADO</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>