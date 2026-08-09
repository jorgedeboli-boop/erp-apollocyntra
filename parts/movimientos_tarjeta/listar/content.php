<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">

    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Entradas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-entradas">0 €</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Total ingresos</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded-circle">
                <div class="icon-base ri ri-arrow-down-circle-line icon-26px"></div>
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
              <p class="text-heading mb-1">Salidas</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-salidas">0 €</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Total egresos</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-danger rounded-circle">
                <div class="icon-base ri ri-arrow-up-circle-line icon-26px"></div>
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
              <p class="text-heading mb-1">Saldo Total</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-saldo">0 €</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Entradas - Salidas</small>
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
  </div>
  
  <!-- Movimientos Tarjeta Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0" id="titulo-listados">Histórico de Movimientos con Tarjeta</h5>
      
      <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 mt-0">
        <div class="col-md-12">
          <div class="d-flex gx-1 gap-1">
            <div class="flex-fill movimiento_sucursal select2-btn-height">
              <!-- El filtro de sucursal se creará dinámicamente -->
            </div>
            <div class="flex-fill movimiento_grupo select2-btn-height">
              <!-- El filtro de grupo se creará dinámicamente -->
            </div>
            <div class="flex-fill select2-btn-height">
              <div class="input-group">
                <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas">
                <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
                <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
                <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                  <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" id="filtro_por_fecha_apunte" href="javascript:void(0);">Por fecha de apunte</a></li>
                  <li><a class="dropdown-item active" id="filtro_dia" href="javascript:void(0);">Día</a></li>
                  <li><a class="dropdown-item" id="filtro_mes" href="javascript:void(0);">Mes</a></li>
                  <li><a class="dropdown-item" id="filtro_todos" href="javascript:void(0);">Todos</a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-movimientos-tarjeta table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Sucursal</th>
            <th>Grupo</th>
            <th>Descripción</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Usuario</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<?php if ($puede_acceder_edit): ?>
<!-- Modal Nuevo Apunte -->
<div class="modal fade" id="modalNuevoApunte" tabindex="-1" aria-labelledby="modalNuevoApunteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalNuevoApunteLabel">Nuevo Movimiento con Tarjeta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevoApunte">
          <div class="mb-3">
            <label for="nuevo-sucursal" class="form-label">Sucursal</label>
            <select class="form-select" id="nuevo-sucursal" name="sucursal" required>
              <option value="">Seleccionar sucursal...</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="nuevo-fecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="nuevo-fecha" name="fecha" required>
          </div>
          
          <div class="mb-3">
            <label for="nuevo-grupo" class="form-label">Grupo</label>
            <select class="form-select" id="nuevo-grupo" name="grupos" required>
              <option value="">Seleccionar grupo...</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="nuevo-descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="nuevo-descripcion" name="descripcion" rows="3" required></textarea>
          </div>
          
          <div class="mb-3">
            <label for="nuevo-importe" class="form-label">Importe (€)</label>
            <input type="number" class="form-control" id="nuevo-importe" name="importe" step="0.01" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnCrearApunte">
          <i class="ri-save-line me-1"></i> Crear Apunte
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Movimiento -->
<div class="modal fade" id="modalEditarMovimiento" tabindex="-1" aria-labelledby="modalEditarMovimientoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarMovimientoLabel">Editar Movimiento #<span id="modal-movimiento-id"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarMovimiento">
          <input type="hidden" id="edit-id-movimiento" name="id">
          
          <div class="mb-3">
            <label for="edit-grupo" class="form-label">Grupo</label>
            <select class="form-select" id="edit-grupo" name="grupos" required>
              <option value="">Seleccionar grupo...</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="edit-descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="edit-descripcion" name="descripcion" rows="3" required></textarea>
          </div>
          
          <div class="mb-3">
            <label for="edit-importe" class="form-label">Importe (€)</label>
            <input type="number" class="form-control" id="edit-importe" name="importe" step="0.01" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" id="btnEliminarMovimiento">
          <i class="ri-delete-bin-line me-1"></i> Eliminar Apunte
        </button>
        <div class="ms-auto">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="btnGuardarMovimiento">
            <i class="ri-save-line me-1"></i> Actualizar Movimiento
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Los scripts se cargan desde javascript.php -->
