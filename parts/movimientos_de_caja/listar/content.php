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
                <div class="icon-base ri ri-wallet-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Movimientos de Caja Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0" id="titulo-listados">Histórico de Movimientos de Caja</h5>
      
      <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 mt-0">
        <div class="col-md-12">
          <div class="d-flex gx-1 gap-1">
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
      <table class="datatables-movimientos-caja table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Grupo</th>
            <th>Concepto</th>
            <th>Salida</th>
            <th>Entrada</th>
            <th>Usuario</th>
            <?php if ($puede_acceder_edit): ?>
            <th class="d-none"></th>
            <th class="text-center">Acciones</th>
            <?php endif; ?>
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
        <h5 class="modal-title" id="modalNuevoApunteLabel">Nuevo Apunte de Caja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevoApunte">
          <div class="mb-3">
            <label for="nuevo-fecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="nuevo-fecha" name="fecha_apunte" required>
          </div>
          
          <div class="mb-3">
            <label for="nuevo-grupo" class="form-label">Grupo</label>
            <select class="form-select" id="nuevo-grupo" name="grupos" required>
              <option value="">Seleccionar grupo...</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="nuevo-concepto" class="form-label">Concepto</label>
            <textarea class="form-control" id="nuevo-concepto" name="concepto" rows="3" required></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="nuevo-salida" class="form-label">Salida (€)</label>
                <input type="number" class="form-control" id="nuevo-salida" name="salida" step="0.01" min="0" value="0">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="nuevo-entrada" class="form-label">Entrada (€)</label>
                <input type="number" class="form-control" id="nuevo-entrada" name="entrada" step="0.01" min="0" value="0">
              </div>
            </div>
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
          <input type="hidden" id="edit-id-movimiento" name="id_movimiento">
          <input type="hidden" id="edit-id-tabla" name="id_tabla">
          
          <div class="mb-3">
            <label for="edit-fecha-apunte" class="form-label">Fecha del apunte</label>
            <input type="text" class="form-control" id="edit-fecha-apunte" readonly tabindex="-1">
          </div>
          
          <div class="mb-3">
            <label for="edit-grupo" class="form-label">Grupo</label>
            <select class="form-select" id="edit-grupo" name="grupos" required>
              <option value="">Seleccionar grupo...</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="edit-concepto" class="form-label">Concepto</label>
            <textarea class="form-control" id="edit-concepto" name="concepto" rows="3" required></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit-salida" class="form-label">Salida (€)</label>
                <input type="number" class="form-control" id="edit-salida" name="salida" step="0.01" min="0" value="0">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit-entrada" class="form-label">Entrada (€)</label>
                <input type="number" class="form-control" id="edit-entrada" name="entrada" step="0.01" min="0" value="0">
              </div>
            </div>
          </div>
          <small class="text-muted">Puede dejar salida y entrada en 0.</small>
        </form>
      </div>
      <div class="modal-footer" style="padding: 15px 9px 22px !important; padding-block-start: 10px !important;">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnGuardarMovimiento">
          <i class="ri ri-check-line me-1"></i> Actualizar Movimiento
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Traslado a Movimientos Tarjeta -->
<div class="modal fade" id="modalMoverMovimientoTarjeta" tabindex="-1" aria-labelledby="modalMoverMovimientoTarjetaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalMoverMovimientoTarjetaLabel">Trasladar a movimientos tarjetas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Movimiento #<span class="mover-movimiento-id-label"></span></p>
        <input type="hidden" class="mover-id-movimiento" id="mover-tarjeta-id-movimiento">
        <input type="hidden" class="mover-id-tabla" id="mover-tarjeta-id-tabla">
        <div class="mb-2"><strong>Fecha:</strong> <span class="mover-fecha-apunte"></span></div>
        <div class="mb-2"><strong>Grupo:</strong> <span class="mover-grupo"></span></div>
        <div class="mb-2"><strong>Concepto:</strong> <span class="mover-concepto"></span></div>
        <div class="mb-2"><strong>Salida:</strong> <span class="mover-salida"></span></div>
        <div class="mb-0"><strong>Entrada:</strong> <span class="mover-entrada"></span></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm btn-confirmar-traslado-movimiento" data-tipo="tarjeta">
          <i class="ri ri-check-line me-1"></i> Confirmar traslado
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Traslado a Movimientos Transferencias -->
<div class="modal fade" id="modalMoverMovimientoTransferencia" tabindex="-1" aria-labelledby="modalMoverMovimientoTransferenciaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalMoverMovimientoTransferenciaLabel">Trasladar a movimientos transferencias</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Movimiento #<span class="mover-movimiento-id-label"></span></p>
        <input type="hidden" class="mover-id-movimiento" id="mover-transferencia-id-movimiento">
        <input type="hidden" class="mover-id-tabla" id="mover-transferencia-id-tabla">
        <div class="mb-2"><strong>Fecha:</strong> <span class="mover-fecha-apunte"></span></div>
        <div class="mb-2"><strong>Grupo:</strong> <span class="mover-grupo"></span></div>
        <div class="mb-2"><strong>Concepto:</strong> <span class="mover-concepto"></span></div>
        <div class="mb-2"><strong>Salida:</strong> <span class="mover-salida"></span></div>
        <div class="mb-0"><strong>Entrada:</strong> <span class="mover-entrada"></span></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm btn-confirmar-traslado-movimiento" data-tipo="transferencia">
          <i class="ri ri-check-line me-1"></i> Confirmar traslado
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Traslado a Movimientos Bizum -->
<div class="modal fade" id="modalMoverMovimientoBizum" tabindex="-1" aria-labelledby="modalMoverMovimientoBizumLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalMoverMovimientoBizumLabel">Trasladar a movimientos bizum</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Movimiento #<span class="mover-movimiento-id-label"></span></p>
        <input type="hidden" class="mover-id-movimiento" id="mover-bizum-id-movimiento">
        <input type="hidden" class="mover-id-tabla" id="mover-bizum-id-tabla">
        <div class="mb-2"><strong>Fecha:</strong> <span class="mover-fecha-apunte"></span></div>
        <div class="mb-2"><strong>Grupo:</strong> <span class="mover-grupo"></span></div>
        <div class="mb-2"><strong>Concepto:</strong> <span class="mover-concepto"></span></div>
        <div class="mb-2"><strong>Salida:</strong> <span class="mover-salida"></span></div>
        <div class="mb-0"><strong>Entrada:</strong> <span class="mover-entrada"></span></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm btn-confirmar-traslado-movimiento" data-tipo="bizum">
          <i class="ri ri-check-line me-1"></i> Confirmar traslado
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<!-- Los scripts se cargan desde javascript.php -->
