<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <h4 class="mb-1">Importaciones</h4>
      <p class="mb-4">Sube un fichero <code>.sql</code> a <code>migration</code> y ejecútalo en la base de datos.</p>
    </div>
  </div>

  <div class="row">
    <div class="col-12 col-xl-6">
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span class="fw-semibold">1) Importar y crear tablas (SQL)</span>
        </div>
        <div class="card-body">
          <form id="formUploadSql" class="row g-3" autocomplete="off">
            <div class="col-12">
              <label for="sqlFile" class="form-label">Fichero SQL / ZIP / GZIP</label>
              <input class="form-control" type="file" id="sqlFile" name="sql" accept=".sql,.zip,.gz,.gzip" required>
              <div class="form-text">Se subirá por FTP a la carpeta <code>migration</code>. Si subes un <code>.zip</code> o <code>.gz</code>, se descomprime y se suben los <code>.sql</code> resultantes.</div>
            </div>
            <div class="col-12 d-flex align-items-center gap-2">
              <button type="submit" class="btn btn-primary" id="btnUploadSql">
                Subir
              </button>
              <div id="uploadStatus" class="small text-muted"></div>
            </div>
          </form>

          <hr class="my-4">

          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-semibold">Ficheros en migration</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshMigrations">Refrescar</button>
          </div>

          <div id="migrationList" class="list-group">
            <div class="list-group-item text-muted">Cargando…</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-6">
      <div class="card mb-4">
        <div class="card-header">
          <span class="fw-semibold">2) Importar datos a tablas destino (mapeo)</span>
        </div>
        <div class="card-body">
          <form id="formImportData" class="row g-3" autocomplete="off">
            <div class="col-12">
              <label class="form-label" for="importFile">Fichero SQL (en migration)</label>
              <select class="form-select" id="importFile" required>
                <option value="">Cargando…</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="destTable">Tabla destino</label>
              <select class="form-select" id="destTable" required>
                <option value="">Cargando…</option>
              </select>
              <div class="form-text">Se insertarán filas en esta tabla (ya existente).</div>
            </div>
            <div class="col-12">
              <label class="form-label" for="importMode">Modo de importación</label>
              <select class="form-select" id="importMode" required>
                <option value="insert" selected>Insertar</option>
                <option value="truncate_insert">Vaciar tabla e insertar</option>
                <option value="upsert">Upsert / reemplazar si existe</option>
              </select>
              <div class="form-text">El modo <code>upsert</code> requiere que la tabla destino tenga clave primaria o índice único.</div>
            </div>
            <div class="col-12">
              <label class="form-label" for="columnMap">Mapeo columnas (origen=destino)</label>
              <textarea class="form-control" id="columnMap" rows="6" placeholder="Ejemplo:\nid_articulo=id_articulo\ndescripcion_articulo=descripcion\npeso_articulo=peso_neto"></textarea>
              <div class="form-text">Una línea por columna. Si dejas vacío, intentará usar columnas iguales (mismo nombre).</div>
            </div>
            <div class="col-12 d-flex align-items-center gap-2">
              <button type="submit" class="btn btn-warning" id="btnImportData">Importar datos</button>
              <div id="importStatus" class="small text-muted"></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span class="fw-semibold">Historial (ejecutados con éxito)</span>
          <div class="d-flex gap-2 align-items-center">
            <select class="form-select form-select-sm" id="importHistAccion" style="width: 220px;">
              <option value="execute_sql" selected>Crear tablas (execute_sql)</option>
              <option value="import_data">Importar datos (import_data)</option>
              <option value="upload">Subidas (upload)</option>
            </select>
            <select class="form-select form-select-sm" id="importHistEstado" style="width: 160px;">
              <option value="success" selected>Éxito</option>
              <option value="running">En curso</option>
              <option value="error">Error</option>
              <option value="pending">Pendiente</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnTruncateImportacionesHist">Vaciar historial</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshImportacionesHist">Refrescar</button>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Fichero</th>
                  <th>Acción</th>
                  <th>Estado</th>
                  <th>Inicio</th>
                  <th>Fin</th>
                  <th>Mensaje</th>
                </tr>
              </thead>
              <tbody id="importacionesHistBody">
                <tr><td colspan="7" class="text-muted">Cargando…</td></tr>
              </tbody>
            </table>
          </div>
          <div class="small text-muted">Se registra automáticamente cada acción. Usa los filtros para ver “Crear tablas” (paso 1) o “Importar datos” (paso 2).</div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->