<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total Empresas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-empresas">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Registradas en el sistema</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-building-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Empresas Activas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-empresas-activas">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Con actividad reciente</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded-circle">
                <div class="icon-base ri ri-checkbox-circle-fill icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Nuevas Empresas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-empresas-nuevas">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Este mes</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-info rounded-circle">
                <div class="icon-base ri ri-add-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Empresas List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Empresas</h5>
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0">
        <div class="col-md-4 user_empresa">
          <!-- El filtro de empresa se creará dinámicamente -->
        </div>
        <div class="col-md-4 user_estado">
          <!-- El filtro de estado se creará dinámicamente -->
        </div>
        <div class="col-md-4 user_caja">
          <!-- El filtro de caja se creará dinámicamente -->
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-empresas table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>NOMBRE EMPRESA</th>
            <th>DIRECCIÓN</th>
            <th>POBLACIÓN</th>
            <th>PROVINCIA</th>
            <th>TELÉFONO</th>
            <th>CIF</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Los scripts se cargan desde javascript.php -->