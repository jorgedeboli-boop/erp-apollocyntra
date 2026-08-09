<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total cuentas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-cuentas">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Cuentas bancarias registradas</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-bank-card-line icon-26px"></div>
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
              <p class="text-heading mb-1">Por defecto</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-cuentas-defecto">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Marcadas como predeterminadas</small>
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
      <h5 class="card-title mb-0">Cuentas bancarias</h5>
      <div class="d-flex justify-content-end row gx-5 pt-4 gap-5 gap-md-0">
        <div class="col-md-4 filtro_banco_cuenta"></div>
        <div class="col-md-4 filtro_empresa_cuenta"></div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-cuentas-banco-config table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nº CUENTA</th>
            <th>BANCO</th>
            <th>EMPRESA</th>
            <th>POR DEFECTO</th>
            <th>FECHA</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
