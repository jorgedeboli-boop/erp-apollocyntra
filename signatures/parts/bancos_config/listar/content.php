<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total bancos</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-bancos">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Registrados en el sistema</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-bank-line icon-26px"></div>
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
              <p class="text-heading mb-1">Bancos activos</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-bancos-activos">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Con estado activo</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded-circle">
                <div class="icon-base ri ri-checkbox-circle-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Bancos</h5>
      <div class="d-flex justify-content-end row gx-5 pt-4 gap-5 gap-md-0">
        <div class="col-md-4 filtro_estado_banco">
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-bancos-config table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>NOMBRE</th>
            <th>CONTACTO</th>
            <th>TELÉFONO</th>
            <th>EMAIL</th>
            <th>POBLACIÓN</th>
            <th>PROVINCIA</th>
            <th>ESTADO</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
