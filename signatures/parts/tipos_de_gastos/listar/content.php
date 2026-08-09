<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total Tipos de Gasto</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-tipos-gasto">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Registrados en el sistema</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-money-dollar-circle-line icon-26px"></div>
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
              <p class="text-heading mb-1">Tipos Activos</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-tipos-activos">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Tipos en uso</small>
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
              <p class="text-heading mb-1">Fecha Último</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="fecha-ultimo-tipo">-</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Último tipo creado</small>
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
  
  <!-- Tipos de Gasto List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Tipos de Gasto</h5>

    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-tipos-gasto table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>NOMBRE TIPO GASTO</th>
            <th>ACCIONES</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Modal Crear Tipo de Gasto -->
<div class="modal fade fadeIn" id="modalCrearTipoGasto" tabindex="-1" aria-labelledby="modalCrearTipoGastoLabel" aria-hidden="true">
                        <div class="modal-dialog modal-sm" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h4 class="modal-title" id="modalCrearTipoGastoLabel">Crear tipo de gasto</h4>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <form id="formCrearTipoGasto">
                                  <div class="row">
                                    <div class="col mb-4 mt-2">
                                      <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="nombreTipoGasto" name="nombreTipoGasto" placeholder="Nombre del tipo de gasto" required>
                                        <label for="nombreTipoGasto">Nombre del Tipo de Gasto</label>
                                      </div>
                                    </div>
                                  </div>
                              </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="btnCrearTipoGasto">Crear</button>
                            </div>
                          </div>
                        </div>
                      </div>

<!-- Modal Editar Tipo de Gasto -->
<div class="modal fade fadeIn" id="modalEditarTipoGasto" tabindex="-1" aria-labelledby="modalEditarTipoGastoLabel" aria-hidden="true">
                        <div class="modal-dialog modal-sm" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h4 class="modal-title" id="modalEditarTipoGastoLabel">Editar tipo de gasto</h4>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <form id="formEditarTipoGasto">
                                  <input type="hidden" id="editIdTipoGasto" name="idTipoGasto">
                                  <div class="row">
                                    <div class="col mb-4 mt-2">
                                      <div class="form-floating form-floating-outline">
                                        <input type="text" class="form-control" id="editNombreTipoGasto" name="nombreTipoGasto" placeholder="Nombre del tipo de gasto" required>
                                        <label for="editNombreTipoGasto">Nombre del Tipo de Gasto</label>
                                      </div>
                                    </div>
                                  </div>
                              </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="btnActualizarTipoGasto">Actualizar</button>
                            </div>
                          </div>
                        </div>
                      </div>

<!-- Los scripts se cargan desde javascript.php -->