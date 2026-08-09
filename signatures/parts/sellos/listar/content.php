<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total sellos</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-sellos">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Registrados en el sistema</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-stamp-line icon-26px"></div>
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
              <p class="text-heading mb-1">Con logotipo</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-sellos-logotipo">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Sellos con imagen</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded-circle">
                <div class="icon-base ri ri-image-line icon-26px"></div>
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
              <p class="text-heading mb-1">Último sello</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="fecha-ultimo-sello">-</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Fecha de creación</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-info rounded-circle">
                <div class="icon-base ri ri-calendar-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Sellos</h5>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-sellos table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>NOMBRE SELLO</th>
            <th>LOGOTIPO</th>
            <th>FECHA CREACIÓN</th>
            <th>CREADO POR</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
