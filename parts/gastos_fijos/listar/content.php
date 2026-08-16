<div class="container-fluid flex-grow-1 container-p-y">

  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h5 class="card-title mb-0">Gastos fijos</h5>

        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_gastos_fijos" aria-expanded="false" aria-controls="collapse_filtros_gastos_fijos">
          <i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar
        </button>

        <?php if ($puede_acceder_crear): ?>
        <a href="crear_gasto_fijo.php" class="btn btn-primary waves-effect waves-light px-3">
          <span class="icon-base ri ri-add-fill icon-22px me-1"></span>Nuevo gasto fijo
        </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-body pb-0">
      <div class="collapse d-lg-block" id="collapse_filtros_gastos_fijos">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">

          <div class="col-md-2">
            <label class="form-label mb-1" for="filtro_forma_pago">Forma de pago</label>
            <?php generarSelectFormasPago(0, 'filtro_forma_pago', 'filtro_forma_pago'); ?>
          </div>

          <div class="col-md-2">
            <label class="form-label mb-1" for="filtro_periodo">Período</label>
            <select class="form-select select2-custom" id="filtro_periodo" name="filtro_periodo">
              <option value="">Todos</option>
              <option value="diario">Diario</option>
              <option value="semanal">Semanal</option>
              <option value="quincenal">Quincenal</option>
              <option value="trimestral">Trimestral</option>
              <option value="semestral">Semestral</option>
              <option value="anual">Anual</option>
              <option value="bianual">Bianual</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label mb-1" for="filtro_tipo_gasto">Tipo de gasto</label>
            <?php generarSelectTiposGasto(0, 'filtro_tipo_gasto', 'filtro_tipo_gasto'); ?>
          </div>

          <div class="col-md-2">
            <label class="form-label mb-1" for="filtro_proveedor">Proveedor</label>
            <?php generarSelectProveedores(0, 'filtro_proveedor', 'filtro_proveedor'); ?>
          </div>

          <div class="col-md-2">
            <label class="form-label mb-1" for="filtro_estado">Estado</label>
            <select class="form-select select2-custom" id="filtro_estado" name="filtro_estado">
              <option value="">Todos</option>
              <option value="true">Activo</option>
              <option value="false">Desactivado</option>
            </select>
          </div>

          <div class="col-md-3 mt-2">
            <label class="form-label mb-1" for="rangeFechas">Fecha inicio</label>
            <div class="input-group">
              <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas">
              <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
              <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-gastos-fijos table border-top">
        <thead>
          <tr>
            <th>Nº</th>
            <th>Fecha alta</th>
            <th>Proveedor</th>
            <th>DNI/NIF</th>
            <th>Total</th>
            <th>Concepto</th>
            <th>Tipo</th>
            <th>Pago</th>
            <th>Inicio</th>
            <th>Período</th>
            <th>Estado</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  <!-- Los scripts se cargan desde javascript.php -->
</div>