<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h5 class="card-title mb-0">Nuevo gasto fijo</h5>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='gastos_fijos.php?categoria=gastos&page=gastos_fijos&btn=list'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Gastos fijos
          </button>
        </div>

        <div class="card-body mt-5">
          <div class="card card-form-custom">
            <form id="formCrearGastoFijo" method="POST" action="parts/gastos_fijos/crear/insertar_gasto_fijo.php" autocomplete="off">

              <div class="row mb-3">
                <div class="col-12">
                  <h5 class="mb-3">Información</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="descripcion_gasto_fijo" name="descripcion_gasto_fijo" placeholder="Descripción" required>
                    <label for="descripcion_gasto_fijo">Descripción gasto fijo *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <h5 class="mb-3">Período</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <select class="form-select select2" id="periodo_gasto_fijo" name="periodo_gasto_fijo" required>
                      <option value="mensual" selected>Mensual</option>
                      <option value="diario">Diario</option>
                      <option value="semanal">Semanal</option>
                      <option value="quincenal">Quincenal</option>
                      <option value="trimestral">Trimestral</option>
                      <option value="semestral">Semestral</option>
                      <option value="anual">Anual</option>
                      <option value="bianual">Bianual</option>
                    </select>
                    <label for="periodo_gasto_fijo">Período *</label>
                  </div>
                </div>

                <div class="col-md-4">
                  <h5 class="mb-3">Fecha inicio</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="date" class="form-control" id="fecha_inicio_gasto_fijo" name="fecha_inicio_gasto_fijo" value="<?php echo date('Y-m-d'); ?>" required>
                    <label for="fecha_inicio_gasto_fijo">Fecha de inicio *</label>
                  </div>
                </div>

                <div class="col-md-4">
                  <h5 class="mb-3">Total</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="number" step="0.01" class="form-control" id="total_gasto_fijo" name="total_gasto_fijo" placeholder="0.00" required>
                    <label for="total_gasto_fijo">Total gasto fijo *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <h5 class="mb-3">Empresa</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectEmpresas(0, 'empresa_gasto_fijo', 'empresa_gasto_fijo', true); ?>
                    <label for="empresa_gasto_fijo">Empresa *</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <h5 class="mb-3">Proveedor</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectProveedores(0, 'proveedor_gasto_fijo', 'proveedor_gasto_fijo', true); ?>
                    <label for="proveedor_gasto_fijo">Proveedor *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <h5 class="mb-3">Forma de pago</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectFormasPago(224, 'forma_pago_gasto_fijo', 'forma_pago_gasto_fijo', true); ?>
                    <label for="forma_pago_gasto_fijo">Forma de pago *</label>
                  </div>
                </div>

                <div class="col-md-4 d-none" id="extra_forma_pago_container">
                  <h5 class="mb-3">Detalle pago</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="numero_forma_pago" name="numero_forma_pago" placeholder="Número / referencia">
                    <label for="numero_forma_pago">Número forma de pago</label>
                  </div>
                </div>

                <div class="col-md-4">
                  <h5 class="mb-3">Tipo de gasto</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectTiposGasto(0, 'tipo_de_gasto_fijo', 'tipo_de_gasto_fijo', true); ?>
                    <label for="tipo_de_gasto_fijo">Tipo de gasto *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <h5 class="mb-3">Categoría (opcional)</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="gasto_tipo" name="gasto_tipo" placeholder="Ej: alquiler, luz...">
                    <label for="gasto_tipo">gasto_tipo</label>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary waves-effect waves-light">
                  <i class="icon-base ri ri-save-line me-1"></i>Guardar gasto fijo
                </button>
                <button type="button" class="btn btn-outline-secondary waves-effect" onclick="window.location.href='gastos_fijos.php?categoria=gastos&page=gastos_fijos&btn=list'">
                  Cancelar
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>