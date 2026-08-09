<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h5 class="card-title mb-0">Crear Cliente (test)</h5>
          <small class="text-muted">Formulario de cliente mediante módulo reutilizable</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='clientes.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Clientes
          </button>
        </div>
        <div class="card-body mt-5">
          <div id="card-form-custom-id" class="card card-form-custom">
            <form id="formCrearClienteTest" method="POST" action="parts/modulo_cliente_form/unique/procesar_cliente.php" class="fv-plugins-bootstrap5 fv-plugins-framework" autocomplete="off">

              <?php
              $modulo_cliente_form_modo = 'crear';
              require_once __DIR__ . '/../../modulo_cliente_form/unique/crear_cliente_modulo.php';
              ?>

              <div class="row mt-4">
                <div class="col-md-6 offset-md-6">
                  <h5 class="mb-3">Sucursal</h5>
                  <div class="form-floating form-floating-outline mb-3">
                    <select class="form-select select2 select-custom" id="sucursal_cliente" name="sucursal_cliente" required>
                      <option value="">Seleccionar sucursal</option>
                      <?php
                      $sucursales = obtener_sucursales();
                      foreach ($sucursales as $sucursal) {
                          echo '<option value="' . (int) $sucursal['id_sucursal'] . '">' . htmlspecialchars($sucursal['nombre_sucursal']) . '</option>';
                      }
                      ?>
                    </select>
                    <label for="sucursal_cliente">Sucursal *</label>
                  </div>
                </div>
              </div>

              <div class="row mt-4">
                <div class="col-md-12">
                  <h5 class="mb-3">Información Adicional</h5>
                  <div class="form-floating form-floating-outline mb-3">
                    <textarea class="form-control" id="observaciones" name="observaciones" placeholder="Observaciones sobre el cliente" style="height: 100px" autocomplete="off"></textarea>
                    <label for="observaciones">Observaciones</label>
                  </div>
                </div>
              </div>

              <hr class="my-4">

              <div class="d-flex justify-content-between">
                <a href="clientes.php" class="btn btn-text-primary me-2">
                  <i class="icon-base ri ri-arrow-left-line me-2"></i>
                  Volver a la lista
                </a>
                <div>
                  <button type="reset" class="btn btn-text-danger me-2">
                    <i class="icon-base ri ri-refresh-line me-2"></i>
                    Limpiar
                  </button>
                  <button class="btn btn-primary" type="button" disabled id="loaderbtn" style="display: none;">
                    <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                    Aguarde...
                  </button>
                  <button type="submit" class="btn btn-primary" id="btnCrearClienteTest">
                    <i class="icon-base ri ri-check-line me-2"></i>
                    Crear Cliente
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->
