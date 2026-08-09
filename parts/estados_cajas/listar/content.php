<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total Sucursales</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-sucursales">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Registradas en el sistema</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-store-line icon-26px"></div>
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
              <p class="text-heading mb-1">Cajas Abiertas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-cajas-abiertas">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Sucursales operativas</small>
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
              <p class="text-heading mb-1">Cajas Cerradas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-cajas-cerradas">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Cajas no operativas</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-warning rounded-circle">
                <div class="icon-base ri ri-lock-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Estados de Cajas List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Estados de Cajas</h5>
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 select2-btn-height">
        <div class="col-md-4 user_estado">
          <!-- El filtro de estado de caja se creará dinámicamente -->
        </div>
        <div class="col-md-4 user_sistema">
          <!-- El filtro de nuevo sistema se creará dinámicamente -->
        </div>
        <div class="col-md-4">
          <!-- Espacio vacío para mantener el layout -->
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-estados-cajas table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>NOMBRE SUCURSAL</th>
            <th>NUEVO SISTEMA CAJA</th>
            <th>ESTADO CAJA</th>
            <th>SALDO</th>
            <th>APERTURA</th>
            <th>IMPORTE APERTURA</th>
            <th>CIERRE</th>
            <th>IMPORTE CIERRE</th>
            <th>ACCIONES</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<?php if ($puede_acceder_edit): ?>
<!-- Modal Abrir Caja -->
<div class="modal fade" id="modalAbrirCaja" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Abrir Caja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">Sucursal: <strong id="modal-sucursal-nombre"></strong></p>
        <div class="mb-3">
          <label for="importe-apertura" class="form-label">Importe de Apertura</label>
          <div class="input-group">
            <input type="number" 
                   class="form-control" 
                   id="importe-apertura" 
                   step="0.01" 
                   min="0"
                   placeholder="0.00">
            <span class="input-group-text">€</span>
          </div>
          <small class="text-muted">Importe con el que se abrirá la caja</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-confirmar-apertura">
          <i class="icon-base ri ri-lock-unlock-line me-1"></i>Abrir Caja
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Los scripts se cargan desde javascript.php -->
