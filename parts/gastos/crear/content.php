<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h5 class="card-title mb-0">Nuevo gasto</h5>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='gastos.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Gastos
          </button>
        </div>

        <div class="card-body mt-5">
          <div class="card card-form-custom">
            <form id="formCrearGasto" method="POST" action="parts/gastos/crear/insertar_gasto.php" autocomplete="off">

              <div class="row mb-3">
                <div class="col-12">
                  <h5 class="mb-3">Información</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <textarea class="form-control" id="descripcion_gasto" name="descripcion_gasto" placeholder="Descripción" style="height: 100px" required></textarea>
                    <label for="descripcion_gasto">Descripción gasto *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <h5 class="mb-3">Fecha gasto</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="date" class="form-control" id="fecha_gasto" name="fecha_gasto" value="<?php echo date('Y-m-d'); ?>" required>
                    <label for="fecha_gasto">Fecha gasto *</label>
                  </div>
                </div>

                <div class="col-md-4">
                  <h5 class="mb-3">Fecha factura</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="date" class="form-control" id="fecha_factura_gasto" name="fecha_factura_gasto" value="<?php echo date('Y-m-d'); ?>" required>
                    <label for="fecha_factura_gasto">Fecha de factura *</label>
                  </div>
                </div>

                <div class="col-md-4">
                  <h5 class="mb-3">Nº factura proveedor</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="numero_factura_proveedor" name="numero_factura_proveedor" placeholder="Ej: A-123" required>
                    <label for="numero_factura_proveedor">Nº factura *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-3">
                  <h5 class="mb-3">Base imponible</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="number" step="0.01" class="form-control" id="base_impobile" name="base_impobile" placeholder="0.00" required>
                    <label for="base_impobile">Base imponible *</label>
                  </div>
                </div>

                <div class="col-md-3">
                  <h5 class="mb-3">Tipo IVA</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <select class="form-select select2" id="tipo_iva" name="tipo_iva" required>
                      <option value="21" selected>21%</option>
                      <option value="10">10%</option>
                      <option value="4">4%</option>
                      <option value="0">0%</option>
                    </select>
                    <label for="tipo_iva">Tipo IVA *</label>
                  </div>
                </div>

                <div class="col-md-3">
                  <h5 class="mb-3">IVA total</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="number" step="0.01" class="form-control" id="iva_total" name="iva_total" placeholder="0.00" readonly>
                    <label for="iva_total">IVA total</label>
                  </div>
                </div>

                <div class="col-md-3">
                  <h5 class="mb-3">IRPF</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="number" step="0.01" class="form-control" id="irpf" name="irpf" placeholder="0.00" value="0.00" required>
                    <label for="irpf">IRPF *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <h5 class="mb-3">Total</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="number" step="0.01" class="form-control" id="total_gasto" name="total_gasto" placeholder="0.00" readonly>
                    <label for="total_gasto">Total gasto</label>
                  </div>
                </div>

                <div class="col-md-4">
                  <h5 class="mb-3">Estado</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <select class="form-select select2" id="estado_gasto" name="estado_gasto" required>
                      <option value="pendiente" selected>Pendiente</option>
                      <option value="pagado">Pagado</option>
                      <option value="cancelado">Cancelado</option>
                    </select>
                    <label for="estado_gasto">Estado *</label>
                  </div>
                </div>

                <div class="col-md-4" id="container_fecha_pago_gasto">
                  <h5 class="mb-3">Fecha pago</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="datetime-local" class="form-control" id="fecha_pago_gasto" name="fecha_pago_gasto">
                    <label for="fecha_pago_gasto">Fecha de pago</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <h5 class="mb-3">Empresa</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectEmpresas(0, 'empresa_gasto', 'empresa_gasto', true); ?>
                    <label for="empresa_gasto">Empresa *</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <h5 class="mb-3">Proveedor</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectProveedores(0, 'proveedor_gasto', 'proveedor_gasto', true); ?>
                    <label for="proveedor_gasto">Proveedor *</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-4">
                  <h5 class="mb-3">Forma de pago</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectFormasPago(224, 'forma_pago_gasto', 'forma_pago_gasto', true); ?>
                    <label for="forma_pago_gasto">Forma de pago *</label>
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
                    <?php generarSelectTiposGasto(0, 'tipo_de_gasto', 'tipo_de_gasto', true); ?>
                    <label for="tipo_de_gasto">Tipo de gasto *</label>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary waves-effect waves-light">
                  <i class="icon-base ri ri-save-line me-1"></i>Guardar gasto
                </button>
                <button type="button" class="btn btn-outline-secondary waves-effect" onclick="window.location.href='gastos.php'">
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