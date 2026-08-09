<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total Proveedores</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-proveedores">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Registrados en el sistema</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-truck-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Con Fundición</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-proveedores-fundicion">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Servicio de fundición</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded-circle">
                <div class="icon-base ri ri-tools-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
  
  <!-- Proveedores List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Proveedores</h5>
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0">
        <div class="col-md-4 user_proveedor">
          <!-- El filtro de proveedor se creará dinámicamente -->
        </div>
        <div class="col-md-4 user_fundicion">
          <!-- El filtro de fundición se creará dinámicamente -->
        </div>
        <div class="col-md-4 user_pago">
          <!-- El filtro de forma de pago se creará dinámicamente -->
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-proveedores table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>NOMBRE PROVEEDOR</th>
            <th>DIRECCIÓN</th>
            <th>POBLACIÓN</th>
            <th>PROVINCIA</th>
            <th>TELÉFONO</th>
            <th>CIF</th>
            <th>ACCIONES</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Los scripts se cargan desde javascript.php -->