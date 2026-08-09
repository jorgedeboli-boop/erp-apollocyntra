<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total Idiomas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-idiomas">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Registrados en el sistema</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-global-line icon-26px"></div>
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
              <p class="text-heading mb-1">Activos</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-idiomas-activos">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Idiomas habilitados</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded-circle">
                <div class="icon-base ri ri-check-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
  
  <!-- Idiomas List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Idiomas</h5>
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0">
        <div class="col-md-4 user_codigo">
          <!-- El filtro de código se creará dinámicamente -->
        </div>
        <div class="col-md-4 user_pais">
          <!-- El filtro de país se creará dinámicamente -->
        </div>
        <div class="col-md-4 user_estado">
          <!-- El filtro de estado se creará dinámicamente -->
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-idiomas table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>CÓDIGO</th>
            <th>DESCRIPCIÓN</th>
            <th>PAÍS</th>
            <th>ESTADO</th>
            <th>ACCIONES</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Los scripts se cargan desde javascript.php -->