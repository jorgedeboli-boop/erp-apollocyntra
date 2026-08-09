<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="clearfix mb-1">
        <button type="button" class="btn btn-primary btn-sm float-end" id="btnAbrirModalMigracion">Crear nueva migracion</button>
        <h4 class="mb-0">Migración</h4>
      </div>
      <p class="mb-4">Herramientas para migrar y corregir datos importados.</p>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col-12 col-lg-12 mb-6 mb-xl-0">
              <small class="fw-medium">MIGRAR</small>
              <div class="demo-inline-spacing mt-4">
                <div class="list-group" id="listaMigraciones">
                  <div class="list-group-item text-muted">Cargando migraciones…</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h5 class="mb-0">Nacionalidades sin mapear (IA)</h5>
            <small class="text-body-secondary">Claude sugiere; tú apruebas; la migración solo aplica mapeos aprobados.</small>
          </div>
          <button type="button" class="btn btn-outline-primary btn-sm" id="btnSugerirNacionalidadesIa">Solicitar sugerencias IA</button>
        </div>
        <div class="card-body">
          <div id="nacionalidadesIaStatus" class="small text-muted mb-3">Cargando sugerencias…</div>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead>
                <tr>
                  <th>Valor en clientes</th>
                  <th>Sugerencia</th>
                  <th>Motivo IA</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody id="nacionalidadesIaBody">
                <tr><td colspan="4" class="text-muted">Cargando…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal nueva migración -->
<div class="modal fade" id="modalNuevaMigracion" tabindex="-1" aria-labelledby="modalNuevaMigracionLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalNuevaMigracionLabel">Crear nueva migración</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formNuevaMigracion" autocomplete="off">
          <div class="mb-3">
            <label for="codigo_migracion" class="form-label">Código migración</label>
            <input type="text" class="form-control" name="codigo_migracion" id="codigo_migracion" maxlength="64" required placeholder="ej: migrar_lotes">
            <div class="form-text">Solo minúsculas, números y guion bajo. Identificador único.</div>
          </div>
          <div class="mb-3">
            <label for="nombre_migracion" class="form-label">Nombre migración</label>
            <input type="text" class="form-control" name="nombre_migracion" id="nombre_migracion" maxlength="120" required placeholder="Título visible en la lista">
          </div>
          <div class="mb-3">
            <label for="descripcion_migracion" class="form-label">Descripción migración</label>
            <textarea class="form-control" name="descripcion_migracion" id="descripcion_migracion" rows="3" required placeholder="Qué hace esta migración"></textarea>
          </div>
          <div class="mb-0">
            <label for="script_migracion" class="form-label">Script migración</label>
            <input type="text" class="form-control" name="script_migracion" id="script_migracion" maxlength="120" required placeholder="ej: migrar_lotes.php">
            <div class="form-text">Se creará el fichero PHP en <code>parts/migracion/unique/</code> y se guardará su ruta en la base de datos.</div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarMigracion">Crear migración</button>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->
