<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <div>
            <h4 class="card-title mb-0">Estructura de itemsSections</h4>
            <small class="text-muted">Visor de columnas de la tabla y alta de nuevas columnas MySQL</small>
          </div>
          <button type="button" class="btn btn-primary<?php echo usuario_sesion_es_root() ? '' : ' d-none'; ?>" id="btnNuevaColumna">
            <i class="ri ri-add-line me-1"></i>
            Nueva columna
          </button>
        </div>
        <div class="card-body">
          <div id="columnas-loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 mb-0 text-muted">Cargando columnas...</p>
          </div>
          <div id="columnas-error" class="alert alert-danger d-none" role="alert"></div>
          <div class="table-responsive d-none" id="columnas-table-wrap">
            <table class="table table-hover table-striped" id="tablaColumnasItemsSections">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Columna</th>
                  <th>Tipo MySQL</th>
                  <th>Null</th>
                  <th>Clave</th>
                  <th>Default</th>
                  <th>Extra</th>
                </tr>
              </thead>
              <tbody id="columnas-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal nueva columna -->
<div class="modal fade" id="modalNuevaColumna" tabindex="-1" aria-labelledby="modalNuevaColumnaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalNuevaColumnaLabel">
          <i class="ri ri-database-2-line me-2"></i>
          Añadir columna a itemsSections
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="formNuevaColumna">
        <div class="modal-body">
          <div class="alert alert-warning mb-3">
            <small>Esta acción ejecuta <code>ALTER TABLE</code> en la base de datos. Solo usuarios root.</small>
          </div>
          <div class="mb-3">
            <label for="nombre_columna" class="form-label">Nombre de la columna *</label>
            <input type="text" class="form-control" id="nombre_columna" name="nombre_columna"
                   placeholder="Ej: nueva_section, mi_campo..." pattern="[a-zA-Z_][a-zA-Z0-9_]*" required>
            <small class="text-muted">Solo letras, números y guión bajo. Debe empezar por letra o _</small>
          </div>
          <div class="mb-3">
            <label for="tipo_columna" class="form-label">Tipo de columna MySQL *</label>
            <select class="form-select" id="tipo_columna" name="tipo_index" required>
              <option value="">Seleccione un tipo...</option>
              <option value="0">enum('false','true') NOT NULL</option>
              <option value="1">enum('true','false') NOT NULL</option>
              <option value="2">varchar(64) NOT NULL</option>
              <option value="3">varchar(68) NOT NULL</option>
              <option value="4">varchar(124) NOT NULL DEFAULT ''</option>
              <option value="5">int(11) NOT NULL</option>
              <option value="6">int(11) NOT NULL DEFAULT 0</option>
              <option value="7">text NOT NULL</option>
              <option value="8">tinyint(1) NOT NULL DEFAULT 0</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="default_valor" class="form-label">Valor DEFAULT (opcional)</label>
            <input type="text" class="form-control" id="default_valor" name="default_valor"
                   placeholder="Ej: false, true, 0, texto...">
            <small class="text-muted">Si el tipo ya incluye DEFAULT, deje este campo vacío</small>
          </div>
          <div class="mb-0">
            <label for="despues_columna" class="form-label">Insertar después de (opcional)</label>
            <select class="form-select" id="despues_columna" name="despues_columna">
              <option value="">Al final de la tabla</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarColumna">
            <i class="ri ri-save-line me-1"></i>
            Añadir columna
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- / Content -->
