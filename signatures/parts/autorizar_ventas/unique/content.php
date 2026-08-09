<div class="container-fluid flex-grow-1 container-p-y">
<?php require_once __DIR__ . '/../../universal/filtros_opciones_autorizaciones.php'; ?>
  <!-- Autorizaciones de Porcentajes Ventas List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Autorizaciones de Porcentajes Ventas</h5>
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 select2-btn-height" id="autorizar_filtros_container">
        <div class="col-md-6 user_sucursal">
          <select id="FiltroSucursal" class="form-select select2-filter text-capitalize select2-custom">
            <option value="">Seleccionar Sucursal</option>
          </select>
        </div>
        <div class="col-md-6 user_estado">
          <select id="FiltroEstado" class="form-select select2-filter text-capitalize select2-custom">
            <?php autorizaciones_imprimir_opciones_estado_devolucion(); ?>
          </select>
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-autorizaciones-porcentajes-ventas table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>SUCURSAL</th>
            <th>ESTADO</th>
            <th>CÓDIGO</th>
            <th>FECHA</th>
            <th>USUARIO</th>
            <th>ARTÍCULO</th>
            <th>INTERÉS ORIGINAL</th>
            <th>INTERÉS NUEVO</th>
            <th>PRECIO ORIGINAL</th>
            <th>PRECIO NUEVO</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<?php if ($puede_acceder_edit): ?>
<!-- Modal Autorizar Cambio de Porcentajes Ventas -->
<div class="modal fade" id="modalAutorizarPorcentajesVentas" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header card-header-forms pb-3">
        <h5 class="modal-title">Autorizar cambio de porcentaje venta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <div class="row align-items-center g-4">
            <div class="col-sm-4">
              <p class="mb-1">Sucursal</p>
              <h5 id="modal-sucursal">-</h5>
            </div>
            <div class="col-sm-4">
              <p class="mb-1">Usuario</p>
              <h5 id="modal-usuario">-</h5>
            </div>
            <div class="col-sm-4">
              <p class="mb-1">Artículo</p>
              <h5 id="modal-articulo">-</h5>
            </div>
            <div class="col-sm-6">
              <div class="bg-label-primary p-4 rounded-4">
                <div class="d-flex">
                  <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                    <div class="me-2 w-100 text-center">
                      <h6 class="mb-0 w-100 text-primary">Interés Original</h6>
                      <h3 class="mb-0 text-primary" id="modal-interes-original">-</h3>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="bg-label-warning p-4 rounded-4">
                <div class="d-flex">
                  <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                    <div class="me-2 w-100 text-center">
                      <h6 class="mb-0 w-100 text-warning">Nuevo Interés</h6>
                      <h3 class="mb-0 text-warning" id="modal-nuevo-interes">-</h3>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="bg-label-info p-4 rounded-4">
                <div class="d-flex">
                  <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                    <div class="me-2 w-100 text-center">
                      <h6 class="mb-0 w-100 text-info">Precio Original</h6>
                      <h3 class="mb-0 text-info" id="modal-precio-original">-</h3>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="bg-label-success p-4 rounded-4">
                <div class="d-flex">
                  <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                    <div class="me-2 w-100 text-center">
                      <h6 class="mb-0 w-100 text-success">Precio Nuevo</h6>
                      <h3 class="mb-0 text-success" id="modal-precio-nuevo">-</h3>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-confirmar-autorizacion">
          <i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Autorizar
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Los scripts se cargan desde javascript.php -->
