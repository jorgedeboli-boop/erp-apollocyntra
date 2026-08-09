<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total Grupos de Apuntes</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-grupos-apuntes">17</h4>
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
              <p class="text-heading mb-1">Grupos Activos</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-grupos-activos">17</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Grupos en uso</small>
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
                <h4 class="mb-1 me-1" id="fecha-ultimo-grupo">22/7/2025</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Último grupo creado</small>
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
  
  <!-- Grupos de Apuntes List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Grupos de Apuntes</h5>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-grupos-apuntes table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>NOMBRE GRUPO</th>
            <th>TIPO GRUPO</th>
            <th>ACCIONES</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Modal Crear Grupo de Apuntes -->
<div class="modal fade" id="modalCrearGrupoApuntes" tabindex="-1" aria-labelledby="modalCrearGrupoApuntesLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCrearGrupoApuntesLabel">
          <i class="icon-base ri ri-add-line me-2"></i>
          Crear Nuevo Grupo de Apuntes
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formCrearGrupoApuntes">
        <div class="modal-body">
          <div class="mb-3">
            <label for="nombreGrupoApuntes" class="form-label">Nombre del Grupo</label>
            <input type="text" class="form-control" id="nombreGrupoApuntes" name="nombre_grupo" placeholder="Ingrese el nombre del grupo" required>
          </div>
          <div class="mb-3">
            <label for="tipoGrupoApuntes" class="form-label">Tipo de Grupo</label>
            <select class="form-select" id="tipoGrupoApuntes" name="tipo_grupo" required>
              <option value="">Seleccione un tipo</option>
              <option value="Entrada y salida">Entrada y salida</option>
              <option value="Entrada o Salida">Entrada o Salida</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnCrearGrupoApuntes">Crear</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Editar Grupo de Apuntes -->
<div class="modal fade" id="modalEditarGrupoApuntes" tabindex="-1" aria-labelledby="modalEditarGrupoApuntesLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarGrupoApuntesLabel">
          <i class="icon-base ri ri-pencil-line me-2"></i>
          Editar Grupo de Apuntes
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditarGrupoApuntes">
        <div class="modal-body">
          <input type="hidden" id="editIdGrupoApuntes" name="id_grupo">
          <div class="mb-3">
            <label for="editNombreGrupoApuntes" class="form-label">Nombre del Grupo</label>
            <input type="text" class="form-control" id="editNombreGrupoApuntes" name="nombre_grupo" placeholder="Ingrese el nombre del grupo" required>
          </div>
          <div class="mb-3">
            <label for="editTipoGrupoApuntes" class="form-label">Tipo de Grupo</label>
            <select class="form-select" id="editTipoGrupoApuntes" name="tipo_grupo" required>
              <option value="">Seleccione un tipo</option>
              <option value="Entrada y salida">Entrada y salida</option>
              <option value="Entrada o Salida">Entrada o Salida</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnActualizarGrupoApuntes">Actualizar</button>
        </div>
      </form>
    </div>
  </div>
</div>